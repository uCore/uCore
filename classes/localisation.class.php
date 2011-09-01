<?php

class uLocale implements ArrayAccess {
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
}
