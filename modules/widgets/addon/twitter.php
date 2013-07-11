<?php

class tabledef_TwitterWidget extends uTableDef {
	public function SetupFields() {
		$this->AddField('widget_id',ftNUMBER);
		
		$this->AddField('twitter_id',ftVARCHAR,20);
		$this->AddField('twitter_widget_id',ftVARCHAR,100);
		$this->AddField('width',ftVARCHAR,20);
		$this->AddField('height',ftVARCHAR,20);
		
		$this->SetPrimaryKey('widget_id');
	}

	static function pull_from_meta() {
		$obj =& utopia::GetInstance('uWidgets',false);
		$obj->BypassSecurity(true);

		$ds = database::query('SELECT * FROM tabledef_Widgets WHERE `block_type` = ? AND `__metadata` IS NOT NULL',array('uTwitterWidget'));
		while (($row = $ds->fetch())) {
			$pk = $row['widget_id'];
			$meta = utopia::jsonTryDecode($row['__metadata']);
			foreach ($meta as $field => $val) {
				$obj->UpdateField($field,$val,$pk);
			}
		}
		$obj->BypassSecurity(false);
		
		$ds = database::query('UPDATE tabledef_Widgets SET `__metadata` = NULL WHERE `block_type` = ? AND `__metadata` IS NOT NULL',array('uTwitterWidget'));
	}
}
uEvents::AddCallback('AfterInit','tabledef_TwitterWidget::pull_from_meta');

class uTwitterWidget implements iWidget {
	static function Initialise($sender) {
		$sender->CreateTable('custom','tabledef_TwitterWidget','blocks','widget_id');
		$sender->AddField('twitter_id','twitter_id','custom','Twitter Username',itTEXT);
		$sender->AddField('twitter_widget_id','twitter_widget_id','custom','Twitter Widget ID',itTEXT);
		$sender->AddField('width','width','custom','Width',itTEXT);
		$sender->AddField('height','height','custom','Height',itTEXT);
		
		$sender->SetFieldType('width',ftNUMBER);
		$sender->SetFieldType('height',ftNUMBER);
	}
	static function DrawData($data) {
		$blockId = $data['block_id'];
		$user = $data['twitter_id'];
		$id = $data['twitter_widget_id'];
		$width = $data['width'] ? $data['width'] : 250; $width = ' width="'.$width.'"';
		$height = $data['height'] ? $data['height'] : 350; $height = ' height="'.$height.'"';
		return <<<FIN
<a class="twitter-timeline"$width$height href="https://twitter.com/$user" data-widget-id="$id">Tweets by @$user</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}else twttr.widgets.load()}(document,"script","twitter-wjs");</script>
FIN;
	}
}
