<?php

class tabledef_ModOpts extends uTableDef {
	public $tablename = 'internal_modopts';

	public function SetupFields() {
		// add all the fields in here
		// AddField($name, $type, $length, $collation='', $attributes='', $null='not null', $default='', $extra='', $comments='')
		// SetPrimaryKey($name);

//		$this->AddField('id',ftNUMBER);
//		$this->AddField('module','varchar',50);
		$this->AddField('ident','varchar',50);
		$this->AddField('name','varchar',100);
		$this->AddField('value','varchar',200);
		$this->SetPrimaryKey('ident');
	}
}
class modOpts extends uListDataModule {
	public function GetTitle() { return 'Module Options'; }
	public function GetOptions() { return IS_ADMIN | ALLOW_FILTER | ALLOW_EDIT | ALLOW_DELETE; }
	public function GetTabledef() { return 'tabledef_ModOpts'; }
	public function SetupFields() {
		$this->CreateTable('opts');
		$this->AddField('name','name','opts','Name');
		$this->AddField('value','value','opts','Value',itTEXT);
	}
	public static $types = array();
	public function SetupParents() {
		$this->AddParent('internalmodule_Admin');
	}
	public function RunModule() {
		$this->ShowData();
	}
	public function UpdateField($fieldAlias,$newValue,&$pkVal=NULL) {
		parent::UpdateField($fieldAlias,$newValue,$pkVal);
		self::RefreshCache();
	}
	public static function AddOption($module,$ident,$name,$init='',$fieldType=itTEXT,$values=NULL) {
		$rec = self::GetOption($module,$ident);
		if ($rec === NULL) {
			$obj = utopia::GetInstance('modOpts');
			$obj->UpdateFields(array('ident'=>$module.'::'.$ident,'name'=>$name,'value'=>$init));
		}
		self::$types[$module.'::'.$ident] = array($fieldType,$values);
	}
	public static $optCache = array();
	public static function RefreshCache() {
		$obj = utopia::GetInstance('modOpts');
		foreach ($obj->GetRows() as $row) {
			self::$optCache[$row['ident']] = $row['value'];
		}
	}
	public static function GetOption($module,$ident) {
		$ident = $module.'::'.$ident;
		if (!array_key_exists($ident,self::$optCache)) self::RefreshCache();
		if (!array_key_exists($ident,self::$optCache)) return NULL;
		return self::$optCache[$ident];
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
?>
