<?php

class internalmodule_DataOnly extends flexDb_BasicModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return ''; }
	public function GetOptions() { return DEFAULT_OPTIONS | NO_NAV | PERSISTENT_PARENT; }

	public function SetupParents() {
		//		$this->AddParent('module_AdvisorCommissionMortgageProc');
		//		$this->AddParent('module_MortgageList');
		//		$this->AddParent('module_LifeList');
		$this->AddParent('*');
		$this->RegisterAjax('excel',array($this,'excel'));
		$this->RegisterAjax('print',array($this,'showPrint'));
		$this->RegisterAjax('raw',array($this,'rawOutput'));
	}

//	public function ParentLoadPoint() { return 0; }
	public function ParentLoad($parent) {
		if (!is_subclass_of($parent,'flexDb_ListDataModule')) return;

		$url = CallModuleFunc($parent,'GetURL',array_merge($_GET,array('__ajax'=>'excel')));
		utopia::LinkList_Add('list_functions:'.$parent,'Export to Excel',$url,10,NULL,array('class'=>'fdb-btn bluebg'));

		$url = CallModuleFunc($parent,'GetURL',array_merge($_GET,array('__ajax'=>'print')));
		utopia::LinkList_Add('list_functions:'.$parent,'Print',$url,10,NULL,array('class'=>'fdb-btn bluebg','target'=>'_blank'));
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
				$data = json_encode(CallModuleFunc(GetCurrentModule(),'GetRawData'));
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
		return;
		$modules = array(GetCurrentModule());
		if (is_array($GLOBALS['children']['/'])) foreach ($GLOBALS['children']['/'] as $info) {
			array_push($modules,$info['moduleName']);
		}
		if (is_array($GLOBALS['children'][GetCurrentModule()])) foreach ($GLOBALS['children'][GetCurrentModule()] as $info) {
			array_push($modules,$info['moduleName']);
		}

		foreach ($modules as $module) { // add in unions
			$unions = GetModuleVar($module,'UnionModules');
			if (is_array($unions)) foreach ($unions as $union)
			$modules[] = $union;
		}
		//    echo GetCurrentModule();

		foreach ($modules as $module) {
			//			$filters = GetModuleVar($module,'filters');
			//			if (is_array($filters)) foreach ($filters as $filterType => $filterTypeData)
			//				if (is_array($filterTypeData)) foreach ($filterTypeData as $filterSetId => $filterSet)
			//					if (is_array($filterSet)) foreach ($filterSet as $uid => $filterData)
			// set to an invalid input type, this will allow the filter to pickup from the querystring, but will stop the filterbox from drawing.
			//						if ($filterData['it'] !== itNONE) $filters[$filterType][$filterSetId][$uid]['it'] = '__';
			CallModuleFunc($module,'HideFilters');
			//			SetModuleVar($module,'filters',$filters);
			CallModuleFunc($module,'_SetupFields');
			$fields = GetModuleVar($module,'fields');
			if (is_array($fields)) foreach ($fields as $alias => $fieldInfo)
			if ($fieldInfo['inputtype'] !== itNONE) $fields[$alias]['inputtype'] = itNONE;
			SetModuleVar($module,'fields',$fields);
		}
		//LoadChildren();

		$filterOutput = '';
		$filters = GetModuleVar(GetCurrentModule(),'filters');
		if (is_array($filters)) foreach ($filters as $filterType => $filterTypeData)
		if (is_array($filterTypeData)) foreach ($filterTypeData as $filterSetId => $filterSet)
		if (is_array($filterSet)) foreach ($filterSet as $filterData) {
			if ($filterData['visiblename'] === NULL) continue;
			$filterValue = CallModuleFunc(GetCurrentModule(),'GetFilterValue',$filterData['uid']);
			if (empty($filterValue)) continue;
			$filterOutput .= "{$filterData['visiblename']} {$filterData['ct']} $filterValue<br>";
		}
		if (!empty($filterOutput)) echo "<p><b>Filters:</b><br>$filterOutput</p>";
		//echo "moo";
		//utopia::UseTemplate(TEMPLATE_PRINT_PATH);
		RunModule();
		//die();
		//echo utopia::GetVar('content');
		//CancelTemplate();
		//include(TEMPLATE_PRINT_PATH);
	}

	public function excel() {
		header('Content-disposition: attachment; filename="'.$title.'.csv"');
		header('Content-type: excel/ms-excel; name="'.$title.'.csv"');

		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT",true);
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT",true);
		header("Cache-Control: no-store, no-cache, must-revalidate",true);
		header("Cache-Control: post-check=0, pre-check=0", true);
		header("Pragma: no-cache",true);

		$fields = GetModuleVar(GetCurrentModule(),'fields');
		$layoutSections = GetModuleVar(GetCurrentModule(),'layoutSections');

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
		$dataset = CallModuleFunc(GetCurrentModule(),'GetDataset');
		$pk = CallModuleFunc(GetCurrentModule(),'GetPrimaryKey');

		$i = 0;
		while (($row = CallModuleFunc(GetCurrentModule(),'GetRecord',$dataset,$i))) {
			$i++;
			$out = array();
			foreach ($fields as $fieldAlias => $fieldData) {
				if ($fieldData['visiblename'] === NULL) continue;
				//				$out[] = $row[$fieldAlias];
				//ErrorLog($fieldAlias);
				$data = trim(CallModuleFunc(GetCurrentModule(),'PreProcess',$fieldAlias,$row[$fieldAlias],$row[$pk]));
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