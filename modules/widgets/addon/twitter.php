<?php

class uTwitterWidget implements iWidget {
	static function Initialise($sender) {
		$sender->AddMetaField('twitter_id','Twitter Username',itTEXT);
		$sender->AddMetaField('twitter_widget_id','Twitter Widget ID',itTEXT);
		$sender->AddMetaField('width','Width',itTEXT);
		$sender->AddMetaField('height','Height',itTEXT);
		
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
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
FIN;
	}
}
