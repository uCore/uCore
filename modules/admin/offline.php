<?php

class module_Offline extends uBasicModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return 'Site Offline'; }
	public function GetOptions() { return NO_NAV; }
	public function SetupParents() {
		modOpts::AddOption('module_Offline','online','Site Online',0,itYESNO);
		$this->AddParentCallback('/',array($this,'siteOffline'));
	}

	public function siteOffline($parent) {
		if (modOpts::GetOption('module_Offline','online')) return;
		if (internalmodule_AdminLogin::IsLoggedIn()) return;
		$obj = utopia::GetInstance($parent);
		if (flag_is_set($obj->GetOptions(),IS_ADMIN)) return;

		$this->_RunModule();
		//utopia::SetVar('current_module',get_class($this));
		return FALSE;
	}

	public function RunModule() {
		echo '<h1>Site is offline for maintenance.</h1>';
	}
}

?>
