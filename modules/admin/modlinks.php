<?php

class modLinks {
	private static $done = array();
	public static function CreateLinkMenu($module = null) {
		$cm = utopia::GetCurrentModule();
		if ($module === null) $module = $cm;
		if (isset(self::$done[$module])) return;
		self::$done[$module] = true;
		
		$cmAdmin = is_subclass_of($cm,'iAdminModule');
		
		$modules = utopia::GetChildren($module);
		$highestpos = 0;
		foreach ($modules as $mid=>$children) {
			if ($cmAdmin && !is_subclass_of($mid,'iAdminModule')) continue;
			if (!$cmAdmin && is_subclass_of($mid,'iAdminModule')) continue;
			foreach ($children as $child) {
				
				if (isset($child['callback'])) continue;
				if (isset($child['fieldLinks']) && $mid !== $cm) continue;
				if (uEvents::TriggerEvent('CanAccessModule',$mid) === FALSE) continue;
				if ($module !== $cm && $child['parent'] === '/') continue;
				$parent = '_modlinks_';
				if (isset($child['parent']) && $child['parent'] !=='/') $parent .= $child['parent'];

				$obj = utopia::GetInstance($mid);
				$position = $obj->GetSortOrder();
				if (isset($child['fieldLinks']) && $mid === $cm) $position = 0;
				if ($position > $highestpos) $highestpos = $position;
				uMenu::AddItem($parent.$mid,$obj->GetTitle(),$obj->GetURL(),$parent,null,$position);

			}
			self::CreateLinkMenu($mid);
		}
		
		if ($module === $cm){
			// add separators
			$i = -10001;
			while ($i < $highestpos) {
				uMenu::AddItem('_sep_'.$i,'','','_modlinks_',null,$i);
				$i = $i + 1000;
			}
		}
	}
	public static function InitLinkMenu() {
		self::CreateLinkMenu();
	}
}

utopia::SetVar('modlinks','{menu._modlinks_}');
uEvents::AddCallback('BeforeRunModule','modLinks::InitLinkMenu');
