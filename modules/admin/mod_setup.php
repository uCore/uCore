<?php

// dependancies
// check dependancies exist - Move to install?

class tabledef_Modules extends uTableDef {
	public $tablename = 'internal_modules';

	public function SetupFields() {
		// add all the fields in here
		// AddField($name, $type, $length, $collation='', $attributes='', $null='not null', $default='', $extra='', $comments='')
		// SetPrimaryKey($name);

		$this->AddField('module_id',ftNUMBER,0);
		$this->AddField('uuid','varchar',36);
		$this->AddFieldArray('module_name','varchar',50,array('readonly'=>TRUE));
		$this->AddField('module_active',ftBOOL);
		$this->AddField('sort_order',ftNUMBER,0);
		$this->SetPrimaryKey('module_id');
		$this->SetUniqueField('uuid');
		//$this->SetUniqueField('module_name');
	}
}

class internalmodule_ModuleSetup extends uListDataModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return 'Module Setup'; }

	public function GetTabledef() { return 'tabledef_Modules'; }
	public function GetOptions() { return ALWAYS_ACTIVE | ALLOW_EDIT | ALLOW_FILTER | IS_ADMIN; }

	public function GetSortOrder() { return -9; }

	public function SetupParents() {
		if (!internalmodule_AdminLogin::IsLoggedIn(ADMIN_USER)) return;
		$this->AddParent('internalmodule_Admin');
	}

	public function SetupFields() {
		$this->CreateTable('modules', 'tabledef_Modules');
		$this->AddField('uuid','uuid','modules','UUID');
		$this->AddField('module_name','module_name','modules','Module Name');
		$this->AddField('module_title',array($this,'findTitle'),'','Module Title');
		//$this->AddPreProcessCallback('module_title',array($this,'findTitle'));

		$this->AddField('module_active','module_active','modules','Module Active',itYESNO);//OPTION,array('Yes'=>1,'No'=>0));

		$this->AddFilter('module_name',ctLIKE,itTEXT);
	}

	public function findTitle($value) {
		$rec = $this->GetCurrentRecord();
		return CallModuleFunc($rec['module_name'],'GetTitle');
	}

	public function ParentLoad($parent) {
	}

	public function RunModule() {
		$this->ShowData();
	}
}

?>
