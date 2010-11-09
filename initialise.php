<?php
timer_start('full process');
//ob_start("ob_gzhandler");
//header('Content-Encoding: gzip');
//define('DEVELOPMENT_MODE',true || array_key_exists('_dev',$_GET));

//if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') && !array_key_exists('__ajax',$_GET)) ob_start("ob_gzhandler"); // else ob_start();

//ini_set('upload_max_filesize', '30M');
//ini_set('magic_quotes_gpc',0);
//set_magic_quotes_runtime(0);
//ini_set('short_open_tag',0);
ini_set('default_charset',CHARSET_ENCODING);
header('Content-type: text/html; charset='.CHARSET_ENCODING);

$_SESSION = array();
if (stripos($_SERVER['HTTP_HOST'],'images.') === FALSE) session_start();

if (!array_key_exists('jsDefine',$GLOBALS)) $GLOBALS['jsDefine'] = array();

//if (DEVELOPMENT_MODE) { // dev mode - include all files
//	error_reporting(E_ALL);// | E_STRICT);
//} else { // production mode - use compiled file
//	error_reporting(E_ALL ^ E_NOTICE);
//}
/*if (array_key_exists('_ra',$_GET)) {
 $ra = $_GET['_ra']; unset($_GET['_ra']);
 $args = explode('/',$ra);
 foreach ($args as $arg) {
 list($key,$val) = explode('=',$arg);
 $_GET[$key] = $val;
 }
 }*/

timer_start('Load Files');
LoadFiles();
timer_end('Load Files');
 /*
timer_start('Load Internal');
LoadModulesDir(PATH_ABS_CORE.'classes/');	// load base classes
LoadModulesDir(PATH_ABS_CORE.'modules/');	// load internal modules
//LoadModulesDir(PATH_ABS_CORE.'optional/');	// load optional modules
timer_end('Load Internal');

timer_start('Load Modules');
LoadModulesDir(PATH_ABS_MODULES); // load custom modules
timer_end('Load Modules');
*/
if(!ob_start("ob_gzhandler")) ob_start();

if (!array_key_exists('_noTemplate',$_GET))	FlexDB::UseTemplate();

$allmodules = FlexDB::GetModules(true,true);
if ($allmodules === NULL || count($allmodules) === 0) {// || (internalmodule_AdminLogin::IsLoggedIn() && array_key_exists('__rebuild',$_REQUEST))) {
	InstallAllModules();
	header('Location: '.preg_replace('/__rebuild(=[^&]*)?/','',$_SERVER['REQUEST_URI'])); exit();
}
/*
if (GetCurrentModule() !== NULL) {
	$uuid = CallModuleFunc(GetCurrentModule(),'GetUUID');
	if (is_array($uuid)) $uuid = $uuid[0];
	if (array_key_exists('uuid',$_REQUEST) && $_REQUEST['uuid'] !== $uuid) {
		$newUrl = str_replace($_REQUEST['uuid'],$uuid,$_SERVER['REQUEST_URI']);
		header('Location: '.$newUrl,true,301); die();
	}
}
*/

timer_start('Module Initialise');
//foreach ($allmodules as $row) {
//	$GLOBALS['modules'][$row['uuid']] = $row['module_name'];
//	$GLOBALS['modules'][$row['module_name']] = $row;
//}
foreach ($allmodules as $row) { // must run second due to requiring GLOB_MOD to be setup fully
  timer_start('Init: '.$row['module_name']);
	CallModuleFunc($row['module_name'],'Initialise'); // setup Parents
	timer_end('Init: '.$row['module_name']);
}

//foreach ($GLOBALS['modules'] as $modName => $info) {
//echo $modName.'<br>';
//if (is_array($info))
//CallModuleFunc($modName,'Initialise'); // setup Parents
//}
timer_end('Module Initialise');


timer_start('Setup Fields');
// setup fields on current module
if (GetCurrentModule())
	CallModuleFunc(GetCurrentModule(),'_SetupFields');
timer_end('Setup Fields');

// process ajax function
if (array_key_exists('__ajax',$_REQUEST)) {
	FlexDB::CancelTemplate();
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
	//echo FlexDB::GetVar('error_log');
	FlexDB::Finish(); // commented why ?
	die();
}

?>