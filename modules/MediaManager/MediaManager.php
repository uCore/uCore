<?php
define('itFILEMANAGER' ,'fileman');
class fileManager extends uBasicModule implements iAdminModule {
	public static function Initialise() {
		utopia::RegisterAjax('media','fileManager::RunPopup');
		self::AddParent('/');
	}
	public function GetSortOrder() { return -8900; }
	function GetTitle() { return 'Media'; }
	function SetupParents() {}
	static function RunPopup() {
		utopia::SetTitle('Browse Media');
		uEvents::RemoveCallback('ProcessDomDocument','uAdminBar::ProcessDomDocument');
		utopia::UseTemplate(TEMPLATE_BLANK); utopia::$noSnip = true;
		$o = utopia::GetInstance('fileManager');
		$o->_RunModule();
	}
	function RunModule() {
		echo '<h1>'.$this->GetTitle().'</h1>';
		echo '{list.'.get_class($this).'}';
		list($path,$pathUpload) = uUploads::Init();
		$relRoot = PATH_REL_ROOT;

		echo '<div class="module-content">You are here: Uploads <span id="mediaPath"></span><div id="fileMan"></div></div>';
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
