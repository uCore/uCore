<?php

/* Admin is a fixed role */
class tabledef_UserRoles extends uTableDef  {
	public function SetupFields() {
		$this->AddField('role_id',ftNUMBER);
		$this->AddField('name',ftVARCHAR,50);
		$this->AddField('allow',ftLONGTEXT);

		$this->SetPrimaryKey('role_id');
	}
}

/* Admin is a fixed role */
uEvents::AddCallback('CanAccessModule','uUserRoles::checkPermission');
class uUserRoles extends uListDataModule implements iAdminModule {
	public function GetTitle() { return 'User Roles'; }
	public function GetOptions() { return ALWAYS_ACTIVE | ALLOW_ADD | ALLOW_DELETE | ALLOW_EDIT | PERSISTENT; }

	public function GetTabledef() { return 'tabledef_UserRoles'; }
	public function SetupFields() {
		$this->CreateTable('roles');
		
		$modules = utopia::GetModules();
		foreach ($modules as $k => $v) {
			$o = utopia::GetInstance($k);
			if (!($o instanceof iAdminModule)) unset($modules[$k]);
			else $modules[$k] = $o->GetTitle();
		}
		$modules = array_flip($modules);
		
		$this->AddField('name','name','roles','Name',itTEXT);
		$this->AddField('allow','allow','roles','Allowed Modules',itCHECKBOX,$modules);
		
		$this->AddFilter('role_id',ctNOTEQ,itNONE,-1);
	}

	public function SetupParents() {
		$this->AddParent('uUsersList');
		uEvents::AddCallback('InitComplete',array($this,'AssertAdminRole'));
	}
	public function AssertAdminRole() {
		$rec = $this->LookupRecord(-1,true);
		if (!$rec) { // insert directly to table to avoid checking permissions
			$o = utopia::GetInstance('tabledef_UserRoles');
			$pk = null;
			$o->UpdateField($o->GetPrimaryKey(),-1,$pk);
			$o->UpdateField('name','Site Administrator',$pk);
		}
	}
	private static $roleCache = null;
	public static function GetUserRole() {
		uUserLogin::TryLogin();
		if (!isset($_SESSION['current_user'])) return FALSE;
		if (!self::$roleCache) {
			$obj = utopia::GetInstance('uUsersList');
			$user = $obj->LookupRecord(array('user_id'=>$_SESSION['current_user']));
			$obj = utopia::GetInstance('uUserRoles');
			if ($user['_roles_pk'] === NULL) return FALSE;

			$role = $obj->LookupRecord($user['_roles_pk'],true); // clear fixed filters
			self::$roleCache = array($role['role_id'],utopia::jsonTryDecode($role['allow']));
		}
		return self::$roleCache;
	}
	
	public static function checkPermission($object) {
		if (!($object instanceof iAdminModule)) return true;
		$parent = get_class($object);

		$role = self::GetUserRole();
		if ($role) {
			if ($role[0] === '-1') return true; // site admin
			foreach ($role[1] as $r) { // iterate role permissions
				if ($r === $parent) return true;
			}
		}
		return false;
	}
	
	public function RunModule() {
		$this->ShowData();
	}
}

