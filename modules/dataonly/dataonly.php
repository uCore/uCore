<?php

class uDataOnly extends uBasicModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return ''; }
	public function GetOptions() { return DEFAULT_OPTIONS | NO_NAV | PERSISTENT_PARENT; }

	public function SetupParents() {
		$this->RegisterAjax('excel',array($this,'excel'));
		$this->RegisterAjax('print',array($this,'showPrint'));
		$this->RegisterAjax('raw',array($this,'rawOutput'));
	}

	public static function inject($module) {
		$obj = utopia::GetInstance(__CLASS__);
		$this->AddParentCallback($module,array($obj,'inject_run'));
	}

	public function inject_run($parent) {
		if (!is_subclass_of($parent,'uListDataModule')) return;
		$obj = utopia::GetInstance($parent);
		$url = $obj->GetURL(array_merge($_GET,array('__ajax'=>'excel')));
		utopia::LinkList_Add('list_functions:'.$parent,'Export to Excel',$url,10,NULL,array('class'=>'btn bluebg'));

		$url = $obj->GetURL(array_merge($_GET,array('__ajax'=>'print')));
		utopia::LinkList_Add('list_functions:'.$parent,'Print',$url,10,NULL,array('class'=>'btn bluebg','target'=>'_blank'));
	}
	
	public function rawOutput() {
		$type = 'json';
		if (array_key_exists('_type',$_GET))
			$type = $_GET['_type'];
		
		if (!array_key_exists('_expires',$_GET) || !$_GET['_expires']) $_GET['_expires'] = 60*60*24*1;
		
		$etag = sha1($_SERVER['REQUEST_URI']);
		
		utopia::Cache_Check($etag,'application/json','',NULL,$_GET['_expires']);
		switch ($type) {
			case 'json':
				$obj = utopia::GetInstance(GetCurrentModule());
				$data = json_encode($obj->GetRawData());
				utopia::Cache_Output($data,$etag,'application/json','',NULL,$_GET['_expires']);
//				die();
			break;
		}
	}

	public function RunModule() {
	}

	public function showPrint() {
		utopia::UseTemplate(TEMPLATE_BLANK);
		RunModule();
	}

	public function excel() {
		$title = GetCurrentModule();
		header('Content-disposition: attachment; filename="'.$title.'.csv"');
		header('Content-type: excel/ms-excel; name="'.$title.'.csv"');

		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT",true);
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT",true);
		header("Cache-Control: no-store, no-cache, must-revalidate",true);
		header("Cache-Control: post-check=0, pre-check=0", true);

		$obj = utopia::GetInstance(GetCurrentModule());

		$fields = $obj->fields;
		$layoutSections = $obj->layoutSections;

		$fullOut = '';
		// section headers
		if (FALSE && count($layoutSections) > 1) {
			$out = array();
			foreach ($layoutSections as $sectionID => $sectionName) {
				$sectionCount = 0;
				foreach ($fields as $fieldName => $fieldData) {
					if ($fieldData['visiblename'] === NULL) continue;
					if ($fieldData['layoutsection'] !== $sectionID) continue;
					$sectionCount++;
				}
				$out[] = $sectionName;
				for ($i = 1; $i<$sectionCount; $i++)
				$out[] = '';//"<td colspan=\"$sectionCount\" class=\"{sorter: false}$secClass\">$sectionName</td>";
			}
			$fullOut .= '"'.join('","',$out)."\"\n";
		}

		// field headers
		$out = array();
		foreach ($fields as $fieldAlias => $fieldData) {
			if ($fieldData['visiblename'] === NULL) continue;
			$section = !empty($layoutSections[$fieldData['layoutsection']]) ? $layoutSections[$fieldData['layoutsection']].' ' : '';
			$out[] = $section.$fieldData['visiblename'];
		}
		$fullOut .= '"'.join('","',$out)."\"\n";

		// rows
		$dataset = $obj->GetDataset();
		$pk = $obj->GetPrimaryKey();

		$i = 0;
		while (($row = $obj->GetRecord($dataset,$i))) {
			$i++;
			$out = array();
			foreach ($fields as $fieldAlias => $fieldData) {
				if ($fieldData['visiblename'] === NULL) continue;
				//				$out[] = $row[$fieldAlias];
				//ErrorLog($fieldAlias);
				$data = trim($obj->PreProcess($fieldAlias,$row[$fieldAlias],$row[$pk]));
				if (empty($data)) $data = '';
				$out[] = $data;
			}
			$fullOut .= '"'.join('","',$out)."\"\n";
		}

		echo '"",""'."\n";
		echo mb_convert_encoding($fullOut,'UTF-16','UTF-8');
	}
}
?>
