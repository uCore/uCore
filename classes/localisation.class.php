<?php

define('LOCALE_LIST_TYPE_LANG_TERR',flag_gen('LOCALE_LIST_TYPE'));
define('LOCALE_LIST_TYPE_LANG',flag_gen('LOCALE_LIST_TYPE'));
define('LOCALE_LIST_TYPE_TERR',flag_gen('LOCALE_LIST_TYPE'));
define('LOCALE_LIST_TYPE_CURR',flag_gen('LOCALE_LIST_TYPE'));
define('LOCALE_LIST_TYPE_CURR_SYMBOL',flag_gen('LOCALE_LIST_TYPE'));

class uLocale implements ArrayAccess {
	public static $locale_cache = array();
	public static $locale_limit = NULL;
	public static $locale_win = array(
		'chinese'=>array('lang'=>'Chinese','terr'=>'China'),
		'chs'=>array('lang'=>'Chinese','terr'=>'Simplified'),
		'cht'=>array('lang'=>'Chinese','terr'=>'Traditional'),
		'csy'=>array('lang'=>'Chech','terr'=>'Czech Republic'),
		'dan'=>array('lang'=>'Danish','terr'=>'Denmark'),
		'nld'=>array('lang'=>'Dutch','terr'=>'Holland'),
		'nlb'=>array('lang'=>'Dutch','terr'=>'Belgium'),
		'ena'=>array('lang'=>'English','terr'=>'Austrailia'),
		'enc'=>array('lang'=>'English','terr'=>'Canada'),
		'enz'=>array('lang'=>'English','terr'=>'New Zealand'),
		'eng'=>array('lang'=>'English','terr'=>'United Kingdom'),
		'enu'=>array('lang'=>'English','terr'=>'United States'),
		'fin'=>array('lang'=>'Finnish','terr'=>'Finland'),
		'fra'=>array('lang'=>'French','terr'=>'France'),
		'frb'=>array('lang'=>'French','terr'=>'Belgium'),
		'frc'=>array('lang'=>'French','terr'=>'Canada'),
		'frs'=>array('lang'=>'French','terr'=>'Switzerland'),
		'deu'=>array('lang'=>'German','terr'=>'Germany'),
		'dea'=>array('lang'=>'German','terr'=>'Austria'),
		'des'=>array('lang'=>'German','terr'=>'Switzerland'),
		'ell'=>array('lang'=>'Greek','terr'=>'Greece'),
		'hun'=>array('lang'=>'Hungarian','terr'=>'Hungary'),
		'isl'=>array('lang'=>'Icelandic','terr'=>'Iceland'),
		'ita'=>array('lang'=>'Italian','terr'=>'Italy'),
		'its'=>array('lang'=>'Italian','terr'=>'Switzerland'),
		'jpn'=>array('lang'=>'Japanese','terr'=>'Japan'),
		'kor'=>array('lang'=>'Korean','terr'=>'Korea'),
		'norwegian'=>array('lang'=>'Norwegian','terr'=>'Norway'),
		'nor'=>array('lang'=>'Norwegian','terr'=>'Bokmål'),
		'non'=>array('lang'=>'Norwegian','terr'=>'Nynorsk'),
		'plk'=>array('lang'=>'Polish','terr'=>'Poland'),
		'ptg'=>array('lang'=>'Portuguese','terr'=>'Portugal'),
		'ptb'=>array('lang'=>'Portuguese','terr'=>'Brazil'),
		'rus'=>array('lang'=>'Russian','terr'=>'Russia'),
		'sky'=>array('lang'=>'Slovak','terr'=>'Slovakia'),
		'esp'=>array('lang'=>'Spanish','terr'=>'Spain'),
		'esm'=>array('lang'=>'Spanish','terr'=>'Mexico'),
		'esn'=>array('lang'=>'Spanish','terr'=>'Modern'),
		'sve'=>array('lang'=>'Swedish','terr'=>'Sweden'),
		'trk'=>array('lang'=>'Turkish','terr'=>'Turkey'),
	);
	private static function GetLocaleCache() {
		if (self::$locale_cache) return self::$locale_cache;
		exec('locale -av',$output);
		
		$locales = array();
		$output = utf8_encode(implode("\n",$output));
		$blocks = explode("\n\n",$output);
		foreach ($blocks as $block) {
			if (!preg_match('/locale: ([a-z_]+)\.utf8\s/iu',$block,$match)) continue; $code = $match[1];
			if (!preg_match('/language \| (.+)/iu',$block,$match)) continue; $lang = $match[1];
			if (!preg_match('/territory \| (.+)/iu',$block,$match)) continue; $terr = $match[1];
			if ($code === $lang.'_'.$terr) continue;
			$locales[$code] = array('code'=>$code,'lang'=>$lang,'terr'=>$terr);
		}
		if (!$locales) $locales = self::$locale_win;
		
		$old = setlocale(LC_ALL,0);
		foreach ($locales as $code => $locale) {
			if (setlocale(LC_ALL,self::GetLocaleTestArray($code)) === FALSE) continue;
			$locales[$code] += localeconv();
		}
		setlocale(LC_ALL,$old);
		self::$locale_cache = $locales;
		return self::$locale_cache;
	}
	public static $defaultFormat = '%i (%l, %t)';
	public static function ListLocale($format='',$keyFormat='%C') {
		if ($format === '') $format = self::$defaultFormat;
		$locales = self::GetLocaleCache();
		
		$return = array();
		// get requested format
		foreach ($locales as $code => $locale) {
			// limit
			$allow = true;
			if (self::$locale_limit) foreach (self::$locale_limit as $limit) {
				$allow = false;
				if (preg_match('/'.preg_quote($limit,'/').'/ui',$code)) { $allow = true; break; }
				if (preg_match('/'.preg_quote($limit,'/').'/ui',$locale['lang'])) { $allow = true; break; }
				if (preg_match('/'.preg_quote($limit,'/').'/ui',$locale['terr'])) { $allow = true; break; }
				if (preg_match('/'.preg_quote($limit,'/').'/ui',$locale['int_curr_symbol'])) { $allow = true; break; }
				if (preg_match('/'.preg_quote($limit,'/').'/ui',$locale['currency_symbol'])) { $allow = true; break; }
			}
			if (!$allow) continue;
			
			$key = self::localef($keyFormat,$locale);
			$return[$key] = self::localef($format,$locale);
		}
		
		asort($return);

		return $return;
	}
	private static function localef($format,$locale) {
		if ($format == NULL) return $locale;
		return str_replace(array('%l','%t','%i','%c','%C'),array($locale['lang'],$locale['terr'],$locale['int_curr_symbol'],$locale['currency_symbol'],$locale['code']),$format);
	}
	public static function LimitLocale($limit=array()) {
		self::$locale_limit = $limit;
	}
	public static function ResetLocale() {
		setlocale(LC_ALL, self::GetLocaleTestArray(DEFAULT_LOCALE));
	}
	public static function GetLocaleTestArray($locale) {
		$arr = array();
		$arr[] = $locale.'.utf8';
		$arr[] = $locale.'.UTF-8';
		$arr[] = $locale.'.UTF8';
		
		$arr[] = $locale;
		return $arr;
	}

	private static $localeArray = array();
	public static function &GetLocale($locale = NULL) {
		$locale = $locale ? $locale : self::FindLocale();
		if (!isset(self::$localeArray[$locale])) self::$localeArray[$locale] = new uLocale($locale);
		return self::$localeArray[$locale];
	}

	public function LocaleExists($locale,$string = null) {
		if ($string) return isset(self::$localeArray[$locale][$string]);
		return isset(self::$localeArray[$locale]);
	}
	// get selected language
	public function FindLocale($string = null) {
		// use specified language
		if (isset($_SESSION['locale'])) $locale = $_SESSION['locale'];
		if (self::LocaleExists($locale,$string)) return $locale;

		// use Accept-Language header
		$localeList = explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
		foreach ($localeList as $locale) {
			$locale = trim($locale);
			if (self::LocaleExists($locale,$string)) return $locale;

			// use Accept-Language broad locale
			if (strpos($locale,'-') !== FALSE) $locale = substr($locale,0,strpos($locale,'-'));
			if (self::LocaleExists($locale,$string)) return $locale;

			// use another subset of Accept-Loanguage locale
			$locales = self::ListLocales();
			$locLen = strlen($locale);
			foreach ($locales as $loc) {
				if (substr($loc,0,$locLen) == $locale) $locale = $loc;
				if (self::LocaleExists($locale,$string)) return $locale;
			}
		}

		// use english
		return 'en';
	}
	// override the $_SERVER['HTTP_ACCEPT_LANGUAGE'] setting
	public function SetLocale($locale) {
		$_SESSION['locale'] = $locale;
	}
	public function ListLocales() {
		return array_keys(self::$localeArray);
	}

	public function __construct($locale) {
		$this->locale = $locale;
	}
	private $localisations = array();
	public function offsetGet($key){
		$key = strtolower($key);
		if (!array_key_exists($key,$this->localisations)) {
			$locale = self::FindLocale($key);
			if ($locale == $this->locale) return $key;
			$L = self::GetLocale($locale);
			return $L[$key];
		}
		if ($this->localisations[$key] === true) return $key;
		return $this->localisations[$key];
	}
	public function offsetSet($key,$val) {
		if ($val === TRUE) $val = $key;
		$key = strtolower($key);
		$this->localisations[$key] = $val;
	}
	public function offsetExists($key) {
		return array_key_exists($key,$this->localisations);
	}
	public function offsetUnset($key) {
		unset($this->localisations[$key]);
	}
	
	public static function InitLocale() {
		uLocale::ResetLocale();
		if (self::$locale_limit === NULL) uLocale::LimitLocale(array(DEFAULT_LOCALE));
	}
}
uConfig::AddConfigVar('DEFAULT_LOCALE','Default Locale','en_GB',uLocale::ListLocale());
uEvents::AddCallback('ConfigDefined','uLocale::InitLocale');