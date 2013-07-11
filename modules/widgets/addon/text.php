<?php

class tabledef_TextWidget extends uTableDef {
	public function SetupFields() {
		$this->AddField('widget_id',ftNUMBER);
		$this->AddField('content',ftTEXT);
		
		$this->SetPrimaryKey('widget_id');
	}
}

class uTextWidget implements iWidget {
	static function Initialise($sender) {
		$sender->CreateTable('custom','tabledef_TextWidget','blocks','widget_id');
		$sender->AddField('content','content','custom','Content',itHTML);
		$sender->FieldStyles_Set('content',array('width'=>'100%','height'=>'20em'));
	}
	static function DrawData($rec) {
		$ret = $rec['content'];
		while (utopia::MergeVars($ret));
		return $ret;
	}
}
