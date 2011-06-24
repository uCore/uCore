<?php
//  SQL FUNCTIONS
ini_set('mysql.connect_timeout',20);
function sql_connect($srv=NULL,$port=NULL,$usr=NULL,$pass=NULL) {
	//    $port = is_empty(constant('SQL_PORT')) ? '' : ';port='.SQL_PORT;
	//   $GLOBALS['dbh'] = new PDO(DB_TYPE.':dbname='.SQL_DBNAME.';host='.SQL_SERVER.$port,SQL_USERNAME,SQL_PASSWORD);
	//    return $GLOBALS['dbh'];
	static $sql_connection = false;
  if ($sql_connection) return $sql_connection;

//	if (!array_key_exists('sql_connection',$GLOBALS) || !$GLOBALS['sql_connection']) {
	if (!$port) $port = is_empty(constant('SQL_PORT')) ? '' : ':'.SQL_PORT;
	if (!$srv) $srv = SQL_SERVER.$port; else $srv = $srv.$port;
	if (!$usr) $usr = SQL_USERNAME;
	if (!$pass) $pass = SQL_PASSWORD;
	switch (strtolower(DB_TYPE)) {
		case 'mysql':
			$sql_connection = mysql_connect($srv,$usr,$pass);
			mysql_select_db(SQL_DBNAME);
			break;
		case 'mssql':
			$sql_connection = mssql_connect($srv,$usr,$pass);
			break;
	}
	if (!$sql_connection) {
		echo "Cannot connect to SQL server.<br>";
		return false;
	}
	sql_query("SET NAMES utf8");
//	}

	return $sql_connection; // $GLOBALS['sql_connection'];
}

function fdb_sql_error() {
	/*    $info = $GLOBALS['dbh']->errorInfo();
	 if (is_array($info))
	 return $info[2];
	 return;    */

	switch (strtolower(DB_TYPE)) {
		case 'mysql':
			$ret = mysql_error();
			break;
		case 'mssql':
			$ret = mssql_get_last_message();
			break;
	}
	return $ret;
}

function &fdb_sql_query($query) {
	//   return $GLOBALS['dbh']->query($query,PDO::FETCH_ASSOC);
	$ret = mysql_query($query);
	return $ret;
	//echo $query.'<br>';
	switch (strtolower(DB_TYPE)) {
		case 'mysql':
			mysql_select_db(SQL_DBNAME);
			$ret =& mysql_query($query);
			break;
		case 'mssql':
			mssql_select_db(SQL_DBNAME);
			$ret =& mssql_query($query);
			break;
	}
	return $ret;
}

function fdb_sql_num_rows($result) {
	//    return $result->rowCount();

	switch (strtolower(DB_TYPE)) {
		case 'mysql':
			$ret = mysql_num_rows($result);
			break;
		case 'mssql':
			$ret = mssql_num_rows($result);
			break;
	}
	return $ret;
}

function fdb_sql_data_seek($result,$rowNum) {
	switch (strtolower(DB_TYPE)) {
		case 'mysql':
			$ret = mysql_data_seek($result,$rowNum);
			break;
		case 'mssql':
			$ret = mssql_data_seek($result,$rowNum);
			break;
	}
	return $ret;
}

function fdb_sql_fetch_assoc($result) {
	switch (strtolower(DB_TYPE)) {
		case 'mysql':
			/*            $fields = mysql_num_fields($result);
			 if ($fields > 0) {
			 $ret = array();
			 for ($i = 0; $i < $fields; $i++) {
			 $ret = mysql_result($result,)
			 }
			 } */
			$ret = mysql_fetch_assoc($result);
			break;
		case 'mssql':
			$ret = mssql_fetch_assoc($result);
			break;
	}
	return $ret;
}

function &sql_query($query) { $false = FALSE;
	//	$GLOBALS['db_handle'] = new PDO('mysql:host=localhost;dbname=test', $user, $pass);
	//new PDO(DB_TYPE.':'.DB_DSN, DB_USERNAME, DB_PASSWORD) //
	//return;
	if (empty($query)) return $false;
	if (!isset($GLOBALS['sql_query_count']))
		$GLOBALS['sql_query_count'] = 0;
	else
		$GLOBALS['sql_query_count']++;

	if (!sql_connect()) die('Could not connect to database: ' . mysql_error());

	$tID='QRY: '.$query;
	timer_start($tID);
	$GLOBALS['sql_queries'][$GLOBALS['sql_query_count']] = $query;
	$result = mysql_query($query);
	$err = mysql_error();	if (!empty($err)) { trigger_error($err."\n\n".$query); ErrorLog($err); }//ErrorLog("$err<br/>$query");
	$timetaken = timer_end($tID);
	/*	if (false && $timetaken > 50) {
		//echo $query." slow: $timetaken<BR>";
		if (strtolower(substr($query,0,6)) == "select") {
		$explain = sql_query("EXPLAIN $query");
		print_r(mysql_fetch_row($explain));
		}
		}*/

	//	$GLOBALS['sql_queries'][] = array('qry_'.$GLOBALS['sql_query_count'],$query,useful_backtrace(1,2),$timetaken,$err);

	return $result;
}

function GetRow($result, $rowNum=NULL) {
	if ($result == FALSE) {
		//		include_once('../../mailer/mail.php');
		//		SendReportEmail('GetRow Error: Invalid Result',mysql_error()."\n\n".var_export($_SERVER,true));
		ErrorLog("ERROR: ". fdb_sql_error());
		return NULL;
	}

	if (mysql_num_rows($result) <= 0) {
		return NULL;
	}

	if ($rowNum !== NULL) {
		//		echo "SEEK:".mysql_num_rows($result).':'.$rowNum;
		//		if (mysql_num_rows($result) < $rowNum) return NULL;
		mysql_data_seek($result,$rowNum);
		//   return $result[$rowNum];
	}

	//  $row = next($result);
	// return $row;

	$row = mysql_fetch_assoc($result);
	if ($row) {
	  $keys = array_keys($row);
    $size = sizeof($keys);
    for ($i = 0; $i < $size; $i++) {
      $row[$keys[$i]] = stripslashes($row[$keys[$i]]);
		}
	}

	return $row;
}

function GetRows($result) {
	if (!$result || !mysql_num_rows($result)) return array();

	mysql_data_seek($result,0);

	$rows = array();
	while (($row = GetRow($result))) $rows[] = $row;

	return $rows;
}

function TableExists($tblName) {
	$result = sql_query('SHOW TABLES',true);

	while (($row = GetRow($result))) {
		if ($row['Tables_in_'.SQL_DBNAME] == $tblName) return TRUE;
	}
	return false;
}


function InterpretSqlString($sqlString, &$module, &$field, &$pkVal) {
	$matches = null;
	if (!preg_match('/([^:]+):([^\(]+)(\(.*\))?/',$sqlString,$matches)) return false;
	//	die(print_r($matches,true));
	$module = $matches[1];
	$field = $matches[2];

	if (!isset($matches[3]) || $matches[3] === '')
		$pkVal = NULL;
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
	$lRes = sql_query("SELECT {$fns} as v, {$pkName} as d FROM {$table}{$where} ORDER BY {$fns}");
	$lv = array();
	while (($row = GetRow($lRes))) {
		$lv[$row['v']] = $row['d'];
	}
	return $lv;
}
?>