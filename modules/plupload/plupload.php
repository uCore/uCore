<?php
uJavascript::IncludeFile(dirname(__FILE__).'/lib/plupload.full.js');
uJavascript::IncludeFile(dirname(__FILE__).'/lib/jquery.ui.plupload/jquery.ui.plupload.js');
uCSS::IncludeFile(dirname(__FILE__).'/lib/jquery.ui.plupload/css/jquery.ui.plupload.css');

class uPlupload {
	static function Init($jsVarName,$uploadPath) {
		$pathCore = PATH_REL_CORE;
		uJavascript::AddText(<<<FIN
var $jsVarName = {
    runtimes : 'html5,flash,browserplus,html4',
    chunk_size : '1mb',
    url : '$uploadPath',
    flash_swf_url : '{$pathCore}modules/plupload/plupload.flash.swf'
};
FIN
		);
	}
}
