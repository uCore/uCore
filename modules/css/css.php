<?php

uCSS::IncludeFile(PATH_REL_CORE.'default.css');
class uCSS extends uBasicModule {
	public function GetOptions() { return PERSISTENT; }
	private static $includeFiles = array();
	public static function IncludeFile($path) {
		// if running ALERT: CANNOT BE CALLED AT RUN TIME
		if (!file_exists($path)) $path = utopia::GetAbsolutePath($path);
		if (!file_exists($path)) return;
		self::$includeFiles[] = $path;
	}
	public function GetUUID() { return 'styles.css'; }

	public function SetupParents() {
		module_Offline::IgnoreClass(__CLASS__);
		$this->SetRewrite(true);
		utopia::AddCSSFile($this->GetURL(),true);

		modOpts::AddOption('jQueryUI-Theme','jQuery UI Theme',null,'ui-lightness');
		$jquitheme = modOpts::GetOption('jQueryUI-Theme');
		utopia::AddCSSFile('//ajax.googleapis.com/ajax/libs/jqueryui/1/themes/'.$jquitheme.'/jquery-ui.css',true);
		uCSS::IncludeFile(PATH_REL_CORE.'modules/javascript/js/jquery.auto-complete.css');
	}

	public function RunModule() {
		utopia::CancelTemplate();

		clearstatcache();
		$uStr = '';
		self::$includeFiles = array_unique(self::$includeFiles);
		foreach (self::$includeFiles as $filename) {
			//does it exist?
			if (!file_exists($filename)) continue;
			$uStr .= $filename.filemtime($filename).'-'.filesize($filename);
		}

		$etag = sha1($uStr.'-'.count(self::$includeFiles).'-'.PATH_REL_CORE);
		utopia::Cache_Check($etag,'text/css',$this->GetUUID());

		// minify caching
		$minifyCache = '';
		if (file_exists(__FILE__.'.cache') && file_exists(__FILE__.'.cache.sha1')) $minifyCache = file_get_contents(__FILE__.'.cache.sha1');
		if ($etag !== $minifyCache) {
			$out = self::BuildCSS(true);
			file_put_contents(__FILE__.'.cache',$out); chmod(__FILE__.'.cache', 0664);
			file_put_contents(__FILE__.'.cache.sha1',$etag); chmod(__FILE__.'.cache.sha1', 0664);
		} else {
			$out = file_get_contents(__FILE__.'.cache');
		}

		utopia::Cache_Output($out,$etag,'text/css',$this->GetUUID());
	}

	static function BuildCSS($minify=true) {
		$body = '';

		foreach (self::$includeFiles as $filename) {
			if (!file_exists($filename)) continue;
			$contents = file_get_contents($filename);

			// convert relative url paths into absolute ones.
			$subDir = utopia::GetRelativePath(dirname($filename));
			$contents = preg_replace('/url\("?\'?([^"\']+)"?\'?\)/Ui','url(' . $subDir . '/$1)',$contents);

			$body .= $contents."\n\n";
		}
    
		if ($minify) $body = cssMin::minify($body);

		return $body;
	}
}
