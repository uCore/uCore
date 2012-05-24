<?php

uEvents::AddCallback('ProcessDomDocument','uFavicon::LinkFavicon');
class uFavicon {
	public static function LinkFavicon($obj,$event,$templateDoc) {
		// first check template, then check root.
		$iconPaths = array();
		$iconPaths[] = utopia::GetTemplateDir(false).'favicon.ico';
		$iconPaths[] = PATH_ABS_ROOT.'favicon.ico';
		$iconPaths[] = PATH_ABS_CORE.'favicon.ico';
		
		$head = $templateDoc->getElementsByTagName('head')->item(0);
		foreach ($iconPaths as $iconPath) {
			if (file_exists($iconPath)) {
				$node = $templateDoc->createElement('link');
				$node->setAttribute('rel','shortcut icon');
				$node->setAttribute('href',utopia::GetRelativePath($iconPath));
				$head->appendChild($node);
				return;
			}
		}
	}
}
