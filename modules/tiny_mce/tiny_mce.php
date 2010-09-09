<?php
define('itRICHTEXT' ,'richtext');
define('itHTML' ,'html');
class module_TinyMCE extends flexDb_BasicModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return ''; } //$row = $this->GetRecord($this->GetDataset(),0); return $row['name']; }
	public function GetOptions() { return ALLOW_FILTER; }

	public function SetupParents() {
		FlexDB::AddInputType(itRICHTEXT,array($this,'drti_func'));
		FlexDB::AddInputType(itHTML,array($this,'drti_func'));
		//$this->AddParent('/');
	}

	public function ParentLoad($parent) {
	}

	private static $hasDrawnJS = false;
	static function InitScript() {
		if (!self::$hasDrawnJS) {
			self::$hasDrawnJS = true;
			FlexDB::AddJSFile(FlexDB::GetRelativePath(dirname(__FILE__)).'/tiny_mce.js');
			$scUrl = FlexDB::GetRelativePath(dirname(__FILE__)).'/plugins/spellchecker/rpc.php';
			$previewUrl = FlexDB::GetRelativePath(dirname(__FILE__)).'/plugins/preview/preview.php';
			list($fileManagerPath) = CallModuleFunc('fileManager' ,'Init');
			$relUploads = FlexDB::GetRelativePath(PATH_UPLOADS);


			$options = array();
			$options['mode'] = "specific_textareas";
			$options['relative_urls'] = false;
			$options['remove_script_host'] = false;
			$options['cleanup_on_startup'] = true;
			$options['cleanup'] = true;
			$options['theme'] = "advanced";
			$options['file_browser_callback'] = "openMediaBrowser";
			$options['spellchecker_rpc_url'] = $scUrl;
			$options['plugin_preview_pageurl'] = $previewUrl;
			$options['theme_advanced_toolbar_location'] = "top";
			$options['theme_advanced_toolbar_align'] = "left";
			$options['forced_root_block'] = false;

			$richOpts = $options;
			$htmlOpts = $options;

			$richOpts['editor_selector'] = "mceEditorRich";
			$richOpts['plugins'] = "inlinepopups,spellchecker";
			$richOpts['valid_elements'] = 'b,strong,i,u,ul,ol,li,del';
			$richOpts['theme_advanced_buttons1'] = "bold,italic,underline,strikethrough,|,numlist,bullist,|,spellchecker";
			$richOpts['theme_advanced_buttons2'] = "";
			$richOpts['theme_advanced_buttons3'] = "";
			$richOpts['theme_advanced_buttons4'] = "";

			$htmlOpts['editor_selector'] = "mceEditorHTML";
			$htmlOpts['extended_valid_elements'] = 'style,div[*],span[*],iframe[src|width|height|name|align|style]';
			$htmlOpts['plugins'] = "inlinepopups,media,advimage,spellchecker,noneditable";
			$htmlOpts['theme_advanced_buttons2_add'] = "media";
			$htmlOpts['theme_advanced_buttons3_add'] = "spellchecker";

			$richOpts = json_encode($richOpts);
			$htmlOpts = json_encode($htmlOpts);

			FlexDB::AppendVar('script_include',<<< FIN
	function updateMCE(className,hourglass) {
		var field = $("."+className);
		var val = tinyMCE.get(field.attr('id')).getContent();
		uf(field,val,hourglass);
	}
	var mb = null;
	function openMediaBrowser(field_name, url, type, win) {
		if (!mb) mb = $('<div></div>');
		fltr = '';
		if (type == 'image')
			fltr = '.jpeg|.jpg|.png|.gif|.tif|.tiff';

		mb.fileManager({ajaxPath:'$fileManagerPath',get:{filter:fltr},mceInfo:{field_name:field_name,win:win,type:type},events:{click:doClick}},pluploadOptions);
		mb.dialog({modal:false,width:'60%',height:500,zIndex:999999});
	}
	function doClick(event) {
		var item = $(this).data('item');
		if (item.type != 0) return;

		var path = '$relUploads/'+item.path;
		var mceInfo = item.target.data('options').mceInfo;

		mceInfo.win.document.getElementById(mceInfo.field_name).value = path;
		mb.dialog('close');
	}
	function InitMCE() {
		$(document).ready(function() {
			tinyMCE.init($richOpts);
			tinyMCE.init($htmlOpts);
		});
		//$(".mceRichText").live("change",function() { uf(this); });
	}
	InitMCE();
FIN
);
		}
	}

	function drti_func($fieldName,$inputType,$defaultValue='',$possibleValues=NULL,$attributes = NULL,$noSubmit = FALSE) {
		self::InitScript();

		$className = 'mceEditorRich';
		if ($inputType == itHTML) $className = 'mceEditorHTML';

		if (!is_array($attributes)) $attributes = array();
		if (array_key_exists('class',$attributes))
			$attributes['class'] .= ' '.$className;
		else
			$attributes['class'] = $className;

		$saveClass = 'mceSave'.rand(1,5000);
		$attributes['class'] .= ' '.$saveClass;
		$ajax = array_key_exists('__ajax',$_REQUEST) ? '<script>InitMCE();</script>' : '';
		return FlexDB::DrawInput($fieldName,itTEXTAREA,$defaultValue,$possibleValues,$attributes,$noSubmit).'<br><input type="button" value="Save" onclick="updateMCE(\''.$saveClass.'\',this)">'.$ajax;
	}
	public function RunModule() {
	}
}

?>