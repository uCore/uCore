<?php
FlexDB::AddCSSFile(FlexDB::GetRelativePath(dirname(__FILE__).'/plupload.queue.css'));
FlexDB::AddJSFile(FlexDB::GetRelativePath(dirname(__FILE__).'/plupload.full.min.js'));
FlexDB::AddJSFile(FlexDB::GetRelativePath(dirname(__FILE__).'/jquery.plupload.queue.min.js'));
$pathUpload = CallModuleFunc('fileManager','GetAjaxUploadPath');
$pathCore = PATH_REL_CORE;
FlexDB::AppendVar('script_include',<<<FIN
	var pluploadOptions = {
			runtimes : 'html5,flash,gears,silverlight,browserplus,html4',
			chunk_size : '1mb',
			//resize : {width : 640, height : 480, quality : 90},
			url : '$pathUpload',
			flash_swf_url : '{$pathCore}modules/plupload/plupload.flash.swf',
			silverlight_xap_url : '{$pathCore}modules/plupload/plupload.silverlight.xap',
			rename:true
	};
FIN
);
?>