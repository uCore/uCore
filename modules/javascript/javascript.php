<?php

uEvents::AddCallback('ProcessDomDocument','uJavascript::LinkToDocument');
uEvents::AddCallback('ProcessDomDocument','uJavascript::ProcessDomDocument',null,MAX_ORDER);
class uJavascript extends uBasicModule {
	static function LinkToDocument($obj,$event,$templateDoc) {
		$head = $templateDoc->getElementsByTagName('head')->item(0);
		array_sort_subkey(self::$linkFiles,'order');
		
		$beforeRef = $head->getElementsByTagName('script')->length ? $head->getElementsByTagName('script')->item(0) : null;
		foreach (self::$linkFiles as $path) {
			$node = $templateDoc->createElement('script');
			$node->setAttribute('type','text/javascript'); $node->setAttribute('src',$path['path']);
			foreach ($path['attr'] as $k=>$v) $node->setAttribute($k,$v);
			
			if ($beforeRef) $head->insertBefore($node,$beforeRef);
			else $head->appendChild($node);
		}
			
		if (self::$script_include) {
			$node = $templateDoc->createElement('script');
			$node->appendChild($templateDoc->createCDATASection(trim(self::$script_include)));
			$head->appendChild($node);
		}
	}
	static function ProcessDomDocument($obj,$event,$doc) {
		$scripts = $doc->getElementsByTagName('script');
		for ($i = 0; $i < $scripts->length; $i++) { // now loop through all scripts, and ensure correct format
			$script = $scripts->item($i);
			// set type
			if (!$script->hasAttribute('type')) $script->setAttribute('type','text/javascript');
			if (!$script->childNodes->length) continue;
			
			// already commented cdata?
			if ($script->childNodes->length == 2 && $script->childNodes->item(0)->nodeType == XML_TEXT_NODE && $script->childNodes->item(1)->nodeType == XML_CDATA_SECTION_NODE) continue;
			// single child is not text or cdata
			if ($script->childNodes->length != 1 || ($script->childNodes->item(0)->nodeType != XML_TEXT_NODE && $script->childNodes->item(0)->nodeType != XML_CDATA_SECTION_NODE)) continue;
			
			$v = NULL;
			try { // attempt to create a fragment from the value - this will check if the data is infact valid xml. if it is then find the cnode child and use it
				$v = $script->childNodes->item(0)->nodeValue;
				$frag = $doc->createDocumentFragment();
				$frag->appendXML($v);
				foreach ($frag->childNodes as $node) {
					if ($node->nodeType === XML_CDATA_SECTION_NODE) {
						$v = trim($node->nodeValue,' /'); break;
					}
				}
			} catch (Exception $e) {} // if it fails, then does not contain cdata so continue as normal
			if ($v === NULL) $v = $script->childNodes->item(0)->nodeValue;
			$script->removeChild($script->childNodes->item(0));
			$cm = $doc->createTextNode("//");
			$ct = $doc->createCDATASection("\n" . trim($v) . "\n//");
			$script->appendChild($cm);
			$script->appendChild($ct);
		}
	}
	public function GetOptions() { return PERSISTENT; }
	private static $linkFiles = array();
	public static function LinkFile($path,$order=null,$attr=array()) {
		if ($order === null) $order = count(self::$linkFiles);
		if (file_exists($path)) $path = utopia::GetRelativePath($path);
		foreach (self::$linkFiles as $link) if ($link['path'] == $path) return;
		self::$linkFiles[] = array('path'=>$path,'order'=>$order,'attr'=>$attr);
	}
	private static $includeFiles = array();
	public static function IncludeFile($path,$order=NULL) {
		// if running ALERT: CANNOT BE CALLED AT RUN TIME
		if (!file_exists($path)) $path = utopia::GetAbsolutePath($path);
		if (!file_exists($path)) return;
		if ($order === null) $order = count(self::$includeFiles);
		self::$includeFiles[] = array('path'=>$path,'order'=>$order);
	}
	private static $includeText = array();
	public static function IncludeText($text,$order=NULL) {
		if ($order === null) $order = count(self::$includeText);
		self::$includeText[] = array('text'=>$text,'order'=>$order);
	}
	private static $script_include = '';
	public static function AddText($text) {
		self::$script_include .= "\n$text";
	}
	public static $uuid = 'javascript.js';

	public function SetupParents() {
		module_Offline::IgnoreClass(__CLASS__);
		$this->SetRewrite(true);
		self::LinkFile($this->GetURL(),-10);
	}

	public function RunModule() {
		utopia::CancelTemplate();

		clearstatcache();
		$uStr = '';
		foreach (self::$includeFiles as $info) {
			if (!file_exists($info['path'])) continue;
			$uStr .= $info['path'].filemtime($info['path']).'-'.filesize($info['path']);
		}

		$etag = sha1($uStr.'-'.count(self::$includeFiles).'-'.sha1(self::GetJavascriptConstants()).count(self::$includeText).'-'.PATH_REL_CORE);
		utopia::Cache_Check($etag,'text/javascript',$this->GetUUID());

		// minify caching
		$minifyCache = '';
		if (file_exists(__FILE__.'.cache') && file_exists(__FILE__.'.cache.sha1')) $minifyCache = file_get_contents(__FILE__.'.cache.sha1');
		if ($etag !== $minifyCache) {
			$out = self::BuildJavascript(true);
			file_put_contents(__FILE__.'.cache',$out); chmod(__FILE__.'.cache', 0664);
			file_put_contents(__FILE__.'.cache.sha1',$etag); chmod(__FILE__.'.cache.sha1', 0664);
		} else {
			$out = file_get_contents(__FILE__.'.cache');
		}

		utopia::Cache_Output($out,$etag,'text/javascript',$this->GetUUID());
	}

	static function GetJavascriptConstants() {
		$body = '';
		array_push($GLOBALS['jsDefine'],'FORMAT_DATETIME','FORMAT_DATE','FORMAT_TIME','USE_TABS','PATH_REL_ROOT','PATH_REL_CORE','PATH_FULL_ROOT','PATH_FULL_CORE');
		$GLOBALS['jsDefine'] = array_unique($GLOBALS['jsDefine']);
		if (array_key_exists('jsDefine',$GLOBALS))
		foreach ($GLOBALS['jsDefine'] as $var) {
			if (!defined($var)) continue;
			$val = is_numeric(constant($var)) ? constant($var) : '\''.constant($var).'\'';
			$body .= "var $var = $val;\n";
		}
		return $body;
	}

	static function BuildJavascript() {
		$textarr = self::$includeText;
		foreach (self::$includeFiles as $info) {
			if (!file_exists($info['path'])) continue;
			$textarr[] = array('text'=>file_get_contents($info['path']),'order'=>$info['order']);
		}
		array_sort_subkey($textarr,'order');

		$body = self::GetJavascriptConstants();
		foreach ($textarr as $info) {
			$body .= $info['text'].';'.PHP_EOL;
		}
    
		return $body;
	}
}

uJavascript::LinkFile(dirname(__FILE__).'/jQuery/jquery-1.10.2.min.js',-100);
uJavascript::LinkFile(dirname(__FILE__).'/jQuery/jquery-ui-1.10.0.min.js',-99);
uJavascript::IncludeFile(dirname(__FILE__).'/javascript.js',-999);
uJavascript::IncludeFile(dirname(__FILE__).'/js/min/jquery.metadata.min.js');
uJavascript::IncludeFile(dirname(__FILE__).'/carousel/jquery.jcarousel.min.js');
uJavascript::IncludeFile(dirname(__FILE__).'/js/ajaxfileupload.js');
uJavascript::IncludeFile(dirname(__FILE__).'/js/sqlDate.js');
uJavascript::IncludeFile(dirname(__FILE__).'/js/functs.js');
