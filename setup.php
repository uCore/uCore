<?php

define('CFG_TYPE_PATH',flag_gen('configType'));
define('CFG_TYPE_PASSWORD',flag_gen('configType'));

uConfig::AddConfigVar('ADMIN_EMAIL','Admin Email');
define('DB_TYPE','mysql');// uConfig::AddConfigVar('DB_TYPE','Database Type',NULL,array('mysql'));
uConfig::AddConfigVar('SQL_SERVER','Database Host');
uConfig::AddConfigVar('SQL_PORT','Database Port',3306);
uConfig::AddConfigVar('SQL_DBNAME','Database Name');
uConfig::AddConfigVar('SQL_USERNAME','Database Username');
uConfig::AddConfigVar('SQL_PASSWORD','Database Password',NULL,NULL,CFG_TYPE_PASSWORD);

uConfig::AddConfigVar('FORMAT_DATE','<a target="_blank" href="http://php.net/manual/en/function.strftime.php">Date Format</a>','%d/%m/%Y');
uConfig::AddConfigVar('FORMAT_TIME','<a target="_blank" href="http://php.net/manual/en/function.strftime.php">Time Format</a>','%H:%M:%S');

uConfig::AddConfigVar('TEMPLATE_ADMIN','Admin Template',PATH_REL_CORE.'themes/admin',array('utopia::GetTemplates',array(false)),CFG_TYPE_PATH);

uConfig::ReadConfig();

class uConfig {
	static $configVars = array();
	static function AddConfigVar($name,$readable,$default=NULL,$values=NULL,$type=NULL) {
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
				list($ident,$val) = explode('=',$line,2);
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
	}
	static $isDefined = FALSE;
	static function DefineConfig() {
		$arr = self::$oConfig;
		if (isset($_SESSION['__config_validate']) && $_SESSION['__config_validate'] && $_POST && isset($_POST['ucore_reconfig'])) {
			$arr = $_POST;
			unset($_SESSION['__config_validate']);
		}

		// upgrade /styles/ to /themes/
		if (isset($arr['TEMPLATE_ADMIN'])) {
			$self = '/'.basename(dirname(__FILE__));
			$arr['TEMPLATE_ADMIN'] = preg_replace('/^'.preg_quote($self.'/styles/','/').'/i',$self.'/themes/',$arr['TEMPLATE_ADMIN']);
		}

		foreach (self::$configVars as $key => $info) {
			if (!isset($arr[$key])) {
				if (!$info['default']) continue;
				$arr[$key] = $info['default'];
			}
			$val = $arr[$key];
			if (!$val && ($info['type'] & CFG_TYPE_PASSWORD) && isset(self::$oConfig[$key])) {
				$val = self::$oConfig[$key];
			}
			define($key,$val);
		}

		define("FORMAT_DATETIME"         , FORMAT_DATE.' '.FORMAT_TIME);

		self::$isDefined = TRUE;
	}
	static $isValid = FALSE;
	static function ValidateConfig() {
		$showConfig = true;
		if (self::$oConfig || $_POST) { // only validate config if there is a config to validate.
			$showConfig = false;
			foreach (self::$configVars as $key => $info) {
				$val = defined($key) ? constant($key) : null;
				if (empty($val)) {
					self::$configVars[$key]['notice'] = "Must not be empty.";
				}
				
				if (($info['type'] & CFG_TYPE_PATH) && !is_dir(PATH_ABS_ROOT.$val)) {
					self::$configVars[$key]['notice'] = "Must be a valid directory.";
				}
				
				if ($key == 'ADMIN_EMAIL' && !preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i',$val)) {
					self::$configVars[$key]['notice'] = "Must be a valid email address.";
				}
			}

			try {
				database::query('SHOW TABLES FROM `'.SQL_DBNAME.'`');
			} catch (Exception $e) {
				self::$configVars['SQL_SERVER']['notice'] = $e->getMessage();
			}
		}

		$changed = false;
		foreach (self::$configVars as $key => $info) {
			if (!defined($key)) $showConfig = true;
			if (isset($info['notice'])) $showConfig = true;
			if (!isset(self::$oConfig[$key]) || self::$oConfig[$key] !== constant($key)) $changed = true;
		}

		if ($showConfig) {
			if (!file_exists(PATH_ABS_CONFIG)) self::ShowConfig();
			self::DownMaintenance();
		}
		
		if ($changed) self::SaveConfig();

		self::$isValid = TRUE;
		return true;
	}
	static function ShowConfig() {
		if (!self::$isValid) {
			$rc = '/'.ltrim(preg_replace('/^'.preg_quote(PATH_REL_ROOT,'/').'/','',PATH_REL_CORE),'/');
			utopia::UseTemplate($rc.'themes/install');
			utopia::SetTitle('uCore Installation');
			echo '<h1>uCore Installation</h1>';
		}

		$frmAction = DEFAULT_FILE;
		echo <<<FIN
<form method="post" action="$frmAction">
<input type="hidden" name="ucore_reconfig" value="true" />
<table>
FIN;
		foreach (self::$configVars as $key => $info) {
			$val = defined($key) ? constant($key) : $info['default'];
			echo '<tr><td class="config-field">'.$info['name'].':</td>';
			if (isset($info['values'][0]) && is_callable($info['values'][0])) {
				$info['values'] = call_user_func_array($info['values'][0],$info['values'][1]);
			}
			echo '<td>';
			if (is_array($info['values'])) {
				$assoc = is_assoc($info['values']);
				echo '<select name="'.$key.'">';
				foreach ($info['values'] as $k => $v) {
					if ($info['type'] & CFG_TYPE_PATH) $v = str_replace(PATH_ABS_ROOT,'',$v);
					$selected = (($assoc ? $k : $v) == $val) ? ' selected="selected"' : '';
					$selVal = $assoc ? ' value="'.$k.'"' : '';
					echo '<option'.$selected.$selVal.'>'.$v.'</option>';
				}
				echo '</select>';
			} else {
				$type = $info['type'] & CFG_TYPE_PASSWORD ? 'password' : 'text';
				$dVal = $info['type'] & CFG_TYPE_PASSWORD ? '' : $val;
				if ($info['type'] & CFG_TYPE_PATH) {
					echo PATH_REL_ROOT;
					$dVal = str_replace(PATH_ABS_ROOT,'',$dVal);
				}
				echo '<input name="'.$key.'" type="'.$type.'" size="40" value="'.$dVal.'">';
			}
			if (isset($info['notice'])) echo '<tr><td></td><td style="color:red;font-size:0.8em;padding-bottom:15px;height:auto;">'.$info['notice'].'</td></tr>';
			echo '</td></tr>';
		}
		$_SESSION['__config_validate'] = true;
		echo '</table><input type="submit" value="Make It So!"></form>';
		if (!self::$isValid) utopia::Finish();
	}
	static function DownMaintenance() {
		$rc = '/'.ltrim(preg_replace('/^'.preg_quote(PATH_REL_ROOT,'/').'/','',PATH_REL_CORE),'/');
		utopia::UseTemplate($rc.'themes/install');

		header("HTTP/1.0 503 Service Unavailable",true,503);
		utopia::SetTitle('Website Down For Maintenance');
		echo '<h1>We Will Be Back Soon</h1>';
		echo '<p>We are currently unavailable while we make upgrades to improve our service to you.  We&#39;ll return very soon.</p>';
		echo '<p>We apologise for the inconvenience and appreciate your patience.<p>';
		echo '<h2>Thank you!</h2>';

		utopia::Finish();
	}
}
