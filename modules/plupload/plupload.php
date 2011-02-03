<?php
class plupload extends uBasicModule {
	function SetupParents() {
		utopia::AddJSFile(utopia::GetRelativePath(dirname(__FILE__).'/plupload.full.min.js'));
		utopia::AddJSFile(utopia::GetRelativePath(dirname(__FILE__).'/jquery.ui.plupload.min.js'));
		utopia::AddCSSFile(utopia::GetRelativePath(dirname(__FILE__).'/jquery.ui.plupload.css'));

		$pathUpload = CallModuleFunc('fileManager','GetAjaxUploadPath');
		$pathCore = PATH_REL_CORE;
		uJavascript::IncludeText(<<<FIN
var pluploadOptions = {
    runtimes : 'gears,html5,flash,silverlight,browserplus,html4',
    chunk_size : '1mb',
    //resize : {width : 640, height : 480, quality : 90},
    url : '$pathUpload',
    flash_swf_url : '{$pathCore}modules/plupload/plupload.flash.swf',
    silverlight_xap_url : '{$pathCore}modules/plupload/plupload.silverlight.xap',
    rename:true
};
FIN
		);
	}
	function ParentLoad($parent) {}
	function RunModule() {}
}

?>
