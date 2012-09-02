<?php
if (PHP_SAPI == "cli") {
	$_SERVER['HTTP_HOST'] = 'cli';
	$_SERVER['REQUEST_URI'] = $argv[1];
	$_SERVER['SCRIPT_NAME'] = '/'.basename(dirname(__FILE__)).'/index.php';
	$_SERVER['REMOTE_ADDR'] = 'cli';
	putenv('HTTP_MOD_REWRITE=On');
}

//--  Charset
define('CHARSET_ENCODING'        , 'utf-8');
define('SQL_CHARSET_ENCODING'    , 'utf8');
define('SQL_COLLATION'           , 'utf8_general_ci');

if (!ini_get('output_buffering')) ob_start();
$enc = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
define ('GZIP_ENABLED',substr_count($enc, 'gzip') || substr_count($enc, 'deflate'));
if (GZIP_ENABLED) ob_start("ob_gzhandler"); else ob_start();

function runtimeHeader($startTime) {
	$endTime = microtime(true);
	header('X-Runtime: '.($endTime-$startTime));
}
$startTime = microtime(true);
register_shutdown_function('runtimeHeader',$startTime);

date_default_timezone_set('GMT');

function fix_path($path,$slash = '') {
	if (!$slash) $slash = DIRECTORY_SEPARATOR;
	$path = str_replace(array('\\','/'),$slash,$path);
	return str_replace($slash.$slash,$slash,$path);
}

define('PATH_ABS_CORE',fix_path(dirname(__FILE__).DIRECTORY_SEPARATOR));
define('PATH_ABS_ROOT',fix_path(realpath(PATH_ABS_CORE.'..').DIRECTORY_SEPARATOR));
define('PATH_ABS_SELF',fix_path(realpath($_SERVER['PHP_SELF'])));

$coreDiff = fix_path(str_replace(PATH_ABS_ROOT,'',PATH_ABS_CORE),'/');
$relroot = substr($_SERVER['SCRIPT_NAME'],0,strpos($_SERVER['SCRIPT_NAME'],$coreDiff)); if (!$relroot) $relroot = '/';
define('PATH_REL_ROOT',$relroot);
define('PATH_REL_CORE',fix_path(PATH_REL_ROOT.$coreDiff,'/'));
define('PATH_REL_SELF',fix_path(PATH_REL_ROOT.basename(PATH_ABS_SELF),'/'));

define('DEFAULT_FILE',PATH_REL_CORE.'index.php');

define('PATH_ABS_CONFIG',fix_path(PATH_ABS_ROOT.'uConfig.php'));

define('PATH_ABS_MODULES',fix_path(PATH_ABS_ROOT.'uModules').'/');
define('PATH_ABS_TEMPLATES',fix_path(PATH_ABS_ROOT.'uTemplates').'/');

ini_set('session.cookie_path',PATH_REL_ROOT);
session_cache_limiter(false);
session_name('ucore');
session_start();

include('error.php');

// glob and load all functs files
foreach (glob(PATH_ABS_CORE.'interfaces/*.php') as $fn) include($fn);

// glob and load all functs files
foreach (glob(PATH_ABS_CORE.'functs/*.php') as $fn) include($fn);

require(PATH_ABS_CORE.'setup.php');
require(PATH_ABS_CORE.'initialise.php'); // init
