<?php

class tabledef_ModOpts extends flexDb_TableDefinition {
	public $tablename = 'internal_modopts';

	public function SetupFields() {
		// add all the fields in here
		// AddField($name, $type, $length, $collation='', $attributes='', $null='not null', $default='', $extra='', $comments='')
		// SetPrimaryKey($name);

		$this->AddField('id',ftNUMBER);
		$this->AddField('module','varchar',50);
		$this->AddField('ident','varchar',50);
		$this->AddField('name','varchar',100);
		$this->AddField('value','varchar',200);
		$this->SetPrimaryKey('id');
	}
}
class modOpts extends flexDb_ListDataModule {
	public function GetTitle() { return 'Module Options'; }
	public function GetOptions() { return IS_ADMIN | ALLOW_FILTER | ALLOW_EDIT; }
	public function GetTabledef() { return 'tabledef_ModOpts'; }
	public function SetupFields() {
		$this->CreateTable('opts');
		$this->AddField('module','module','opts','Home');
		$this->AddField('ident','ident','opts');
		$this->AddField('name','name','opts','Name');
		$this->AddField('value','value','opts','Value',itTEXT);
	}
	public function SetupParents() {
		$this->AddParent('internalmodule_Admin');
	}
	public function ParentLoad($parent) {}
	public function RunModule() {
		$this->ShowData();
	}
	public function UpdateField($fieldAlias,$newValue,&$pkVal=NULL) {
		parent::UpdateField($fieldAlias,$newValue,$pkVal);
		self::RefreshCache();
	}
	public static function AddOption($module,$ident,$name,$init='') {
		$rec = self::GetOption($module,$ident);// CallModuleFunc('modOpts','LookupRecord',array('module'=>$module,'ident'=>$ident));
		if ($rec === NULL) {
			CallModuleFunc('modOpts','UpdateFields',array('module'=>$module,'ident'=>$ident,'name'=>$name,'value'=>$init));
		}
	}
	public static $optCache = array();
	public static function RefreshCache() {
		foreach (CallModuleFunc('modOpts','GetRows') as $row) {
			self::$optCache[$row['module']][$row['ident']] = $row['value'];
		}
	}
	public static function GetOption($module,$ident) {
		if (!array_key_exists($module,self::$optCache) || !array_key_exists($ident,self::$optCache[$module])) self::RefreshCache();
		if (!array_key_exists($module,self::$optCache) || !array_key_exists($ident,self::$optCache[$module])) return NULL;
		return self::$optCache[$module][$ident];
		//$rec = CallModuleFunc('modOpts','LookupRecord',array('module'=>$module,'ident'=>$ident));
		//return $rec['value'];
	}
}
?>