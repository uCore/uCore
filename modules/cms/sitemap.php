<?php

utopia::AddTemplateParser('menu1','uSitemap::GetMenu','.*',true);
utopia::AddTemplateParser('menu','uSitemap::GetNestedMenu','.*',true);
utopia::AddTemplateParser('sitemap','uSitemap::DrawNestedMenu','',true);
class uSitemap {
	private static $items = array();
	public static function &AddItem($id,$text,$url,$group='',$attr=null,$pos=null) {
		if ($group === NULL) $group = '';
		if ($pos === NULL) $pos = isset(self::$items[$group]) ? count(self::$items[$group])+1 : 0;
		self::$items[$group][$id] = array(
			'id'	=>	$id,
			'text'	=>	$text,
			'url'	=>	$url,
			'group'	=>	$group,
			'attr'	=>	$attr,
			'pos'	=>	$pos,
			'menu'	=>	true,
		);
		return self::$items[$group][$id];
	}
	public static function GetMenu($group='',$level = 1) {
		if (!isset(self::$items[$group])) return;
		$level = $level -1;
		
		array_sort_subkey(self::$items[$group],'pos');
		
		echo '<ul class="u-menu">';
		foreach (self::$items[$group] as $item) {
			if ($item['menu'] !== true) continue;
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

class uSitemapXML extends uBasicModule {
	public function GetTitle() { return 'XML Sitemap'; }
	//public function GetOptions() { return ALLOW_ADD | ALLOW_EDIT | ALLOW_DELETE | ALLOW_FILTER; }
	public function GetUUID() { return 'sitemap';}
	public function SetupParents() {
		$this->SetRewrite(true);
	}
	public function RunModule() {
		utopia::CancelTemplate();
		header('Content-Type: application/xml',true);

		$obj = utopia::GetInstance('uCMS_List');
		$viewObj = utopia::GetInstance('uCMS_View');
		$arr = $obj->GetRows();

		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		foreach ($arr as $entry) {
			$url = 'http://'.utopia::GetDomainName().$viewObj->GetURL($entry['cms_id']);
			echo <<<FIN

<url>
	<loc>{$url}</loc>
	<priority>0.5</priority>
	<changefreq>monthly</changefreq>
</url>

FIN;
		}
		echo '</urlset>';
		die();
	}
}

