<?php
timer_start('full process');

timer_start('Load Files');
LoadFiles();
timer_end('Load Files');

/**
 * Strip slashes from http inputs if magic quotes is enabled
 */
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

$rows = 0;
$result = database::query('SHOW TABLE STATUS WHERE `name` = ?',array('__table_checksum'));
if (!($r = $result->fetch())) {
        database::query('CREATE TABLE __table_checksum (`name` varchar(200) PRIMARY KEY, `checksum` varchar(40)) ENGINE='.MYSQL_ENGINE);
} else {
        if ($r['Engine'] != MYSQL_ENGINE) database::query('ALTER TABLE __table_checksum ENGINE='.MYSQL_ENGINE);
}
uTableDef::checksumValid(null,null); // cache table checksums
uTableDef::TableExists(null); // cache table exists

uEvents::TriggerEvent('BeforeInit');

timer_start('Module Initialise');
$allmodules = utopia::GetModulesOf('uTableDef') + utopia::GetModulesOf('uBasicModule');
foreach ($allmodules as $row) { // must run second due to requiring GLOB_MOD to be setup fully
	timer_start('Init: '.$row['module_name']);
	$obj =& utopia::GetInstance($row['module_name']);
	if (method_exists($obj,'Initialise'))
		$obj->Initialise(); // setup Parents
	timer_end('Init: '.$row['module_name']);
}
timer_end('Module Initialise');

uEvents::TriggerEvent('InitComplete');
uEvents::TriggerEvent('AfterInit');

utopia::UseTemplate(TEMPLATE_DEFAULT);
