<?php

class modLinks extends uBasicModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return 'Admin Home'; }
	public function GetOptions() { return NO_NAV | ALWAYS_ACTIVE | PERSISTENT; }

	public function SetupParents() {
		uEvents::AddCallback('BeforeRunModule','modLinks::drawLinks',utopia::GetCurrentModule());
	}
	public static function GetLinks($module,$specificOnly=false) {
		$arr = array();

		//$admin = true;
		//if (class_exists($module)) {
		//	$parentObj = utopia::GetInstance($module);
		//	$admin = ($parentObj instanceof iAdminModule);
		//}
		//$isadmin = uUserLogin::IsLoggedIn();
		$children = utopia::GetChildren($module);

		foreach ($children as $links) {
			foreach ($links as $child) {
				if (isset($child['callback'])) continue;
				if ($specificOnly && $child['parent'] !== $module) continue;

				if (isset($child['fieldLinks'])) {
					$obj = utopia::GetInstance(utopia::GetCurrentModule());
					$fv = false;
					if ($obj instanceof uDataModule) foreach ($child['fieldLinks'] as $link) {
						$fltr = $obj->FindFilter($link['fromField']);
						if ($obj->GetFilterValue($fltr['uid'])) $fv = true;
					}
					if (!$fv) continue; // if fieldlinks and the linked field is not currently have a filtered.
				}

				$obj = utopia::GetInstance($child['moduleName']);
				if ($obj->isDisabled) continue;
				
				$opts = $obj->GetOptions();
				if (flag_is_set($opts,NO_NAV)) continue;
				//if (!$admin && ($obj instanceof iAdminModule)) continue;
				//if (!($obj instanceof iAdminModule) && $admin) continue;
				//if (($obj instanceof iAdminModule) && !$isadmin) continue;

				$order = $obj->GetSortOrder();
				$url = $obj->GetURL();
				$title = $obj->GetTitle();
				if (!$url || !$title) continue;

				$arr[] = array(
					'title'	=>$title,
					'url'	=>$url,
					'order'	=>$order,
					'module'=>$child['moduleName'],
					'parent'=>$child['parent'],
					'children'=>self::GetLinks($child['moduleName'],true),
				);
			}
		}
		array_sort_subkey($arr,'order');
		return $arr;
	}
	public static function findLink($needle,$haystack) {
		foreach ($haystack as $child) {
			if ($child['module'] == $needle) return true;
			if ($child['children'] && self::findLink($needle,$child['children'])) return true;
		}
		return false;
	}
	
	//public function ParentLoadPoint() { return 0; }
	public static function drawLinks($object) {
		$parent = get_class($object);
		$arr = self::GetLinks($parent);

		// find current module in list.
		$foundCurrent = self::findLink(utopia::GetCurrentModule(),$arr);
		
		// if current module isnt in list, add it
		if (!$foundCurrent) {
			$obj = utopia::GetInstance(utopia::GetCurrentModule());
			$order = $obj->GetSortOrder();
			$title = $obj->GetTitle();
			$arr[] = array(
				'title'	=>$title,
				'url'	=>$_SERVER['REQUEST_URI'],
				'order'	=>$order,
				'parent'=>'',
				'module'=>utopia::GetCurrentModule(),
				'children'=>self::GetLinks(utopia::GetCurrentModule(),true),
			);
		}

		// if a link belongs to another link, move it to its children array
		foreach ($arr as $k => $child) {
			foreach ($arr as $k2 => $c2) {
				if (self::findLink($child['module'],$c2['children'])) {
					unset($arr[$k]);
				}
			}
		}
/*		foreach ($arr as $k => $child) {
			foreach ($arr as $k2 => $c2) {
				if ($child['parent'] === $c2['module']) {
					if (array_search($child,$arr[$k2]['children'])===FALSE)
						$arr[$k2]['children'][] = $child;
					unset($arr[$k]);
				}
			}
		}*/

		array_sort_subkey($arr,'order'); // sort first, to find the highest order value
		$highest = end($arr); $highest = $highest['order'];

		$i = -10000;
		while ($i < $highest) {
			$arr[] = array('title'=>'','url'=>'','order'=>$i-0.5);
			$i = $i + 1000;
		}

		array_sort_subkey($arr,'order'); // sort again to insert spacings at each 1000 interval

		if ($arr) utopia::SetVar('modlinks','<ul id="modlinks">'.self::getlinkhtml($arr).'</ul>');
	}
	public static function getlinkhtml($links) {
		$out = array();
		$lastWasBlank = true;
		foreach ($links as $link) {
			if (empty($link['url']) && $lastWasBlank) continue;
			if (isset($link['module']) && uEvents::TriggerEvent('CanAccessModule',$link['module']) === FALSE) continue;
			$l = !empty($link['url']) ? '<a href="'.$link['url'].'">'.$link['title'].'</a>' : '&nbsp;';
			$out[] = '<li>';
			$out[] = $l;
			
			if (isset($link['children']) && $link['children']) {
				$out[] = '<ul>';
				$out[] = self::getlinkhtml($link['children']);
				$out[] = '</ul>';
			}
			
			$out[] = '</li>';
			$lastWasBlank = empty($link['url']);
		}
		if ($lastWasBlank) array_pop($out);
		return implode('',$out);
	}

	public function RunModule() {
		utopia::PageNotFound();
	}
}
