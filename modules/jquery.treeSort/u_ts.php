<?php
class treeSort {
	static $init = false;
	public static function Init() {
		if (self::$init) return;
		self::$init = true;

		FlexDB::AddJSFile(FlexDB::GetRelativePath(dirname(__FILE__).'/jquery.treeSort.js'));
		FlexDB::AddCSSFile(FlexDB::GetRelativePath(dirname(__FILE__).'/jquery.treeSort.css'));
	}
}
?>