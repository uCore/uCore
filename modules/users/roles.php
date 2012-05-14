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
		self::InitModules();
		$this->CreateTable('roles');
		$this->AddField('name','name','roles','Name',itTEXT);
		$this->AddField('allow','allow','roles','Allowed Modules',itCHECKBOX,&self::$modules);
		
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
	private static $modules = array();
	private static function InitModules() {
		if (self::$modules) return;
		$modules = utopia::GetModules();
		foreach ($modules as $k => $v) {
			$o = utopia::GetInstance($k);
			if (!($o instanceof iAdminModule)) unset($modules[$k]);
			else $modules[$k] = $o->GetTitle();
		}
		self::$modules = array_flip($modules);
	}
	private static $linked = array();
	public static function LinkRoles($id,$modules) {
		self::InitModules();
		if (!is_array($modules)) $modules = array($modules);
		$modules = array_filter($modules);
		if (!$modules) return;
		
		foreach (self::$modules as $t => $mod) {
			if (array_search($mod,$modules) !== FALSE) unset(self::$modules[$t]);
		}
		self::$modules[$id] = $id;
		
		if (!isset(self::$linked[$id])) self::$linked[$id] = array();
		self::$linked[$id] = array_merge(self::$linked[$id],$modules);
	}
	private static $customRoles = array();
	public static function SetCustom($module,$callback) {
		if (isset(self::$customRoles[$module])) throw new Exception('Custom role for '.$module.' already exsits.');
		self::$customRoles[$module] = $callback;
	}
	
	public static function checkPermission($object) {
		if (!($object instanceof iAdminModule)) return true;
		self::InitModules();
		$parent = get_class($object);

		$role = self::GetUserRole();
		if ($role) {
			// site admin
			if ($role[0] === '-1') return true;
			
			// custom permission
			if (isset(self::$customRoles[$parent]) && is_callable(self::$customRoles[$parent])) return call_user_func_array(self::$customRoles[$parent],array($parent));
			
			if (!is_array($role[1])) $role[1] = array($role[1]);
			foreach ($role[1] as $r) { // iterate role permissions
				if (isset(self::$linked[$r]) && array_search($parent,self::$linked[$r])!==FALSE) return true;
				if ($r === $parent) return true;
			}
		}
		return false;
	}
	public static function NoRole($module) {
		self::InitModules();
		foreach (self::$modules as $t => $mod) {
			if ($mod === $module) unset(self::$modules[$t]);
		}
		self::SetCustom($module,'uUserRoles::RetTrue');
	}
	private static function RetTrue() {
		return true;
	}
	
	public function RunModule() {
		$this->ShowData();
	}
}
