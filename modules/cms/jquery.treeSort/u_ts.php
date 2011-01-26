<?php
class treeSort {
	static $init = false;
	public static function Init() {
		if (self::$init) return;
		self::$init = true;

		utopia::AddJSFile(utopia::GetRelativePath(dirname(__FILE__).'/jquery.treeSort.js'));
		utopia::AddCSSFile(utopia::GetRelativePath(dirname(__FILE__).'/jquery.treeSort.css'));
	}
}
?>
