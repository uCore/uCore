<?php

class modLinks extends uBasicModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return 'Admin Home'; }
	public function GetOptions() { return IS_ADMIN | NO_NAV | ALWAYS_ACTIVE; }

	public function SetupParents() {
		$this->AddParentCallback('/',array($this,'drawLinks'));
	}

	//public function ParentLoadPoint() { return 0; }
	public function drawLinks($parent) {
		$arr = array();

		$parentObj = utopia::GetInstance($parent);
		$admin = flag_is_set($parentObj->GetOptions(),IS_ADMIN);
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
				if (!$admin && flag_is_set($opts,IS_ADMIN)) continue;
				if (!flag_is_set($opts,IS_ADMIN) && $admin) continue;
				if (flag_is_set($opts,IS_ADMIN) && !$isadmin) continue;

				$order = $obj->GetSortOrder();
				$url = $obj->GetURL();
				$title = $obj->GetTitle();
				if (!$url || !$title) continue;
				$arr[] = array($title,$url,$order,$child['moduleName']);
			}
		}

		array_sort_subkey($arr,2);
		$out = array();
		foreach ($arr as $link) $out[] = '<li><a href="'.$link[1].'">'.$link[0].'</a></li>';
		if ($arr) utopia::SetVar('modlinks','<ul id="modlinks">'.implode('',$out).'</ul>');
	}

	public function RunModule() {
		utopia::PageNotFound();
	}
}
?>
