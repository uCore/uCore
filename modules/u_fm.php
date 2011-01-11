<?php
define('PATH_UPLOADS',PATH_ABS_CORE.'.uploads');
define('itFILEMANAGER' ,'fileman');
class fileManager extends flexDb_BasicModule {
	function GetTitle() {
		return 'File Manager';
	}
	function SetupParents() {
		$this->AddParent('internalmodule_Admin');
		$this->RegisterAjax('fileManagerAjax',array($this,'ajax'));
		utopia::AddInputType(itFILEMANAGER,array($this,'show_fileman'));
	}
	function show_fileman($fieldName,$inputType,$defaultValue='',$possibleValues=NULL,$attributes = NULL,$noSubmit = FALSE) {
		list($path) = $this->Init();
		//if (!is_array($attributes)) $attributes = array();
		//$attributes['onclick'] = 'alert("moo");return false;';
		utopia::AppendVar('script_include', <<<FIN
	function filesel(id,item) {
		if (item.type != 0) return;
		$('#fileMan').dialog('close');
		alert(item.fullPath);
		uf(id,item.fullPath,'$fieldName');
	}
//	$(document).ready(function() {
//		$('#fileMan').dialog({autoOpen: false});
//	}
FIN
);
		return '<div id="fileMan"></div>'.flexDB::DrawInput($fieldName,itTEXT,$defaultValue,$possibleValues,$attributes,$noSubmit).
			'<input id="'.$fieldName.'" type="button" onclick="$(\'#fileMan\').fileManager({ajaxPath:\''.$path.'\',events:{dblclick:function(event) {filesel(\''.$fieldName.'\',$(this).data(\'item\'))} }}).dialog();" value="Choose File">';
		//return $out.$defaultValue.flexDB::DrawInput($fieldName,itBUTTON,'Choose File',$possibleValues,$attributes,$noSubmit);
	}
	function GetOptions() {return IS_ADMIN;}
	function ParentLoad($parent) {}
	function ajax() {
		utopia::CancelTemplate();
		if (array_key_exists('upload',$_GET))
			jqFileManager::ProcessUpload(PATH_UPLOADS);
		else
			jqFileManager::ProcessAjax(PATH_UPLOADS,null,'fileManager::OnRename');
	}
	function Init() {
		utopia::AddJSFile(jqFileManager::GetPathJS());
		utopia::AddCSSFile(jqFileManager::GetPathCSS());
		utopia::AppendVar('script_include', <<<FIN
	function dclick(event) {
		var item = $(this).data('item');
		if (item.type != 0) return;
		window.open(item.fullPath);
	}
FIN
);
		return array($this->GetAjaxPath(),$this->GetAjaxUploadPath());
	}
	function GetAjaxPath() {
		return $this->GetURL(array('__ajax'=>'fileManagerAjax'));
	}
	function GetAjaxUploadPath() {
		return $this->GetURL(array('__ajax'=>'fileManagerAjax','upload'=>1));
	}
	function RunModule() {
		list($path,$pathUpload) = $this->Init();

		echo '<div id="fileMan"></div>';
		//uPlupload::Init();
		utopia::AppendVar('script_include', "$(document).ready(function() { $('#fileMan').fileManager({ajaxPath:'$path',upload:true,events:{dblclick:dclick}},pluploadOptions);});");
	}
	static function OnRename($from,$to) {
		// has been renamed.. fix in CMS
		$from = jqFileManager::GetRelativePath($from);
		$to = jqFileManager::GetRelativePath($to);
//		$rows = cubeDB::lookupSimple(cubeCMS::GetTable(),'*','content LIKE \'%'.cubeDB::escape($from).'%\'');
//		foreach ($rows as $row) {
//			$newVal = str_replace($from,$to,$row['content']);
//			cubeDB::updateRecord(cubeCMS::GetTable(),array('content'=>$newVal),array(cubeCMS::GetPrimaryKey()=>$row[cubeCMS::GetPrimaryKey()]));
//		}
	}
}
?>
