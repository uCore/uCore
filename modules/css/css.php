<?php

uCSS::IncludeFile(PATH_REL_CORE.'default.css');
uEvents::AddCallback('ProcessDomDocument','uCSS::LinkToDocument');
uEvents::AddCallback('ProcessDomDocument','uCSS::ProcessDomDocument','',99999);
class uCSS extends uBasicModule {
	static function LinkToDocument($obj,$event,$templateDoc) {
		$head = $templateDoc->getElementsByTagName('head')->item(0);
		array_sort_subkey(self::$linkFiles,'order');
		foreach (self::$linkFiles as $path) {
			$node = $templateDoc->createElement('link');
			$node->setAttribute('type','text/css'); $node->setAttribute('rel','stylesheet'); $node->setAttribute('href',$path['path']);
			$head->appendChild($node);
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
			$style->appendChild($doc->createCDATASection("*/\n" . trim($v) . "\n/*"));
			$style->appendChild($doc->createTextNode("*/"));
		}
	}
	public function GetOptions() { return PERSISTENT; }
	public function GetUUID() { return 'styles.css'; }
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

	public function SetupParents() {
		module_Offline::IgnoreClass(__CLASS__);
		$this->SetRewrite(true);
		self::LinkFile($this->GetURL(),-10);

		self::IncludeFile(PATH_REL_ROOT.TEMPLATE_ADMIN.'/global.css');

		modOpts::AddOption('jQueryUI-Theme','jQuery UI Theme',null,'ui-lightness');
		$jquitheme = modOpts::GetOption('jQueryUI-Theme');
		self::LinkFile('//ajax.googleapis.com/ajax/libs/jqueryui/1/themes/'.$jquitheme.'/jquery-ui.css',-100);
		
		self::IncludeFile(PATH_REL_CORE.'modules/javascript/js/jquery.auto-complete.css');
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

		$etag = sha1($uStr.'-'.count(self::$includeFiles).'-'.PATH_REL_CORE);
		utopia::Cache_Check($etag,'text/css',$this->GetUUID());

		// minify caching
		$minifyCache = '';
		if (file_exists(__FILE__.'.cache') && file_exists(__FILE__.'.cache.sha1')) $minifyCache = file_get_contents(__FILE__.'.cache.sha1');
		if ($etag !== $minifyCache) {
			$out = self::BuildCSS(true);
			file_put_contents(__FILE__.'.cache',$out); chmod(__FILE__.'.cache', 0664);
			file_put_contents(__FILE__.'.cache.sha1',$etag); chmod(__FILE__.'.cache.sha1', 0664);
		} else {
			$out = file_get_contents(__FILE__.'.cache');
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
