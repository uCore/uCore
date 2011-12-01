<?php
class treeSort extends uBasicModule {
	public function SetupParents() {
		uJavascript::IncludeFile(utopia::GetRelativePath(dirname(__FILE__).'/jquery.treeSort.js'));
		uCSS::IncludeFile(utopia::GetRelativePath(dirname(__FILE__).'/jquery.treeSort.css'));
	}
	public function RunModule() {}
}
