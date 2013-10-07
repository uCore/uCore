<?php

class uAdminBar implements iUtopiaModule {
	public static function Initialise() {
		uEvents::AddCallback('ProcessDomDocument','uAdminBar::ProcessDomDocument');
	}
	static $items = array();
	public static function AddItem($menu=FALSE,$body=FALSE,$order=null,$class='') {
		if ($order === null) $order = count(self::$items);
		uCSS::LinkFile(dirname(__FILE__).'/adminbar.css',999);
		uJavascript::LinkFile(dirname(__FILE__).'/adminbar.js');
		self::$items[] = array('menu'=>$menu,'body'=>$body,'order'=>$order,'class'=>$class);
	}
	public static function DrawAdminBar() {
		if (!self::$items) return '';

		$items = self::$items;
		array_sort_subkey($items,'order');
		$arr = array();
		$body = array();
		foreach($items as $r => $itm) {
			$r = 'menu_'.$r;
			$class = array($r);
			if ($itm['class']) $class[] = $itm['class'];
			if ($itm['menu'] && $itm['body']) $class[] = 'toggle';

			$classt = ' class="'.implode(' ',$class).'"';
			if ($itm['menu'] !== FALSE) $arr[] = '<li'.$classt.' rel="'.$r.'">'.$itm['menu'].'</li>';

			$class[] = 'admin-body-item';
			$classt = ' class="'.implode(' ',$class).'"';
			if ($itm['body'] !== FALSE) $body[] = '<div'.$classt.'>'.$itm['body'].'</div>';
		}

		return '<div class="u-admin admin-bar"><div class="admin-toggle"><span/></div><div class="admin-container"><ul class="admin-menu">'.implode('',$arr).'</ul><div class="admin-body">'.implode('',$body).'</div></div></div>';
	}
	static function ProcessDomDocument($obj,$event,$templateDoc) {
		if (is_subclass_of(utopia::GetCurrentModule(),'iAdminModule')) return;
		$html = self::DrawAdminBar();
		if (!$html) return;
		$body = $templateDoc->getElementsByTagName('body')->item(0);
		$node = $templateDoc->createDocumentFragment();
		$node->appendXML($html);
		$body->appendChild($node);
	}
}
