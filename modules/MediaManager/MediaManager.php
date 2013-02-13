<?php
define('itFILEMANAGER' ,'fileman');
class fileManager extends uBasicModule implements iAdminModule {
	public function GetSortOrder() { return -8700; }
	function GetTitle() { return 'Media'; }
	function SetupParents() {
		$this->AddParent('/');
	}
	function RunModule() {
		$tabGroupName = utopia::Tab_InitGroup();
		ob_start();
		list($path,$pathUpload) = uUploads::Init();

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
			.fileManager({ajaxPath:'$path',upload:true}$pluploadOpts)
			.on('changed',function(event, data){ $('#mediaPath').text(data.path.replace(/\//g,' > ')); })
			.on('dblclick','.fmFile',FileManagerItemClick);
	});
FIN
);

		$out = ob_get_contents();
		ob_end_clean();
		utopia::Tab_Add($this->GetTitle(),$out,$this->GetModuleId(),$tabGroupName,false,$this->GetSortOrder());
		utopia::Tab_InitDraw($tabGroupName);
	}
}
