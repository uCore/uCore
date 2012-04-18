<?php

class uSitemap extends uBasicModule {
	private static $items = array();
	public static function &AddItem($loc,$additional=array(),$group = '') {
		$arr = array(
			'loc'	=>	$loc,
		);
		if (!is_array($additional)) $additional = array($additional);
		$arr = array_merge($arr,$additional);
		self::$items[$group][] =& $arr;
		return $arr;
	}
	
	public function GetTitle() { return 'XML Sitemap'; }
	//public function GetOptions() { return ALLOW_ADD | ALLOW_EDIT | ALLOW_DELETE | ALLOW_FILTER; }
	public function GetUUID() { return 'sitemap';}
	public function SetupParents() {
		$this->SetRewrite(array('{group}'));
	}
	public function RunModule() {
		$grp = isset($_GET['group']) ? $_GET['group'] : '';
		if (!isset(self::$items[$grp])) utopia::PageNotFound();
		
		utopia::CancelTemplate();
		header('Content-Type: application/xml',true);
		
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		foreach (self::$items[$grp] as $entry) {
			echo '<url>';
			foreach ($entry as $k=>$v)
				echo "	<$k>$v</$k>";
			echo '</url>';
		}
		echo '</urlset>';
		die();
	}
}
