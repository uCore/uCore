<?php
define('itNONE'		,'');

//--  InputType
define('itBUTTON'	,'button');
define('itSUBMIT'	,'submit');
define('itRESET'	,'reset');
define('itCHECKBOX'	,'checkbox');
define('itOPTION'	,'option');
define('itPASSWORD'	,'password');
define('itTEXT'		,'text');
define('itTEXTAREA'	,'textarea');
define('itSUGGEST'	,'suggest');
define('itSUGGESTAREA'	,'suggestarea');
define('itCOMBO'	,'combo');
define('itLISTBOX'	,'listbox');
define('itFILE'		,'file');
define('itDATE'		,'date');
define('itTIME'		,'time');
define('itDATETIME'	,'datetime');
//define('itSCAN'		,'scan');
//define('itCUSTOM'	,'~~custom~~');


interface iInput {
	static function DrawInput();
	static function hasValues();
	static function isAssoc();
	static function toMySQL($val);
	static function fromMySQL($val);
}

?>
