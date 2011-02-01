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
  foreach ($files as $file) if (file_exists($file)) include_once($file);
}

function hasNoScan($var) {
	return preg_match('/.u_noscan$/i',$var) > 0;
}

function LoadModulesDir($dir, $recursive = TRUE) {
        $Directory = new RecursiveDirectoryIterator($dir);
        $Iterator = new RecursiveIteratorIterator($Directory);
        $Regex = new RegexIterator($Iterator, '/(\.php|.u_noscan)$/i', RecursiveRegexIterator::GET_MATCH);
	$files = array_keys(iterator_to_array($Regex));
	$ns = array_filter($files,'hasNoScan');
	foreach ($ns as $noScanPath) {
		$c = substr($noScanPath,0,-9);
		foreach ($files as $k => $path) {
			if (strpos($path,$c) !== FALSE) unset($files[$k]);
		}
	}
	return $files;


	$files = array();
	$dir = rtrim($dir,'/'); 
//	if (preg_match('/\.svn$/i',$dir)) return $files;
	if (file_exists($dir.'/.u_noscan')) return $files;
	
	if (!is_dir($dir)) return $files;

	$dirs = array();
	if ($dh = opendir($dir)) {
		while (($file = readdir($dh)) !== false) {
			if ($file == '.' || $file == '..' || $file == '.svn') continue;
			if (is_dir($dir.'/'.$file)) { $dirs[] = $dir.'/'.$file; continue; }
			//if (pathinfo($dir.'/'.$file, PATHINFO_EXTENSION) != 'php') continue;
			if (substr($file, -3) != 'php') continue;
			
			//include_once($dir.'/'.$file);
			$files[] = $dir.'/'.$file;
		}
		closedir($dh);
	}

	foreach ($dirs as $dir) {
		$subFiles = LoadModulesDir($dir);
		$files = array_merge($files,$subFiles);
	}
	return $files;
	/*
	$glob = glob($dir.'*.php');
	if ($glob) foreach ($glob as $id => $filename) {
		include_once($filename);
		$files[] = $filename;
	}

	$glob = glob($dir.'*');
	$dirs = array();
	if ($recursive === TRUE && $glob) foreach ($glob as $dirname) {
		if ($dirname == '.' || $dirname == '..') continue;
		if (!is_dir($dirname)) continue;
		$subFiles = LoadModulesDir($dirname.'/',$recursive);
		$files = array_merge($files,$subFiles);
	}

	return $files;*/
}

function &CallModuleFunc($classname,$funcname) {
	static $null = NULL;

	if (!$classname) { ErrorLog("Executing function ($funcname) in null class<br/>".print_r(useful_backtrace(),true)); return $null; }
	//ErrorLog("Calling {$classname}->{$funcname}");

//  $args = func_get_args();array_shift($args);array_shift($args);
	// get args by reference.
	$stack = debug_backtrace();
	$args = array();
	if (isset($stack[0]["args"]))
		for($i=2; $i < count($stack[0]["args"]); $i++)
			$args[$i-2] = & $stack[0]["args"][$i];

	if (!method_exists($classname,$funcname)) { return $null; }

//static methods should be called directly, not using CallModuleFunc
//	$method = new ReflectionMethod($classname,$funcname);
//	if ($method->isStatic() || is_object($classname))
//		$instance = $classname;
//	else {
//		$instance = utopia::GetInstance($classname);
//	}
  $instance = utopia::GetInstance($classname);

	if ($instance == NULL) { ErrorLog("Error Calling {$classname}->{$funcname}"); return $null;}

	$call = array($instance,$funcname);
	$return = call_user_func_array($call,$args);
	return $return;
}

function &CallModuleFuncByRef($classname,$funcname,&$one=null,&$two=null,&$three=null,&$four=null,&$five=null,&$six=null,&$seven=null,&$eight=null,&$nine=null,&$ten=null,&$eleven=null,&$twelve=null) {
	$null = NULL;
	if (!$classname) { ErrorLog("Executing function ($funcname) in null class<br/>".print_r(useful_backtrace(),true)); return $null; }

	$instance =& utopia::GetInstance($classname);
	if ($instance == NULL) { ErrorLog($funcname); return $null;}

	if (!method_exists($instance,$funcname)) return $null;
	//	if (!is_callable($call)) return $null;
	//    $trace = debug_backtrace();
	//    if ($trace[1]['function'] != 'LoadChildren')
	//    	LoadChildren($classname); // allow for linked parents on uninitialised modules

	$call = array($instance,$funcname);
	$args = array(&$one,&$two,&$three,&$four,&$five,&$six,&$seven,&$eight,&$nine,&$ten,&$eleven,&$twelve);
	$return = ref_call_user_func_array($call,$args);
	return $return;
}

function &GetModuleVar($classname,$varname) {
	$null = NULL;
	if (($instance = utopia::GetInstance($classname)) == NULL) return $null;
	if (!property_exists($instance,$varname)) return $null;

	return $instance->$varname;
}

function SetModuleVar($classname,$varname,$value) {
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

function _LoadChildren($withParent = NULL,$tiers=1) {
	//	utopia::CancelTemplate();
	//	ErrorLog("LoadChildren($withParent = NULL,$tiers=1)");
	if (!array_key_exists('children',$GLOBALS)) return TRUE;
	if (empty($withParent)/* || $withParent === '*'*/) $withParent = GetCurrentModule();
	if ($withParent !== NULL && !array_key_exists($withParent,$GLOBALS['children']) && !array_key_exists('*',$GLOBALS['children'])) return TRUE;

	//			echo '<br><br>'.$withParent.': ';
	//	if (!array_key_exists('loadedChildren',$GLOBALS)) $GLOBALS['loadedChildren'] = array();

	foreach ($GLOBALS['children'] as $parentModule => $parentArray) {
		if ($parentModule !== $withParent && $parentModule !== '*' &&
		!($withParent == GetCurrentModule() && $parentModule == '/')) continue;
		//echo "$parentModule with parent $withParent... <br>";

		//ErrorLog(GetCurrentModule()."::$withParent");
		//if (array_key_exists($parentModule,$GLOBALS['loadedChildren'])) continue;
		//$GLOBALS['loadedChildren'][$parentModule] = TRUE;
		//ErrorLog("checking for $parentModule with $withParent....");

		// make an array of next tier children to pass AFTER current module. (not during)
		//$nextTier = array();
		foreach ($parentArray as $parentID => $child) {
			//echo ', '.$child['moduleName'];
			//if ($child['moduleName'] == $withParent) continue;
			//$persistent = flag_is_set(CallModuleFunc($child['moduleName'],'GetOptions'),PERSISTENT_PARENT);

			//if ($tiers <= 0 && !$childPersistent) continue;
			//if ($tiers <= 0) continue;

			//if (!CallModuleFunc($child['moduleName'],'CanParentLoad',$withParent)) continue;

			//ErrorLog($withParent.'->'.$child['moduleName'].' '.($persistent ? '1' : '0'));
			//CallModuleFunc($child['moduleName'],'_SetupFields');
			CallModuleFunc($child['moduleName'],'_ParentLoad',$withParent);
			//if ( === FALSE) return FALSE;
			//echo 'done<br>';
			//if ($tiers > 0) $nextTier[] = $child['moduleName'];
		}

		//foreach ($nextTier as $child) {
		//	if (LoadChildren($child,$tiers-1) === FALSE) return FALSE;
		//}
	}
	return TRUE;
}
/*
 function GetModuleFromUUID($uuid) {
 return $GLOBALS['modules'][$uuid];
 }

 function GetUUIDFromModule($module) {
 return array_search($module,$GLOBALS['modules']);
 }*/

function GetCurrentModule() {
	if (utopia::VarExists('current_module')) return utopia::GetVar('current_module');
	if (!array_key_exists('uuid',$_GET)) {
		if (!class_exists(BASE_MODULE)) return NULL;
		return BASE_MODULE;
	}

	$m = utopia::UUIDExists($_GET['uuid']);
	return $m['module_name'];
}

function RunModule($module = NULL) {
	if ($module == NULL) $module = GetCurrentModule();
	
	if (!utopia::ModuleExists($module,true)) {
		utopia::PageNotFound();
	}
	utopia::SetVar('current_module',$module);
	utopia::SetVar('title',CallModuleFunc($module,'GetTitle'));
	// run module
	if (!is_empty($module)) CallModuleFunc($module,'_RunModule');

	utopia::Finish();
}

function InstallAllModules() {
	// uninstall any non-existant
	timer_start('module uninstallation');
	//	sql_query("TRUNCATE TABLE internal_modules");
	$result = sql_query("SELECT * FROM internal_modules");
	while (($row = GetRow($result))) if (!class_exists($row['module_name'])) sql_query("DELETE FROM internal_modules WHERE `module_id` = '{$row['module_id']}'");
	timer_end('module uninstallation');
  
  // TABLE CHANGE CHECKER
  sql_query('CREATE TABLE IF NOT EXISTS __table_checksum (`name` varchar(200) PRIMARY KEY, `checksum` varchar(40))');
//  $r = sql_query('SHOW TABLES LIKE \'__table_checksum\'');
//  if (!mysql_num_rows($r)) {
    // create internal table check
//  }

	$installed = array();
	$classes = get_declared_classes();
	timer_start('table installation');
	foreach ($classes as $classname){ // install tables
		if ($classname == 'uTableDef' || !is_subclass_of($classname,'uTableDef')) continue;
    
		timer_start('table install ('.$classname.')');
		CallModuleFunc($classname,'InstallTable');
		$installed[] = $classname;
		timer_end('table install ('.$classname.')');
	}
	timer_end('table installation');

	timer_start('module installation');
	foreach ($classes as $classname) // install modules
	if (is_subclass_of($classname,'uBasicModule')
	&& $classname != 'uBasicModule'
	&& $classname != 'uDataModule'
	&& $classname != 'uListDataModule'
	&& $classname != 'uSingleDataModule') {
		//mail('oridan82@gmail.com','installmodule',$classname);
		$installed[] = $classname;
		CallModuleFunc($classname,'InstallModule');
	}
	timer_end('module installation');
	return $installed;
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
