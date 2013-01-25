<?php

utopia::AddTemplateParser('menu1','uMenu::GetMenu','.*');
utopia::AddTemplateParser('menu','uMenu::GetNestedMenu','.*');
utopia::AddTemplateParser('sitemap','uMenu::DrawNestedMenu','');
class uMenu {
	private static $items = array();
	public static function &AddItem($id,$text,$url,$group='',$attr=null,$pos=null) {
		if ($group === NULL) $group = '';
		$group = strtolower($group);
		if ($pos === NULL) $pos = isset(self::$items[$group]) ? count(self::$items[$group])+1 : 0;
		self::$items[$group][$id] = array(
			'id'	=>	$id,
			'text'	=>	$text,
			'url'	=>	$url,
			'group'	=>	$group,
			'attr'	=>	$attr,
			'pos'	=>	$pos,
		);
		return self::$items[$group][$id];
	}
	public static function &GetItem($id,$group='') {
		if (!isset(self::$items[$group][$id])) return;
		return self::$items[$group][$id];
	}
	public static function GetMenu($group='',$level = 1) {
		$group = strtolower($group);
		if (!isset(self::$items[$group])) return '';
		$level = $level -1;
		
		array_sort_subkey(self::$items[$group],'pos');
		
		$lastWasBlank = true;
		$items = array();
		foreach (self::$items[$group] as $item) {
			if (empty($item['url']) && $lastWasBlank) continue;
			$attrs = BuildAttrString($item['attr']);

			$ret = '<li '.$attrs.'>';
			if (!empty($item['url']))
				$ret .= '<a href="'.$item['url'].'" title="'.$item['text'].'">'.$item['text'].'</a>';
			else
				$ret .= '&nbsp;';
			if ($level !== 0) $ret .= self::GetMenu($item['id'],$level);
			$ret .= '</li>';
			$items[] = $ret;
			$lastWasBlank = empty($item['url']);
		}
		$ret = '<ul class="u-menu '.$group.'">'.implode('',$items).'</ul>';
		return $ret;
	}
	static function GetNestedMenu($group='') {
		return self::GetMenu($group,-1);
	}
	
	static function AddStyles() {
		uCSS::IncludeFile(dirname(__FILE__).'/menu.css');
		uJavascript::IncludeFile(dirname(__FILE__).'/menu.js');
	}
}
uEvents::AddCallback('AfterInit','uMenu::AddStyles');
