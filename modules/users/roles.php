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
	public function GetTitle() { return 'Role Management'; }
	public function GetOptions() { return ALWAYS_ACTIVE | ALLOW_ADD | ALLOW_DELETE | ALLOW_EDIT; }

	public function GetTabledef() { return 'tabledef_UserRoles'; }
	public function SetupFields() {	
		$this->CreateTable('roles');
		$this->AddField('name','name','roles','Name',itTEXT);
		$this->AddField('allow','allow','roles','Allowed Modules',itCHECKBOX,'uUserRoles::GetModules');
		
		$this->AddFilter('role_id',ctNOTEQ,itNONE,-1);
	}
	public static function Initialise() {
		uEvents::AddCallback('AfterInit','uUserRoles::AssertAdminRole');
		self::AddParent('uUsersList');
	}

	public function SetupParents() {
	}
	public static function AssertAdminRole() {
		$obj = utopia::GetInstance(__CLASS__);
		$obj->BypassSecurity(true);
		$rec = $obj->LookupRecord(-1,true);
		$obj->BypassSecurity(false);
		if (!$rec) { // insert directly to table to avoid checking permissions
			$tbl = utopia::GetInstance('tabledef_UserRoles');
			$pk = null;
			$tbl->UpdateField($tbl->GetPrimaryKey(),-1,$pk);
			$tbl->UpdateField('name','Site Administrator',$pk);
		}
	}
	private static $roleCache = null;
	public static function GetUserRole() {
		uUserLogin::TryLogin();
		if (!isset($_SESSION['current_user'])) return FALSE;
		if (!self::$roleCache) {
			$obj = utopia::GetInstance('uUsersList');
			$obj->BypassSecurity(true);
			$user = $obj->LookupRecord(array('user_id'=>$_SESSION['current_user']),true);
			$obj->BypassSecurity(false);
			if ($user['_roles_pk'] === NULL) return FALSE;

			$obj = utopia::GetInstance('uUserRoles');
			$obj->BypassSecurity(true);
			$role = $obj->LookupRecord($user['_roles_pk'],true); // clear fixed filters
			$obj->BypassSecurity(false);
			self::$roleCache = array($role['role_id'],utopia::jsonTryDecode($role['allow']));
		}
		return self::$roleCache;
	}
	public static function IsAdmin() {
		$role = self::GetUserRole();
		return ($role && $role[0] === '-1');
	}
	
	public static function GetModules() {
		$modules = utopia::GetModulesOf('iRestrictedAccess');
		foreach ($modules as $k => $v) {
			$modules[$k] = $k;
		}

		foreach (self::$linked as $k => $mods) {
			// unset all mods
			foreach ($mods as $m) unset($modules[$m]);
			// add k to modules
			$modules[$k] = $k;
		}

		// remove custom roles
		foreach (self::$customRoles as $k => $c) {
			unset($modules[$k]);
		}

		return $modules;
	}
	private static $linked = array();
	public static function LinkRoles($id,$modules) {
		if (!is_array($modules)) $modules = array($modules);
		$modules = array_filter($modules);
		if (!$modules) return;
		
		if (!isset(self::$linked[$id])) self::$linked[$id] = array();
		self::$linked[$id] = array_merge(self::$linked[$id],$modules);
	}
	private static $customRoles = array();
	public static function AddCustomRole($module,$callback) {
		if (isset(self::$customRoles[$module])) throw new Exception('Custom role for '.$module.' already exsits.');
		self::$customRoles[$module] = $callback;
	}
	
	public static function checkPermission($object) {
		// site admin
		if (self::IsAdmin()) return true;

		$parent = get_class($object);

		// custom permission
		if (isset(self::$customRoles[$parent]) && is_callable(self::$customRoles[$parent])) return call_user_func_array(self::$customRoles[$parent],array($parent));

		// only valid for iRestrictedAccess modules
		if (!($object instanceof iRestrictedAccess)) return true;
		
		$role = self::GetUserRole();
		if ($role) {
			if (!is_array($role[1])) $role[1] = array($role[1]);
			foreach ($role[1] as $r) { // iterate role permissions
				if (isset(self::$linked[$r]) && array_search($parent,self::$linked[$r])!==FALSE) return true;
				if ($r === $parent) return true;
			}
		}
		return false;
	}
	public static function NoRole($module) {
		self::AddCustomRole($module,'uUserRoles::RetTrue');
	}
	private static function RetTrue() {
		return true;
	}
	
	public function RunModule() {
		$this->ShowData();
	}
}
