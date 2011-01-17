<?php

utopia::AddTemplateParser('top_menu','uSitemap::DrawMenu','',true);
utopia::AddTemplateParser('sitemap','uSitemap::DrawSitemap','',true);
class uSitemap {
	static function DrawSitemap() {
		self::DrawMenu(-1);
	}
	static function DrawMenu($level = 1) {
		$arr = CallModuleFunc('uCMS_List','GetNestedArray');
		self::DrawChildren($arr,$level);
	}
	static function DrawChildren($children,$level = -1) {
		if (!$children) return;
		$level = $level -1;
		array_sort_subkey($children,'position');
		echo '<ul class="u-menu">';
		foreach ($children as $child) {
			if ($child['hide']) continue;
			$menu_title = $child['nav_text'] ? $child['nav_text'] : $child['title'];
			//$hide = $child['hide'] ? 'hiddenItem' : '';  //class="'.$hide.'" 
			$url = CallModuleFunc('uCMS_View','GetURL',$child['cms_id']);
			//$sel = (strpos($url,$_SERVER['REQUEST_URI']) !== FALSE) ? ' u-menu-active' : '';
			//$sel = ($url == $_SERVER['REQUEST_URI']) ? ' u-menu-active' : ''; //handled by javascript
			echo '<li id="'.$child['cms_id'].'" style="position:relative;cursor:pointer">';
			echo '<a class="cmsEdit" href="'.$url.'">'.$menu_title.'</a>';
			if ($level !== 0) self::DrawChildren($child['children'],$child['cms_id'],$level);
			echo '</li>';
		}
		echo '</ul>';
	}
}

class uSitemapXML extends uBasicModule {
	public function GetTitle() { return 'XML Sitemap'; }
	//public function GetOptions() { return IS_ADMIN | ALLOW_ADD | ALLOW_EDIT | ALLOW_DELETE | ALLOW_FILTER; }
	public function GetUUID() { return 'sitemap';}
	public function SetupParents() {
		$this->SetRewrite(true);
	}
	public function ParentLoad($parent) { }
	public function RunModule() {
		utopia::CancelTemplate();
		$arr = CallModuleFunc('uCMS_List','GetRows');

		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		foreach ($arr as $entry) {
			$url = 'http://'.utopia::GetDomainName().CallModuleFunc('uCMS_View','GetURL',$entry['cms_id']);
			echo <<<FIN

<url>
	<loc>{$url}</loc>
	<priority>0.5</priority>
	<changefreq>monthly</changefreq>
</url>

FIN;
		}
		echo '</urlset>';
	}
}

?>
