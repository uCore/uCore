<?php
class database {
	static function connect($srv=NULL,$port=NULL,$usr=NULL,$pass=NULL) {
		$sql_connection =& utopia::GetVar('sql_connection');
		if ($sql_connection) return $sql_connection;

		if (!$port) $port = is_empty(constant('SQL_PORT')) ? '' : ':'.SQL_PORT;
		if (!$srv) $srv = SQL_SERVER.$port; else $srv = $srv.$port;
		if (!$usr) $usr = SQL_USERNAME;
		if (!$pass) $pass = SQL_PASSWORD;

		$sql_connection = new PDO(DB_TYPE.':host='.SQL_SERVER.';port='.SQL_PORT.';dbname='.SQL_DBNAME,$usr,$pass,array(PDO::ATTR_PERSISTENT => true));
	
		if (!$sql_connection) {
			echo "Cannot connect to SQL server.<br>";
			return false;
		}

		return $sql_connection; // $GLOBALS['sql_connection'];
	}
	static function &query($query, $args=NULL) { $false = FALSE;
		if (empty($query)) return $false;
		if (!isset($GLOBALS['sql_query_count']))
			$GLOBALS['sql_query_count'] = 0;
		else
			$GLOBALS['sql_query_count']++;

		if (!($pdo = self::connect())) {
			$err = $stm->errorInfo();
			if ( $err[0] != '00000' ) {
				throw new Exception($err[2]);
			}
		}

		$tID='QRY: '.$query;
		timer_start($tID);
		$GLOBALS['sql_queries'][$GLOBALS['sql_query_count']] = $query;
	
		$stm = $pdo->prepare($query);
		if (!$stm->execute($args)) {
			$err = $stm->errorInfo();
			if ( $err[0] != '00000' ) {
				throw new Exception($err[2]);
			}
		}
		$stm->setFetchMode(PDO::FETCH_ASSOC);

		$timetaken = timer_end($tID);
		return $stm;
	}
}


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
