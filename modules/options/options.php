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
		$this->AddField('value',ftLONGTEXT);
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
	public function SetupParents() {
		self::AddOption('site_name','Site Name');
		self::AddOption('site_url','Site URL',NULL,'http://'.$_SERVER['HTTP_HOST'].PATH_REL_ROOT);
		$this->AddParent('/');
	}
	public function RunModule() {
		$groups = database::query('SELECT DISTINCT `group` FROM tabledef_ModOpts WHERE `group` IS NOT NULL ORDER BY (`group` = ?) DESC, `group` ASC',array('Site Options'))->fetchAll();

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
			$inputTypeOverride = modOpts::$types[$row[$pk]][0];
			$valuesOverride = modOpts::$types[$row[$pk]][1];
		}
		return parent::GetCellData($fieldName, $row, $url, $inputTypeOverride, $valuesOverride);
	}
	public static $types = array();
	public static function AddOption($ident,$name,$group=NULL,$init='',$fieldType=itTEXT,$values=NULL) {
		if (!$group) $group = 'Site Options';
		self::$types[$ident] = array($fieldType,$values,$name,$group,$init);
		return self::GetOption($ident);
	}
	public static function GetOption($ident) {
		$obj =& utopia::GetInstance(__CLASS__);
		$obj->_SetupParents();
		$obj->_SetupFields();
		
		$obj->BypassSecurity(true);
		$rec = $obj->LookupRecord($ident,true);
		$obj->BypassSecurity(false);
		if (!$rec) {
			$obj->BypassSecurity(true);
			$obj->UpdateFields(array('ident'=>$ident,'value'=>self::$types[$ident][4]));
			$obj->BypassSecurity(false);
			return self::$types[$ident][4];
		}
		
		// check group and name
		if ($rec['name'] !== self::$types[$ident][2] || $rec['group'] !== self::$types[$ident][3]) {
			$obj->BypassSecurity(true);
			$obj->UpdateFields(array('name'=>self::$types[$ident][2],'group'=>self::$types[$ident][3]),$ident);
			$obj->BypassSecurity(false);
		}
		
		return $rec['value'];
	}
	public static function SetOption($ident,$value) {
		$obj = utopia::GetInstance(__CLASS__);
		$obj->BypassSecurity(true);
		$obj->UpdateField('value',$value,$ident);
		$obj->BypassSecurity(false);
	}
}
