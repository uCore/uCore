<?php

utopia::AddTemplateParser('gravatar','uGravatar::GetImage');
class uGravatar {
	public static function GetImageField($originalValue,$pkVal,$value,$rec,$fieldName) {
		if (!$originalValue) return '';
		$o =& utopia::GetInstance($rec['_module']);
		$size = $o->GetFieldProperty($fieldName,'size');
		
		return self::GetImage($originalValue,$size);
	}
	public static function GetImage($email,$size=null) {
		if (!$email) return '';
		if ($size === null) $size = 40;
		return '<img src="http://www.gravatar.com/avatar/'.self::GetHash($email).'?d=mm&s='.$size.'" />';
	}
	public static function GetProfile($email) {
		try {
			$profile = unserialize(curl_get_contents('http://www.gravatar.com/'.self::GetHash($email).'.php'));
		} catch (Exception $e) { return FALSE; }
		if (!is_array($profile) || !isset($profile['entry'])) return FALSE;
		return $profile;
	}
	
	public static function GetHash($email) {
		return md5(strtolower(trim($email)));
	}
}
