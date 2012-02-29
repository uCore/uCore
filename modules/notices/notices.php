<?php

define('NOTICE_TYPE_INFO'	, 'info');
define('NOTICE_TYPE_WARNING', 'warning');
define('NOTICE_TYPE_ERROR'	, 'error');

utopia::AddTemplateParser('notices','uNotices::ShowNotices','',true);
class uNotices extends uBasicModule {
	public function SetupParents() {
		uCSS::IncludeFile(dirname(__FILE__).'/notices.css');
	}

	public static function AddNotice($message,$type=NOTICE_TYPE_INFO) {
		if (!AjaxEcho('$(\'.uNotices\').append(Base64.decode("\''.base64_encode(self::GetNotice($message,$type)).'\'"));'))
			$_SESSION['notices'][] = array('message'=>$message, 'type'=>$type);
	}

	public static function GetNotice($message,$type=NOTICE_TYPE_INFO) {
		$icon = $type === NOTICE_TYPE_WARNING || $type === NOTICE_TYPE_ERROR ? 'alert' : 'info';
		return '<div class="uNotice uNotice-'.$type.'"><p><span class="ui-icon ui-icon-'.$icon.'" style="float: left; margin-right: .3em;"></span>'.$message.'</p></div>';
	}
	public function RunModule() {
		self::ShowNotices();
	}
	public static function ShowNotices() {
		echo '<div class="uNotices">';
		if (isset($_SESSION['notices'])) foreach ($_SESSION['notices'] as $k => $notice) {
			echo self::GetNotice($notice['message'],$notice['type']);
			unset($_SESSION['notices'][$k]);
		}
		echo '</div>';
	}
}
