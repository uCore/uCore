<?php

define('NOTICE_TYPE_INFO'	, 'info');
define('NOTICE_TYPE_WARNING', 'warning');
define('NOTICE_TYPE_ERROR'	, 'error');

class uNotices {
	public static function AddNotice($message,$type=NOTICE_TYPE_INFO) {
		$script = 'var msg = Base64.decode("'.base64_encode(self::GetNotice($message,$type)).'"); utopia.ShowNotice(msg);';
		if (!AjaxEcho($script)) $_SESSION['notices'][] = $script;
	}
	public static function GetNotice($message,$type=NOTICE_TYPE_INFO) {
		$icon = $type === NOTICE_TYPE_WARNING || $type === NOTICE_TYPE_ERROR ? 'alert' : 'info';
		return '<div class="uNotice uNotice-'.$type.'">'.$message.'</div>';
	}
	public static function ShowNotices() {
		// is redirect issued?  If so, don't draw now.
		foreach (headers_list() as $h) {
			if (preg_match('/^location:/i',$h)) return;
		}
		
		$scripts = '$(function(){'.implode(PHP_EOL,$_SESSION['notices']).'});';
		$_SESSION['notices'] = array();
		uJavascript::AddText($scripts);
	}
	public static function Init() {
		uCSS::IncludeFile(dirname(__FILE__).'/notices.css');
		uJavascript::IncludeFile(dirname(__FILE__).'/notices.js');
	}
}

uEvents::AddCallback('AfterInit','uNotices::Init');
uEvents::AddCallback('BeforeOutputTemplate','uNotices::ShowNotices');