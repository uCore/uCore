<?php

define('NO_TABS',flag_gen()); // used in layout section

class tabledef_Layout extends flexDb_TableDefinition {
	public $tablename = "internal_layout";

	public function SetupFields() {
		// add all the fields in here
		// AddField($name, $type, $length, $collation='', $attributes='', $null='not null', $default='', $extra='', $comments='')
		// SetPrimaryKey($name);

		$this->AddField('id','int',0);
		$this->AddField('div_id',ftTEXT,150);
		$this->AddField('top','varchar',10);
		$this->AddField('left','varchar',10);

		$this->SetPrimaryKey('id');
	}
}

class internalmodule_LayoutAjax extends flexDb_BasicModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return ''; }
	public function GetOptions() { return DEFAULT_OPTIONS; }

	public function SetupParents() {
		//$this->AddParent('/');
		// register ajax
		$this->RegisterAjax('posQuery',array($this,'PosQuery'));
		//echo GetCurrentModule();
		// must be defined here so that JS ajax query will take it in
//		$noTabs = flag_is_set(CallModuleFunc(GetCurrentModule(),'GetOptions'),NO_TABS);
//		if ($noTabs) FlexDB::LinkList_Add('child_buttons','Draggable',"#",10,array('class'=>'linklist-options','onclick'=>"ToggleDraggable();"));
		define('USE_TABS',true);
	}

	public function ParentLoad($parent) {
	}

	public function RunModule() {
	}

	public function PosQuery() {
		// check if position exists
		if ($_REQUEST['action'] == 'set') {
			if (!(intval($_REQUEST['top']) == $_REQUEST['top']) || !(intval($_REQUEST['left']) == $_REQUEST['left']))
			die('Invalid parameters.');

			$result = sql_query("SELECT * FROM internal_layout WHERE div_id = '{$_REQUEST['id']}'");
			if (mysql_num_rows($result) >0) { // update row
				sql_query("UPDATE internal_layout SET `top` = '{$_REQUEST['top']}', `left` = '{$_REQUEST['left']}' WHERE `div_id` = '{$_REQUEST['id']}'");
			} else { // create a new row.
				sql_query("INSERT INTO internal_layout (`div_id`,`top`,`left`) VALUES ('{$_REQUEST['id']}','{$_REQUEST['top']}','{$_REQUEST['left']}')");
			}
			echo mysql_error();
		} else if ($_REQUEST['action'] == 'get') {
			$result = sql_query("SELECT `top`, `left` FROM internal_layout WHERE `div_id` = '{$_REQUEST['id']}'");
			$row = GetRow($result);
			if (mysql_errno() > 0) echo mysql_error();
			else die("top={$row['top']}&left={$row['left']}");
		} else
		die("Unknown action.");
	}
}
?>