<?php

// dependancies
// check dependancies exist - Move to install?

uJavascript::IncludeFile(dirname(__FILE__).'/js/min/jquery.metadata.min.js');
uJavascript::IncludeFile(dirname(__FILE__).'/carousel/jquery.jcarousel.min.js');
uJavascript::IncludeFile(dirname(__FILE__).'/js/ajaxfileupload.js');
uJavascript::IncludeFile(dirname(__FILE__).'/js/sqlDate.js');
uJavascript::IncludeFile(dirname(__FILE__).'/js/functs.js');
utopia::AddJSFile(PATH_REL_CORE.'.javascript.js');
$s = (utopia::IsRequestSecure()) ? 's' : '';
utopia::AddJSFile('http'.$s.'://www.google.com/jsapi?autoload='.urlencode('{"modules":[{"name":"jquery","version":"1"},{"name":"jqueryui","version":"1"}]}'),true);
utopia::AddCSSFile('http'.$s.'://ajax.googleapis.com/ajax/libs/jqueryui/1/themes/ui-lightness/jquery-ui.css');
utopia::AddCSSFile(PATH_REL_CORE.'modules/javascript/js/jquery.auto-complete.css');

$b = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
if (strpos($b,'MSIE 6') !== FALSE || strpos($b,'MSIE 7') !== FALSE) utopia::AddJSFile(dirname(__FILE__).'/js/DD_belatedPNG-min.js');

class uJavascript {
	private static $includeFiles = array();
	public static function IncludeFile($path) {
		// if running ALERT: CANNOT BE CALLED AT RUN TIME
		self::$includeFiles[] = $path;
	}
	private static $includeText = '';
	public static function IncludeText($text) {
		self::$includeText .= "\n$text";
	}
	public static function AddText($text) {
		utopia::AppendVar('script_include',$text);
	}

	public function SetupParents() {
		// register ajax
		//$this->RegisterAjax('getJavascript',array($this,'BuildJavascript'),false);
		//utopia::AddJSFile(PATH_REL_CORE.'index.php?__ajax=getJavascript',true);
	}

	public function ParentLoad($parent) {
	}

	public function RunModule() {
	}

	static function BuildJavascript() {
		$body = '';
		array_push($GLOBALS['jsDefine'],'FORMAT_DATETIME','FORMAT_DATE','FORMAT_TIME','USE_TABS','PATH_REL_ROOT','PATH_REL_CORE');
		if (array_key_exists('jsDefine',$GLOBALS))
		foreach ($GLOBALS['jsDefine'] as $var) {
			if (!defined($var)) continue;
			$val = is_numeric(constant($var)) ? constant($var) : '\''.constant($var).'\'';
			$body .= "var $var = $val;\n";
		}
/*
		$lastTime = NULL;
		foreach (self::$includeFiles as $filename) {
			//does it exist?
			if (!file_exists($filename)) continue;
			if ($lastTime == NULL || filemtime($filename) > $lastTime) $lastTime = filemtime($filename);
		}

		$etag = sha1($lastTime.'-'.count(self::$includeFiles).'-'.strlen($body));
		utopia::Cache_Check($etag,'text/javascript');
*/
		foreach (self::$includeFiles as $filename) {
//			//does it exist?
			if (!file_exists($filename)) continue;
			$body .= file_get_contents($filename).';';
		}
    
    $body .= self::$includeText;
    
		$body = JSMin::minify($body);
		file_put_contents(PATH_ABS_CORE.'.javascript.js',$body);
//		ob_end_clean();
//		header('Content-Encoding: ',true);
		
//		utopia::Cache_Output($body,$etag,'text/javascript');
	}
}

?>
