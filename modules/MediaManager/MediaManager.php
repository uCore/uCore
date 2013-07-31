<?php
define('itFILEMANAGER' ,'fileman');
class fileManager extends uBasicModule implements iAdminModule {
	public function GetSortOrder() { return -8700; }
	function GetTitle() { return 'Media'; }
	function SetupParents() {
		$this->AddParent('/');
		utopia::RegisterAjax('media',array($this,'RunPopup'));
	}
	function RunPopup() {
		utopia::SetTitle('Browse Media');
		uEvents::RemoveCallback('ProcessDomDocument','uAdminBar::ProcessDomDocument');
		utopia::UseTemplate(TEMPLATE_BLANK); utopia::$noSnip = true;
		$this->_RunModule();
	}
	function RunModule() {
		list($path,$pathUpload) = uUploads::Init();
		$relRoot = PATH_REL_ROOT;

		echo '<div>Uploads <span id="mediaPath"></span></div><div id="fileMan"></div>';
		$pluploadOpts = '';
		if (class_exists('uPlupload')) {
			$jsOptionVar = 'filemanagerOptions';
			uPlupload::Init($jsOptionVar,$pathUpload);
			$pluploadOpts = ','.$jsOptionVar;
		}
		uJavascript::AddText(<<<FIN
	$(function(){
		$('#fileMan')
			.fileManager({ajaxPath:'$path',virtualRoot:'{$relRoot}uploads',upload:true}$pluploadOpts)
			.on('changed',function(event, data){ $('#mediaPath').text(data.path.replace(/\//g,' > ')); })
			.on('dblclick','.fmFile',function() {
				var item = $(this).data('item');
				if (item.type != 0) return;
				window.open(item.fullPath);
			});
	});
FIN
);
	}
}
