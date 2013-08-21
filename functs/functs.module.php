<?php

/**
 * LoadFiles: includes all PHP files within the core and the uModules path
 * @see LoadModulesDir
 */
function LoadFiles() {
	$files = array();
	$files = array_merge($files,LoadModulesDir(PATH_ABS_CORE.'classes/')); // load base classes
	$files = array_merge($files,LoadModulesDir(PATH_ABS_CORE.'modules/')); // load internal modules
	$files = array_merge($files,LoadModulesDir(PATH_ABS_MODULES)); // load custom modules
	if (!$files) return;
	foreach ($files as $file) include($file);
}

/**
 * LoadModulesDir: finds php files to include, meeting specified critera.
 * If current folder contains a file named '.u_noscan'. The current folder is skipped.
 * If current folder contains a file named '.u_noscandeep'. No recursion is performed beneath the current folder.
 * Skips subversion and git hidden folders.
 *
 * @param string $indir path of folder to search
 * @param bool $recursive recurse into subdirectories
 * @return array of absolute paths to php files within $indir folder
 */
function LoadModulesDir($indir, $recursive = TRUE) {
	if (!is_dir($indir)) return array();
	if (glob($indir.'.u_noscan')) return array();
	$files = glob($indir.'*.php');
	if (!is_array($files)) $files = array();
	if (!$recursive || glob($indir.'.u_noscandeep')) return $files;

	$dir = glob($indir.'*', GLOB_MARK);
	foreach ($dir as $d) {
		if ($d == '.' || $d == '..' || $d == '.git' || $d == '.svn') continue;
		$files = array_merge($files,LoadModulesDir($d));
	}
	return $files;
}

function retTrue() { return true; }
function &ref_call_user_func_array($callable, $args)
{
	if(is_scalar($callable))
	{
		// $callable is the name of a function
		$call = $callable;
	}
	else
	{
		if(is_object($callable[0]))
		{
			// $callable is an object and a method name
			$call = "\$callable[0]->{$callable[1]}";
		}
		else
		{
			// $callable is a class name and a static method
			$call = "{$callable[0]}::{$callable[1]}";
		}
	}

	// Note because the keys in $args might be strings
	// we do this in a slightly round about way.
	$argumentString = array();
	$argumentKeys = array_keys($args);
	foreach($argumentKeys as $argK)
	{
		$argumentString[] = "\$args[$argumentKeys[$argK]]";
	}
	$argumentString = implode($argumentString, ', ');
	// Note also that eval doesn't return references, so we
	// work around it in this way...
	set_error_handler('retTrue');
	eval("\$result =& {$call}({$argumentString});");
	restore_error_handler();

	return $result;
}
