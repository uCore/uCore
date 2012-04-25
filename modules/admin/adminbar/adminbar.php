<?php

utopia::AppendVar('<body>','{admin_bar}');
utopia::AddTemplateParser('admin_bar','uAdminBar::DrawAdminBar','');
class uAdminBar {
	static $items = array();
	public static function AddItem($menu=FALSE,$body=FALSE,$order=null,$class='') {
		if ($order === null) $order = count(self::$items);
		utopia::AddCSSFile(utopia::GetRelativePath(dirname(__FILE__).'/adminbar.css'));
		utopia::AddJSFile(utopia::GetRelativePath(dirname(__FILE__).'/adminbar.js'));
		self::$items[] = array('menu'=>$menu,'body'=>$body,'order'=>$order,'class'=>$class);
	}
	public static function DrawAdminBar() {
		if (!self::$items) return '';

		if (utopia::GetInstance(utopia::GetCurrentModule()) instanceof iAdminModule)
			self::AddItem('<a target="_blank" href="'.PATH_REL_ROOT.'">View Site</a>','',-9);

		$items = self::$items;
		array_sort_subkey($items,'order');
		$arr = array();
		$body = array();
		foreach($items as $r => $itm) {
			$r = 'menu_'.$r;
			if ($itm['class']) $r .= ' '.$itm['class'];
			$toggle = ($itm['menu'] && $itm['body']) ? ' class="toggle '.$r.'"' : '';
			if ($itm['menu'] !== FALSE) $arr[] = '<li'.$toggle.' rel="'.$r.'">'.$itm['menu'].'</li>';
			if ($itm['body'] !== FALSE) $body[] = '<div class="admin-body-item '.$r.'">'.$itm['body'].'</div>';
		}

		return '<div class="admin-bar"><ul class="admin-menu">'.implode('',$arr).'</ul><div class="admin-body">'.implode('',$body).'</div></div>';
	}
}
