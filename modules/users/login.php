<?php

class adminLogout extends uBasicModule {
	public function GetOptions() { return PERSISTENT; }
	public function GetTitle() { return 'Logout'; }
	public function GetSortOrder() { return -9900;}
	public function SetupParents() {
		if (!uUserLogin::IsLoggedIn()) return;
		$this->AddParent('/');
	}
	public function RunModule() {
		unset($_SESSION['current_user']);
		$obj = utopia::GetInstance('uDashboard');
		header('Location: '.$obj->GetURL());
		die();
	}
}

uEvents::AddCallback('BeforeRunModule',function ($object) {
	$ret = uEvents::TriggerEvent('CanAccessModule',$object);
	if ($ret === FALSE && get_class($object) == utopia::GetCurrentModule()) uNotices::AddNotice('Sorry, you do not have access to this feature.',NOTICE_TYPE_WARNING);
	return $ret;
});

utopia::AddTemplateParser('login_user','uUserLogin::GetLoginUserBox','');
utopia::AddTemplateParser('login_pass','uUserLogin::GetLoginPassBox','');
class uUserLogin extends uDataModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return 'User Login'; }
	public function GetOptions() { return ALWAYS_ACTIVE | NO_HISTORY | PERSISTENT | NO_NAV; }
	public function GetUUID() { return 'login'; }
	public function GetTabledef() { return 'tabledef_Users'; }
	public function SetupFields() {
		$this->CreateTable('users','tabledef_Users');
		$this->AddField('username','username','users');
		$this->AddField('password','password','users');
	}

	public function SetupParents() {
		uEvents::AddCallback('BeforeRunModule',array($this,'checkLogin'),utopia::GetCurrentModule());
		uEvents::AddCallback('InitComplete',array($this,'CheckSession'));

		self::TryLogin();
		$this->SetRewrite(true);
	}
	
	public function CheckSession() {
		if (!isset($_SESSION['current_user'])) return;
		$rec = $this->LookupRecord($_SESSION['current_user']);
		if (!$rec) {
			uNotices::AddNotice('Your user no longer exists.',NOTICE_TYPE_ERROR);
			unset($_SESSION['current_user']);
		}
	}

	public static function TryLogin() {
		// login not attempted.
		if (!array_key_exists('__login_u',$_REQUEST)) return;

		$un = $_REQUEST['__login_u']; $pw = $_REQUEST['__login_p'];
		unset($_REQUEST['__login_u']); unset($_REQUEST['__login_p']);

		$obj = utopia::GetInstance(__CLASS__);
		$rec = $obj->LookupRecord(array('username'=>$un,'password'=>md5($pw)));
		if ($rec) {
			$_SESSION['current_user'] = $rec['user_id'];
		} else {
			uNotices::AddNotice('Username and password do not match.',NOTICE_TYPE_ERROR);
		}

/*		if (self::IsLoggedIn() && ((utopia::GetCurrentModule() == __CLASS__) || (array_key_exists('adminredirect',$_REQUEST) && $_REQUEST['adminredirect'] == 1))) {
			$obj = utopia::GetInstance('uDashboard');
			header('Location: '.$obj->GetURL()); die();
		}*/
	}

	public static function IsLoggedIn() {
		self::TryLogin();
		if (!isset($_SESSION['current_user'])) return false;
		
		return ($_SESSION['current_user']);
	}

	public function checkLogin($object) {
		if (self::IsLoggedIn()) return;
		if (flag_is_set($object->GetOptions(), PERSISTENT)) return;

		$parent = get_class($object);

		if ($parent == utopia::GetCurrentModule() && $parent !== __CLASS__ && !AjaxEcho('window.location.reload();')) {
			$this->_RunModule();
		}
		return FALSE;
	}

	public static function GetLoginUserBox() {
		return utopia::DrawInput('__login_u',itTEXT,'',NULL,array('id'=>'lu'));
	}
	public static function GetLoginPassBox() {
		return utopia::DrawInput('__login_p',itPASSWORD);
	}

	public function RunModule() {
		if (self::IsLoggedIn()) {
			echo '<p>You are already logged in.</p>';
			return;
		}
		echo 'Please log in';
		echo '<form id="loginForm" action="" method="POST"><table>';
		echo '<tr><td align="right">Username:</td><td>{login_user}</td></tr>';
		echo '<tr><td align="right">Password:</td><td>{login_pass}</td></tr>';
		echo '<tr><td></td><td align="right">'.utopia::DrawInput('',itSUBMIT,'Log In').'</td></tr>';
		echo '</table></form><script type="text/javascript">$(function (){$(\'#lu\').focus()})</script>';
		uEvents::TriggerEvent('AfterShowLogin');
	}
}
