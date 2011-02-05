<?php
timer_start('full process');

timer_start('Load Files');
LoadFiles();
timer_end('Load Files');

$configArr = (isset($_REQUEST['__config_submit'])) ? $_REQUEST : uConfig::ReadConfig();
$valid = uConfig::ValidateConfig($configArr);
if ($valid) {
        if ($valid === 2 || isset($_REQUEST['__config_submit'])) uConfig::SaveConfig($configArr);
        uConfig::DefineConfig($configArr);
} else {
        uConfig::ShowConfig($configArr);
        die();
}

InstallAllModules();

ini_set('default_charset',CHARSET_ENCODING);
header('Content-type: text/html; charset='.CHARSET_ENCODING);

if (!array_key_exists('jsDefine',$GLOBALS)) $GLOBALS['jsDefine'] = array();

if(!ob_start("ob_gzhandler")) ob_start();

if (!array_key_exists('_noTemplate',$_GET))	utopia::UseTemplate();

$allmodules = utopia::GetModules(true,true);
if ($allmodules === NULL || count($allmodules) === 0) {// || (internalmodule_AdminLogin::IsLoggedIn() && array_key_exists('__rebuild',$_REQUEST))) {
	InstallAllModules();
	header('Location: '.preg_replace('/__rebuild(=[^&]*)?/','',$_SERVER['REQUEST_URI'])); exit();
}

timer_start('Module Initialise');
foreach ($allmodules as $row) { // must run second due to requiring GLOB_MOD to be setup fully
	timer_start('Init: '.$row['module_name']);
	CallModuleFunc($row['module_name'],'Initialise'); // setup Parents
	timer_end('Init: '.$row['module_name']);
}
timer_end('Module Initialise');


timer_start('Setup Fields');
// setup fields on current module
if (GetCurrentModule())
	CallModuleFunc(GetCurrentModule(),'_SetupFields');
timer_end('Setup Fields');

// process ajax function
if (array_key_exists('__ajax',$_REQUEST)) {
	utopia::CancelTemplate();
	// TODO: ajax parentloading?  EG: login modules
	$lc = CallModuleFunc(GetCurrentModule(),'LoadChildren'); // now part of runmodule and loadparents, call here to check for
	if ($lc !== TRUE && $lc !== NULL) die();


	$ajaxIdent	= $_REQUEST['__ajax'];
	if (!array_key_exists('ajax',$GLOBALS) || !array_key_exists($ajaxIdent,$GLOBALS['ajax'])) die("Cannot perform ajax request, '$ajaxIdent' has not been registered.");

	$requireAdmin = $GLOBALS['ajax'][$ajaxIdent]['req_admin'];
	$callback	= $GLOBALS['ajax'][$ajaxIdent]['callback'];
	$class		= $GLOBALS['ajax'][$ajaxIdent]['class'];

	//CallModuleFunc($class,'_SetupFields');

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

?>
