<?php
/*
class tabledef_MYTABLE extends uTableDef {
  public function SetupFields() {
    $this->AddField('id',ftNUMBER);
    $this->AddField('MY_FIELD',ftVARCHAR,150);

    $this->SetPrimaryKey('id');
  }
}

class uMODULENAME_List extends uListDataModule implements iAdminModule {
	public function GetTitle() { return 'My Module'; }
	public function GetOptions() { return ALLOW_DELETE | ALLOW_FILTER | ALLOW_EDIT; }
	public function GetTabledef() { return 'tabledef_MYTABLE'; }
	public function SetupFields() {
		$this->CreateTable('mytable');
		$this->AddField('id','id','mytable','ID');
		$this->AddField('MY_FIELD','MY_FIELD','mytable','My Field');
	}
	public function SetupParents() {
		$this->AddParent('/');
	}
	public function RunModule() {
		$this->ShowData();
	}
}

class uMODULENAME_Edit extends uSingleDataModule implements iAdminModule {
	public function GetTitle() { return 'Edit My Record'; }
	public function GetOptions() { return ALLOW_ADD | ALLOW_EDIT | ALLOW_DELETE | ALLOW_FILTER; }
	public function GetTabledef() { return 'tabledef_MYTABLE'; }
	public function SetupFields() {
		$this->CreateTable('mytable');
		$this->AddField('id','id','mytable','ID');
		$this->AddField('MY_FIELD','MY_FIELD','mytable','My Field',itTEXT);
	}
	public function SetupParents() {
		$this->AddParent('uMODULENAME_List','id','*');
	}
	public function RunModule() {
		$this->ShowData();
	}
}
*/
