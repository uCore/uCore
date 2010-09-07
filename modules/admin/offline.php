<?php

class module_Offline extends flexDb_BasicModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return 'Site Offline'; }
	public function GetOptions() { return DEFAULT_OPTIONS; }

	public function SetupParents() {
		if (internalmodule_AdminLogin::IsLoggedIn()) return;
		$this->AddParent('/');
	}

	public function ParentLoadPoint() { return 0; }
	public function ParentLoad($parent) {
		if (!$this->IsActive()) return true;
		if (internalmodule_AdminLogin::IsLoggedIn()) return;
		if (flag_is_set(CallModuleFunc($parent,'GetOptions'),IS_ADMIN)) return;

		$this->_RunModule();
		//FlexDB::SetVar('current_module',get_class($this));
		return FALSE;
	}

	public function RunModule() {
		echo '<h1>Site is currently offline.</h1>';
	}
}

?>