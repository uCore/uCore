<?php

class module_Offline extends uBasicModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return 'Site Offline'; }

	public function SetupParents() {
		modOpts::AddOption('module_Offline','online','Site Online',0,itYESNO);
//		if (internalmodule_AdminLogin::IsLoggedIn()) return;
		$this->AddParent('/');
	}

	public function ParentLoadPoint() { return 0; }
	public function ParentLoad($parent) {
		if (modOpts::GetOption('module_Offline','online')) return;
		if (internalmodule_AdminLogin::IsLoggedIn()) return;
		if (flag_is_set(CallModuleFunc($parent,'GetOptions'),IS_ADMIN)) return;

		$this->_RunModule();
		//utopia::SetVar('current_module',get_class($this));
		return FALSE;
	}

	public function RunModule() {
		echo '<h1>Site is offline for maintenance.</h1>';
	}
}

?>
