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
