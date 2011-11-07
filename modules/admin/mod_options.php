<?php

class tabledef_ModOpts extends uTableDef {
	public $tablename = 'internal_modopts';

	public function SetupFields() {
		// add all the fields in here
		// AddField($name, $type, $length, $collation='', $attributes='', $null='not null', $default='', $extra='', $comments='')
		// SetPrimaryKey($name);

//		$this->AddField('id',ftNUMBER);
		$this->AddField('ident','varchar',50);
		$this->AddField('module','varchar',50);
		$this->AddField('name','varchar',100);
		$this->AddField('value','varchar',200);
		$this->SetPrimaryKey('ident');
	}
}
class modOpts extends uListDataModule implements iAdminModule {
	public function GetTitle() { return 'Module Options'; }
	public function GetOptions() { return ALLOW_FILTER | ALLOW_EDIT | ALLOW_DELETE; }
	public function GetTabledef() { return 'tabledef_ModOpts'; }
	public function GetSortOrder() { return -9999.5; }
	public function SetupFields() {
		$this->CreateTable('opts');
		$this->AddField('module','module','opts');
		$this->AddField('name','name','opts','Name');
		$this->AddField('value','value','opts','Value',itTEXT);

		$this->AddGrouping('ident');
	}
	public static $types = array();
	public function SetupParents() {
		$this->AddParent('/');
	}
	public function RunModule() {
		$this->ShowData();
	}
	public function UpdateField($fieldAlias,$newValue,&$pkVal=NULL) {
		parent::UpdateField($fieldAlias,$newValue,$pkVal);
		self::RefreshCache();
	}
	public static function AddOption($module,$ident,$name,$init='',$fieldType=itTEXT,$values=NULL) {
		if (!self::OptionExists($module,$ident)) {
			$obj = utopia::GetInstance('modOpts');
			$obj->UpdateFields(array('ident'=>$module.'::'.$ident,'module'=>$module,'name'=>$name,'value'=>$init));
		}
		self::$types[$module.'::'.$ident] = array($fieldType,$values);
	}
	public static $optCache = NULL;
	public static function RefreshCache() {
		self::$optCache = array();
		$obj = utopia::GetInstance('modOpts');
		foreach ($obj->GetRows() as $row) {
			if (!$row['module']) {
				$module = substr($row['ident'],0,strpos($row['ident'],'::'));
				$obj->UpdateField('module',$module,$row['ident']);
			}
			self::$optCache[$row['ident']] = $row['value'];
		}
	}
	public static function OptionExists($module,$ident) {
		if (self::$optCache === NULL) self::RefreshCache();
		$ident = $module.'::'.$ident;
		return array_key_exists($ident,self::$optCache);
	}
	public static function GetOption($module,$ident) {
		if (!self::OptionExists($module,$ident)) return NULL;
		$ident = $module.'::'.$ident;
		return self::$optCache[$ident];
	}
	public static function SetOption($module,$ident,$value) {
		$ident = $module.'::'.$ident;
		$obj = utopia::GetInstance(__CLASS__);
		$obj->UpdateField('value',$value,$ident);
	}
	public function GetCellData($fieldName, $row, $url = '', $inputTypeOverride=NULL, $valuesOverride=NULL) {
		$pk = $this->GetPrimaryKey();
		if ($fieldName == 'value' && isset(self::$types[$row[$pk]])) {
			$inputTypeOverride = self::$types[$row[$pk]][0];
			$valuesOverride = self::$types[$row[$pk]][1];
		}
		return parent::GetCellData($fieldName, $row, $url, $inputTypeOverride, $valuesOverride);
	}
}
