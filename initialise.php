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

ini_set('default_charset',CHARSET_ENCODING);
header('Content-type: text/html; charset='.CHARSET_ENCODING);
header('Vary: if-none-match, accept-encoding');

ob_start('utopia::output_buffer',2);
register_shutdown_function('utopia::Finish');

uConfig::DefineConfig();
uConfig::ValidateConfig();
uEvents::TriggerEvent('ConfigDefined');

timer_start('Static Initialise');
$allmodules = utopia::GetModulesOf('iUtopiaModule');
foreach ($allmodules as $row) { // must run second due to requiring GLOB_MOD to be setup fully
	timer_start('Init: '.$row['module_name']);
	$row['module_name']::Initialise();
	timer_end('Init: '.$row['module_name']);
}
timer_end('Static Initialise');

timer_start('Before Init');
uEvents::TriggerEvent('BeforeInit');
timer_end('Before Init');

timer_start('Table Initialise');
uTableDef::TableExists(null); // cache table exists
$allmodules = utopia::GetModulesOf('uTableDef');
foreach ($allmodules as $row) { // must run second due to requiring GLOB_MOD to be setup fully
        timer_start('Init: '.$row['module_name']);
        $obj = utopia::GetInstance($row['module_name']);
        $obj->AssertTable(); // setup Parents
        timer_end('Init: '.$row['module_name']);
}
timer_end('Table Initialise');

define('INIT_COMPLETE',TRUE);

timer_start('After Init');
uEvents::TriggerEvent('InitComplete');
uEvents::TriggerEvent('AfterInit');
timer_end('After Init');

if ($_SERVER['HTTP_HOST'] !== 'cli') utopia::UseTemplate(TEMPLATE_DEFAULT);
