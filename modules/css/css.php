<?php

class uCSS extends uBasicModule {
	static function LinkToDocument($obj,$event,$templateDoc) {
		$head = $templateDoc->getElementsByTagName('head')->item(0);
		array_sort_subkey(self::$linkFiles,'order');
		
		$beforeRef = $head->getElementsByTagName('link')->length ? $head->getElementsByTagName('link')->item(0) : null;
		foreach (self::$linkFiles as $path) {
			// already exists?
			$exists = false;
			foreach ($templateDoc->getElementsByTagName('link') as $l) {
				if ($l->getAttribute('rel') == 'stylesheet' && $l->getAttribute('href') == $path['path']) { $exists = true; break; }
			}
			if ($exists) continue;
			$node = $templateDoc->createElement('link');
			$node->setAttribute('type','text/css'); $node->setAttribute('rel','stylesheet'); $node->setAttribute('href',$path['path']);
			
			if ($beforeRef) $head->insertBefore($node,$beforeRef);
			else $head->appendChild($node);
		}
	}
	static function ProcessDomDocument($obj,$event,$doc) {
		$styles = $doc->getElementsByTagName('style');
		for ($i = 0; $i < $styles->length; $i++) { // now loop through all scripts, and ensure correct format
			$style = $styles->item($i);
			if (!$style->hasAttribute('type')) $style->setAttribute('type','text/css');
			if (!$style->childNodes->length) continue;
			
			// already commented cdata?
			if ($style->childNodes->length == 2 && $style->childNodes->item(0)->nodeType == XML_TEXT_NODE && $style->childNodes->item(1)->nodeType == XML_CDATA_SECTION_NODE) continue;
			// single child is not text or cdata
			if ($style->childNodes->length != 1 || ($style->childNodes->item(0)->nodeType != XML_TEXT_NODE && $style->childNodes->item(0)->nodeType != XML_CDATA_SECTION_NODE)) continue;
			
			$v = NULL;
			try { // attempt to create a fragment from the value - this will check if the data is infact valid xml. if it is then find the cnode child and use it
				$v = $style->childNodes->item(0)->nodeValue;
				$frag = $doc->createDocumentFragment();
				$frag->appendXML($v);
				foreach ($frag->childNodes as $node) {
					if ($node->nodeType === XML_CDATA_SECTION_NODE) {
						$v = trim($node->nodeValue,' /'); break;
					}
				}
			} catch (Exception $e) {} // if it fails, then does not contain cdata so continue as normal
			if ($v === NULL) $v = $style->childNodes->item(0)->nodeValue;
			$style->removeChild($style->childNodes->item(0));
			$style->appendChild($doc->createTextNode("/*"));
			$v = preg_replace('/^\s*\*\/\s*/','',$v);
			$v = preg_replace('/\s*\/\*\s*$/','',$v);
			$style->appendChild($doc->createCDATASection("*/\n" . trim($v) . "\n/*"));
			$style->appendChild($doc->createTextNode("*/"));
		}
	}
	public function GetOptions() { return PERSISTENT; }
	public static $uuid = 'styles.css';
	private static $includeFiles = array();
	public static function IncludeFile($path) {
		// if running ALERT: CANNOT BE CALLED AT RUN TIME
		if (!file_exists($path)) $path = utopia::GetAbsolutePath($path);
		if (!file_exists($path)) return;
		self::$includeFiles[] = $path;
	}
	
	private static $linkFiles = array();
	public static function LinkFile($path,$order=null) {
		if ($order === null) $order = count(self::$linkFiles);
		if (file_exists($path)) $path = utopia::GetRelativePath($path);
		foreach (self::$linkFiles as $link) if ($link['path'] == $path) return;
		self::$linkFiles[] = array('path'=>$path,'order'=>$order);
	}
	
	public static function Initialise() {
		uEvents::AddCallback('ProcessDomDocument','uCSS::LinkToDocument');
		uEvents::AddCallback('ProcessDomDocument','uCSS::ProcessDomDocument',null,MAX_ORDER);
		uEvents::AddCallback('AfterInit','uCSS::LinkGlobal');
		
		self::LinkFile('/styles.css',-10);
		self::LinkFile(dirname(__FILE__).'/jQuery/jquery-ui.min.css',-99);
		self::IncludeFile(PATH_REL_CORE.'default.css');

		module_Offline::IgnoreClass(__CLASS__);
	}
	public static function LinkGlobal() {
		self::IncludeFile(PATH_REL_ROOT.TEMPLATE_ADMIN.'/global.css');		
	}
	public function SetupParents() {
		$this->SetRewrite(true);
	}
	public function RunModule() {
		utopia::CancelTemplate();

		clearstatcache();
		$uStr = '';
		self::$includeFiles = array_unique(self::$includeFiles);
		foreach (self::$includeFiles as $filename) {
			//does it exist?
			if (!file_exists($filename)) continue;
			$uStr .= $filename.filemtime($filename).'-'.filesize($filename);
		}

		$identifiers = array($_SERVER['REQUEST_URI'],$uStr,count(self::$includeFiles),PATH_REL_CORE);
		$etag = utopia::checksum($identifiers);
		utopia::Cache_Check($etag,'text/css',$this->GetUUID());

		$out = uCache::retrieve($identifiers);
		if ($out) {
			$out = file_get_contents($out);
		} else {
			$out = self::BuildCSS(true);
			uCache::store($identifiers,$out);
		}

		utopia::Cache_Output($out,$etag,'text/css',$this->GetUUID());
	}

	static function BuildCSS($minify=true) {
		$body = '';

		foreach (self::$includeFiles as $filename) {
			if (!file_exists($filename)) continue;
			$contents = file_get_contents($filename);

			// convert relative url paths into absolute ones.
			$subDir = utopia::GetRelativePath(dirname($filename));
			$contents = preg_replace('/url\("?\'?([^"\']+)"?\'?\)/Ui','url(' . $subDir . '/$1)',$contents);

			$body .= $contents."\n\n";
		}
    
		if ($minify) $body = cssMin::minify($body);

		return $body;
	}
}
