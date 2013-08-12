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
	}

	private static $states = array();
	public static function IgnoreClass($class,$state=true) {
		self::$states[$class] = $state;
	}

	public static function siteOffline($object) {
		if (modOpts::GetOption('site_online')) return;
		
		if (flag_is_set($object->GetOptions(), PERSISTENT)) return;
		$parent = get_class($object);
		if (isset(self::$states[$parent]) && self::$states[$parent]) return;

		if (uUserRoles::IsAdmin()) return; // site admin

		if ($object instanceof iAdminModule) return;
		uConfig::DownMaintenance();
	}
	public static function DashboardWidget() {
		if (modOpts::GetOption('site_online')) return;
		if (!uUserRoles::IsAdmin()) return;
		
		$modOptsObj =& utopia::GetInstance('modOptsList');
		$row = $modOptsObj->LookupRecord('site_online');
		echo '<p>This website is currently offline. Go Online? '.$modOptsObj->GetCell('value',$row).'</p>';
	}

	public function RunModule() { uConfig::DownMaintenance(); }
}

uEvents::AddCallback('BeforeRunModule','module_Offline::siteOffline');
uEvents::AddCallback('ShowDashboard','module_Offline::DashboardWidget',null,-999);
