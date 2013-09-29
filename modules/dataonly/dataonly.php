<?php

utopia::RegisterAjax('dataonly','uDataOnly::process');
uDataOnly::RegisterType('csv','uDataOnly::csv');
uDataOnly::RegisterType('json','uDataOnly::json');
uEvents::AddCallback('AfterInit','uDataOnly::processInjectionQueue',null,MAX_ORDER);
class uDataOnly {
	private static $types = array();
	public static function RegisterType($type,$callback) {
		self::$types[$type] = $callback;
	}
	private static $queue = array();
	public static function inject($module) {
		self::$queue[] = $module;
	}
	public static function processInjectionQueue() {
		foreach (self::$queue as $module) {
			self::doInject($module);
		}
	}
	private static function doInject($obj) {
		if (is_string($obj)) {
			$parent = $obj;
			$obj = utopia::GetInstance($parent);
		} else {
			$parent = get_class($obj);
		}
		if (!is_subclass_of($parent,'uDataModule')) return;
		$url = $obj->GetURL(array_merge($_GET,array('__ajax'=>'dataonly','dataonly-type'=>'csv')));
		utopia::LinkList_Add('list_functions:'.$parent,'Export to CSV',$url,10,NULL,array('class'=>'export export-csv'));
		self::$allowed[] = $parent;
	}
	private static $allowed = array();
	private static function IsAllowed($module) {
		return (array_search($module,self::$allowed) !== FALSE);
	}
	public static function process() {
		$type = isset($_GET['dataonly-type']) ? $_GET['dataonly-type'] : NULL;
		if (!$type || !isset(self::$types[$type])) {
			utopia::UseTemplate();
			utopia::PageNotFound(); return;
		}
		
		$qs = $_GET;
		unset($qs['__ajax']);
		unset($qs['dataonly-type']);
		
		$cm = utopia::GetCurrentModule();
		if (!self::IsAllowed($cm)) {
			// redirect to module
			$obj = utopia::GetInstance($cm);
			header('Location: '.$obj->GetURL($qs)); return;
		}
		
		try { // try to access it dataonly, if it fails for any reason, break out of , redirect
			call_user_func(self::$types[$type]);
		} catch (Exception $e) {
			$obj = utopia::GetInstance($cm);
			header('Location: '.$obj->GetURL($qs)); return;
		}
	}
	
	public static function json() {
		$etag = sha1($_SERVER['REQUEST_URI']);
		
		utopia::Cache_Check($etag,'application/json');

		$obj = utopia::GetInstance(utopia::GetCurrentModule());
		$data = json_encode($obj->GetRawData());
		utopia::Cache_Output($data,$etag,'application/json');
	}

	public static function csv() {
		$obj = utopia::GetInstance(utopia::GetCurrentModule());
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
