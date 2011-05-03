<?php
class fancybox {
	public static function InitFancybox($mousewheel = false, $easing = false) {
		$rs = utopia::GetRelativePath(dirname(__FILE__).'/jquery.fancybox-1.3.0.css');
		$rj = utopia::GetRelativePath(dirname(__FILE__).'/jquery.fancybox-1.3.0.pack.js');

		utopia::AddCSSFile($rs);
		utopia::AddJSFile($rj);
		if ($mousewheel) utopia::AddJSFile(utopia::GetRelativePath(dirname(__FILE__).'/jquery.mousewheel-3.0.2.pack.js'));
		if ($easing) utopia::AddJSFile(utopia::GetRelativePath(dirname(__FILE__).'/jquery.easing-1.3.pack.js'));
	}
}
?>
