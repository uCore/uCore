<?php

utopia::RegisterAjax('csv','uDataOnly::csv');
utopia::RegisterAjax('raw','uDataOnly::rawOutput');
class uDataOnly  {
	public static function inject($module) {
		uEvents::AddCallback('BeforeRunModule','uDataOnly::inject_run',$module);
	}

	public static function inject_run($obj) {
		if (is_string($obj)) {
			$parent = $obj;
			$obj =& utopia::GetInstance($parent);
		} else {
			$parent = get_class($obj);
		}
		if (!is_subclass_of($parent,'uDataModule')) return;
		$url = $obj->GetURL(array_merge($_GET,array('__ajax'=>'csv')));
		utopia::LinkList_Add('list_functions:'.$parent,'Export to CSV',$url,10,NULL,array('class'=>'btn btn-csv'));
	}
	
	public static function rawOutput() {
		$type = 'json';
		if (array_key_exists('_type',$_GET))
			$type = $_GET['_type'];
		
		if (!array_key_exists('_expires',$_GET) || !$_GET['_expires']) $_GET['_expires'] = 60*60*24*1;
		
		$etag = sha1($_SERVER['REQUEST_URI']);
		
		utopia::Cache_Check($etag,'application/json','',NULL,$_GET['_expires']);
		switch ($type) {
			case 'json':
				$obj =& utopia::GetInstance(utopia::GetCurrentModule());
				$data = json_encode($obj->GetRawData());
				utopia::Cache_Output($data,$etag,'application/json','',NULL,$_GET['_expires']);
//				die();
			break;
		}
	}

	public static function csv() {
		$obj =& utopia::GetInstance(utopia::GetCurrentModule());
		$title = $obj->GetTitle();

		$fields = $obj->fields;
		$layoutSections = $obj->layoutSections;

		$fullOut = '';
		// field headers
		$out = array();
		foreach ($fields as $fieldAlias => $fieldData) {
				if (!$fieldData['visiblename']) continue;
			$out[] = $fieldData['visiblename'];
		}
		$fullOut .= '"'.join('","',$out)."\"\n";

		// rows
		$dataset = $obj->GetDataset();
		$pk = $obj->GetPrimaryKey();

		$i = 0;
		while (($row = $dataset->fetch())) {
			$i++;
			$out = array();
			foreach ($fields as $fieldAlias => $fieldData) {
				if (!$fieldData['visiblename']) continue;
				$data = strip_tags(trim($obj->PreProcess($fieldAlias,$row[$fieldAlias],$row[$pk])));
				if (empty($data)) $data = '';
				$out[] = preg_replace('/"/','""',$data);
			}
			$fullOut .= '"'.join('","',$out)."\"\n";
		}

		$etag = utopia::checksum($fullOut);
		utopia::Cache_Output($fullOut,$etag,'text/csv',$title.'.csv');
	}
}
