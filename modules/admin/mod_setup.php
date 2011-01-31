<?php

// dependancies
// check dependancies exist - Move to install?

class tabledef_Modules extends uTableDef {
	public $tablename = 'internal_modules';

	public function SetupFields() {
		// add all the fields in here
		// AddField($name, $type, $length, $collation='', $attributes='', $null='not null', $default='', $extra='', $comments='')
		// SetPrimaryKey($name);

		$this->AddField('module_id',ftNUMBER,0);
		$this->AddField('uuid','varchar',36);
		$this->AddFieldArray('module_name','varchar',50,array('readonly'=>TRUE));
		//$this->AddField('module_active',ftBOOL);
		$this->AddField('sort_order',ftNUMBER,0);
		$this->SetPrimaryKey('module_id');
		$this->SetUniqueField('uuid');
		//$this->SetUniqueField('module_name');
	}
}

?>
