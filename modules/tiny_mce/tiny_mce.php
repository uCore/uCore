<?php
define('itRICHTEXT' ,'richtext');
define('itHTML' ,'html');
class module_TinyMCE extends uBasicModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return ''; } //$row = $this->GetRecord($this->GetDataset(),0); return $row['name']; }
	public function GetOptions() { return ALLOW_FILTER; }

	public function SetupParents() {
		utopia::AddInputType(itRICHTEXT,array($this,'drti_func'));
		utopia::AddInputType(itHTML,array($this,'drti_func'));
		uJavascript::IncludeText('var tinyMCEPreInit={base:"' . utopia::GetRelativePath(dirname(__FILE__)) . '/tiny_mce",suffix:""};');
		uJavascript::IncludeFile(dirname(__FILE__).'/tiny_mce/tiny_mce.js',true);
		module_TinyMCE::InitScript();
	}

	private static $hasDrawnJS = false;
	static function InitScript() {
		if (!self::$hasDrawnJS) {
			self::$hasDrawnJS = true;
		//	$scUrl = utopia::GetRelativePath(dirname(__FILE__)).'/tiny_mce/plugins/spellchecker/rpc.php';
			$previewUrl = utopia::GetRelativePath(dirname(__FILE__)).'/tiny_mce/plugins/preview/preview.php';
			list($fileManagerPath) = fileManager::Init();
			$relUploads = utopia::GetRelativePath(PATH_UPLOADS);

			$options = array();
			$options['mode'] = "specific_textareas";
			$options['convert_urls'] = false;
			$options['remove_script_host'] = false;
			$options['cleanup_on_startup'] = true;
			$options['cleanup'] = true;
			$options['theme'] = "advanced";
			$options['file_browser_callback'] = "openMediaBrowser";
			//$options['spellchecker_rpc_url'] = $scUrl;
			$options['plugin_preview_pageurl'] = $previewUrl;
			$options['theme_advanced_toolbar_location'] = "top";
			$options['theme_advanced_toolbar_align'] = "left";
			$options['forced_root_block'] = false;
			$options['preformatted'] = true;
			$options['content_css'] = PATH_REL_CORE.'default.css';
			$options['setup'] = 'tinyMceSetup';
			$options['save_onsavecallback'] = 'onSave';
			$options['save_enablewhendirty'] = true;

			$richOpts = array();
			$richOpts['plugins'] = "inlinepopups,spellchecker,save";
			$richOpts['valid_elements'] = 'b,strong,i,u,ul,ol,li,p';
			$richOpts['theme_advanced_buttons1'] = "save,|,bold,italic,underline,strikethrough,|,numlist,bullist,|,spellchecker";
			$richOpts['theme_advanced_buttons2'] = "";
			$richOpts['theme_advanced_buttons3'] = "";
			$richOpts['theme_advanced_buttons4'] = "";

			$htmlOpts = array();
			$htmlOpts['valid_elements'] = '*[*]';// 'style,div[*],span[*],iframe[src|width|height|name|align|style]';
			$htmlOpts['extended_valid_elements'] = '*[*]';// 'style,div[*],span[*],iframe[src|width|height|name|align|style]';
			$htmlOpts['plugins'] = "inlinepopups,media,advimage,spellchecker,table,noneditable,style,layer,fullscreen,save";
			$htmlOpts['theme_advanced_buttons1_add'] = '|,forecolor,backcolor,|,fullscreen';
			$htmlOpts['theme_advanced_buttons2_add'] = 'media,|,insertlayer,moveforward,movebackward,absolute';
			$htmlOpts['theme_advanced_buttons3_add'] = ",|,styleprops,spellchecker,|,tablecontrols";
			$htmlOpts['theme_advanced_buttons4'] = "save";

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
		ed.onInit.add(function(ed, evt) {
			tinymce.dom.Event.add(ed.getWin(), 'blur', function(e) {
				// hide toolbar
				$('.mceExternalToolbar').hide();
			});
		});
	}
	function onSave(ed) {
		uf(ed.getElement(),ed.getContent(),ed.getContainer());		
	}
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

		mceInfo.win.document.getElementById(mceInfo.field_name).value = item.fullPath;
		mb.dialog('close');
	}
	var mceDefaultOptions = $baseOpts;
	var mceRichOptions = $.extend({},mceDefaultOptions,$richOpts);
	var mceHtmlOptions = $.extend({},mceDefaultOptions,$htmlOpts);
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
			$extendOpts = ','.$attributes['mce_options'];
			unset($attributes['mce_options']);
		}
		$optName = 'mceRichOptions';
		if ($inputType == itHTML) $optName = 'mceHtmlOptions';
		$script = '<script type="text/javascript">tinyMCE.init($.extend({},'.$optName.$extendOpts.',{editor_selector:"'.$saveClass.'"}))</script>';
		
		return utopia::DrawInput($fieldName,itTEXTAREA,$defaultValue,$possibleValues,$attributes,$noSubmit).$script;
	}
	public function RunModule() {
	}
}
