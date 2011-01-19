<?php

class internalmodule_Reconfigure extends uBasicModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return 'Reconfigure Database'; }
	public function GetOptions() { return IS_ADMIN | ALWAYS_ACTIVE; }

	public function SetupParents() {
		if (!internalmodule_AdminLogin::IsLoggedIn(ADMIN_USER)) return;
		$this->AddParent('internalmodule_Admin');
	}

	public function GetSortOrder() { return -10; }

	public function ParentLoad($parent) {
		//utopia::LinkList_Add('child_buttons',$this->GetTitle(),$this->GetURL(),$this->GetSortOrder(),NULL,array('class'=>'fdb-btn'));
	}

	public function RunModule() {
		//utopia::CancelTemplate();
		uConfig::ShowConfig();
	}
}


class internalmodule_Admin extends uBasicModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return 'Admin Home'; }
	public function GetOptions() { return IS_ADMIN | ALWAYS_ACTIVE; }

  public function GetSortOrder() { return -100; }
  public function GetURL($filters = NULL, $encodeAmp = false) {
    return PATH_REL_CORE.'index.php';
  }
	public function SetupParents() {
		//$this->AddParent('*');
		$this->AddParent('internalmodule_Admin');
		$this->RegisterAjax('toggleT',array($this,'toggleT'));
		$this->RegisterAjax('toggleQ',array($this,'toggleQ'));
//		$this->RegisterAjax('optimizeTables',array($this,'optimizeTables'),false);
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
		$res = sql_query("SHOW TABLE STATUS FROM `" . $db_name . "`") or die('Query : ' . mysql_error());
		$to_optimize = array();
		while ( $rec = mysql_fetch_array($res) ) {
			if ( $rec['Data_free'] > 0 ) {
				$to_optimize [] = $rec['Name'];
				echo $rec['Name'] . ' needs optimization' . "\n";
			}
		}
		if ( count ( $to_optimize ) > 0 ) foreach ( $to_optimize as $tbl )
			sql_query("OPTIMIZE TABLE `" . $tbl ."`");
		else
			echo "No tables require optimization.\n";

		$time = microtime();
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];
		$finish = $time;
		$total_time = round(($finish - $start), 6);
		echo "\nParsed in $total_time secs\n</pre>";
	}

	public function toggleT() {
		if (!array_key_exists('admin_showT',$_SESSION))
			$_SESSION['admin_showT'] = true;
		else
			$_SESSION['admin_showT'] = !$_SESSION['admin_showT'];
		die('window.location.reload();');
	}

	public function toggleQ() {
		if (!array_key_exists('admin_showQ',$_SESSION))
			$_SESSION['admin_showQ'] = true;
		else
			$_SESSION['admin_showQ'] = !$_SESSION['admin_showQ'];
		die('window.location.reload();');
	}

	//public function ParentLoadPoint() { return 0; }
	public function ParentLoad($parent) {
	//	if ($parent == GetCurrentModule()) {
	//		$m = utopia::GetModules(true);
	//		foreach ($m as $module) CallModuleFunc($module['module_name'],'CreateParentNavButtons');
	//	}
//		if (!flag_is_set(CallModuleFunc($parent,'GetOptions'),IS_ADMIN)) return;
//		if ($parent == GetCurrentModule()) {
			//utopia::LinkList_Add('child_buttons','Admin Home',$this->GetURL(),-50,NULL,array('class'=>'fdb-btn'));
//			utopia::PrependVar('content','{list.child_buttons}');//utopia::LinkList_Get('child_buttons',null,null,array('class'=>'fdb-btn')));
//		}

//		if (is_subclass_of($parent,'uListDataModule')) {
//			utopia::LinkList_Add('list_functions:'.$parent,'Home',$this->GetURL(),-5,NULL,array('class'=>'fdb-btn','style'=>'background-color:#ff2222'));
//		}
	}

	public function RunModule() {
		//echo 'admin';
//		if (internalmodule_AdminLogin::IsLoggedIn(ADMIN_USER,false))
		utopia::AppendVar('content:before',"<h1>Welcome to Admin Home</h1>");

		if (!internalmodule_AdminLogin::IsLoggedIn(ADMIN_USER)) return;
		GetFiles(true);
		uJavascript::BuildJavascript();

		// if uModules doesnt exist, create it
		if (!file_exists(PATH_ABS_MODULES)) mkdir(PATH_ABS_MODULES);

		// if uTemplates doesnt exist, create it and copy CORE/styles/default to it
		if (!file_exists(PATH_ABS_TEMPLATES)) {
			mkdir(PATH_ABS_TEMPLATES);
			smartCopy(PATH_ABS_CORE.'styles/default',PATH_ABS_TEMPLATES);
		}

		$rc = PATH_REL_CORE;
		$ucStart = '## uCore ##';
		$ucEnd	 = '##-uCore-##';
		$content = <<<FIN
<FilesMatch "\.(ico|pdf|flv|jpg|jpeg|png|gif|js|css|swf)$">
SetOutputFilter DEFLATE
Header set Cache-Control "max-age=290304000, public"
Header set Expires "Thu, 15 Jan 2015 20:00:00 GMT"
</FilesMatch>

php_value short_open_tag 0
php_value display_errors 1


<IfModule mod_rewrite.c>
	# Tell PHP that the mod_rewrite module is ENABLED.
	SetEnv HTTP_MOD_REWRITE On

	RewriteEngine on
	RewriteRule ^(.*/)?\.svn/ - [F,L]
	ErrorDocument 403 "Access Forbidden"

	RewriteRule u/([^/?$]+)	{$rc}index.php?uuid=$1&%2 [NE,L,QSA]

	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_FILENAME} !-d [OR]
	RewriteCond %{REQUEST_URI} ^/$
	RewriteRule ^(.*)$ {$rc}index.php?uuid=cms [NE,L,QSA]
	#RewriteRule ^(.*\.(js|css))$ {$rc}index.php?__ajax=getCompressed&file=$1 [L]
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
			echo 'Updated .htaccess';
		}
		echo "<h3>Variables</h3><pre>";
		echo 'PATH_ABS_ROOT: '.PATH_ABS_ROOT.'<br>';
		echo 'PATH_REL_ROOT: '.PATH_REL_ROOT.'<br>';
		echo 'PATH_ABS_CORE: '.PATH_ABS_CORE.'<br>';
		echo 'PATH_REL_CORE: '.PATH_REL_CORE.'<br>';
		echo 'PATH_ABS_CONFIG: '.PATH_ABS_CONFIG.'<br>';
		echo '</pre>';

		$installed = InstallAllModules();
		echo '<h3 style="cursor:pointer" onclick="$(\'#modulesList\').toggle();">Installed Modules</h3><div id="modulesList" style="display:none"><pre>'.join("\n",$installed).'</pre></div>';
		$this->optimizeTables();
	}
}
?>
