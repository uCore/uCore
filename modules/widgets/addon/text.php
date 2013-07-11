<?php

class tabledef_TextWidget extends uTableDef {
	public function SetupFields() {
		$this->AddField('widget_id',ftNUMBER);
		$this->AddField('content',ftTEXT);
		
		$this->SetPrimaryKey('widget_id');
	}

	static function pull_from_meta() {
		$obj =& utopia::GetInstance('uWidgets',false);
		$obj->BypassSecurity(true);

		$ds = database::query('SELECT * FROM tabledef_Widgets WHERE `block_type` = ? AND `__metadata` IS NOT NULL',array('uTextWidget'));
		while (($row = $ds->fetch())) {
			$pk = $row['widget_id'];
			$meta = utopia::jsonTryDecode($row['__metadata']);
			foreach ($meta as $field => $val) {
				$obj->UpdateField($field,$val,$pk);
			}
		}
		$obj->BypassSecurity(false);
		
		$ds = database::query('UPDATE tabledef_Widgets SET `__metadata` = NULL WHERE `block_type` = ? AND `__metadata` IS NOT NULL',array('uTextWidget'));
	}
}
uEvents::AddCallback('AfterInit','tabledef_TextWidget::pull_from_meta');

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
