<?php

class tabledef_ModOpts extends uTableDef {
	public $tablename = 'internal_modopts';

	public function SetupFields() {
		// add all the fields in here
		// AddField($name, $type, $length, $collation='', $attributes='', $null='not null', $default='', $extra='', $comments='')
		// SetPrimaryKey($name);

		$this->AddField('ident',ftVARCHAR,100);
		$this->AddField('group',ftVARCHAR,100);
		$this->AddField('name',ftVARCHAR,100);
		$this->AddField('value',ftTEXT);
		$this->SetPrimaryKey('ident');
	}
}

utopia::AddTemplateParser('option','modOpts::GetOption','.+');
class modOpts extends uListDataModule implements iAdminModule {
	public function GetTitle() { 
		$f =& $this->FindFilter('group');
		if ($f['value']) return $f['value'];
		return 'Options';
	}
	public function GetSortOrder() { return 10000-2; }
	public function GetOptions() { return ALLOW_EDIT | LIST_HIDE_HEADER; }
	public function GetTabledef() { return 'tabledef_ModOpts'; }
	public function SetupFields() {
		$this->CreateTable('opts');
		$this->AddField('ident','ident','opts');
		$this->SetFieldOptions('ident',ALLOW_ADD);
		$this->AddField('group','group','opts');
		$this->AddField('name','name','opts','Name');
		$this->AddField('value','value','opts','Value',itTEXT);
		$this->AddFilter('name',ctISNOT,itNONE,'NULL');
		$this->AddFilter('group',ctEQ,itNONE);
		$this->AddOrderBy('name');
	}
	public function SetupParents() {}
	public static function Initialise() {
		self::AddParent('/');
		self::AddOption('site_name','Site Name');
		self::AddOption('site_url','Site URL',NULL,'http://'.$_SERVER['HTTP_HOST'].PATH_REL_ROOT);
	}
	public function RunModule() {
		$groups = database::query('SELECT DISTINCT `group` FROM tabledef_ModOpts WHERE `group` IS NOT NULL ORDER BY (`group` = ?) DESC, `group` ASC',array('Site Options'))->fetchAll();

		$ids = array_keys(self::$types);
		$this->AddFilter('ident',ctIN,itNONE,$ids);

		$f =& $this->FindFilter('group');
		foreach ($groups as $group) {
			$group = $group['group'];
			$f['value'] = $group;
			$this->ShowData();
		}
	}
	public function GetCellData($fieldName, $row, $url = '', $inputTypeOverride=NULL, $valuesOverride=NULL) {
		$pk = $this->GetPrimaryKey();
		if ($fieldName == 'value' && isset(modOpts::$types[$row[$pk]])) {
			if ($inputTypeOverride === null) $inputTypeOverride = modOpts::$types[$row[$pk]][0];
		}
		return parent::GetCellData($fieldName, $row, $url, $inputTypeOverride, $valuesOverride);
	}
	public function GetValues($alias,$pkVal=null,$stringify = FALSE) {
		if ($alias !== 'value') return parent::GetValues($alias,$pkVal,$stringify);
		if (!$pkVal) return null;
		if (!isset(modOpts::$types[$pkVal])) return null;
		return modOpts::$types[$pkVal][1];
	}
	public static $types = array();
	public static function AddOption($ident,$name,$group=NULL,$init='',$fieldType=itTEXT,$values=NULL) {
		if (!$group) $group = 'Site Options';
		self::$types[$ident] = array($fieldType,$values,$name,$group,$init);
	}
	
	protected static $_optionCache = null;
	protected static function GetCachedItem($ident) {
		$obj = utopia::GetInstance(__CLASS__);
		if (self::$_optionCache === NULL) {
			$obj->BypassSecurity(true);
			$rows = $obj->GetDataset(null,true)->fetchAll();
			$obj->BypassSecurity(false);
			foreach ($rows as $row) {
				self::$_optionCache[$row['ident']] = $row;
			}
		}
		if (isset(self::$_optionCache[$ident])) return self::$_optionCache[$ident];
		return false;
	}
	protected static function SetCacheValue($ident,$value) {
		// ensure cache is created
		self::GetCachedItem($ident);
		self::$_optionCache[$ident]['value'] = $value;
	}
	
	public static function GetOption($ident) {
		$obj = utopia::GetInstance(__CLASS__);
		$cache = self::GetCachedItem($ident);
		
		if (!isset(self::$types[$ident])) {
			if ($cache) return $cache['value'];
			return FALSE;
		}
		
		if (!$cache) {
			$obj->BypassSecurity(true);
			$obj->UpdateFields(array('ident'=>$ident,'value'=>self::$types[$ident][4]));
			$obj->BypassSecurity(false);
			return self::$types[$ident][4];
		}
		
		// check group and name
		if ($cache['name'] !== self::$types[$ident][2] || $cache['group'] !== self::$types[$ident][3]) {
			$obj->BypassSecurity(true);
			$obj->UpdateFields(array('name'=>self::$types[$ident][2],'group'=>self::$types[$ident][3]),$ident);
			$obj->BypassSecurity(false);
		}
		
		return $cache['value'];
	}
	public static function SetOption($ident,$value) {
		$obj = utopia::GetInstance(__CLASS__);
		$obj->BypassSecurity(true);
		if (self::GetCachedItem($ident) === false) {
			$obj->UpdateField('ident',$ident);
		}
		$obj->UpdateField('value',$value,$ident);
		$obj->BypassSecurity(false);
		
		self::SetCacheValue($ident,$value);
	}
}
