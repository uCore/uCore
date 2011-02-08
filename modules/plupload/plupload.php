<?php
class uPlupload {
	static function Init($jsVarName,$uploadPath) {
		utopia::AddJSFile(utopia::GetRelativePath(dirname(__FILE__).'/plupload.full.min.js'));
		utopia::AddJSFile(utopia::GetRelativePath(dirname(__FILE__).'/jquery.ui.plupload.min.js'));
		utopia::AddCSSFile(utopia::GetRelativePath(dirname(__FILE__).'/jquery.ui.plupload.css'));

		$pathCore = PATH_REL_CORE;
		uJavascript::AddText(<<<FIN
var $jsVarName = {
    runtimes : 'gears,html5,flash,silverlight,browserplus,html4',
    chunk_size : '1mb',
    url : '$uploadPath',
    flash_swf_url : '{$pathCore}modules/plupload/plupload.flash.swf',
    silverlight_xap_url : '{$pathCore}modules/plupload/plupload.silverlight.xap'
};
FIN
		);
	}
}

?>
