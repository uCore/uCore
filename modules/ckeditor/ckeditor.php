<?php
define('itRICHTEXT' ,'richtext');
define('itHTML' ,'html');
class module_CKEditor extends uBasicModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return ''; }
	public function GetOptions() { return ALLOW_FILTER; }

	public function SetupParents() {
		utopia::AddInputType(itRICHTEXT,array($this,'drti_func'));
		utopia::AddInputType(itHTML,array($this,'drti_func'));
		self::InitScript();
		uJavascript::IncludeFile(dirname(__FILE__).'/lib/ckeditor.js',1000);
		uJavascript::IncludeFile(dirname(__FILE__).'/ckeditor.js',1005);
		uEvents::AddCallback('AfterRunModule',array($this,'MediaScript'),'fileManager');
	}
	public function MediaScript() {
		if (!isset($_REQUEST['__ajax']) || $_REQUEST['__ajax'] !== 'media') return;
		uJavascript::AddText(<<<FIN
	$(function(){
		$('#fileMan').off('dblclick','.fmFile').on('dblclick','.fmFile',function(event){
			var item = $(this).data('item');
			if (item.type != 0) return;
			window.opener.CKEDITOR.tools.callFunction({$_GET['CKEditorFuncNum']}, item.fullPath);
			window.close();
		});
	});
FIN
);
echo '<style>.ui-widget{font-size:0.8em}body{font-family:Arial}</style>';
	}
	private static $hasDrawnJS = false;
	static function InitScript() {
		if (!self::$hasDrawnJS) {
			self::$hasDrawnJS = true;
			list($fileManagerPath,$uploadPath) = uUploads::Init();
			$basepath = utopia::GetRelativePath(dirname(__FILE__).'/lib/');
			uJavascript::IncludeText(<<< FIN
var CKEDITOR_BASEPATH = '$basepath/';
var FILE_BROWSE_URL = PATH_REL_CORE+'index.php?__ajax=media';
FIN
);
		}
	}

	function drti_func($fieldName,$inputType,$defaultValue='',$possibleValues=NULL,$attributes = NULL,$noSubmit = FALSE) {
		
		if (!is_array($attributes)) $attributes = array();
		if (isset($attributes['class'])) $attributes['class'] .= ' ckEditReplace';
		else $attributes['class'] = 'ckEditReplace';

		if (!isset($attributes['data-toolbar'])) {
			if ($inputType == itRICHTEXT) $attributes['data-toolbar'] = 'Basic';
		}
		if (isset($attributes['contenteditable'])) {
			$attributes['class'] = str_replace(array('inputtype-html','inputtype'),'',$attributes['class']);
			$attr = BuildAttrString($attributes);
			return '<div'.$attr.'>'.$defaultValue.'</div>';
		}
		
		return utopia::DrawInput($fieldName,itTEXTAREA,$defaultValue,$possibleValues,$attributes,$noSubmit);
	}
	public function RunModule() {
	}
	public static function CanResetField($o,$e,$fieldAlias) {
		if ($o->fields[$fieldAlias]['inputtype'] == itHTML) return FALSE;
		if ($o->fields[$fieldAlias]['inputtype'] == itRICHTEXT) return FALSE;
	}
	
	public static function AddExternalPlugins() {
		$ppath = utopia::GetRelativePath(dirname(__FILE__)).'/plugins/';
		$plugins = glob(dirname(__FILE__).'/plugins/*/plugin.js');
		$p = array();
		foreach ($plugins as $file) {
			preg_match('/\/([^\/]+)\/plugin\.js/i',$file,$match);
			$match = $match[1];
			uJavascript::IncludeText("CKEDITOR.plugins.addExternal('$match','$ppath$match/', 'plugin.js');",1003);
			$p[] = $match;
		}
		uJavascript::IncludeText("CKEDITOR.config.extraPlugins = '".implode(',',$p)."';",1004);
	}
}
uEvents::AddCallback('BeforeResetField','module_CKEditor::CanResetField');
uEvents::AddCallback('AfterInit','module_CKEditor::AddExternalPlugins');
