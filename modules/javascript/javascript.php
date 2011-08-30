<?php

class uStylesheet extends uBasicModule {
	public function GetUUID() { return 'styles.css'; }
	private static $includeFiles = array();
	public static function IncludeFile($path) {
		// if running ALERT: CANNOT BE CALLED AT RUN TIME
		if (!file_exists($path)) {
			if (!file_exists(PATH_ABS_ROOT.$path)) return;
			$path = PATH_ABS_ROOT.$path;
		}
		self::$includeFiles[] = $path;
	}
	public function SetupParents() {
		module_Offline::IgnoreClass(__CLASS__);
		$this->SetRewrite(true);
		utopia::AddCSSFile($this->GetURL(),true);

		modOpts::AddOption('uJavascript','jQueryUI-Theme','jQuery UI Theme','ui-lightness');
		$jquitheme = modOpts::GetOption('uJavascript','jQueryUI-Theme');
		utopia::AddCSSFile('//ajax.googleapis.com/ajax/libs/jqueryui/1/themes/'.$jquitheme.'/jquery-ui.css',true);

		uStylesheet::IncludeFile(PATH_REL_CORE.'modules/javascript/js/jquery.auto-complete.css');
	}
	public function RunModule() {
		$uStr = ''; $out = '';
		self::$includeFiles = array_unique(self::$includeFiles);
		foreach (self::$includeFiles as $filename) {
			//does it exist?
			clearstatcache(true,$filename);
			$uStr .= filemtime($filename).'-'.filesize($filename);
			$out .= file_get_contents($filename);
		}

		$etag = sha1($uStr.'-'.count(self::$includeFiles));
		utopia::Cache_Check($etag,'text/css');

		utopia::CancelTemplate();
		utopia::Cache_Output($out,$etag,'text/css','styles.css');
	}
}

class uJavascript extends uBasicModule {
	private static $includeFiles = array();
	public static function IncludeFile($path) {
		// if running ALERT: CANNOT BE CALLED AT RUN TIME
		if (!file_exists($path)) {
			if (!file_exists(PATH_ABS_ROOT.$path)) return;
			$path = PATH_ABS_ROOT.$path;
		}
		self::$includeFiles[] = $path;
	}
	private static $includeText = '';
	public static function IncludeText($text) {
		self::$includeText .= "\n$text";
	}
	public static function AddText($text) {
		utopia::AppendVar('script_include',"\n$text");
	}
	public function GetUUID() { return 'javascript.js'; }

	public function SetupParents() {
		module_Offline::IgnoreClass(__CLASS__);
		$this->SetRewrite(true);
		utopia::AddJSFile($this->GetURL(),true);

		utopia::AddJSFile('//ajax.googleapis.com/ajax/libs/jqueryui/1/jquery-ui.min.js',true);
		utopia::AddJSFile('//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js',true);

		uJavascript::IncludeFile(dirname(__FILE__).'/js/min/jquery.metadata.min.js');
		uJavascript::IncludeFile(dirname(__FILE__).'/carousel/jquery.jcarousel.min.js');
		uJavascript::IncludeFile(dirname(__FILE__).'/js/ajaxfileupload.js');
		uJavascript::IncludeFile(dirname(__FILE__).'/js/sqlDate.js');
		uJavascript::IncludeFile(dirname(__FILE__).'/js/functs.js');

		modOpts::AddOption('uJavascript','googleAPI','Google API Key');
		$key = ($gAPI = modOpts::GetOption('uJavascript','googleAPI')) ? 'key='.$gAPI.'&' : '';

		// commented because if a user enters an incorrect version (too high for example) they can not change it back.
//		modOpts::AddOption('uJavascript','jQuery','jQuery Version',1);
//		$jq  = modOpts::GetOption('uJavascript','jQuery');

//		modOpts::AddOption('uJavascript','jQueryUI','jQuery UI Version',1);
//		$jqui = modOpts::GetOption('uJavascript','jQueryUI');
	}

	public function RunModule() {
		$uStr = '';
		self::$includeFiles = array_unique(self::$includeFiles);
		foreach (self::$includeFiles as $filename) {
			//does it exist?
			if (!file_exists($filename)) continue;
			clearstatcache(true,$filename);
			$uStr .= filemtime($filename).'-'.filesize($filename);
		}

		$etag = sha1($uStr.'-'.count(self::$includeFiles).'-'.sha1(self::GetJavascriptConstants()));
		utopia::Cache_Check($etag,'text/javascript');

		utopia::CancelTemplate();
		$out = uJavascript::BuildJavascript(true);
		utopia::Cache_Output($out,$etag,'text/javascript','javascript.js');
	}

	static function GetJavascriptConstants() {
		$body = '';
		array_push($GLOBALS['jsDefine'],'FORMAT_DATETIME','FORMAT_DATE','FORMAT_TIME','USE_TABS','PATH_REL_ROOT','PATH_REL_CORE');
		if (array_key_exists('jsDefine',$GLOBALS))
		foreach ($GLOBALS['jsDefine'] as $var) {
			if (!defined($var)) continue;
			$val = is_numeric(constant($var)) ? constant($var) : '\''.constant($var).'\'';
			$body .= "var $var = $val;\n";
		}
		return $body;
	}

	static function BuildJavascript($minify=true) {
		$body = self::GetJavascriptConstants();

		foreach (self::$includeFiles as $filename) {
			if (!file_exists($filename)) continue;
			$body .= file_get_contents($filename).";\n\n";
		}
    
		$body .= self::$includeText;

		if ($minify) $body = JSMin::minify($body);

		return $body;
	}
}

?>
