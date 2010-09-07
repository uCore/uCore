<?php
class fancybox {
	public static function InitFancybox($mousewheel = false, $easing = false) {
		$rs = FlexDB::GetRelativePath(dirname(__FILE__).'/jquery.fancybox-1.3.0.css');
		$rj = FlexDB::GetRelativePath(dirname(__FILE__).'/jquery.fancybox-1.3.0.pack.js');

		FlexDB::AddCSSFile($rs);
		FlexDB::AddJSFile($rj);
		if ($mousewheel) FlexDB::AddJSFile(FlexDB::GetRelativePath(dirname(__FILE__).'/jquery.mousewheel-3.0.2.pack.js'));
		if ($easing) FlexDB::AddJSFile(lexDB::GetRelativePath(dirname(__FILE__).'/jquery.easing-1.3.pack.js'));
	}
}
?>