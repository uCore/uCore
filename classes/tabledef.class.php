<?php

// text
define('ftNONE'				,'');
define('ftVARCHAR'			,'varchar');
define('ftTEXT'				,'text');
define('ftMEDIUMTEXT'			,'mediumtext');
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

interface iLinkTable {}

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
		case ftBOOL:
			return 'tinyint';
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
	protected $customscript = '';
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
			$name = (array)$name;
		}
		foreach ($name as $k=>$v) {
			if (!isset($this->fields[$v])) { unset($name[$k]); continue; }
			$this->fields[$v]['null'] = SQL_NOT_NULL;
		}
		$this->primary = $name;
	}
	public function SetUniqueField($name) {
		$name = (array)$name;
		foreach ($name as $k=>$v) {
			if (!isset($this->fields[$v])) { unset($name[$k]); continue; }
			$this->fields[$v]['null'] = SQL_NOT_NULL;
		}
		$this->unique[] = $name;
	}
	public function SetIndexField($name) {
		$name = (array)$name;
		foreach ($name as $k=>$v) {
			if (!isset($this->fields[$v])) { unset($name[$k]); continue; }
		}
		$this->index[] = $name;
	}

	public function GetPrimaryKey() {
		return reset($this->primary);
	}
	
	public function IsIndex($field) {
		if ($this->primary == $field) return true;
		if (is_array($this->primary) && array_search($field,$this->primary) !== FALSE) return true;
		foreach ($this->index as $f) {
			if ($f == $field) return true;
			if (is_array($f) && array_search($field,$f) !== FALSE) return true;
		}
		foreach ($this->unique as $f) {
			if ($f == $field) return true;
			if (is_array($f) && array_search($field,$f) !== FALSE) return true;
		}
		return false;
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
		if ($length == NULL && $type == ftNUMBER) $length = "11";
		if ($type == ftBOOL) $length = "1";
		$sqltype = getSqlTypeFromFieldType($type);

		$zeroIfNull = array_flip(array(ftNUMBER,ftBOOL,ftDECIMAL,ftPERCENT,ftCURRENCY,ftTIMESTAMP,ftTIME));
		$emptyIfNull = array();
		if ($default === NULL && isset($zeroIfNull[$sqltype]))
			$default = 0;
		if ($default === NULL && in_array($sqltype,$emptyIfNull))
			$default = '';

		$field['length'] = $length;
		$field['null'] = $null;
		$field['default'] = $default;
		$field['extra'] = $extra;
		$field['collation'] = (!stristr($sqltype, 'binary') && !stristr($sqltype, 'blob')) ? $collation : NULL;
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
		if ($refresh || self::$tableCache === NULL) {
			$stm = database::query('SHOW TABLES');
			self::$tableCache = $stm->fetchAll();
		}
		foreach (self::$tableCache as $tbl) {
			if ($tbl['Tables_in_'.SQL_DBNAME] == $tableName) return TRUE;
		}
		return FALSE;
	}

	public static $tableChecksum = NULL;
	public static function checksumValid($class,$checksum,$refresh=false) {
		if ($refresh || self::$tableChecksum === NULL) {
			$stm = database::query('SELECT * FROM `__table_checksum`');
			self::$tableChecksum = $stm->fetchAll();
		}
		foreach (self::$tableChecksum as $row) {
			if ($row['name'] == TABLE_PREFIX.$class) return $row['checksum'] === $checksum;
		}
		return FALSE;
	}
	
	private function GetColDef($fieldName,$current = null,$position = null) {
		$fieldData = $this->fields[$fieldName];
		$type = getSqlTypeFromFieldType($fieldData['type']).(empty($fieldData['length']) ? '' : "({$fieldData['length']})");
		if ($fieldData['type'] == ftTIMESTAMP) {
			if (strtolower($fieldData['default']) == 'current_timestamp') $default = 'CURRENT_TIMESTAMP';
			else $default = "0000-00-00 00:00:00";
		} else
			$default = $fieldData['default'] === NULL ? NULL : "{$fieldData['default']}";
		$null = $fieldData['null'];
		$comments = $fieldData['comments'];
		$collate = $fieldData['collation'];
		if (!in_array($fieldData['type'],array(ftVARCHAR,ftTEXT,ftLONGTEXT))) $collate = null;

		$extra = $fieldData['extra'];
		if ($this->auto_increment == $fieldName) {
			$extra = trim('AUTO_INCREMENT '.$extra);
			$default = null;
		}
		
		$changed = true;
		if ($current) {
			$changed = false;
			if ($type != $current['Type']) $changed = true;
			if ($null == 'null' && $current['Null'] != 'YES') $changed = true;
			if ($null != 'null' && $current['Null'] != 'NO') $changed = true;
			if (strcasecmp($default,$current['Default'])!==0) $changed = true;
			if ($comments != $current['Comment']) $changed = true;
			if (strcasecmp($extra, $current['Extra'])!==0) $changed = true;
			if ($collate != $current['Collation']) $changed = true;
		}
		if ($default !== NULL) {
			if ($default !== 'CURRENT_TIMESTAMP' && !is_numeric($default)) $default = "'$default'";
			$default = "DEFAULT $default";
		}
		if ($comments) $comments = "COMMENT '$comments'";
		if ($collate) $collate = "COLLATE '$collate'";

		if (!$changed) return '';
		$data = array_filter(array($type,$null,$default,$extra,$comments,$collate,$position));

		return "`$fieldName` ".implode(' ',$data);
	}

	public function Initialise() {
		// create / update table
		// is table already existing?
		if ($this->isDisabled) return;
		$this->_SetupFields();
		if (empty($this->fields)) return;
		if (!$this->engine) $this->engine = MYSQL_ENGINE;

		$oldTable = isset($this->tablename) ? $this->tablename : NULL;
		$this->tablename = TABLE_PREFIX.get_class($this);

		// checksum
		$tableExists = self::TableExists($this->tablename);
		$renamed = false;
		if (!$tableExists && $oldTable && self::TableExists($oldTable)) {
			$stm = database::query('RENAME TABLE `'.$oldTable.'` TO `'.$this->tablename.'`');
			$tableExists = ($stm->rowCount) ? true : false;
			$renamed = true;
		}

		$checksum = utopia::checksum(array($oldTable,$this->tablename,$this->engine,$this->fields,$this->primary,$this->unique,$this->index,$this->customscript));

		if (!$tableExists) { // create table
			$this->CreateTable();
		} else {
			// checksum
			if (!$renamed && self::checksumValid(get_class($this),$checksum)) return;

			$stm = database::query('SHOW FULL COLUMNS FROM `'.$this->tablename.'`');
			if ($stm) $fullColumns = $stm->fetchAll();

			// update table
			$this->RefreshTable($fullColumns);
		}

		if ($this->customscript) database::query($this->customscript);
		database::query('INSERT INTO `__table_checksum` VALUES (?,?) ON DUPLICATE KEY UPDATE `checksum` = ?',array($this->tablename,$checksum,$checksum));
	}

	function RefreshTable($rows) {
		// loop fields
		$pk = NULL;
		$currentPK = NULL;

		$alterArray = array();
		$otherArray = array();

		// lets keep the sql querys to a minimum, get all the rows first, and process them locally.
		$keys = array_keys($this->fields);
		$count = -1;
		foreach ($this->fields as $fieldName => $fieldData) {
			$count++;

			$row = NULL;
			for ($i = 0,$rowCount = count($rows); $i < $rowCount; $i++) // find if field is already in the table
			if (strtolower($rows[$i]['Field']) === strtolower($fieldName)) { $row = $rows[$i]; break; }
			
			// build field
			$position = null;
			if ($count !== $i) $position = $count==0 ? 'FIRST' : 'AFTER `'.$keys[$count-1].'`';
			$col_def = $this->GetColDef($fieldName,$row,$position);
			if (!$col_def) continue;
			
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
			}
		}

		// change engine?
		$stm = database::query('SHOW TABLE STATUS LIKE ?',array($this->tablename));
		if ($stm && ($row = $stm->fetch()) && $row['Engine'] != $this->engine) {
			database::query("ALTER IGNORE TABLE `$this->tablename` ENGINE={$this->engine}");
		}

		// update all indexes and keys
		$idx = array(); $unq = array();
		$indexes = database::query("SHOW INDEX FROM `$this->tablename`")->fetchAll();
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
		
		if ($alterArray) {
			array_unshift($alterArray,"CHARACTER SET ".SQL_CHARSET_ENCODING." COLLATE ".SQL_COLLATION);
			array_unshift($otherArray,"ALTER IGNORE TABLE `$this->tablename` ".join(', ',$alterArray).";");
		}
		if (!$otherArray) return;

		foreach ($otherArray as $qry) {
			database::query($qry);
		}

		uEvents::AddCallback('AfterInit',array($this,'TableChangedCallback'));
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

		foreach ($this->index as $f) {
			$flds[] = ' INDEX (`'.implode('`,`',$f).'`)';
		}
		foreach ($this->unique as $f) {
			$flds[] = ' UNIQUE (`'.implode('`,`',$f).'`)';
		}

		$qry .= join(",\n",$flds)."\n) CHARACTER SET ".SQL_CHARSET_ENCODING." COLLATE ".SQL_COLLATION.";";
		//echo "$qry\n";
		database::query($qry);

		uEvents::AddCallback('AfterInit',array($this,'TableCreatedCallback'));
	}
	public function TableCreatedCallback() {
		uEvents::TriggerEvent('TableCreated',$this);
	}
	public function TableChangedCallback() {
		uEvents::TriggerEvent('TableChanged',$this);
	}
	
	public function __construct() {/* $this->AddInputDate(); */ $this->_SetupFields(); }
	public function AddInputDate($fieldName = 'input_date',$update=false) {
		$this->AddFieldArray($fieldName,ftTIMESTAMP,NULL,array('default'=>'CURRENT_TIMESTAMP'));
		if ($update) $this->SetFieldProperty($fieldName,'extra','ON UPDATE CURRENT_TIMESTAMP');
	}

	public function UpdateField($fieldName,$newValue,&$pkVal=NULL,$fieldType=NULL) {
		uEvents::TriggerEvent('BeforeUpdateField',$this,array($fieldName,$newValue,&$pkVal,$fieldType));
		//AjaxEcho('//'.str_replace("\n",'',get_class($this)."@UpdateField($fieldName,,$pkVal)\n"));
		if ($fieldType === NULL) $fieldType = $this->fields[$fieldName]['type'];
		
		if (is_array($newValue)) $newValue = json_encode($newValue);
		
		if ($newValue) switch ($fieldType) {
			case ftRAW: break;
			case ftDATE:
			case ftTIME:
			case ftDATETIME:	// datetime
			case ftTIMESTAMP:
				$parsed = strptime($newValue,FORMAT_TIME);
				if ($parsed===FALSE) $parsed = strptime($newValue,FORMAT_DATE);
				if ($parsed===FALSE) $parsed = strptime($newValue,FORMAT_DATETIME);
				if ($parsed!==FALSE) $parsed = mktime($parsed['tm_hour'], $parsed['tm_min'], $parsed['tm_sec'], 1 , $parsed['tm_yday'] + 1, $parsed['tm_year'] + 1900); 
				else $parsed = strtotime($newValue);
				$newValue = $newValue == '' ? 'NULL' : date('Y-m-d H:i:s',$parsed); break;
			case ftFLOAT:		// float
			case ftDECIMAL:		$l=setlocale(LC_ALL,'en_US'); $newValue = floatval($newValue); setlocale(LC_ALL,$l); break;
			case ftBOOL:		// bool
			case ftPERCENT:		// percent
			case ftCURRENCY:	// currency
			case ftNUMBER:		$newValue = ($newValue==='' ? '' : preg_replace('/[^0-9\.-]/','',$newValue)); break;
		}

		if (($newValue === '' || $newValue === NULL) && $this->GetFieldProperty($fieldName,'null') !== SQL_NOT_NULL)
			$newValue = NULL;

		$updateQry = array();

		$raw = $fieldType == ftRAW;
		$args = array();
		if ($pkVal === NULL) {
			$query = 'INSERT INTO '.$this->tablename.' (`'.$fieldName.'`) VALUES ('.($raw?$newValue:'?').')';
			if (!$raw) $args[] = $newValue;
		} else {
			$query = 'UPDATE '.$this->tablename.' SET `'.$fieldName.'` = '.($raw?$newValue:'?').' WHERE `'.$this->GetPrimaryKey().'` = ?';
			if (!$raw) $args[] = $newValue;
			$args[] = $pkVal;
		}
		
		database::query($query,$args);
		if ($fieldName == $this->GetPrimaryKey() && $newValue !== NULL) {
			// this allows us to get the real evaluated value of the new primary key
			$stm = database::query('SELECT ? AS new_pk',array($newValue));
			$row = $stm->fetch();
			$pkVal = $row['new_pk'];
		} elseif ($pkVal === NULL) $pkVal = database::connect()->lastInsertId();
		uEvents::TriggerEvent('AfterUpdateField',$this,array($fieldName,$newValue,&$pkVal,$fieldType));
	}
	public function LookupRecord($pkVal) {
		$stm = database::query('SELECT * FROM '.$this->tablename.' WHERE '.$this->GetPrimaryKey().' = ?',array($pkVal));
		$row = $stm->fetch();
		return $row;
	}
}
