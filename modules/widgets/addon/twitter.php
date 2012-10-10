<?php

class uTwitterWidget implements iWidget {
	static function Initialise($sender) {
		$sender->AddMetaField('twitter_id','Twitter ID',itTEXT);
		$sender->AddMetaField('width','Width',itTEXT);
		$sender->AddMetaField('height','Height',itTEXT);
		
		$sender->SetFieldType('width',ftNUMBER);
		$sender->SetFieldType('height',ftNUMBER);
	}
	static function DrawData($data) {
		$blockId = $data['block_id'];
		$id = $data['twitter_id'];
		$width = $data['width'] ? $data['width'] : 250;
		$height = $data['height'] ? $data['height'] : 350;
		return <<<FIN
<div id="twitter_$blockId"></div>
<script type="text/javascript">
$.getScript('http://widgets.twimg.com/j/2/widget.js',function () {
new TWTR.Widget({
  id: 'twitter_$blockId',
  version: 2,
  type: 'profile',
  rpp: 4,
  interval: 6000,
  width: $width,
  height: $height,
  theme: {
    shell: {
      background: '#333333',
      color: '#ffffff'
    },
    tweets: {
      background: '#000000',
      color: '#ffffff',
      links: '#4aed05'
    }
  },
  features: {
    scrollbar: false,
    loop: false,
    live: false,
    hashtags: true,
    timestamp: true,
    avatars: false,
    behavior: 'all'
  }
}).render().setUser('$id').start();
});
</script>
FIN;
	}
}
