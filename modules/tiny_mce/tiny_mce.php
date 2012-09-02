<?php
define('itRICHTEXT' ,'richtext');
define('itHTML' ,'html');
class module_TinyMCE extends uBasicModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return ''; }
	public function GetOptions() { return ALLOW_FILTER; }

	public function SetupParents() {
		utopia::AddInputType(itRICHTEXT,array($this,'drti_func'));
		utopia::AddInputType(itHTML,array($this,'drti_func'));
		uJavascript::IncludeText('var tinyMCEPreInit={base:"' . utopia::GetRelativePath(dirname(__FILE__)) . '/lib",suffix:""};');
		uJavascript::IncludeFile(dirname(__FILE__).'/lib/tiny_mce.js',true);
		module_TinyMCE::InitScript();
	}

	private static $hasDrawnJS = false;
	static function InitScript() {
		if (!self::$hasDrawnJS) {
			self::$hasDrawnJS = true;
		//	$scUrl = utopia::GetRelativePath(dirname(__FILE__)).'/lib/plugins/spellchecker/rpc.php';
			$previewUrl = utopia::GetRelativePath(dirname(__FILE__)).'/lib/plugins/preview/preview.php';
			list($fileManagerPath) = fileManager::Init();
			$relUploads = utopia::GetRelativePath(PATH_UPLOADS);

			$options = array();
			$options['mode'] = "specific_textareas";
			$options['document_base_url'] = utopia::GetSiteURL();
			$options['convert_urls'] = false;
			$options['remove_script_host'] = false;
			$options['relative_urls'] = true;
			$options['cleanup_on_startup'] = true;
			$options['cleanup'] = true;
			$options['theme'] = "advanced";
			$options['file_browser_callback'] = "openMediaBrowser";
			//$options['spellchecker_rpc_url'] = $scUrl;
			$options['plugin_preview_pageurl'] = $previewUrl;
			$options['theme_advanced_toolbar_location'] = "top";
			$options['theme_advanced_toolbar_align'] = "left";
			$options['theme_advanced_statusbar_location'] = "bottom";
			$options['theme_advanced_resizing'] = true;
			$options['forced_root_block'] = false;
			$options['preformatted'] = true;
			$options['content_css'] = PATH_REL_CORE.'default.css';
			$options['setup'] = 'tinyMceSetup';
			$options['save_onsavecallback'] = 'onSave';

			$richOpts = array();
			$richOpts['plugins'] = "paste,inlinepopups,spellchecker,save";
			$richOpts['valid_elements'] = 'b,strong,i,em,u,ul,ol,li,p,br,span[style]';
			$richOpts['theme_advanced_buttons1'] = "bold,italic,underline,strikethrough,|,numlist,bullist,|,spellchecker";
			$richOpts['theme_advanced_buttons2'] = "";
			$richOpts['theme_advanced_buttons3'] = "";
			$richOpts['theme_advanced_buttons4'] = "";

			$htmlOpts = array();
			$htmlOpts['valid_elements'] = '*[*]';// 'style,div[*],span[*],iframe[src|width|height|name|align|style]';
			$htmlOpts['extended_valid_elements'] = '*[*]';// 'style,div[*],span[*],iframe[src|width|height|name|align|style]';

			$htmlOpts['plugins'] = "paste,inlinepopups,media,advimage,spellchecker,table,noneditable,style,layer,autoresize,save";
			$htmlOpts['theme_advanced_buttons1_add'] = '|,forecolor,backcolor';
			$htmlOpts['theme_advanced_buttons2_add'] = 'media,|,insertlayer,moveforward,movebackward,absolute';
			$htmlOpts['theme_advanced_buttons3_add'] = ",|,styleprops,spellchecker,|,tablecontrols";

			$baseOpts = json_encode($options);
			$richOpts = json_encode($richOpts);
			$htmlOpts = json_encode($htmlOpts);

			$includeOpts = '';
			if (class_exists('uPlupload')) {
				$jsOptionVar = 'uCorePluploadOptions';
				uPlupload::Init($jsOptionVar,$fileManagerPath);
				$includeOpts = ','.$jsOptionVar;
			}

			uJavascript::IncludeText(<<< FIN
	function tinyMceSetup(ed) {
		$(document).trigger('tinyMceSetup',ed);
	}
	function onSave(ed) {
		uf(ed.getElement(),ed.getContent(),ed.getContainer());
	}
	$(document).on('submit','form',function(){tinyMCE.triggerSave(true,true)});
	$(document).on('click','.btn-submit',function(){tinyMCE.triggerSave(true,true)});
	var mb = null;
	function openMediaBrowser(field_name, url, type, win) {
		if (!mb) mb = $('<div></div>');
		fltr = '';
		if (type == 'image')
			fltr = '.jpeg|.jpg|.png|.gif|.tif|.tiff';

		mb.fileManager({ajaxPath:'$fileManagerPath',upload:true,get:{filter:fltr},mceInfo:{field_name:field_name,win:win,type:type},events:{click:doClick}}$includeOpts);
		mb.dialog({modal:false,width:'60%',height:500,zIndex:999999});
	}
	function doClick(event) {
		var item = $(this).data('item');
		if (item.type != 0) return;

		var mceInfo = item.target.data('options').mceInfo;
		var absPath = 'http://' + window.location.hostname + item.fullPath;

		mceInfo.win.document.getElementById(mceInfo.field_name).value = absPath;

		// are we an image browser
		if (typeof(mceInfo.win.ImageDialog) != "undefined") {
			// we are, so update image dimensions...
			if (mceInfo.win.ImageDialog.getImageData)
				mceInfo.win.ImageDialog.getImageData();

			// ... and preview if necessary
			if (mceInfo.win.ImageDialog.showPreviewImage)
				mceInfo.win.ImageDialog.showPreviewImage(absPath);
		}

		mb.dialog('close');
	}
	var mceDefaultOptions = $baseOpts;
	var mceRichOptions = $.extend({},$richOpts);
	var mceHtmlOptions = $.extend({},$htmlOpts);
FIN
);
		}
	}

	function drti_func($fieldName,$inputType,$defaultValue='',$possibleValues=NULL,$attributes = NULL,$noSubmit = FALSE) {
		$saveClass = 'mceSave'.rand(1,5000);
		
		if (!is_array($attributes)) $attributes = array();
		if (array_key_exists('class',$attributes))
			$attributes['class'] .= ' '.$saveClass;
		else
			$attributes['class'] = $saveClass;

		$extendOpts = '';
		if (isset($attributes['mce_options'])) {
			$extendOpts = ','.json_encode($attributes['mce_options']);
			unset($attributes['mce_options']);
		}
		$optName = 'mceRichOptions';
		if ($inputType == itHTML) $optName = '$.extend({protect:[/<\?php.*?\?>/g,/<a[^>]*?><\/a>/g]}, mceHtmlOptions)';
		
		
		$extendOpts .= ',($(".'.$saveClass.'").closest("form").length ? {} : {theme_advanced_buttons1_add_before:"save,|,"})';
		
		$script = '<script type="text/javascript">tinyMCE.init($.extend({},mceDefaultOptions,'.$optName.$extendOpts.',{editor_selector:"'.$saveClass.'"}))</script>';
		
		return utopia::DrawInput($fieldName,itTEXTAREA,$defaultValue,$possibleValues,$attributes,$noSubmit).$script;
	}
	public function RunModule() {
	}
	public static function CanResetField($o,$e,$fieldAlias) {
		if ($o->fields[$fieldAlias]['inputtype'] == itHTML) return FALSE;
		if ($o->fields[$fieldAlias]['inputtype'] == itRICHTEXT) return FALSE;
	}
}
uEvents::AddCallback('BeforeResetField','module_TinyMCE::CanResetField');
