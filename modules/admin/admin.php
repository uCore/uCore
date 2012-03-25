<?php

class internalmodule_Reconfigure extends uBasicModule implements iAdminModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return 'Reconfigure'; }
	public function GetOptions() { return ALWAYS_ACTIVE; }

	public function SetupParents() {
		$this->AddParent('/');
	}

	public function GetSortOrder() { return -9998; }

	public function RunModule() {
		uConfig::ShowConfig();
	}
}

class uDashboard extends uBasicModule implements iAdminModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return 'Dashboard'; }
	public function GetOptions() { return ALWAYS_ACTIVE; }

	public function GetSortOrder() { return -10000; }
	public function GetURL($filters = NULL, $encodeAmp = false) {
		$qs = $filters ? '?'.http_build_query($filters) : '';
		return PATH_REL_CORE.'index.php'.$qs;
	}
	public function SetupParents() {
		$this->AddParent('/');
		$this->RegisterAjax('toggleT',array($this,'toggleT'));
		$this->UpdateHtaccess();
	}
	public function optimizeTables() {
		echo '<h3>Optimise Tables</h3>';
		echo '<pre>';
		set_time_limit( 100 );

		$time = microtime();
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];
		$start = $time;

		$db_name = SQL_DBNAME;
		echo "Database : $db_name \n";
		$res = sql_query("SHOW TABLES FROM `" . $db_name . "`") or die('Query : ' . mysql_error());
		while ( $rec = mysql_fetch_row($res) ) {
			sql_query('OPTIMIZE TABLE `'.$rec[0].'`');
		}

		$time = microtime();
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];
		$finish = $time;
		$total_time = round(($finish - $start), 6);
		echo "\nTables optimised in $total_time secs\n</pre>";
	}

	public function toggleT() {
		if (!array_key_exists('admin_showT',$_SESSION))
			$_SESSION['admin_showT'] = true;
		else
			$_SESSION['admin_showT'] = !$_SESSION['admin_showT'];
		die('window.location.reload();');
	}

	public function RunModule() {
		echo '<h1>Welcome to Dashboard</h1>';

		uEvents::TriggerEvent('ShowDashboard');
	}
	public function UpdateHtaccess() {
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

	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^(.*)$	{$rc}index.php [NE,L,QSA]
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
