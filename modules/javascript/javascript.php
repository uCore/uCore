<?php

// dependancies
// check dependancies exist - Move to install?

uJavascript::IncludeFile(dirname(__FILE__).'/js/min/jquery.metadata.min.js');
uJavascript::IncludeFile(dirname(__FILE__).'/carousel/jquery.jcarousel.min.js');
uJavascript::IncludeFile(dirname(__FILE__).'/js/ajaxfileupload.js');
uJavascript::IncludeFile(dirname(__FILE__).'/js/sqlDate.js');
uJavascript::IncludeFile(dirname(__FILE__).'/js/functs.js');
FlexDB::AddJSFile(PATH_REL_CORE.'.javascript.js');
$s = (FlexDB::IsRequestSecure()) ? 's' : '';
FlexDB::AddJSFile('http'.$s.'://www.google.com/jsapi?autoload='.urlencode('{"modules":[{"name":"jquery","version":"1"},{"name":"jqueryui","version":"1"}]}'),true);
FlexDB::AddCSSFile('http'.$s.'://ajax.googleapis.com/ajax/libs/jqueryui/1/themes/ui-lightness/jquery-ui.css');
FlexDB::AddCSSFile(PATH_REL_CORE.'modules/javascript/js/jquery.auto-complete.css');

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

	public function SetupParents() {

		// register ajax
		//$this->RegisterAjax('getJavascript',array($this,'BuildJavascript'),false);
		//FlexDB::AddJSFile(PATH_REL_CORE.'index.php?__ajax=getJavascript',true);
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
		FlexDB::Cache_Check($etag,'text/javascript');
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
		
//		FlexDB::Cache_Output($body,$etag,'text/javascript');
	}
}

?>
