<?php

class adminLogout extends uBasicModule {
	public function GetOptions() { return PERSISTENT; }
	public function GetTitle() { return 'Logout'; }
	public function GetSortOrder() { return -9900; }
	public function GetUUID() { return 'logout'; }
	public function SetupParents() {
		$this->SetRewrite(true);
		utopia::AddTemplateParser('logout','<a href="'.$this->GetURL().'">Logout</a>','');
		
		if (!uUserLogin::IsLoggedIn()) return;
		$this->AddParent('/');
	}
	public function RunModule() {
		$_SESSION = array();

		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
		}

		session_destroy();
		$ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : PATH_REL_ROOT;
		header('Location: '.$ref);
		die();
	}
}


utopia::AddTemplateParser('login_user','uUserLogin::GetLoginUserBox','');
utopia::AddTemplateParser('login_pass','uUserLogin::GetLoginPassBox','');
utopia::AddTemplateParser('login_status','uUserLogin::GetLoginStatus','');
utopia::AddTemplateParser('login','uUserLogin::LoginForm','',true);
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
		$this->AddField('can_login','(!({email_confirm} <=> {username}))','users');
		$this->AddFilter('can_login',ctEQ,itNONE,1);
	}

	public function SetupParents() {
		uCSS::IncludeFile(dirname(__FILE__).'/login.css');

		uEvents::AddCallback('BeforeRunModule',array($this,'checkLogin'),utopia::GetCurrentModule());
		uEvents::AddCallback('AfterInit',array($this,'CheckSession'));

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
	public static function GetLoginStatus() {
		$u = self::IsLoggedIn();
		if (!$u) return 'Login';
		$o =& utopia::GetInstance(__CLASS__);
		$rec = $o->LookupRecord($u);
		return $rec['username'];
	}
	public static function TryLogin() {
		// login not attempted.
		if (!array_key_exists('__login_u',$_POST)) return;

		$un = $_POST['__login_u']; $pw = $_POST['__login_p'];
		unset($_POST['__login_u']); unset($_POST['__login_p']);

		$obj =& utopia::GetInstance(__CLASS__);
		$rec = $obj->LookupRecord(array('username'=>$un,'password'=>md5($pw)));
		if ($rec) {
			$_SESSION['current_user'] = $rec['user_id'];
			$obj =& utopia::GetInstance('uUserProfile');
			$obj->UpdateFieldRaw('last_login','NOW()',$rec['user_id']);
			if (isset($_REQUEST['remember_me'])) {
				session_set_cookie_params(604800,PATH_REL_ROOT);
				session_regenerate_id(true);
				$_SESSION['SESSION_LIFETIME'] = 604800;
			}
		} else {
			uNotices::AddNotice('Username and password do not match.',NOTICE_TYPE_ERROR);
		}
	}

	public static function IsLoggedIn() {
		self::TryLogin();
		if (!isset($_SESSION['current_user'])) return false;
		
		return ($_SESSION['current_user']);
	}
	public static function SetLogin($id) {
		$_SESSION['current_user'] = $id;
	}

	public function checkLogin($object) {
		if (flag_is_set($object->GetOptions(), PERSISTENT)) return;
		if (uEvents::TriggerEvent('CanAccessModule',$object) !== FALSE) return;
		
		if (self::IsLoggedIn()) {
			uNotices::AddNotice('Sorry, you do not have access to this feature.',NOTICE_TYPE_WARNING);
			return FALSE;
		}

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
		self::LoginForm();
	}
	public static function LoginForm() {
		if (self::IsLoggedIn()) return;
		echo '<div id="login-wrap">';
		echo '<h1>Please log in</h1>';
		echo '<form id="login-form" action="" method="POST"><table>';
		echo '<tr><td align="right">Email:</td><td>{login_user}</td></tr>';
		echo '<tr><td align="right">Password:</td><td>{login_pass}</td></tr>';
		echo '<tr><td colspan="2" align="right"><input type="checkbox" value="1" name="remember_me" id="remember_me"/><label for="remember_me"> Remember Me</label> '.utopia::DrawInput('',itSUBMIT,'Log In').'</td></tr>';
		echo '<tr><td colspan="2" align="right">';
		uEvents::TriggerEvent('LoginButtons');
		echo '</td></tr>';
		echo '</table></form><script type="text/javascript">$(function (){$(\'#lu\').focus()})</script>';
		uEvents::TriggerEvent('AfterShowLogin');
		echo '</div>';
	}
}
uEvents::AddCallback('AfterInit','uUserLogin::IsLoggedIn',-1000);


class uResetPassword extends uDataModule {
	public function GetTitle() { return 'Reset Password'; }
	public function GetTabledef() { return 'tabledef_Users'; }
	public function GetOptions() { return PERSISTENT | ALLOW_EDIT; }

	public function GetUUID() { return 'reset-password'; }

	public function SetupParents() {
		$this->SetRewrite(array('{e}','{c}'));
		uEmailer::InitialiseTemplate('account_resetpw','Reset your password','<p>You can reset your password by clicking the link below:</p><p><a href="{home_url_abs}/{activate_link}">{home_url_abs}/{activate_link}</a></p>',array('email','activate_link'));
		uEvents::AddCallback('LoginButtons',array($this,'forgottenPasswordButton'));
	}
	public function forgottenPasswordButton() {
		echo '<a href="'.$this->GetURL(array()).'" class="forgotten-password">Forgotten Password?</a>';
	}

	public function SetupFields() {
		$this->CreateTable('users');
		$this->CreateTable('roles','tabledef_UserRoles','users',array('role'=>'role_id'));

		$this->AddField('user_id','user_id','users');
		$this->AddField('username','username','users','Username',itTEXT);
		$this->AddField('password','password','users','Password',itPASSWORD);
		$this->AddField('role','name','roles','Role',itCOMBO);
		$this->AddField('email_confirm','email_confirm','users');
		$this->AddField('email_confirm_code','email_confirm_code','users');
	}

	public function ResetPW($user) {
		$rec = $this->LookupRecord(array('username'=>$user));
		if (!$rec) return FALSE; // user not found.

		// account has not yet been validated.
		if ($rec['username'] == $rec['email_confirm']) {
			uVerifyEmail::VerifyAccount($rec['user_id']);
			return;
		}
		
		$randKey = genRandom(20);
		$this->UpdateField('email_confirm_code',$randKey,$rec['user_id']);

		//email out verification
		$name = $rec['username'] ? ' '.$rec['username'] : '';
		$url = $this->GetURL(array('e'=>$user,'c'=>$randKey));
		$url = preg_replace('/^'.preg_quote(PATH_REL_ROOT,'/').'/','',$url);
		if (empty($rec['password']))
			uEmailer::SendEmailTemplate('account_activate',array('email'=>$user,'contact_name'=>$name,'activate_link'=>$url),'email');
		else
			uEmailer::SendEmailTemplate('account_resetpw',array('email'=>$user,'contact_name'=>$name,'activate_link'=>$url),'email');
	}

	public function RunModule() {
		$noticeBox = '<div style="color:#b20000;background-color:#eee8e5; border:1px solid #b20000; padding:10px;"><span style="font-weight:bold;font-size:1.25em;">! Important Message</span><br>';
		$email = array_key_exists('e',$_REQUEST) ? $_REQUEST['e'] : '';
		$notice = '';

		$rec = $this->LookupRecord(array('username'=>$email));
		if (empty($email) || !$rec) {
			if (!$rec && !empty($email)) echo $noticeBox.'No account was found with this email address. Please try again.</div>';
			echo '<h1>Reset Password</h1>';
			echo '<form id="reset-password-form" action="'.$this->GetURL(array()).'" method="post">';
			echo '<p>What is your email address?</p>';
			echo '<div style="margin-left:20px;">My e-mail address is '.utopia::DrawInput('e',itTEXT).'</div>';
			echo '<input type="submit" class="btn" value="Reset Password" />';
			echo '</form>';
			return;
		}

		if (!array_key_exists('c',$_REQUEST)) { // reset pw
			echo '<p>An email has been sent to &quot;'.$email.'&quot; with your password reset link. Please click the link and enter a new password for your account.</p><p>Please be patient; the delivery of email may be delayed. Remember to confirm that the email above is correct and to check your junk or spam folder or filter if you do not receive this email.</p>';
			$this->ResetPW($email);
			return true;
		}

		if ($rec['email_confirm_code'] !== $_REQUEST['c']) {
			echo '<p>Unfortunately we could not validate this request.</p><p>If you are trying to activate your account or reset your password, please <a href="'.$this->GetURL(array('e'=>$email)).'">click here</a> for a new link.</p>';
			return;
		}
		if (array_key_exists('__newpass_c',$_POST)) {
			if ($_POST['__newpass'] !== $_POST['__newpass_c']) {
				$notice = $noticeBox.'Password confirmation did not match, please try again.</div>';
			} else {
				$this->UpdateFields(array('email_confirm_code'=>'','password'=>$_POST['__newpass']),$rec['user_id']);
				echo '<p>You have successfully reset your password.</p>';
				return;
			}
		}

		echo $notice;
		if (empty($rec['password'])) $action = 'Activate Account';
		else $action = 'Reset Password';
		echo '<h1>'.$action.'</h1>';
		echo '<form id="loginForm" action="" method="post"><input type="hidden" name="e" value="'.$email.'"><input type="hidden" name="c" value="'.$_REQUEST['c'].'">';
		echo '<table style="margin-left:20px;margin-top:10px;" cellpadding="5">';
		echo '<tr><td align="right">New Password:</td><td>'.utopia::DrawInput('__newpass',itPASSWORD).'</td></tr>';
		echo '<tr><td align="right">Confirm Password:</td><td>'.utopia::DrawInput('__newpass_c',itPASSWORD).'</td></tr>';
		echo '<tr><td colspan="2" align="right"><input type="submit" class="btn" value="Set Password" /></td></tr>';
		echo '</table></form>';
	}
}
