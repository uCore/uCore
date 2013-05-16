<?php

interface iCrypt {
	public static function CanUse();
	public static function Encrypt($string,$salt = null);
	public static function Test($string,$digest);
}



class uCryptBlowfish implements iCrypt {
	public static function CanUse() { return (defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH == 1); }
	public static function CreateSalt() {
		return '$2a$06$'.uCrypt::GetRandom(22,'./abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789').'$';
	}
	public static function Encrypt($string,$salt = null) {
		if ($salt === null) $salt = self::CreateSalt();
		return crypt($string,$salt);
	}
	public static function Test($string,$digest) {
		return (crypt($string, $digest) === $digest);
	}
}
uCrypt::RegisterClass('uCryptBlowfish');


class uCryptSHA512 implements iCrypt {
	public static function CanUse() { return (defined('CRYPT_SHA512') && CRYPT_SHA512 == 1); }
	public static function CreateSalt() {
		return '$6$rounds=5000$'.uCrypt::GetRandom(16,'./abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789').'$';
	}
	public static function Encrypt($string,$salt = null) {
		if ($salt === null) $salt = self::CreateSalt();
		return crypt($string,$salt);
	}
	public static function Test($string,$digest) {
		return (crypt($string, $digest) === $digest);
	}
}
uCrypt::RegisterClass('uCryptSHA512');



class uCryptSHA256 implements iCrypt {
	public static function CanUse() { return (defined('CRYPT_SHA256') && CRYPT_SHA256 == 1); }
	public static function CreateSalt() {
		return '$5$rounds=5000$'.uCrypt::GetRandom(16,'./abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789').'$';
	}
	public static function Encrypt($string,$salt = null) {
		if ($salt === null) $salt = self::CreateSalt();
		return crypt($string,$salt);
	}
	public static function Test($string,$digest) {
		return (crypt($string, $digest) === $digest);
	}
}
uCrypt::RegisterClass('uCryptSHA256');



class uCryptMD5 implements iCrypt {
	public static function CanUse() { return true; }
	public static function CreateSalt() {
		return '$1$'.uCrypt::GetRandom(12,'./abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
	}
	public static function Encrypt($string,$salt = null) {
		if ($salt === null) $salt = self::CreateSalt();
		return crypt($string,$salt);
	}
	public static function Test($string,$digest) {
		if (substr($digest,0,3) !== '$1$') return (md5($string) === $digest);
		return (crypt($string, $digest) === $digest);
	}
}
uCrypt::RegisterClass('uCryptMD5',99999);


class uCryptPlain implements iCrypt {
	public static function CanUse() { return true; }
	public static function Encrypt($string,$salt = null) {
		return $string;
	}
	public static function Test($string,$digest) {
		return $string === $digest;
	}
}
uCrypt::RegisterClass('uCryptPlain',999999);


class uCrypt {
	public static function GetRandom($length,$charset = 'abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789') {
		srand((double)microtime()*1000000); // start the random generator
		$str='';
		for ($i=0;$i<$length;$i++) {
			$str .= substr ($charset, rand() % strlen($charset), 1);
		}
		return $str;
	}
	public static function Encrypt($string) {
		foreach (self::$classes as $class=>$order) {
			$result = call_user_func($class.'::Encrypt',$string);
			if ($result && strlen($result) > 13) return $result;
		}
		// fallback
		return crypt($string);
	}
	public static function Test($string,$digest) {
		foreach (self::$classes as $class=>$order) {
			$result = call_user_func($class.'::Test',$string,$digest);
			if ($result === true) return true;
		}
		return false;
	}
	public static function IsStrongest($string,$digest) { // only works if combination is correct
		reset(self::$classes);
		$class = key(self::$classes);
		$result = call_user_func($class.'::Test',$string,$digest);
		return ($result === true);
	}
	
	private static $classes = array();
	public static function RegisterClass($class, $order=null) {
		if (!call_user_func($class.'::CanUse')) return false;
		if ($order === null) $order = count(self::$classes);
		self::$classes[$class] = $order;
		asort(self::$classes);
		return true;
	}
}