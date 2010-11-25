<?php

define('CFG_TYPE_TEXT',flag_gen('configType'));
define('CFG_TYPE_PATH',flag_gen('configType'));
define('CFG_TYPE_PASSWORD',flag_gen('configType'));

//FlexConfig::AddConfigVar('MODULES_PATH','Module Path',NULL,CFG_TYPE_PATH);
FlexConfig::AddConfigVar('DB_TYPE','Database Type',NULL,array('mysql'));
FlexConfig::AddConfigVar('SQL_SERVER','Database Server Address');
FlexConfig::AddConfigVar('SQL_PORT','Database Port');
FlexConfig::AddConfigVar('SQL_DBNAME','Database Name');
FlexConfig::AddConfigVar('SQL_USERNAME','Database Username');
FlexConfig::AddConfigVar('SQL_PASSWORD','Database Password',NULL,CFG_TYPE_PASSWORD);

//FlexConfig::AddConfigVar('BASE_MODULE','Initial Module'); // if none specified by uuid
/*
$templates = glob(PATH_ABS_TEMPLATES.'*'); // find all templates
if (is_array($templates)) foreach ($templates as $k => $v) {
	if ($v == '.' || $v == '..' || !is_dir($v)) {
		unset($templates[$k]);
		continue;
	}
	$templates[$k] = basename($v);
}
$templates = is_array($templates) ? array_values($templates) : array();
FlexConfig::AddConfigVar('DEFAULT_TEMPLATE','Default Template',NULL,$templates);*/
FlexConfig::AddConfigVar('DEFAULT_CURRENCY','Default Currency');

FlexConfig::AddConfigVar('FORMAT_DATE','<a target="_blank" href="http://dev.mysql.com/doc/refman/5.1/en/date-and-time-functions.html#function_date-format">Date Format</a>','%d %b %Y');
FlexConfig::AddConfigVar('FORMAT_TIME','<a target="_blank" href="http://dev.mysql.com/doc/refman/5.1/en/date-and-time-functions.html#function_date-format">Time Format</a>','%H:%i:%s');

FlexConfig::AddConfigVar('admin_user','Admin Username');
FlexConfig::AddConfigVar('admin_pass','Admin Password',NULL,CFG_TYPE_PASSWORD);

class FlexConfig {
	static $configVars = array();
	static function AddConfigVar($name,$readable,$default=NULL,$type=CFG_TYPE_TEXT) {
		if (array_key_exists($name,self::$configVars)) { echo "Config variable $name already added." ; return false;}
		self::$configVars[$name] = array('name'=>$readable,'default'=>$default,'type'=>$type);
	}
	static function ReadConfig() {
		$arr = array();
		if (file_exists(PATH_ABS_CONFIG)) {
			// read config
			$conf = file_get_contents(PATH_ABS_CONFIG);
			$lines = explode(PHP_EOL,$conf);
			array_shift($lines);
			foreach ($lines as $line) {
				list($ident,$val) = explode('=',$line);
				$arr[trim($ident)] = trim($val);
			}
		}
		return $arr;
	}
	static function SaveConfig($arr) {
		//    if (!array_key_exists('config_submit',$_REQUEST)) return;
		// we have config info
		//   if (file_exists($config_path)) return;
		// no config exists, save now
		$text = "<?php die('Direct access to this file is prohibited.'); ?>".PHP_EOL;
		foreach ($arr as $key => $val) {
			//        if (strtolower($key) == 'config_submit' || strtolower($key) == 'phpsessid') continue;
			$text .= "$key=$val".PHP_EOL;
		}
		file_put_contents(PATH_ABS_CONFIG,trim($text,PHP_EOL));
	}
	static function DefineConfig($arr) {
		foreach ($arr as $key => $val)
		define($key,$val);

		//--  Charset
		define('CHARSET_ENCODING'        , 'utf-8');
		define('SQL_CHARSET_ENCODING'    , 'utf8');
		define('SQL_COLLATION'           , 'utf8_general_ci');

		define("FORMAT_DATETIME"         , FORMAT_DATE.' '.FORMAT_TIME);
	}
	static function ValidateConfig(&$arr) {
		$changed = false;
//		$validFields = array('MODULES_PATH','DB_TYPE','SQL_SERVER','SQL_PORT','SQL_DBNAME','SQL_USERNAME','SQL_PASSWORD',
//	                         'DEFAULT_FILE','BASE_MODULE','STYLE_PATH','DEFAULT_CURRENCY','FORMAT_DATE','FORMAT_TIME',
//	                         'admin_user','admin_pass');
		$validFields = array_keys(self::$configVars);
		foreach ($arr as $key => $val) {
			if (($pos = array_search($key,$validFields)) === FALSE) {
				$changed = true;
				unset($arr[$key]);
				continue;
			}
			unset($validFields[$pos]);

			if (self::$configVars[$key]['type'] == CFG_TYPE_PATH) {
				if (!is_dir(PATH_ABS_ROOT.$val))
					$failure .= self::$configVars[$key]['name']." must be a valid directory.\n";
				else
					$arr[$key] = rtrim($arr[$key],'/').'/';
			}
		}
		$failure = '';
		if (count($validFields) > 0) $failure .= "Some fields are missing:\n".join("\n",$validFields)."\n\n";

    if (array_key_exists('SQL_SERVER',$arr) && $arr['SQL_SERVER'] && array_key_exists('SQL_USERNAME',$arr) && $arr['SQL_USERNAME'] && array_key_exists('SQL_PASSWORD',$arr) && $arr['SQL_PASSWORD'] && array_key_exists('SQL_DBNAME',$arr) && $arr['SQL_DBNAME']) {
  		$srv = $arr['SQL_SERVER'].(array_key_exists('SQL_PORT',$arr) ? ':'.$arr['SQL_PORT'] : '');

  		if (mysql_connect($srv,$arr['SQL_USERNAME'],$arr['SQL_PASSWORD']) === FALSE)
  			$failure .= "Unable to connect to server. ".mysql_error()."\n";
  		elseif (mysql_select_db($arr['SQL_DBNAME']) === FALSE)
  			$failure .= "Unable to set the default schema. ".mysql_error()."\n";
    } else {
      $failure .= "Missing SQL server information.\n";
    }
		if ($failure == '') {
			if (!$changed) return true;
			return 2;
		}
		echo nl2br($failure);
		return false;
	}
	static function ShowConfig($configArr = NULL) {
		if (!$configArr) $configArr = FlexConfig::ReadConfig();
		echo <<<FIN
<form method="post">
<table>
	<colgroup>
		<col align="right">
		<col style="text-align: left; padding-left: 15px">
	</colgroup>
FIN;
		foreach (self::$configVars as $key => $info) {
			$val = isset($configArr[$key]) && $configArr[$key] ? $configArr[$key] : $info['default'];
			echo "<tr><td>{$info['name']}:</td>";
			if (is_array($info['type'])) {
				$assoc = is_assoc($info['type']);
				echo '<td><select name="'.$key.'">';
				foreach ($info['type'] as $k => $v) {
					$selVal = $assoc ? ' value="'.$k.'"' : '';
					echo '<option'.$selVal.'>'.$v.'</option>';
				}
				echo '</select></td>';
			} else {
				$type = $info['type']==CFG_TYPE_PASSWORD ? 'password' : 'text';
				$dVal = $info['type']==CFG_TYPE_PASSWORD ? '' : $val;
				echo '<td>';
				if ($info['type'] == CFG_TYPE_PATH) echo PATH_REL_ROOT;
				echo '<input name="'.$key.'" type="'.$type.'" size="40" value="'.$dVal.'"></td>';
			}
			echo '</tr>';
		}
		echo '</table><input name="__config_submit" type="submit" value="Save"></form>';
	}
}
?>
