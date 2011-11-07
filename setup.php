<?php

define('CFG_TYPE_TEXT',flag_gen('configType'));
define('CFG_TYPE_PATH',flag_gen('configType'));
define('CFG_TYPE_PASSWORD',flag_gen('configType'));
define('CFG_TYPE_CALLBACK',flag_gen('configType'));

uConfig::AddConfigVar('ERROR_EMAIL','Debug Email Address');
uConfig::AddConfigVar('DB_TYPE','Database Type',NULL,array('mysql'));
uConfig::AddConfigVar('SQL_SERVER','Database Server Address');
uConfig::AddConfigVar('SQL_PORT','Database Port');
uConfig::AddConfigVar('SQL_DBNAME','Database Name');
uConfig::AddConfigVar('SQL_USERNAME','Database Username');
uConfig::AddConfigVar('SQL_PASSWORD','Database Password',NULL,NULL,CFG_TYPE_PASSWORD);

uConfig::AddConfigVar('DEFAULT_CURRENCY','Default Currency');

uConfig::AddConfigVar('FORMAT_DATE','<a target="_blank" href="http://dev.mysql.com/doc/refman/5.1/en/date-and-time-functions.html#function_date-format">Date Format</a>','%d/%m/%Y');
uConfig::AddConfigVar('FORMAT_TIME','<a target="_blank" href="http://dev.mysql.com/doc/refman/5.1/en/date-and-time-functions.html#function_date-format">Time Format</a>','%H:%i:%s');

uConfig::AddConfigVar('admin_user','Admin Username');
uConfig::AddConfigVar('admin_pass','Admin Password',NULL,NULL,CFG_TYPE_PASSWORD);

uConfig::AddConfigVar('TEMPLATE_ADMIN','Admin Template',PATH_REL_CORE.'styles/uCore',array('utopia::GetTemplates',array(false)),CFG_TYPE_CALLBACK|CFG_TYPE_PATH);

uConfig::ReadConfig();

class uConfig {
	static $configVars = array();
	static function AddConfigVar($name,$readable,$default=NULL,$values=NULL,$type=CFG_TYPE_TEXT) {
		if (array_key_exists($name,self::$configVars)) { echo "Config variable $name already added." ; return false;}
		self::$configVars[$name] = array('name'=>$readable,'default'=>$default,'values'=>$values,'type'=>$type);
	}
	static $oConfig = '';
	static function ReadConfig() {
		$arr = array();
		// read config
		if (file_exists(PATH_ABS_CONFIG)) {
			$conf = file_get_contents(PATH_ABS_CONFIG);
			$lines = explode(PHP_EOL,$conf);
			if (!$lines) return $arr;
			array_shift($lines);
			foreach ($lines as $line) {
				if (!$line) continue;
				list($ident,$val) = explode('=',$line);
				$arr[trim($ident)] = trim($val);
			}
		}
		self::$oConfig = $arr;
	}
	static function SaveConfig() {
		$text = "<?php die('Direct access to this file is prohibited.'); ?>".PHP_EOL;
		foreach (self::$configVars as $key => $info) {
			if (!defined($key)) continue;
			$val = constant($key);
			$text .= "$key=$val".PHP_EOL;
		}
		file_put_contents(PATH_ABS_CONFIG,trim($text,PHP_EOL));
		unset($_SESSION['config_edit_authed']);
	}
	static $isDefined = FALSE;
	static function DefineConfig($arr) {
		if (!$arr) $arr = self::$oConfig;
		foreach (self::$configVars as $key => $info) {
			if (!isset($arr[$key])) continue;
			$val = $arr[$key];
			if (!$val && $info['type'] == CFG_TYPE_PASSWORD && isset(self::$oConfig[$key])) {
				$val = self::$oConfig[$key];
			}
			define($key,$val);
		}
//		foreach ($arr as $key => $val) define($key,$val);

		//--  Charset
		define('CHARSET_ENCODING'        , 'utf-8');
		define('SQL_CHARSET_ENCODING'    , 'utf8');
		define('SQL_COLLATION'           , 'utf8_general_ci');

		define("FORMAT_DATETIME"         , FORMAT_DATE.' '.FORMAT_TIME);

		self::$isDefined = TRUE;
	}
	static function ValidateConfig() {
		$showConfig = false;
		foreach (self::$configVars as $key => $info) {
			if (!defined($key)) {
				$showConfig = true;
			}
			$val = defined($key) ? constant($key) : null;
			if (($info['type'] & CFG_TYPE_PASSWORD) && empty($val)) {
				$showConfig = true;
				self::$configVars[$key]['notice'] = "Must not be empty.";
			}
			
			if (($info['type'] & CFG_TYPE_PATH) && !is_dir(PATH_ABS_ROOT.$val)) {
				$showConfig = true;
				self::$configVars[$key]['notice'] = "Must be a valid directory.";
			}
		}

		if ($showConfig) self::ShowConfig();

		$srv = SQL_SERVER.(SQL_PORT !== '' ? ':'.SQL_PORT : '');

		if (mysql_connect($srv,SQL_USERNAME,SQL_PASSWORD) === FALSE)
			self::$configVars['SQL_SERVER']['notice'] = mysql_error();
		elseif (mysql_select_db(SQL_DBNAME) === FALSE)
			self::$configVars['SQL_SERVER']['SQL_DBNAME'] = "Unable to set the default schema. ".mysql_error();

		$changed = false;
		foreach (self::$configVars as $key => $info) {
			if (isset($info['notice'])) self::ShowConfig();
			if (self::$oConfig[$key] !== constant($key)) $changed = true;
		}
		
		if ($changed) {
			self::SaveConfig();
		}		
		
		return true;
	}
	static function ShowConfig() {
		utopia::UseTemplate(TEMPLATE_ADMIN);
		echo '<h1>uCore Configuration</h1>';

		// does login exist?
		if (defined('admin_user') && defined('admin_pass')) {
			// not authed?
			internalmodule_AdminLogin::TryLogin(true);
			if (!internalmodule_AdminLogin::IsLoggedIn(ADMIN_USER) && !isset($_SESSION['config_edit_authed'])) {
				$obj = utopia::GetInstance('internalmodule_AdminLogin');
				$obj->_RunModule();
				utopia::Finish();
			}
		}
	
		echo <<<FIN
<form method="post">
<table>
	<colgroup>
		<col align="right">
		<col style="text-align: left; padding-left: 15px">
	</colgroup>
FIN;
		foreach (self::$configVars as $key => $info) {
			$val = defined($key) ? constant($key) : $info['default'];
			if (isset($info['notice'])) {
				echo '<tr><td style="color:red">'.$info['name'].':<br><span style="font-size:0.8em">'.$info['notice'].'</span></td>';
			} else {
				echo '<tr><td>'.$info['name'].':</td>';
			}
			if (($info['type'] & CFG_TYPE_CALLBACK) && is_callable($info['values'][0])) {
				$info['values'] = call_user_func_array($info['values'][0],$info['values'][1]);
			}
			if (is_array($info['values'])) {
				$assoc = is_assoc($info['values']);
				echo '<td>';
				if ($info['type'] & CFG_TYPE_PATH) echo PATH_REL_ROOT;
				echo '<select name="'.$key.'">';
				foreach ($info['values'] as $k => $v) {
					if ($info['type'] & CFG_TYPE_PATH) $v = str_replace(PATH_ABS_ROOT,'',$v);
					$selected = (($assoc ? $k : $v) == $val) ? ' selected="selected"' : '';
					$selVal = $assoc ? ' value="'.$k.'"' : '';
					echo '<option'.$selected.$selVal.'>'.$v.'</option>';
				}
				echo '</select></td>';
			} else {
				$type = $info['type'] & CFG_TYPE_PASSWORD ? 'password' : 'text';
				$dVal = $info['type'] & CFG_TYPE_PASSWORD ? '' : $val;
				echo '<td>';
				if ($info['type'] & CFG_TYPE_PATH) {
					echo PATH_REL_ROOT;
					$dVal = str_replace(PATH_ABS_ROOT,'',$dVal);
				}
				echo '<input name="'.$key.'" type="'.$type.'" size="40" value="'.$dVal.'"></td>';
			}
			echo '</tr>';
		}
		echo '</table><input name="__config_submit" type="submit" value="Save"></form>';
		utopia::Finish();
	}
}
