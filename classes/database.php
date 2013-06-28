<?php
class mainSchema extends sqlSchema {
	protected $servername	= SQL_SERVER;
	protected $port			= SQL_PORT;
	protected $dbname		= SQL_DBNAME;
	protected $username		= SQL_USERNAME;
	protected $password		= SQL_PASSWORD;
}
class database {
	private static $conn = null;
	static function connect() {
		$conn = new mainSchema(array(PDO::ATTR_PERSISTENT => true, PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8', PDO::MYSQL_ATTR_FOUND_ROWS => true));
	
		if (!$conn) {
			echo "Cannot connect to SQL server.<br>";
			return false;
		}

		return $conn;
	}
	
	static $queryCount = 0;
	static function &query($query, $args=NULL) { $false = FALSE;
		if (empty($query)) return $false;
		if (!isset($GLOBALS['sql_query_count']))
			$GLOBALS['sql_query_count'] = 0;
		else
			$GLOBALS['sql_query_count']++;

		if (utopia::DebugMode()) {
			$tID='QRY'.$GLOBALS['sql_query_count'].': '.$query;
			timer_start($tID,$args);
		}
	
		$pdo = self::connect();
		$pdo->reset();
		if (is_array($args)) foreach ($args as $a) {
			$pdo->addByVal($a,self::getType($a));
		}
		try {
			self::$queryCount++;
			$stm = $pdo->call($query);
			$stm->setFetchMode(PDO::FETCH_ASSOC);
		} catch (Exception $e) { if (utopia::DebugMode()) $timetaken = timer_end($tID); throw $e;}

		if (utopia::DebugMode()) $timetaken = timer_end($tID);
		return $stm;
	}
	static function getKeyValuePairs($query,$args=null) {
		$result = self::query($query,$args);
		$arr = array();
		while ($result !== false && (($row = $result->fetch(PDO::FETCH_NUM)) !== FALSE)) {
			if (isset($row[1])) {
				$arr[$row[0]] = $row[1];
			} else {
				$arr[$row[0]] = $row[0];
			}
		}
		return $arr;
	}
	static function getType($val) {
		if ($val === NULL) return PDO::PARAM_NULL;
		if (is_bool($val)) return PDO::PARAM_BOOL;
		if (is_int($val)) return PDO::PARAM_INT;
		//if (is_numeric($val) || is_float($val)) //default str
		if (is_resource($val)) return PDO::PARAM_LOB;

		return PDO::PARAM_STR;
	}
}
