<?php

utopia::AppendVar('<body>','{admin_bar}');
utopia::AddTemplateParser('admin_bar','uAdminBar::DrawAdminBar','');
class uAdminBar {
	static $items = array();
	public static function AddItem($data,$body='',$order=10) {
		utopia::AddCSSFile(utopia::GetRelativePath(dirname(__FILE__).'/adminbar.css'));
		utopia::AddJSFile(utopia::GetRelativePath(dirname(__FILE__).'/adminbar.js'));
		self::$items[] = array('data'=>$data,'body'=>$body,'order'=>$order);
	}
	public static function DrawAdminBar() {
		if (!self::$items) return '';

		$items = self::$items;
		array_sort_subkey($items,'order');
		$arr = array();
		$body = array();
		foreach($items as $r => $itm) {
			$r = 'menu_'.$r;
			$toggle = ($itm['data'] && $itm['body']) ? ' class="toggle"' : '';
			if ($itm['data']) $arr[] = '<li'.$toggle.' rel="'.$r.'">'.$itm['data'].'</li>';
			if ($itm['body']) $body[] = '<div class="admin-body-item '.$r.'">'.$itm['body'].'</div>';
		}

		return '<div class="admin-bar"><ul class="admin-menu">'.implode('',$arr).'</ul><div class="admin-body">'.implode('',$body).'</div></div>';
	}
}
