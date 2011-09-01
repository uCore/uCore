<?php

class module_Offline extends uBasicModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return 'Site Offline'; }
	public function GetOptions() { return NO_NAV; }
	public function SetupParents() {
		modOpts::AddOption('module_Offline','online','Site Online',0,itYESNO);
		$this->AddParentCallback('/',array($this,'siteOffline'));
	}

	private static $states = array();
	public static function IgnoreClass($class,$state=true) {
		self::$states[$class] = $state;
	}

	public function siteOffline($parent) {
		if (isset(self::$states[$parent]) && self::$states[$parent]) return;

		if (modOpts::GetOption('module_Offline','online')) return;
		if (internalmodule_AdminLogin::IsLoggedIn()) return;

		$obj = utopia::GetInstance($parent);
		if ($obj instanceof iAdminModule) return;

		$this->_RunModule();
		return FALSE;
	}

	public function RunModule() {
		echo '<h1>Site is offline for maintenance.</h1>';
	}
}
