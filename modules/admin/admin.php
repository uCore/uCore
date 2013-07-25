<?php

class internalmodule_Reconfigure extends uBasicModule implements iAdminModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return 'Configuration'; }
	public function GetOptions() { return ALWAYS_ACTIVE; }

	public function SetupParents() {
		$this->AddParent('/');
	}

	public function GetSortOrder() { return 10000-1; }

	public function RunModule() {
		echo '<h1>'.$this->GetTitle().'</h1>';
		echo '<div class="layoutListSection module-content">';
		uConfig::ShowConfig();
		echo '</div>';
	}
}

class uDashboard extends uBasicModule implements iAdminModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return 'Dashboard'; }
	public function GetOptions() { return ALWAYS_ACTIVE; }

	public function GetSortOrder() { return -10000; }
	public function GetURL($filters = NULL) {
		$qs = $filters ? '?'.http_build_query($filters) : '';
		return PATH_REL_CORE.'index.php'.$qs;
	}
	public function SetupParents() {
		if (uEvents::TriggerEvent('CanAccessModule',$this) !== FALSE)
			uAdminBar::AddItem('<a class="btn dashboard-link" href="'.PATH_REL_CORE.'"><span/>Dashboard</a>',FALSE,-100);

		$this->AddParent('/');
		utopia::RegisterAjax('toggle_debug',array($this,'toggleDebug'));
		$this->UpdateHtaccess();
	}

	public function toggleDebug() {
		utopia::DebugMode(!utopia::DebugMode());
		die('window.location.reload();');
	}

	public function RunModule() {
		uEvents::TriggerEvent('ShowDashboard');
	}
	public function UpdateHtaccess() {
		if ($_SERVER['HTTP_HOST'] == 'cli') return; // don't rewrite htaccess for CLI
		$rc = PATH_REL_CORE;
		$ucStart = '## uCore ##';
		$ucEnd	 = '##-uCore-##';
		$content = <<<FIN
#don't use file id in ETag
FileETag MTime Size

#deny access to config file
<Files uConfig.php>
	order allow,deny
	deny from all
</Files>

#enable default cache control and compression
<FilesMatch "\.(ico|pdf|flv|jpg|jpeg|png|gif|js|css|swf)$">
	<IfModule mod_deflate.c>
		SetOutputFilter DEFLATE
	</IfModule>
	<IfModule mod_headers.c>
		Header set Cache-Control "max-age=290304000, public"
		Header set Expires "Thu, 15 Jan 2015 20:00:00 GMT"
	</IfModule>
</FilesMatch>

#URL Rewriting
<IfModule mod_rewrite.c>
	# Tell PHP that the mod_rewrite module is ENABLED.
	SetEnv HTTP_MOD_REWRITE On

	RewriteEngine on
	RewriteRule ^(.*/)?(\.svn)|(\.git) - [F,L]
	ErrorDocument 403 "Access Forbidden"
	
	RewriteRule ^uFiles - [F,L]
	ErrorDocument 403 "Access Forbidden"
	
	# Skip if file exists
	RewriteCond %{REQUEST_FILENAME} -f
	RewriteRule .* - [L]
	
	RewriteRule .*	{$rc}index.php [NE,L,QSA]
</IfModule>
FIN;
		$search = PHP_EOL.PHP_EOL.PHP_EOL.$ucStart.PHP_EOL.$content.PHP_EOL.$ucEnd;
		$htaccess = '';
		if (file_exists(PATH_ABS_ROOT.'.htaccess')) $htaccess = file_get_contents(PATH_ABS_ROOT.'.htaccess');
		if (strpos($htaccess,$search) === FALSE) {
			// first remove existing (outdated)
			$s = strpos($htaccess,$ucStart);
			$e = strrpos($htaccess,$ucEnd); // PHP5
			//$e = strpos(strrev($htaccess),strrev($ucEnd)); // PHP4
			if ($s !== FALSE && $e !== FALSE) {
				$e += strlen($ucEnd); // PHP5
				//$e = strlen($htaccess) - $e; // PHP4
				$htaccess = substr_replace($htaccess,'',$s,$e);
			}

			$htaccess = trim($htaccess).$search;
			file_put_contents(PATH_ABS_ROOT.'.htaccess',$htaccess);
			return true;
		}
	}
}
