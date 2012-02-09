<?php

uEvents::AddCallback('BeforeOutputTemplate','uFavicon::LinkFavicon');
class uFavicon {
	public static function LinkFavicon() {
		// first check template, then check root.
		$iconPaths = array();
		$iconPaths[] = utopia::GetTemplateDir(false).'favicon.ico';
		$iconPaths[] = PATH_ABS_ROOT.'favicon.ico';
		$iconPaths[] = PATH_ABS_CORE.'favicon.ico';
		foreach ($iconPaths as $iconPath) {
			if (file_exists($iconPath)) {
				utopia::PrependVar('<head>','<link rel="shortcut icon" href="'.utopia::GetRelativePath($iconPath).'"/>');
				return;
			}
		}
	}
}
