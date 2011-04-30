<?php
include_once('interfaces.php');

// text
define('ftNONE'				,'');
define('ftVARCHAR'			,'varchar');
define('ftTEXT'				,'text');
define('ftLONGTEXT'			,'longtext');
// time
define('ftDATE'				,'date');
define('ftTIME'				,'time');
define('ftDATETIME'			,'datetime');
define('ftTIMESTAMP'		,'timestamp');
// numbers
define('ftBOOL'				,'bool');
define('ftCURRENCY'			,'currency');
define('ftPERCENT'			,'percent');
define('ftFLOAT'			,'float');
define('ftNUMBER'			,'int');
define('ftDECIMAL'			,'decimal');

define('ftIMAGE'			,'image');
define('ftFILE'				,'file');
define('ftUPLOAD'			,'upload');

// raw
define('ftRAW'				,'raw');
define('SQL_NULL'			,'null');
define('SQL_NOT_NULL'		,'not null');

uConfig::AddConfigVar('TABLE_PREFIX','Table Prefix','');
uConfig::AddConfigVar('MYSQL_ENGINE','MySQL Engine','InnoDB',array('InnoDB','MyIASM'));

function getSqlTypeFromFieldType($fieldType) {
	switch ($fieldType) {
		case ftCURRENCY:
		case ftPERCENT:
			return 'decimal';
		case ftIMAGE:
			return 'longblob';
		case ftFILE:
			return 'longblob';
		case ftUPLOAD:
			return 'varchar(500)';
		default:
			return $fieldType;
	}
}

abstract class uTableDef implements iUtopiaModule {
	public $fields = array();
	public $engine = NULL;
	public abstract function SetupFields();

	private $isDisabled = false;
	public function DisableModule() {
		$this->isDisabled = true;
	}

	public $fieldsSetup = FALSE;
	public function _SetupFields() {
		if ($this->fieldsSetup == TRUE) return;
		$this->fieldsSetup = TRUE;

		$this->SetupFields();
	}

	public function SetPrimaryKey($name, $auto_increment = true) {
		//$name = strtolower($name);
		if ($this->GetFieldProperty($name,'index') === TRUE || $this->GetFieldProperty($name,'unique') === TRUE) {
			ErrorLog('Cannot assign unique flag to $name, already an indexed field.'); return; }
		$this->fields[$name]['pk'] = true;
		$this->fields[$name]['default'] = NULL;
		if ($this->fields[$name]['type'] == ftNUMBER && $auto_increment) $this->fields[$name]['extra'] = 'auto_increment';
	}
	public function SetUniqueField($name) {
		//$name = strtolower($name);
		if ($this->GetFieldProperty($name,'index') === TRUE || $this->GetFieldProperty($name,'pk') === TRUE) {
			ErrorLog('Cannot assign unique flag to $name, already an indexed field.'); return; }
			$this->fields[$name]['unique'] = true;
	}
	public function SetIndexField($name) {
		//$name = strtolower($name);
		if ($this->GetFieldProperty($name,'unique') === TRUE || $this->GetFieldProperty($name,'pk') === TRUE) {
			ErrorLog('Cannot assign index flag to $name, already an indexed field.'); return; }
			$this->fields[$name]['index'] = true;
	}

	public function GetPrimaryKey() {
		foreach ($this->fields as $field => $fData) {
			if (!array_key_exists('pk',$fData)) continue;
			if ($fData['pk'] === true) return $field;
		}
		return NULL;
	}

	public function GetLookupData($fieldName) {
		//$fieldName = strtolower($fieldName);
		$lookupData = $this->GetFieldProperty($fieldName,'lookup_data');
		//	if (!empty($lookupData))
		//		$lookupData['lookupField'] = $this->GetPrimaryKey();
		return $lookupData;
	}

	public function AddFieldArray($name, $type, $length, $arr) {
		$this->AddField($name,$type,$length);
		foreach ($arr as $key => $val)
			$this->fields[$name][$key] = $val;
	}

	public function AddField($name, $type, $length=NULL, $collation=SQL_COLLATION, $attributes=NULL, $null=SQL_NULL, $default=NULL, $extra=NULL, $comments=NULL) {
		//$name = strtolower($name);
		$this->fields[$name] = array();
		$field =& $this->fields[$name];

		$field['type'] = $type;
		if ($length == NULL && $type == ftCURRENCY) $length = "10,2";
		if ($length == NULL && $type == ftPERCENT) $length = "5,2";
		$sqltype = getSqlTypeFromFieldType($type);
		$field['length'] = $length;
		$field['collation'] = (!stristr($sqltype, 'binary') && !stristr($sqltype, 'blob')) ? $collation : NULL;
		$field['attributes'] = $attributes;
		$field['null'] = $null;

		$zeroIfNull = array_flip(array(ftNUMBER,ftBOOL,ftDECIMAL,ftPERCENT,ftCURRENCY,ftTIMESTAMP,ftTIME));
		$emptyIfNull = array();
		if ($default === NULL && isset($zeroIfNull[$sqltype]))
			$default = 0;
		if ($default === NULL && in_array($sqltype,$emptyIfNull))
			$default = '';

		$field['default'] = $default;
		$field['extra'] = $extra;
		$field['comments'] = $comments;

		if ($type == ftFILE || $type == ftIMAGE) {
			$this->AddField($name.'_filename', ftVARCHAR, 255);
			$this->AddField($name.'_filetype', ftVARCHAR, 255);
		}
	}

	public function FieldExists($fieldName) {
		//$fieldName = strtolower($fieldName);
		$this->_SetupFields();
		return isset($this->fields[$fieldName]);
	}

	public function SetFieldProperty($fieldName,$propertyName,$propertyValue) {
		//$fieldName = strtolower($fieldName);
		if (!isset($this->fields[$fieldName])) return;
		$this->fields[$fieldName][$propertyName] = $propertyValue;
	}

	public function GetFieldProperty($fieldName,$propertyName) {
		//$fieldName = strtolower($fieldName);
		//$propertyName = strtolower($propertyName);
		$this->_SetupFields();
		if (!isset($this->fields[$fieldName])) return;
		if (!isset($this->fields[$fieldName][$propertyName])) return;
		return $this->fields[$fieldName][$propertyName];
	}

	static $tableCache = NULL;
	static function TableExists($tableName,$refresh=false) {
		if ($refresh || self::$tableCache === NULL) self::$tableCache = GetRows(sql_query('SHOW TABLES'));
		foreach (self::$tableCache as $tbl) {
			if ($tbl['Tables_in_'.SQL_DBNAME] == $tableName) return TRUE;
		}
		return FALSE;
	}

	static $tableChecksum = NULL;
	public function checksumValid($checksum,$refresh=false) {
		if ($refresh || self::$tableChecksum === NULL) self::$tableChecksum = GetRows(sql_query('SELECT * FROM `__table_checksum`'));
		$class = get_class($this);
		foreach (self::$tableChecksum as $row) {
			if ($row['name'] == TABLE_PREFIX.$class) return $row['checksum'] === $checksum;
		}
		return FALSE;
	}

	public function Initialise() {
		// create / update table
		// is table already existing?
		if ($this->isDisabled) return;
		$this->_SetupFields();
		if (empty($this->fields)) return;

		$oldTable = isset($this->tablename) ? $this->tablename : NULL;
		$this->tablename = TABLE_PREFIX.get_class($this);

		// rename old table name to class name
		if (self::TableExists($oldTable) && !self::TableExists($this->tablename))
			sql_query('RENAME TABLE '.mysql_real_escape_string($oldTable).' TO '.$this->tablename);

		// checksum
		$engine = MYSQL_ENGINE;
		if ($this->engine) $engine = $this->engine;
		$checksum = sha1($engine.print_r($this->fields,true));
		if ($this->checksumValid($checksum)) return;
		sql_query('INSERT INTO `__table_checksum` VALUES (\''.$this->tablename.'\',\''.$checksum.'\') ON DUPLICATE KEY UPDATE `checksum` = \''.$checksum.'\'');

		$unique = array();
		$index = array();
		if (self::TableExists($this->tablename)) {
			// loop fields
			$pk = NULL;
			$currentPK = NULL;

			$alterArray = array();
			$otherArray = array();
			$alterArray[] = "CHARACTER SET ".SQL_CHARSET_ENCODING." COLLATE ".SQL_COLLATION;

			// lets keep the sql querys to a minimum, get all the rows first, and process them locally.
			$rows = GetRows(sql_query("SHOW FULL FIELDS FROM `$this->tablename`"));
/*			foreach ($rows as $data) {
				if (!array_key_exists($data['Field'],$this->fields)) {
					$alterArray[] = "DROP `{$data['Field']}`";
				}
			}*/
			//print_r($this->fields);
			foreach ($this->fields as $fieldName => $fieldData) {
				// build field
				if (array_key_exists('pk',$fieldData) && $fieldData['pk'] === true) if ($pk === NULL) $pk = $fieldName; else { $pk = FALSE; break; /* multiple pri key - break */ };
				$type = getSqlTypeFromFieldType($fieldData['type']);
				$length = empty($fieldData['length']) ? '' : "({$fieldData['length']})";
				if ($fieldData['type'] == ftTIMESTAMP) {
					if (strtolower($fieldData['default']) == 'current_timestamp') $default = " DEFAULT CURRENT_TIMESTAMP";
					else $default = " DEFAULT 0";
				} else
				$default = $fieldData['default'] === NULL ? '' : " DEFAULT '{$fieldData['default']}'";
				$comments = $fieldData['comments'] === NULL ? '' : " COMMENT '{$fieldData['comments']}'";
				$collate = $fieldData['collation'] === NULL ? '' : " COLLATE '{$fieldData['collation']}'";

				$len = '';
				if ($fieldData['type'] == ftTEXT || $fieldData['type'] == ftVARCHAR) $len = "({$fieldData['length']})";
				if (array_key_exists('unique',$fieldData) && $fieldData['unique'] === TRUE) $unique[] = "`$fieldName`$len";
				if (array_key_exists('index',$fieldData) && $fieldData['index'] === TRUE) $index[] = "`$fieldName`$len";

				$row = NULL;
				for ($i = 0,$rowCount = count($rows); $i < $rowCount; $i++) // find if field is already in the table
				if (strtolower($rows[$i]['Field']) === strtolower($fieldName)) { $row = $rows[$i]; break; }

				if ($row !== NULL) {
					// field exists, "modify" it
					if (strtolower($row['Key']) == 'pri') $currentPK = $row['Field'];

					$alterArray[] = "\nMODIFY `$fieldName` $type$length {$fieldData['null']} {$fieldData['attributes']}$default {$fieldData['extra']}$comments$collate";
				} else {
					// field doesnt exist, either hasnt been renamed, or hasnt been created yet. -- NO RENAME YET
					$alterArray[] = "\nADD `$fieldName` $type$length {$fieldData['null']} {$fieldData['attributes']}$default {$fieldData['extra']}$comments$collate";
				}

				//				sql_query($qry);
				// timestamps do not set their value correctly for previously created records if the default value is current_timestamp, we must set it now, to the default value
				if ((strtolower($fieldData['type']) == 'timestamp') && (strtolower($fieldData['default']) == 'current_timestamp')) {
					if ($fieldData['null'] == 'null')
					$otherArray[] = "\nUPDATE `$this->tablename` SET `$fieldName` = NOW() WHERE `$fieldName` IS NULL";
					else
					$otherArray[] = "\nUPDATE `$this->tablename` SET `$fieldName` = NOW() WHERE `$fieldName` = 0";
					//					sql_query($qry,true);
				}
			}

			// drop all indexes
			$indexResult = sql_query("SHOW INDEX FROM `$this->tablename`");
			while ($indexRow = GetRow($indexResult)) {
				if ($indexRow['Key_name'] === 'PRIMARY') continue;
				array_unshift($alterArray,"\nDROP INDEX `".$indexRow['Key_name']."`");
			}

			// reset pk
			//if ($pk === NULL)		ErrorLog('Must specify a PRIMARY KEY ('.get_class($this).')');
			if ($pk === FALSE)	ErrorLog('Cannot assign multiple PRIMARY KEYS ('.get_class($this).')');
			elseif ($pk !== NULL && $pk !== $currentPK) {
				$dropold = $currentPK === NULL ? '' : ' DROP PRIMARY KEY,';
				$alterArray[] = "$dropold ADD PRIMARY KEY ($pk)";
			}

			foreach ($index as $val) {
				$alterArray[] = "\nADD INDEX ($val)";
			}

			foreach ($unique as $val) {
				$alterArray[] = "\nADD UNIQUE ($val)";
			}

			sql_query("ALTER IGNORE TABLE `$this->tablename` ENGINE=$engine");
			array_unshift($otherArray,"ALTER IGNORE TABLE `$this->tablename` ".join(', ',$alterArray).";");
			//echo "ALTER IGNORE TABLE `$this->tablename` ".join(', ',$alterArray).";";
			//sql_query("ALTER IGNORE TABLE `$this->tablename` ".join(', ',$alterArray).";");
			foreach ($otherArray as $qry) {
				//echo $qry;
				sql_query($qry);
				$err = mysql_error();
				if (!empty($err)) die($qry."\n".$err);
			}
		} else {
			// create table
			$pk = NULL;
			$flds = array();
			$qry = "CREATE TABLE `$this->tablename` (";
			foreach ($this->fields as $fieldName => $fieldData) {
				// check pk
				if (array_key_exists('pk',$fieldData) && $fieldData['pk'] === true) if ($pk === NULL) $pk = $fieldName; else { $pk = FALSE; break; };
				// build field
				$type = getSqlTypeFromFieldType($fieldData['type']);
				$length = empty($fieldData['length']) ? '' : "({$fieldData['length']})";
				if ($fieldData['type'] == ftTIMESTAMP) {
					if (strtolower($fieldData['default']) == 'current_timestamp') $default = " DEFAULT CURRENT_TIMESTAMP";
					else $default = " DEFAULT 0";
				} else
				$default = $fieldData['default'] === NULL ? '' : " DEFAULT '{$fieldData['default']}'";
				$comments = $fieldData['comments'] === NULL ? '' : " COMMENT '{$fieldData['comments']}'";
				$collate = $fieldData['collation'] === NULL ? '' : " COLLATE '{$fieldData['collation']}'";
				$flds[] = "`$fieldName` $type$length {$fieldData['null']}$default {$fieldData['extra']}$comments$collate";
			}
			if ($pk === NULL)		ErrorLog('Must specify a PRIMARY KEY ('.get_class($this).')');
			elseif ($pk === FALSE)	ErrorLog('Cannot assign multiple PRIMARY KEYS ('.get_class($this).')');
			else {
				$flds[] = "PRIMARY KEY (`$pk`)";
				$qry .= join(",\n",$flds)."\n) CHARACTER SET ".SQL_CHARSET_ENCODING." COLLATE ".SQL_COLLATION.";";
				//				echo "$qry\n";
				sql_query($qry,true);
			}
		}
	}
	public function __construct() {/* $this->AddInputDate(); */ $this->_SetupFields(); }
	public function AddInputDate($fieldName = 'input_date') { $this->AddFieldArray($fieldName,ftTIMESTAMP,NULL,array('default'=>'CURRENT_TIMESTAMP')); }
}

?>
