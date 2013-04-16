<?php
global $ucore_start_time;
$ucore_start_time = microtime(true);

function shutdownErrorHandler() { error_reporting(0); }
register_shutdown_function('shutdownErrorHandler');

if (PHP_SAPI == "cli") {
	$_SERVER['HTTP_HOST'] = 'cli';
	if (isset($argv[1])) $_SERVER['REQUEST_URI'] = $argv[1];
	else $_SERVER['REQUEST_URI'] = $argv[0];
	$_SERVER['SCRIPT_NAME'] = '/'.basename(dirname(__FILE__)).'/index.php';
	$_SERVER['REMOTE_ADDR'] = 'cli';
	putenv('HTTP_MOD_REWRITE=On');

	$q = parse_url($_SERVER['REQUEST_URI'],PHP_URL_QUERY);
	parse_str($q,$_GET);
}

$_SERVER['REQUEST_URI'] = urldecode($_SERVER['REQUEST_URI']);

//--  Charset
define('CHARSET_ENCODING'        , 'utf-8');
define('SQL_CHARSET_ENCODING'    , 'utf8');
define('SQL_COLLATION'           , 'utf8_general_ci');

if (!ini_get('output_buffering')) ob_start();
$enc = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
define ('GZIP_ENABLED',substr_count($enc, 'gzip') || substr_count($enc, 'deflate'));
if (GZIP_ENABLED) ob_start("ob_gzhandler"); else ob_start();

date_default_timezone_set('GMT');

function fix_path($path,$slash = '') {
	if (!$slash) $slash = DIRECTORY_SEPARATOR;
	$path = str_replace(array('\\','/'),$slash,$path);
	return str_replace($slash.$slash,$slash,$path);
}

define('PATH_ABS_CORE',fix_path(dirname(__FILE__).DIRECTORY_SEPARATOR));
define('PATH_ABS_ROOT',fix_path(realpath(PATH_ABS_CORE.'..').DIRECTORY_SEPARATOR));
define('PATH_ABS_SELF',fix_path(realpath($_SERVER['PHP_SELF'])));

$coreDiff = fix_path(preg_replace('/^'.preg_quote(PATH_ABS_ROOT,'/').'/','',PATH_ABS_CORE),'/');
$called = get_included_files(); $called = $called[0];
$diff = preg_replace('/^'.preg_quote(PATH_ABS_ROOT,'/').'/','',$called);
$relroot = preg_replace('/'.preg_quote($diff,'/').'$/','',$_SERVER['SCRIPT_NAME']);

define('PATH_REL_ROOT',$relroot);
define('PATH_REL_CORE',fix_path(PATH_REL_ROOT.$coreDiff,'/'));
define('PATH_REL_SELF',fix_path(PATH_REL_ROOT.basename(PATH_ABS_SELF),'/'));

define('DEFAULT_FILE',PATH_REL_CORE.'index.php');

define('PATH_ABS_CONFIG',fix_path(PATH_ABS_ROOT.'uConfig.php'));

define('PATH_ABS_MODULES',fix_path(PATH_ABS_ROOT.'uModules').'/');
define('PATH_ABS_TEMPLATES',fix_path(PATH_ABS_ROOT.'uTemplates').'/');
define('PATH_ABS_THEMES',fix_path(PATH_ABS_ROOT.'uThemes').'/');

define('PATH_FULL_ROOT',((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')?'https':'http').'://'.$_SERVER['HTTP_HOST'].PATH_REL_ROOT);
define('PATH_FULL_CORE',((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')?'https':'http').'://'.$_SERVER['HTTP_HOST'].PATH_REL_CORE);

ini_set('session.cookie_path',PATH_REL_ROOT);
session_cache_limiter(false);
session_name('ucore');
session_start();
$timeout = 3600; if (isset($_SESSION['SESSION_LIFETIME'])) $timeout = $_SESSION['SESSION_LIFETIME'];
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
	session_unset();
	session_destroy();
}
if (!isset($_SESSION['CREATED'])) $_SESSION['CREATED'] = time();
if (time() - $_SESSION['CREATED'] > 1800) { // session started more than 30 minates ago
	session_regenerate_id(true);
	$_SESSION['CREATED'] = time();
}
$_SESSION['LAST_ACTIVITY'] = time();

include('error.php');

// glob and load all functs files
foreach (glob(PATH_ABS_CORE.'interfaces/*.php') as $fn) include($fn);

// glob and load all functs files
foreach (glob(PATH_ABS_CORE.'functs/*.php') as $fn) include($fn);

require(PATH_ABS_CORE.'setup.php');
require(PATH_ABS_CORE.'initialise.php'); // init
