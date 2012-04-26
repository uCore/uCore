<?php

utopia::AddTemplateParser('menu1','uMenu::GetMenu','.*',true);
utopia::AddTemplateParser('menu','uMenu::GetNestedMenu','.*',true);
utopia::AddTemplateParser('sitemap','uMenu::DrawNestedMenu','',true);
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
	public static function GetMenu($group='',$level = 1) {
		$group = strtolower($group);
		if (!isset(self::$items[$group])) return;
		$level = $level -1;
		
		array_sort_subkey(self::$items[$group],'pos');
		
		echo '<ul class="u-menu">';
		foreach (self::$items[$group] as $item) {
			$attrs = BuildAttrString($item['attr']);

			echo '<li '.$attrs.'>';
			echo '<a href="'.$item['url'].'" title="'.$item['text'].'">'.$item['text'].'</a>';
			if ($level !== 0) self::GetMenu($item['id'],$level);
			echo '</li>';
		}
		echo '</ul>';
	}
	static function GetNestedMenu($group='') {
		self::GetMenu($group,-1);
	}
}
