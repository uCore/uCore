<?php

class cms_publish_update implements iUtopiaModule {
	public static function Initialise() {
		uEvents::AddCallback('AfterInit','cms_publish_update::uCMS_publish_update');
	
	}
	public static function uCMS_publish_update() {
		$done = modOpts::GetOption('cms_publish_update');
		if ($done) return;
	
		$o = utopia::GetInstance('uCMS_Edit');
		$o->BypassSecurity(true);
		$ds = $o->GetDataset(array('{content_published_time} > {content_time}'));
		while (($row = $ds->fetch())) {
			$o->UpdateFieldRaw('content_published_time','`content_time`',$row['cms_id']);
		}
		$o->BypassSecurity(false);
		
		modOpts::SetOption('cms_publish_update',true);
	}
}