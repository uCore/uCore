<?php
class uBlob extends uBasicModule {
	function SetupParents() {
		$this->SetRewrite(array('{module}','{field}','{pk}','{filename}'));
	}
	function GetUUID() { return 'blob'; }
	function RunModule() {
		$obj = utopia::GetInstance($_GET['module']);
		$rec = $obj->LookupRecord(mysql_real_escape_string($_GET['pk']),true);

		if (!$rec || !isset($rec[$_GET['field']])) utopia::PageNotFound();
                utopia::CancelTemplate();

		$fieldName = $_GET['field'];
		$data = $rec[$fieldName];
		
		// allow browsers to auto detect content type
		header('Content-Type:');
		die($data);
	}
	function GetFileLink($module,$field,$pk,$filename=NULL) {
		if ($filename === NULL) {
			$obj = utopia::GetInstance($module);
			$rec = $obj->LookupRecord($pk);
			$filename = $rec[$field.'_filename'];
		}
		return parent::GetURL(array(
			'module'=>$module,
			'field'=>$field,
			'pk'=>$pk,
			'filename'=>$filename,
		));
	}
}

?>
