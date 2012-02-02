<?php
define('ADMIN_USER',flag_gen());

class tabledef_AdminUsers extends uTableDef {
  public $tablename = 'admin_users';
  public function SetupFields() {
    $this->AddField('username',ftVARCHAR,40);
    $this->AddField('password',ftVARCHAR,40);

    $this->SetPrimaryKey('username');
  }
}

class uAdminUsersList extends uListDataModule implements iAdminModule {
        public function GetTitle() { return 'Admin Accounts'; }
        public function GetOptions() { return ALWAYS_ACTIVE | ALLOW_ADD | ALLOW_DELETE | ALLOW_EDIT; }

        public function GetTabledef() { return 'tabledef_AdminUsers'; }
        public function SetupFields() {
                $this->CreateTable('users','tabledef_AdminUsers');
		$this->AddField('username','username','users','Username',itTEXT);
                $this->AddField('password','password','users','Password',itPASSWORD);
        }

        public function SetupParents() {
		$this->AddParent('');
	}

	public function RunModule() {
		$this->ShowData();
	}
}