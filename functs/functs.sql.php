<?php

function InterpretSqlString($sqlString, &$module, &$field, &$pkVal) {
	$matches = null;
	if (!preg_match('/([^:]+):([^\(]+)(\(.*\))?/',$sqlString,$matches)) return false;
	//	die(print_r($matches,true));
	$module = $matches[1];
	$field = $matches[2];

	if (!isset($matches[3]) || $matches[3] === '')
		$pkVal = $pkVal;
	elseif ($matches[3] == '()') {
		$pkVal = '';
	} else
		$pkVal = substr($matches[3],1,-1);

	return true;
}

function InterpretSqlDeleteString($sqlString, &$module, &$table, &$where) {
	$matches = null;
	if (!preg_match('/(.+):(.+)\((.*)\)/',$sqlString,$matches)) return false;
	//	die(print_r($matches,true));
	$module = $matches[1];
	$table = $matches[2];
	$where = $matches[3];
	return true;
	//	$where = trim(substr($sqlString,strpos($sqlString,'(')),'()');
	//	$table = substr($sqlString,0,strpos($sqlString,'('));

	//	return true;
}

function GetPossibleValues($table,$pkName,$field,$where = '') {
	//echo "GetPossibleValues($table,$pkName,$field,$where)";
	if (!empty($where)) $where = " WHERE $where";
	$fns = CreateConcatString($field,$table);
	$lRes = database::query("SELECT {$fns} as d, {$pkName} as v FROM {$table}{$where} ORDER BY {$fns}");
	$lv = array();
	while (($row = $lRes->fetch())) {
		$lv[$row['v']] = $row['d'];
	}
	return $lv;
}
