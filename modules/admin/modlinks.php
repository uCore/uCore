<?php

class modLinks extends uBasicModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return 'Admin Home'; }
	public function GetOptions() { return NO_NAV | ALWAYS_ACTIVE; }

	public function SetupParents() {
		$this->AddParentCallback('/',array($this,'drawLinks'));
	}

	//public function ParentLoadPoint() { return 0; }
	public function drawLinks($parent) {
		$arr = array();

		$current = GetCurrentModule();
		$currentAdded = false;
		$parentObj = utopia::GetInstance($parent);
		$admin = ($parentObj instanceof iAdminModule);
		$isadmin = internalmodule_AdminLogin::IsLoggedIn();
		$children = utopia::GetChildren($parent);
		foreach ($children as $links) {
			foreach ($links as $child) {
				if (isset($child['fieldLinks'])) continue;
				if (isset($child['callback'])) continue;

				$obj = utopia::GetInstance($child['moduleName']);
				if ($obj->isDisabled) continue;

				$opts = $obj->GetOptions();
				if (flag_is_set($opts,NO_NAV)) continue;
				if (!$admin && ($obj instanceof iAdminModule)) continue;
				if (!($obj instanceof iAdminModule) && $admin) continue;
				if (($obj instanceof iAdminModule) && !$isadmin) continue;

				$order = $obj->GetSortOrder();
				$url = $obj->GetURL();
				$title = $obj->GetTitle();
				if (!$url || !$title) continue;
				if ($child['moduleName'] == $current) $currentAdded = true;
				$arr[] = array($title,$url,$order,$child['moduleName']);
			}
		}

		if (!$currentAdded && $current != 'uCMS_View') {
			$obj = utopia::GetInstance($current);
			$arr[] = array($obj->GetTitle(),$_SERVER['REQUEST_URI'],-100,$current);
		}

		$arr[] = array('','',-9000);

		array_sort_subkey($arr,2);
		$out = array();
		foreach ($arr as $link) {
			if (empty($link[1]) && empty($out)) continue;
			$l = !empty($link[1]) ? '<a href="'.$link[1].'">'.$link[0].'</a>' : '&nbsp;';
			$out[] = '<li>'.$l.'</li>';
		}
		if ($arr) utopia::SetVar('modlinks','<ul id="modlinks">'.implode('',$out).'</ul>');
	}

	public function RunModule() {
		utopia::PageNotFound();
	}
}
?>
