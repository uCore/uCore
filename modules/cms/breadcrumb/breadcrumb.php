<?php

utopia::AddTemplateParser('breadcrumb','uBreadcrumb::GetTrail','.*');
class uBreadcrumb {
	private static $extras = array();
	static function AddCrumb($name,$url) {
		self::$extras[] = array($name,$url);
	}
	static function GetTrail($start = null) {
		$obj = utopia::GetInstance('uCMS_List');
		$arr = $obj->GetNestedArray();
		$out = array();

		//TODO: add (Search Results) to end of title if current page is filtered.
		$out[$_SERVER['REQUEST_URI']] ='{utopia.title}';// '<a href="'.$_SERVER['REQUEST_URI'].'">{utopia.title}</a>';

		foreach (self::$extras as $a) {
			$out[$a[1]]=$a[0];//'<a href="'.$a[1].'">'.$a[0].'</a>';
		}

		$obj = utopia::GetInstance('uCMS_View');
		$row = uCMS_View::findPage();
		do {
			if (!$row) break;
			$url = $obj->GetURL($row['cms_id']);
			$title = $row['nav_text'] ? $row['nav_text'] : $row['title'];
			$out[$url] = $title;
		} while ($row['parent'] && ($row = uCMS_List::findKey($arr,$row['parent'])));

		$build = array();
		foreach ($out as $k => $v) {
			$build[] = '<a href="'.$k.'">'.$v.'</a>';
		}
		$build = array_unique($build);
		if ($start) $build[] = $start;
		if (count($build) < 1) return '';

		return implode(' &gt; ',array_reverse($build));
	}
}
