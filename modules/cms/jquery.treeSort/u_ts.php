<?php

function initialiseTreesort() {
	uJavascript::IncludeFile(utopia::GetRelativePath(dirname(__FILE__).'/jquery.treeSort.js'));
	uCSS::IncludeFile(utopia::GetRelativePath(dirname(__FILE__).'/jquery.treeSort.css'));
}
uEvents::AddCallback('AfterInit','initialiseTreesort');
