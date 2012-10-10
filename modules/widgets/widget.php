<?php

class tabledef_Widgets extends uTableDef {
	public $tablename = 'tabledef_DataBlocks';
	public function SetupFields() {
		$this->AddField('block_id',ftVARCHAR,150);
		$this->AddField('block_type',ftVARCHAR,150);
		$this->AddField('module',ftTEXT);
		$this->AddField('content',ftTEXT);
		$this->AddField('filter',ftTEXT);
		$this->AddField('order',ftVARCHAR,30);
		$this->AddField('limit',ftVARCHAR,10);

		$this->AddField('display',ftTEXT);

		$this->SetPrimaryKey('block_id');
	}
}

class uWidgets_List extends uListDataModule implements iAdminModule {
	public function GetTitle() { return 'Widgets'; }
	public function GetOptions() { return ALLOW_DELETE | ALLOW_FILTER; }
	public function GetSortOrder() { return -8800; }
	public function GetTabledef() { return 'tabledef_Widgets'; }
	public function SetupFields() {
		$this->CreateTable('blocks');
		$this->AddField('block_id','block_id','blocks','ID');
		$this->AddField('block_type','block_type','blocks','Type');
	}
	public function SetupParents() {
		utopia::RegisterAjax('getWidgets',array($this,'getWidgets'));
		//$this->AddParentCallback('uCMS_List',array($this,'ShowData'));
		$this->AddParent('/');
	}
	public function getWidgets() {
		// static
		$rows = array();
		foreach(uWidgets::$staticWidgets as $name => $widget) {
			$rows[] = array('block_id'=>$name,'block_type'=>'Fixed Widgets');
		}
		// widgets
		$widgets = $this->GetRows();
		array_sort_subkey($widgets,'block_type');
		$rows = array_merge($rows,$widgets);

		$obj = utopia::GetInstance('uWidgets');
		$newUrl = $obj->GetURL();

		$rows = array($newUrl, $rows);
		header('Content-Type: application/json');
		echo json_encode($rows);
	}

	public function RunModule() {
		$this->ShowData();
		// show conclicts between static block ids and custom ids
		$rows = $this->GetRows();
		foreach ($rows as $row) {
			if (uWidgets::StaticWidgetExists($row['block_id'])) echo "Conflict: Widget ({$row['block_id']}) already exists as a static Widget.  Please rename it.";
		}
	}
}

utopia::AddTemplateParser('widget','uWidgets::DrawWidget');
class uWidgets extends uSingleDataModule implements iAdminModule {
	public function GetTitle() { return 'Edit Widget'; }
	public function GetOptions() { return ALLOW_DELETE | ALLOW_FILTER | ALLOW_EDIT | ALLOW_ADD; }
	public function GetTabledef() { return 'tabledef_Widgets'; }
	public function SetupFields() {
		$this->CreateTable('blocks');
		$this->AddField('block_id','block_id','blocks','Widget ID',itTEXT);

		$installed = array();
		$classes = get_declared_classes();
		foreach ($classes as $classname) { // install tables
			$reflectionA = new ReflectionClass($classname);
			if ($reflectionA->implementsInterface('iWidget')) $installed[$classname] = $classname;
		}
		$this->AddField('block_type','block_type','blocks','Type',itCOMBO,$installed);
	}
	public function SetupParents() {
		uJavascript::IncludeFile(dirname(__FILE__).'/widget.js');
		$this->AddParent('uWidgets_List','block_id','*');
	}

	public function InitInstance($type) {
		if (class_exists($type)) call_user_func(array($type,'Initialise'),$this);
		$this->NewSection('Preview');
		$this->AddField('preview',array($this,'getPreview'),'blocks','');
	}
	public function UpdateField($fieldAlias,$newValue,&$pkVal=NULL) {
		$rec = $this->LookupRecord($pkVal);
		$this->InitInstance($rec['block_type']);

		$ret = parent::UpdateField($fieldAlias,$newValue,$pkVal);
		if ($fieldAlias == 'block_type') AjaxEcho("window.location.reload(false);");
		return $ret;
	}
	public function RunModule() {
		$fltr = $this->FindFilter($this->GetPrimaryKey(),ctEQ,itNONE);
		$v = $this->GetFilterValue($fltr['uid']);
		if ($v) {
			$rec = $this->LookupRecord();
			$this->InitInstance($rec['block_type']);
		}

		$this->ShowData();
	}

	static function DrawWidget($rec) {
		if (!is_array($rec)) {
			if (self::StaticWidgetExists($rec)) return call_user_func(self::$staticWidgets[$rec]);

			$obj = utopia::GetInstance('uWidgets');
			$rec = $obj->LookupRecord($rec);
			if (!$rec) return '';
		}
		$content = '';
		if ($rec['block_type'] && class_exists($rec['block_type'])) $content = call_user_func(array($rec['block_type'],'DrawData'),$rec);

		$ret = '<div class="uWidget uWidget-'.$rec['block_id'].'">'.$content.'</div>';

		return $ret;
	}

	static function getPreview($originalVal,$pk,$processedVal,$rec) {
		return self::DrawWidget($rec);
	}

	public static $staticWidgets = array();
	public static function AddStaticWidget($ident,$callback) {
		self::$staticWidgets[$ident] = $callback;
	}
	public static function StaticWidgetExists($id) {
		return isset(self::$staticWidgets[$id]);
	}
	public static function IsStaticWidget($id) {
		return isset(self::$staticWidgets[$id]);
	}
}
