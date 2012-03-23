<?php


// This module controls access to any modules that do not implement iAdminModule 
// anyone who doesnt have site admin role will see the offline message
// 
class module_Offline extends uBasicModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return 'Site Offline'; }
	public function GetOptions() { return NO_NAV; }
	public function SetupParents() {
		modOpts::AddOption('site_online','Site Online',NULL,0,itYESNO);
		uEvents::AddCallback('CanAccessModule',array($this,'siteOffline'),utopia::GetCurrentModule());
	}
	public function GetUUID() { return 'site-offline'; }

	private static $states = array();
	public static function IgnoreClass($class,$state=true) {
		self::$states[$class] = $state;
	}

	public function siteOffline($object) {
		if (flag_is_set($object->GetOptions(), PERSISTENT)) return;
		$parent = get_class($object);
		if (isset(self::$states[$parent]) && self::$states[$parent]) return;

		if (modOpts::GetOption('site_online')) return;

		$role = uUserRoles::GetUserRole();
		if ($role && $role[0] === '-1') return; // site admin
			
		$obj = utopia::GetInstance($parent);
		if ($obj instanceof iAdminModule) return;

		//don't use SetCurrentModule because we don't want to 301 redirect 
		$this->RunModule();
		return FALSE;
	}

	public function RunModule() {
		header("HTTP/1.0 503 Service Temporarily Unavailable",true,503);
		header("Status: 503 Service Temporarily Unavailable",true,503);
		echo '<h1>Site is offline for maintenance.</h1>';
		utopia::Finish();
	}
}
