<?php
define('ADMIN_USER',flag_gen());

class tabledef_Users extends uTableDef {
	public $tablename = 'tabledef_AdminUsers';
	public function SetupFields() {
		$this->AddField('user_id',ftNUMBER);
		$this->AddField('username',ftVARCHAR,100);
		$this->AddField('password',ftVARCHAR,100);

		$this->SetPrimaryKey('user_id');
		$this->SetIndexField('username');
	}
}

class uUsersList extends uListDataModule implements iAdminModule {
	public function GetTitle() { return 'User Accounts'; }
	public function GetOptions() { return ALWAYS_ACTIVE | ALLOW_ADD | ALLOW_DELETE | ALLOW_EDIT; }

	public function GetTabledef() { return 'tabledef_Users'; }
	public function SetupFields() {
		$this->CreateTable('users');
		
		$this->AddField('username','username','users','Username',itTEXT);
		$this->AddField('password','password','users','Password',itPASSWORD);
	}

	public function SetupParents() {
		$this->AddParent('');
	}

	public function RunModule() {
		$this->ShowData();
	}
	public function &GetDataset($refresh = FALSE) {
		return parent::GetDataset($refresh);
	}
}

/* Admin is a fixed role */
class tabledef_UserRoles {
	public function SetupFields() {
		$this->AddField('role_id',ftNUMBER);
		$this->AddField('name',ftVARCHAR,50);
		$this->AddField('allow',ftLONGTEXT);

		$this->SetPrimaryKey('role_id');
	}
}

/*class tabledef_UserDetails extends uTableDef {
	public function SetupFields() {
		$this->AddField('detail_id',ftNUMBER);
		$this->AddField('user_id',ftNUMBER);
		$this->AddField('name',ftVARCHAR,50);

		$this->SetPrimaryKey('detail_id');
		
		uEvents::AddCallback('uUsersList','AfterSetupFields',array($this,'addfields'));
	}
	
	public function addfields($obj, $eventName) {
		$obj->CreateTable('detail','tabledef_UserDetails','users','user_id');
		$obj->AddField('name','name','detail','Name',itTEXT);
	}
}
*/
