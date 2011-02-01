<?php
class uBlob extends uBasicModule {
	function SetupParents() {
		$this->SetRewrite(array('{module}','{field}','{pk}','{filename}'));
	}
	function GetUUID() { return 'blob'; }
	function ParentLoad($parent) {}
	function RunModule() {
		$rec = CallModuleFunc($_GET['module'],'LookupRecord',mysql_real_escape_string($_GET['pk']));

		if (!$rec || !isset($rec[$_GET['field']])) utopia::PageNotFound();
                utopia::CancelTemplate();

		$fieldName = $_GET['field'];
		$data = $rec[$fieldName];
		
		// allow browsers to auto detect content type
		header('Content-Type:');
		die($data);
	}
}

?>
