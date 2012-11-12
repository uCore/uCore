<?php

class uTextWidget implements iWidget {
	static function Initialise($sender) {
		$sender->AddMetaField('content','Content',itHTML);
		$sender->FieldStyles_Set('content',array('width'=>'100%','height'=>'20em'));
	}
	static function DrawData($rec) {
		$ret = $rec['content'];
		while (utopia::MergeVars($ret));
		return $ret;
	}
}
