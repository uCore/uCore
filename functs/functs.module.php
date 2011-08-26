<?php

function GetFiles($refresh = false) {
  $files = array();
    $files = array_merge($files,LoadModulesDir(PATH_ABS_CORE.'classes/')); // load base classes
    $files = array_merge($files,LoadModulesDir(PATH_ABS_CORE.'modules/')); // load internal modules
    $files = array_merge($files,LoadModulesDir(PATH_ABS_MODULES)); // load custom modules

  return $files;
}
function LoadFiles() {
  $files = GetFiles();
  if (!$files) return;
  foreach ($files as $file) include_once($file);
}

function LoadModulesDir($indir, $recursive = TRUE) {
	if (!is_dir($indir)) return array();
	if (glob($indir.'.u_noscan')) return array();
	$files = glob($indir.'*.php');
	if (!is_array($files)) $files = array();
	if (!$recursive || glob($indir.'.u_noscandeep')) return $files;

	$dir = glob($indir.'*', GLOB_MARK);
	foreach ($dir as $d) {
		if ($d == '.' || $d == '..') continue;
		$files = array_merge($files,LoadModulesDir($d));
	}
	return $files;
}

function &CallModuleFunc($classname,$funcname) {
	trigger_error("CallModuleFunc is deprecated.", E_USER_DEPRECATED);
	static $null = NULL;

	if (!$classname) { ErrorLog("Executing function ($funcname) in null class<br/>".print_r(useful_backtrace(),true)); return $null; }
	//ErrorLog("Calling {$classname}->{$funcname}");

	// get args by reference.
	$stack = debug_backtrace();
	$args = array();
	if (isset($stack[0]["args"]))
		for($i=2; $i < count($stack[0]["args"]); $i++)
			$args[$i-2] = & $stack[0]["args"][$i];

	if (!method_exists($classname,$funcname)) { return $null; }

	$instance = utopia::GetInstance($classname);

	if ($instance == NULL) { ErrorLog("Error Calling {$classname}->{$funcname}"); return $null;}

	$call = array($instance,$funcname);
	$return = call_user_func_array($call,$args);
	return $return;
}

function &GetModuleVar($classname,$varname) {
	trigger_error("GetModuleVar is deprecated.", E_USER_DEPRECATED);
	$null = NULL;
	if (($instance = utopia::GetInstance($classname)) == NULL) return $null;
	if (!property_exists($instance,$varname)) return $null;

	return $instance->$varname;
}

function SetModuleVar($classname,$varname,$value) {
	trigger_error("SetModuleVar is deprecated.", E_USER_DEPRECATED);
	if (($instance = utopia::GetInstance($classname)) == NULL) return NULL;

	$instance->$varname = $value;
}

function parseSqlTableSetupChildren($parent,&$qryString) {
	$paraCount = 0;
	if (!is_array($parent)) return 0;
	if (!array_key_exists('children',$parent)) return 0;
	//	$parent['children'] = array_reverse($parent['children']);
	foreach ($parent['children'] as $child) {
		$qryString.="\n {$child['joinType']} {$child['table']} AS {$child['alias']} ON ";
		$joins = array();
		foreach ($child['joins'] as $fromField => $toField) {
			$ct = '=';
			$fromFull = ($fromField[0] == "'" || $fromField[0] == '"' || stristr($fromField,'.') !== FALSE) ? $fromField : $parent['alias'].'.'.$fromField;//$child['alias'].'.'.$toField;
			if (is_array($toField)) { // can process compare type also
				$ct = $toField[0];
				$toField = $toField[1];
				$toFull = $toField;
			} else
			$toFull = ($toField[0] == "'" || $toField[0] == '"' || stristr($toField,'.') !== FALSE)? $toField : $child['alias'].'.'.$toField;
			$joins[] = "$fromFull $ct $toFull";
		}
		$qryString.=join(' AND ',$joins);
		$paraCount++;
		$paraCount = $paraCount + parseSqlTableSetupChildren($child,$qryString);
	}
	return $paraCount;
}

function &recurseSqlSetupSearch(&$searchin,$searchfor) {
	// is the current table?
	if ($searchin['alias'] == $searchfor) { return $searchin; }

	// if not, does it have children?
	if (!empty($searchin['children'])) {
		for ($i = 0, $maxCount = count($searchin['children']); $i < $maxCount; $i++) {
			// check those children
			if ($tbl =& recurseSqlSetupSearch($searchin['children'][$i],$searchfor)) return $tbl;
		}
	}
	$false = FALSE;
	return $false;
}

function GetCurrentModule() {
	if (utopia::VarExists('current_module')) return utopia::GetVar('current_module');
	if (!isset($_GET['uuid'])) return 'uCMS_View';

	$m = utopia::UUIDExists($_GET['uuid']);
	return $m['module_name'];
}

function RunModule($module = NULL) {
	if ($module == NULL) $module = GetCurrentModule();

	if (!utopia::ModuleExists($module)) {
		utopia::PageNotFound();
	}
	utopia::SetVar('current_module',$module);
	$obj = utopia::GetInstance($module);
	utopia::SetVar('title',$obj->GetTitle());
	// run module
	if (!is_empty($module)) $obj->_RunModule();

	utopia::Finish();
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
?>
