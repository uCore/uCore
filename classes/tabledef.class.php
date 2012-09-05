<?php

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

define('TABLE_PREFIX',''); //uConfig::AddConfigVar('TABLE_PREFIX','Table Prefix','');
define('MYSQL_ENGINE','MyISAM'); //uConfig::AddConfigVar('MYSQL_ENGINE','MySQL Engine','InnoDB',array('MyISAM','InnoDB'));

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
	public $index = array();
	public $unique = array();
	public $primary = array();
	public $engine = NULL;
	public $auto_increment = null;
	public abstract function SetupFields();

	private $isDisabled = false;
	public function DisableModule() {
		$this->isDisabled = true;
	}

	public $fieldsSetup = FALSE;
	public function _SetupFields() {
		if ($this->fieldsSetup == TRUE) return;
		$this->fieldsSetup = TRUE;
		
		uEvents::TriggerEvent('BeforeSetupFields',$this);
		$this->SetupFields();
		uEvents::TriggerEvent('AfterSetupFields',$this);
	}

	public function SetPrimaryKey($name, $auto_increment = true) {
		if (!is_array($name)) {
			if (isset($this->fields[$name]) && $this->fields[$name]['type'] == ftNUMBER && $auto_increment && $this->auto_increment === null) {
				$this->fields[$name]['default'] = NULL;
				$this->auto_increment = $name;
			}
			$name = array($name);
		}
		foreach ($name as $k=>$v) {
			if (!isset($this->fields[$v])) { unset($name[$k]); continue; }
		}
		$this->primary = $name;

	}
	public function SetUniqueField($name) {
		if (!is_array($name)) $name = array($name);
		foreach ($name as $k=>$v) if (!isset($this->fields[$v])) unset($name[$k]);
		$this->unique[] = $name;
	}
	public function SetIndexField($name) {
		if (!is_array($name)) $name = array($name);
		foreach ($name as $k=>$v) if (!isset($this->fields[$v])) unset($name[$k]);
		$this->index[] = $name;
	}

	public function GetPrimaryKey() {
		return reset($this->primary);
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

	public function AddField($name, $type, $length=NULL, $default=NULL, $null=SQL_NULL, $collation=SQL_COLLATION, $extra=NULL, $comments=NULL) {
		//$name = strtolower($name);
		$this->fields[$name] = array();
		$field =& $this->fields[$name];

		$field['type'] = $type;
		if ($length == NULL && $type == ftCURRENCY) $length = "10,2";
		if ($length == NULL && $type == ftPERCENT) $length = "5,2";
		$sqltype = getSqlTypeFromFieldType($type);
		$field['length'] = $length;
		$field['collation'] = (!stristr($sqltype, 'binary') && !stristr($sqltype, 'blob')) ? $collation : NULL;
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
		if ($type == ftCURRENCY) {
			$this->AddField($name.'_locale', ftVARCHAR, 25, DEFAULT_LOCALE);
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

	public static $tableChecksum = NULL;
	public static function checksumValid($class,$checksum,$refresh=false) {
		if ($refresh || self::$tableChecksum === NULL) self::$tableChecksum = GetRows(sql_query('SELECT * FROM `__table_checksum`'));
		foreach (self::$tableChecksum as $row) {
			if ($row['name'] == TABLE_PREFIX.$class) return $row['checksum'] === $checksum;
		}
		return FALSE;
	}
	
	private function GetColDef($fieldName) {
		$fieldData = $this->fields[$fieldName];
		$type = getSqlTypeFromFieldType($fieldData['type']);
		$length = empty($fieldData['length']) ? '' : "({$fieldData['length']})";
		if ($fieldData['type'] == ftTIMESTAMP) {
			if (strtolower($fieldData['default']) == 'current_timestamp') $default = " DEFAULT CURRENT_TIMESTAMP";
			else $default = " DEFAULT 0";
		} else
			$default = $fieldData['default'] === NULL ? '' : "DEFAULT '{$fieldData['default']}'";
		$comments = $fieldData['comments'] === NULL ? '' : "COMMENT '{$fieldData['comments']}'";
		$collate = $fieldData['collation'] === NULL ? '' : "COLLATE '{$fieldData['collation']}'";

		$len = '';
		if ($fieldData['type'] == ftTEXT || $fieldData['type'] == ftVARCHAR) $len = "({$fieldData['length']})";

		$auto = '';
		if ($this->auto_increment == $fieldName) {
			$auto = 'AUTO_INCREMENT';
			$default = 'NULL';
		}

		return "`$fieldName` $type$length {$fieldData['null']} $default $auto {$fieldData['extra']} $comments $collate";
	}

	public function Initialise() {
		// create / update table
		// is table already existing?
		if ($this->isDisabled) return;
		$this->_SetupFields();
		if (empty($this->fields)) return;
		if (!$this->engine) $this->engine = MYSQL_ENGINE;

		$this->AddField('__metadata',ftLONGTEXT);

		$oldTable = isset($this->tablename) ? $this->tablename : NULL;
		$this->tablename = TABLE_PREFIX.get_class($this);

		// checksum
		$tableExists = self::TableExists($this->tablename);
		$renamed = false;
		if (!$tableExists) {
			$tableExists = (sql_query('RENAME TABLE '.mysql_real_escape_string($oldTable).' TO '.$this->tablename,true)) ? true : false;
			$renamed = true;
		}

		$checksum = sha1($oldTable.$this->tablename.$this->engine.print_r($this->fields,true).print_r($this->primary,true).print_r($this->unique,true).print_r($this->index,true));
		if (!$tableExists) { // create table
			$this->CreateTable();
		} else {
			// checksum
			if (!$renamed && self::checksumValid(get_class($this),$checksum)) return;

			$fullColumns = sql_query('SHOW FULL COLUMNS FROM `'.$this->tablename.'`',true);
			$fullColumns = GetRows($fullColumns);

			// update table
			$this->RefreshTable($fullColumns);
		}

		sql_query('INSERT INTO `__table_checksum` VALUES (\''.$this->tablename.'\',\''.$checksum.'\') ON DUPLICATE KEY UPDATE `checksum` = \''.$checksum.'\'');
	}

	function RefreshTable($rows) {
		// loop fields
		$pk = NULL;
		$currentPK = NULL;

		$alterArray = array();
		$otherArray = array();
		$alterArray[] = "CHARACTER SET ".SQL_CHARSET_ENCODING." COLLATE ".SQL_COLLATION;

		// lets keep the sql querys to a minimum, get all the rows first, and process them locally.

		$keys = array_keys($this->fields);
		$count = -1;
		foreach ($this->fields as $fieldName => $fieldData) {
			$count++;
			// build field
			$position = $count==0 ? "FIRST" : 'AFTER `'.$keys[$count-1].'`';
			$col_def = $this->GetColDef($fieldName).' '.$position;

			$row = NULL;
			for ($i = 0,$rowCount = count($rows); $i < $rowCount; $i++) // find if field is already in the table
			if (strtolower($rows[$i]['Field']) === strtolower($fieldName)) { $row = $rows[$i]; break; }
			
			if ($row !== NULL) // field exists, "modify" it
				$alterArray[] = "MODIFY $col_def";
			else // field doesnt exist, either hasnt been renamed, or hasnt been created yet. -- NO RENAME YET
				$alterArray[] = "ADD $col_def";

			// timestamps do not set their value correctly for previously created records if the default value is current_timestamp, we must set it now, to the default value
			if ((strtolower($fieldData['type']) == 'timestamp') && (strtolower($fieldData['default']) == 'current_timestamp')) {
				if ($fieldData['null'] == 'null')
				$otherArray[] = "UPDATE `$this->tablename` SET `$fieldName` = NOW() WHERE `$fieldName` IS NULL";
				else
				$otherArray[] = "UPDATE `$this->tablename` SET `$fieldName` = NOW() WHERE `$fieldName` = 0";
				//					sql_query($qry,true);
			}
		}

		// change engine?
		$row = GetRow(sql_query("SHOW TABLE STATUS LIKE '$this->tablename'"));
		if ($row && $row['Engine'] != $this->engine) {
			sql_query("ALTER IGNORE TABLE `$this->tablename` ENGINE={$this->engine}");
		}

		$idx = array(); $unq = array();
		$indexes = GetRows(sql_query("SHOW INDEX FROM `$this->tablename`"));
		foreach ($indexes as $v) {
			if ($v['Key_name'] == 'PRIMARY') {
				$pri[] = $v['Column_name'];
			} else if ($v['Non_unique'] == '0') {
				$unq[$v['Key_name']][] = $v['Column_name'];
			} else {
				$idx[$v['Key_name']][] = $v['Column_name'];
			}
		}
		
		$priDiff = array_merge(array_diff($this->primary,$pri),array_diff($pri,$this->primary));
		if ($priDiff) {
			$alterArray[] = ' DROP PRIMARY KEY, ADD PRIMARY KEY (`'.implode('`,`',$this->primary).'`)';
		}

		// remove indexes that no longer exist
		if ($diff = self::keyDiff($idx,$this->index)) {
			foreach ($diff as $k=>$v) {
				array_unshift($alterArray,"DROP INDEX `$k`");
			}
		}

		// add new indexes
		if ($diff = self::keyDiff($this->index,$idx)) {
			foreach ($diff as $f) {
				$alterArray[] = 'ADD INDEX (`'.implode('`,`',$f).'`)';
			}
		}

		// remove unique indexes that no longer exist
		if ($diff = self::keyDiff($unq,$this->unique)) {
			foreach ($diff as $k=>$v) {
				array_unshift($alterArray,"DROP INDEX `$k`");
			}
		}

		// add new unique indexes
		if ($diff = self::keyDiff($this->unique,$unq)) {
			foreach ($diff as $f) {
				$alterArray[] = 'ADD UNIQUE (`'.implode('`,`',$f).'`)';
			}
		}

		array_unshift($otherArray,"ALTER IGNORE TABLE `$this->tablename` ".join(', ',$alterArray).";");
		foreach ($otherArray as $qry) {
			sql_query($qry);
		}
	}
	private static function keyDiff($arr1,$arr2) {
		$diff = array();
		foreach ($arr1 as $k=>$v) {
			foreach ($arr2 as $kk => $vv) {
				if ($v == $vv) continue(2);
			}
			$diff[$k] = $v;
		}
		return $diff;
	}
	
	private function CreateTable() {
		// create table
		$pk = NULL;
		$flds = array();
		$qry = "CREATE TABLE `$this->tablename` (";
		foreach ($this->fields as $fieldName => $fieldData) {
			$flds[] = $this->GetColDef($fieldName);
		}

		$flds[] = 'PRIMARY KEY ('.implode(',',$this->primary).')';
		if ($this->index) $flds[] = ' INDEX ('.implode(',',$this->index).')';
		if ($this->unique) $flds[] = ' UNIQUE ('.implode(',',$this->unique).')';

		$qry .= join(",\n",$flds)."\n) CHARACTER SET ".SQL_CHARSET_ENCODING." COLLATE ".SQL_COLLATION.";";
		//echo "$qry\n";
		sql_query($qry,true);

	}
	public function __construct() {/* $this->AddInputDate(); */ $this->_SetupFields(); }
	public function AddInputDate($fieldName = 'input_date') { $this->AddFieldArray($fieldName,ftTIMESTAMP,NULL,array('default'=>'CURRENT_TIMESTAMP')); }

	public function UpdateField($fieldName,$newValue,&$pkVal=NULL,$fieldType=NULL) {
		//AjaxEcho('//'.str_replace("\n",'',get_class($this)."@UpdateField($fieldName,,$pkVal)\n"));
		if ($fieldType === NULL) $fieldType = $this->fields[$fieldName]['type'];
		
		if (is_array($newValue))
			$newValue = json_encode($newValue);
		
		if ($fieldType != ftRAW) $newValue = mysql_real_escape_string($newValue);
		if ($newValue) switch ($fieldType) {      //"STR_TO_DATE('$newValue','".FORMAT_DATE."')"; break;
			case ftRAW: break;
			case ftDATE:		$newValue = $newValue == '' ? 'NULL' : "(STR_TO_DATE('".fixdateformat($newValue)."','".FORMAT_DATE."'))"; break;
			case ftTIME:		$newValue = $newValue == '' ? 'NULL' : "(STR_TO_DATE('$newValue','".FORMAT_TIME."'))"; break;
			case ftDATETIME:	// datetime
			case ftTIMESTAMP:	$newValue = $newValue == '' ? 'NULL' : "(STR_TO_DATE('$newValue','".FORMAT_DATETIME."'))"; break;
			case ftCURRENCY:	// currency
			case ftPERCENT:		// percent
			case ftFLOAT:		// float
			case ftDECIMAL:		$newValue = floatval(preg_replace('/[^0-9\.-]/','',$newValue)); break;
			case ftBOOL:		// bool
			case ftNUMBER:		$newValue = ($newValue==='' ? '' : intval(preg_replace('/[^0-9\.-]/','',$newValue))); break;
		}

		if ($newValue === '' || $newValue === NULL)
			$newValue = 'NULL';
		else {
			$dontQuoteTypes = array(ftRAW,ftDATE,ftTIME,ftDATETIME,ftTIMESTAMP,ftCURRENCY,ftPERCENT,ftFLOAT,ftDECIMAL,ftBOOL,ftNUMBER);
			if (!in_array($fieldType,$dontQuoteTypes)) {
				$newValue = "'$newValue'";
			}
		}

		$updateQry = array();

		if ($pkVal === NULL) {
			$query = 'INSERT INTO `'.$this->tablename.'` (`'.$fieldName.'`) VALUES ('.$newValue.')';
		} else {
			$query = 'UPDATE `'.$this->tablename.'` SET `'.$fieldName.'` = '.$newValue.' WHERE `'.$this->GetPrimaryKey().'` = \''.$pkVal.'\'';
		}
		
		sql_query($query);
		if ($fieldName == $this->GetPrimaryKey()) {
			// this allows us to get the real evaluated value of the new primary key
			$row = GetRow(sql_query('SELECT '.$newValue.' AS new_pk'));
			$pkVal = $row['new_pk'];
		}
		elseif ($pkVal === NULL) $pkVal = mysql_insert_id();
	}
}
