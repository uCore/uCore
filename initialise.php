<?php
timer_start('full process');

timer_start('Load Files');
LoadFiles();
timer_end('Load Files');

ob_start('utopia::output_buffer',2);
register_shutdown_function('utopia::Finish');

uConfig::DefineConfig();
uConfig::ValidateConfig();

ini_set('default_charset',CHARSET_ENCODING);
header('Content-type: text/html; charset='.CHARSET_ENCODING);
header('Vary: if-none-match, accept-encoding');

if (!array_key_exists('jsDefine',$GLOBALS)) $GLOBALS['jsDefine'] = array();

$result = sql_query('SHOW TABLE STATUS WHERE `name` = \'__table_checksum\'');
if (!mysql_num_rows($result))
        sql_query('CREATE TABLE __table_checksum (`name` varchar(200) PRIMARY KEY, `checksum` varchar(40)) ENGINE='.MYSQL_ENGINE);
else {
        $r = mysql_fetch_assoc($result);
        if ($r['Engine'] != MYSQL_ENGINE) sql_query('ALTER TABLE __table_checksum ENGINE='.MYSQL_ENGINE);
}
uTableDef::checksumValid(null,null); // cache table checksums
uTableDef::TableExists(null); // cache table exists

$allmodules = utopia::GetModules(true);
timer_start('Module Initialise');
foreach ($allmodules as $row) { // must run second due to requiring GLOB_MOD to be setup fully
	timer_start('Init: '.$row['module_name']);
	$obj = utopia::GetInstance($row['module_name']);
	if (method_exists($obj,'Initialise'))
		$obj->Initialise(); // setup Parents
	timer_end('Init: '.$row['module_name']);
}
timer_end('Module Initialise');

uEvents::TriggerEvent('InitComplete');

// process ajax function
if (array_key_exists('__ajax',$_REQUEST)) {
	//utopia::CancelTemplate();
	// TODO: ajax parentloading?  EG: login modules
	$cm = utopia::GetCurrentModule();
	if (uEvents::TriggerEvent('CanAccessModule',$cm) === FALSE) die();
	if ($cm && $cm !== 'uDashboard') {
		$obj = utopia::GetInstance($cm);
		$lc = $obj->LoadChildren(0); // now part of runmodule and loadparents, call here to check for
		if ($lc !== TRUE && $lc !== NULL) die();
	}


	$ajaxIdent	= $_REQUEST['__ajax'];
	if (!array_key_exists('ajax',$GLOBALS) || !array_key_exists($ajaxIdent,$GLOBALS['ajax'])) die("Cannot perform ajax request, '$ajaxIdent' has not been registered.");

	$requireAdmin = $GLOBALS['ajax'][$ajaxIdent]['req_admin'];
	$callback	= $GLOBALS['ajax'][$ajaxIdent]['callback'];
	$class		= $GLOBALS['ajax'][$ajaxIdent]['class'];

	if (is_bool($requireAdmin) && ($requireAdmin === TRUE && !internalmodule_AdminLogin::IsLoggedIn()))
		die('// Not Authenticated');
	elseif (function_exists($requireAdmin) && !call_user_func($requireAdmin))
		die('// Not Authenticated');

	// validate
	if (!is_callable($callback))
	die("Callback function for ajax method '$ajaxIdent' does not exist.");

	//RunModule();
	//ErrorLog(print_r($callback,true));
	call_user_func($callback);
	//echo utopia::GetVar('error_log');
	utopia::Finish(); // commented why ?
	die();
}

if (!array_key_exists('_noTemplate',$_GET)) utopia::UseTemplate();
