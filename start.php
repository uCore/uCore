<?php
//$_SESSION = array();
if (stripos($_SERVER['HTTP_HOST'],'images.') === FALSE) session_start();

include_once('error.php');

date_default_timezone_set('GMT');

define('OS_WIN',stristr(PHP_OS, 'WIN'));

function fix_path($path,$slash = '') {
	if (!$slash) $slash = DIRECTORY_SEPARATOR;
	$path = str_replace(array('\\','/'),$slash,$path);
    return str_replace($slash.$slash,$slash,$path);
}

define('PATH_ABS_ROOT',fix_path(realpath($_SERVER['DOCUMENT_ROOT']).DIRECTORY_SEPARATOR));
define('PATH_ABS_CORE',fix_path(dirname(__FILE__).DIRECTORY_SEPARATOR));
define('PATH_ABS_SELF',fix_path($_SERVER['PHP_SELF']));

$coreDiff = str_replace(PATH_ABS_ROOT,'',PATH_ABS_CORE);
define('PATH_REL_ROOT','/');
define('PATH_REL_CORE',fix_path(PATH_REL_ROOT.$coreDiff,'/'));
define('PATH_REL_SELF',fix_path(PATH_REL_ROOT.basename(PATH_ABS_SELF),'/'));

define('DEFAULT_FILE',PATH_REL_CORE.'index.php');

//if (!defined('PATH_ABS_CONFIG')) // allows overriding of the config file location
define('PATH_ABS_CONFIG',fix_path(PATH_ABS_CORE.'.config.php'));

define('PATH_ABS_MODULES',fix_path(PATH_ABS_CORE.'../uModules/'));
define('PATH_ABS_TEMPLATES',fix_path(PATH_ABS_CORE.'../uTemplates/'));


// glob and load all functs files
foreach (glob(PATH_ABS_CORE.'functs/*.php') as $fn) include($fn);

require_once(PATH_ABS_CORE.'setup.php');
require_once(PATH_ABS_CORE.'initialise.php'); // init
