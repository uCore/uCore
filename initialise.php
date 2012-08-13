<?php
timer_start('full process');

timer_start('Load Files');
LoadFiles();
timer_end('Load Files');

if ($_POST && get_magic_quotes_gpc()) $_POST = utopia::stripslashes_deep($_POST);
if ($_GET && get_magic_quotes_gpc()) $_GET = utopia::stripslashes_deep($_GET);
if ($_REQUEST && get_magic_quotes_gpc()) $_REQUEST = utopia::stripslashes_deep($_REQUEST);

ob_start('utopia::output_buffer',2);
register_shutdown_function('utopia::Finish');

uConfig::DefineConfig();
uConfig::ValidateConfig();

ini_set('default_charset',CHARSET_ENCODING);
header('Content-type: text/html; charset='.CHARSET_ENCODING);
header('Vary: if-none-match, accept-encoding');

uEvents::TriggerEvent('ConfigDefined');

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

uEvents::TriggerEvent('BeforeInit');

timer_start('Module Initialise');
$allmodules = utopia::GetModulesOf('uTableDef') + utopia::GetModulesOf('uBasicModule');
foreach ($allmodules as $row) { // must run second due to requiring GLOB_MOD to be setup fully
	timer_start('Init: '.$row['module_name']);
	$obj = utopia::GetInstance($row['module_name']);
	if (method_exists($obj,'Initialise'))
		$obj->Initialise(); // setup Parents
	timer_end('Init: '.$row['module_name']);
}
timer_end('Module Initialise');

uEvents::TriggerEvent('AfterInit');
uEvents::TriggerEvent('InitComplete');

// process ajax function
if (array_key_exists('__ajax',$_REQUEST)) {
	$ajaxIdent	= $_REQUEST['__ajax'];
	if (!array_key_exists('ajax',$GLOBALS) || !array_key_exists($ajaxIdent,$GLOBALS['ajax'])) die("Cannot perform ajax request, '$ajaxIdent' has not been registered.");

	$callback	= $GLOBALS['ajax'][$ajaxIdent]['callback'];
	$class		= $GLOBALS['ajax'][$ajaxIdent]['class'];

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

if (!isset($_GET['inline'])) utopia::UseTemplate();
else echo '<script>InitJavascript.run();</script>';
