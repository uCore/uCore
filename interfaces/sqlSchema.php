<?php
abstract class sqlSchema extends PDO {
	protected $servername	= '';
	protected $dbname		= '';
	protected $username		= '';
	protected $password		= '';
	protected $engine		= 'mysql';

	public $returnValue = null;
	public $resultSets = array();

	private $hasReturn = null;
	private $params = array();
	function setReturn($type,$length=-1) {
		if (is_null($type)) {
			$this->hasReturn = null; return;
		}
		if ($type !== PDO::PARAM_INT && $length === -1) {
			trigger_error('Invalid length for non integer return value.',E_USER_ERROR);
		}
		$this->hasReturn = array($type,$length);
	}
	function &addByRef(&$var,$type=PDO::PARAM_STR,$length) {
		$type = $type | PDO::PARAM_INPUT_OUTPUT;
		if (is_string($var) && strlen($var) > $length)
			$var = substr($var,0,$length);
		$param = array(&$var,$type,$length,true);
		$this->params[] =& $param;
		return $param;
	}
	function &addByVal($var,$type=PDO::PARAM_STR) {
		if (is_null($var)) $type=PDO::PARAM_NULL;
		$param = array($var,$type,null,false);
		$this->params[] =& $param;
		return $param;
	}
	private $columnBindings = array();
	function &bindColumn($column_number, &$var, $stream=false) {
		$col = array(&$var,$stream);
		$this->columnBindings[$column_number] =& $col;
		return $col;
	}
	function &call($fnName,$function = false) {
		if (!$function) $this->setReturn(null);
		// build function call query

		$paramCount = count($this->params);

		if (!$function) {
			$qry = $fnName;
		} else {
			$qry = '';
			if (!is_null($this->hasReturn)) $qry = '? = ';
			$qry .= 'CALL '.$fnName.'(';
			for ($i = 0; $i < $paramCount; $i++) {
				$qry .= '?';
				if ($i+1 < $paramCount) {
					$qry .= ',';
				}
			}
			$qry .= ')';
			$qry = '{'.$qry.'}';
		}

	//	try {
			if ($this->columnBindings && $this->engine == 'sqlsrv') {
				return $this->callWithSqlSrv($qry);
			}
			return $this->callWithPDO($qry);
	/*	} catch (Exception $e) {
			$backtrace = debug_backtrace();
			$caller = next($backtrace);
			//print_r($caller);
			//echo $e->getMessage();
			//echo $e->getCode();
			//echo $caller['file'];
			//echo $caller['line'];
			echo $e->getMessage();
			//throw new ErrorException($e->getMessage(), $e->getCode(), 1, $caller['file'], $caller['line'],$e);
		}*/
	}
	private function &callWithSqlSrv($qry) {
//		$serverName = "(local)";
		$connectionInfo = array( "UID" => $this->username, "PWD" => $this->password, "Database"=>$this->dbname);
		$conn = sqlsrv_connect( $this->servername, $connectionInfo);
		$params = array();
		foreach ($this->params as $param) {
			$params[] = &$param[0];
		}

		if (!($stmt = sqlsrv_prepare( $conn, $qry, $params))) {
		      throw new Exception(sqlsrv_errors());
		      return false;
		}
		
		if (!sqlsrv_execute($stmt)) {
			throw new Exception(sqlsrv_errors());
			return false;
		}
		return $stmt;
		
		if (!sqlsrv_fetch($stmt)) return false;
		
		foreach ($this->columnBindings as $k=>$col) {
			if ($col[1]) $col[0] = sqlsrv_get_field($stmt, $k, SQLSRV_PHPTYPE_STREAM( $col[1]));
			else $col[0] = sqlsrv_get_field($stmt, $k);
		}
		
		// successfully created connection and executed query, return true.
		// the user should now be calling "movenext"
		$this->sqlsrvConnection = &$conn;
		$this->sqlsrvStatement = &$stmt;
		return true;
	}
	private function &callWithPDO($qry) {
		// get PDOStatement
		$stmt = $this->prepare( $qry );
		if (!$stmt)	{
			throw new Exception('Invalid query: '.$qry);
			return false;
		}

		// add return value parameter
		if (!is_null($this->hasReturn))
		$stmt->bindParam(1,&$this->returnValue,$this->hasReturn[0] | PDO::PARAM_INPUT_OUTPUT,$this->hasReturn[1]);

		// add other parameters in order
		$paramCount = count($this->params);
		for ($i = 0,$inc = is_null($this->hasReturn)?1:2; $i < $paramCount; $i++) {
			if ($this->params[$i][3])
				$stmt->bindParam($i+$inc,$this->params[$i][0],$this->params[$i][1],$this->params[$i][2]);
			else
				$stmt->bindValue($i+$inc,$this->params[$i][0],$this->params[$i][1]);
		}

		// execute statement
		try {
			$stmt->execute();
		} catch(Exception $e) {} // allow error handling below to return real error
		
		if ($stmt->errorCode() !== '00000') {
			$err = $stmt->errorInfo();
			if (!$err[1]) {
				throw new Exception ($qry.PHP_EOL.var_export($this->params,true),$err[0]);
			} else {
				throw new Exception($err[2],$err[1]);
			}
			return false;
		}
		
		return $stmt;
		
		$this->resultSets = array();
		do {
			try {
				$this->resultSets[] = $stmt->fetchAll(PDO::FETCH_ASSOC);
			} catch (Exception $e) {}
		} while ($stmt->nextRowset());
			
		return true;
	}
	
	public $sqlsrvConnection = NULL;
	public $sqlsrvStatement = NULL;
	public function SqlSrvMoveNext() {
		if (!isset($this->sqlsrvConnection)) return false;
		if (!isset($this->sqlsrvStatement)) return false;

		if (!sqlsrv_fetch($this->sqlsrvStatement)) return false;
//		if (!sqlsrv_next_result($this->sqlsrvStatement)) return false; // resultset
//		echo 'a';
		
		foreach ($this->columnBindings as $k=>$col) {
			if ($col[1]) $col[0] = sqlsrv_get_field($this->sqlsrvStatement, $k, SQLSRV_PHPTYPE_STREAM( SQLSRV_ENC_CHAR));
			else $col[0] = sqlsrv_get_field($this->sqlsrvStatement, $k);
		}
		return true;
	}

	function reset() {
		$this->returnValue = null;
		$this->resultSets = array();
		$this->hasReturn = null;
		$this->params = array();
	}

	public static $connections = array();
	function __construct($options = NULL) {
		if (!$this->servername)	trigger_error('Please declare protected $servername', E_USER_ERROR);
		if (!$this->port)		trigger_error('Please declare protected $port', E_USER_ERROR);
		if (!$this->dbname)		trigger_error('Please declare protected $dbname', E_USER_ERROR);
		if (!$this->username)	trigger_error('Please declare protected $username', E_USER_ERROR);
		if (!$this->password)	trigger_error('Please declare protected $password', E_USER_ERROR);

		$dns = $this->engine.':host='.$this->servername.";port=".$this->port.";dbname=".$this->dbname;
		//$this->setReturn(PDO::PARAM_INT);
		try {
			parent::__construct( $dns, $this->username, $this->password,$options);
			$this->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		} catch (Exception $e) {
			trigger_error($e->getMessage().' '.$dns, E_USER_ERROR);
			return;
		}
		//$this->setAttribute( PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_SYSTEM );
		
		self::$connections[] =& $this;
//		register_shutdown_function('unset',$this);
	}
	public static function close_connections() {
		foreach (self::$connections as $conn) {
//			if (isset($conn->sqlsrvStatement)) sqlsrv_free_stmt($conn->sqlsrvStatement);
//			if (isset($conn->sqlsrvConnection)) sqlsrv_close($conn->sqlsrvConnection);
			unset($conn);
		}
	}
}
register_shutdown_function('sqlSchema::close_connections');
