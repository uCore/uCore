<?php

class adminLogout extends uBasicModule implements iAdminModule {
	public function GetOptions() { return PERSISTENT; }
	public function GetTitle() { return 'Log Out'; }
	public function GetSortOrder() { return -9900; }
	public static $uuid = 'logout';
	public function SetupParents() {
		$this->SetRewrite(true);
		utopia::AddTemplateParser('logout','<a href="'.$this->GetURL().'">Log Out</a>','');
		
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
	public static function NoRole() {
		uUserRoles::NoRole('adminLogout');
	}
}
uEvents::AddCallback('AfterInit','adminLogout::NoRole');


utopia::AddTemplateParser('login_user','uUserLogin::GetLoginUserBox','');
utopia::AddTemplateParser('login_pass','uUserLogin::GetLoginPassBox','');
utopia::AddTemplateParser('login_status','uUserLogin::GetLoginStatus','');
utopia::AddTemplateParser('login','uUserLogin::LoginForm','',true);
uEvents::AddCallback('BeforeRunModule','uUserLogin::checkLogin');
class uUserLogin extends uDataModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return 'User Log In'; }
	public function GetOptions() { return ALWAYS_ACTIVE | NO_HISTORY | PERSISTENT | NO_NAV; }
	public static $uuid = 'login';
	public function GetTabledef() { return 'tabledef_Users'; }
	public function SetupFields() {
		$this->CreateTable('users');
		$this->AddField('username','username','users');
		$this->AddField('password','password','users');
		$this->SetFieldOptions('password',ALLOW_EDIT);
		$this->AddField('last_login','last_login','users');
		$this->SetFieldOptions('last_login',ALLOW_EDIT);
		$this->AddField('can_login','(!({email_confirm} <=> {username}))','users');
		$this->AddFilter('can_login',ctEQ,itNONE,1);
	}

	public function SetupParents() {
		uCSS::IncludeFile(dirname(__FILE__).'/login.css');

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
		if (!$u) return 'Log In';
		$o =& utopia::GetInstance(__CLASS__);
		$rec = $o->LookupRecord($u);
		return $rec['username'];
	}
	public static function TryLogin() {
		if (isset($_SESSION['current_user'])) return;
		// login not attempted.
		if (!array_key_exists('__login_u',$_POST)) return;
		if (!array_key_exists('__login_p',$_POST)) return;

		$un = $_POST['__login_u']; $pw = $_POST['__login_p'];
		unset($_POST['__login_p']);

		if (($userID = uUsersList::TestCredentials($un,$pw)) !== false) {
			self::SetLogin($userID);
			
			$obj =& utopia::GetInstance(__CLASS__);
			$rec = $obj->LookupRecord($userID,true);
			// check if password is the most secure we can have.
			if ($rec && !uCrypt::IsStrongest($pw,$rec['password'])) {
				$pk = $rec['user_id'];
				$obj->UpdateField('password',uCrypt::Encrypt($pw),$pk);
			}
			
			$obj->UpdateFieldRaw('last_login','NOW()',$userID);
			if (isset($_REQUEST['remember_me'])) {
				session_set_cookie_params(604800,PATH_REL_ROOT);
				session_regenerate_id(true);
				$_SESSION['SESSION_LIFETIME'] = 604800;
			}
			uEvents::TriggerEvent('AfterLogin');
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

	public static function checkLogin($object) {
		if (flag_is_set($object->GetOptions(), PERSISTENT)) return;
		if (uEvents::TriggerEvent('CanAccessModule',$object) !== FALSE) return;
		
		if (self::IsLoggedIn()) {
			uNotices::AddNotice('Sorry, you do not have access to this feature.',NOTICE_TYPE_WARNING);
			return FALSE;
		}

		$parent = get_class($object);
		if ($parent == utopia::GetCurrentModule() && $parent !== __CLASS__ && !AjaxEcho('window.location.reload();')) {
			$obj =& utopia::GetInstance(__CLASS__);
			$obj->_RunModule();
		}
		return FALSE;
	}

	public static function GetLoginUserBox() {
		$val = isset($_POST['__login_u']) ? $_POST['__login_u'] : '';
		return utopia::DrawInput('__login_u',itTEXT,$val,NULL,array('id'=>'__login_u'));
	}
	public static function GetLoginPassBox() {
		return utopia::DrawInput('__login_p',itPASSWORD,'',NULL,array('id'=>'__login_p'));
	}

	public function RunModule() {
		if (self::IsLoggedIn()) {
			// redirect to user profile
			$o =& utopia::GetInstance('uUserProfile');
			$o->AssertURL(307,false);
			return;
		}
		self::LoginForm();
	}
	public static function LoginForm() {
		if (self::IsLoggedIn()) return;
		?>
		<div id="login-register-wrap">
			<div id="login-wrap">
			<h1>Log In</h1>
			<form action="" method="POST">
			<div class="form-field">
			<label for="__login_u">Email/Username</label>{login_user}
			</div>
			<div class="form-field">
			<label for="__login_p">Password</label>{login_pass}
			</div>
			<label><input type="checkbox" value="1" name="remember_me" /> Remember Me</label>
		<?php
		$o =& utopia::GetInstance('uResetPassword');
		echo utopia::DrawInput('',itSUBMIT,'Log In',null,array('class'=>'right'));
		echo '<a href="'.$o->GetURL(null).'" class="forgotten-password">Forgotten Password?</a>';
		echo '</form><script type="text/javascript">$(function (){$(\'#__login_u\').focus()})</script>';
		uEvents::TriggerEvent('LoginButtons');
		echo '</div>';

		// register
		uEvents::TriggerEvent('AfterShowLogin');
		
		echo '</div>';
	}
}
uEvents::AddCallback('AfterInit','uUserLogin::IsLoggedIn',-1000);


class uResetPassword extends uDataModule {
	public function GetTitle() { return 'Reset Password'; }
	public function GetTabledef() { return 'tabledef_Users'; }
	public static $uuid = 'reset-password';

	public function SetupParents() {
		$this->SetRewrite(array('{e}','{c}'));
		uEmailer::InitialiseTemplate('account_resetpw','Reset your password','<p>You can reset your password by clicking the link below:</p><p><a href="{home_url_abs}/{activate_link}">{home_url_abs}/{activate_link}</a></p>',array('email','activate_link'));
	}

	public function SetupFields() {
		$this->CreateTable('users');

		$this->AddField('user_id','user_id','users');
		$this->AddField('username','username','users','Username',itTEXT);
		$this->AddField('password','password','users','Password',itPASSWORD);
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
		
		$randKey = uCrypt::GetRandom(20);
		$this->SetFieldOptions('email_confirm_code',ALLOW_EDIT);
		$this->UpdateField('email_confirm_code',$randKey,$rec['user_id']);
		$this->SetFieldOptions('email_confirm_code',NULL);

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
		$email = array_key_exists('e',$_REQUEST) ? $_REQUEST['e'] : '';
		$notice = '';

		$rec = $this->LookupRecord(array('username'=>$email));
		if (!empty($email) && !$rec) uNotices::AddNotice('No account was found with this email address. Please try again.',NOTICE_TYPE_ERROR);
		if (empty($email) || !$rec) {
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
				uNotices::AddNotice('Password confirmation did not match, please try again.',NOTICE_TYPE_ERROR);
			} else {
				$this->SetFieldOptions('email_confirm_code',ALLOW_EDIT); $this->SetFieldOptions('password',ALLOW_EDIT);
				$this->UpdateFields(array('email_confirm_code'=>'','password'=>$_POST['__newpass']),$rec['user_id']);
				$this->SetFieldOptions('email_confirm_code',NULL); $this->SetFieldOptions('password',NULL);
				echo '<p>You have successfully reset your password.</p>';
				return;
			}
		}

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
