<?php
class tabledef_Users extends uTableDef {
	public $tablename = 'tabledef_AdminUsers';
	public function SetupFields() {
		$this->AddField('user_id',ftNUMBER);
		$this->AddField('username',ftVARCHAR,100);
		$this->AddField('password',ftVARCHAR,100);
		$this->AddField('role',ftNUMBER);
		
		$this->AddField('email_confirm',ftVARCHAR,150);
		$this->AddField('email_confirm_code',ftVARCHAR,100);
		
		// require verification
		// start with random email field - maybe hash the email?
		// 

		$this->SetPrimaryKey('user_id');
		$this->SetIndexField('username');
		
		uEmailer::InitialiseTemplate('account_activate','Confirm your email address','<p>Please verify your email by clicking the link below:</p><p>{activate_link}</p>',array('email','active_link'));
	}
	public function UpdateField($fieldName,$newValue,&$pkVal=NULL,$fieldType=NULL) {
		if ($fieldName == 'username') {
			$newValue = trim($newValue);
			if (!preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i',$newValue)) {
				uNotices::AddNotice('You must enter a valid email address.',NOTICE_TYPE_ERROR);
				return FALSE;
			}
			// does this email already exist?
			$r = sql_query('SELECT * FROM `'.$this->tablename.'` WHERE `username` = \''.$newValue.'\'');
			if (mysql_num_rows($r)) {
				uNotices::AddNotice('This email is already registered to an account.',NOTICE_TYPE_ERROR);
				return FALSE;
			}
			
			if ($pkVal === NULL) parent::UpdateField('username','unverified_'.genRandom(75),$pkVal);
			// email address has been updated - set email_confirm and email_confirm_code
			$randKey = genRandom(20);
			parent::UpdateField('email_confirm',$newValue,$pkVal);
			parent::UpdateField('email_confirm_code',$randKey,$pkVal);
			// TODO: send verification email!
			$obj = utopia::GetInstance('uVerifyEmail');
			$url = 'http://'.utopia::GetDomainName().$obj->GetURL(array('c'=>$randKey));
			uNotices::AddNotice('Please check your email for a validation link.');
			uEmailer::SendEmailTemplate('account_activate',array('email'=>$newValue,'activate_link'=>$url),'email');
			return TRUE;
		}
		if ($pkVal === NULL) parent::UpdateField('username','unverified_'.genRandom(75),$pkVal);
		if ($fieldName == 'email_confirm_code' && $newValue === true) {
			// set username to email_confirm
			parent::UpdateField('username','email_confirm',$pkVal,ftRAW);
			// clear email_confirm + code
			parent::UpdateField('email_confirm','',$pkVal);
			parent::UpdateField('email_confirm_code','',$pkVal);
		}
		parent::UpdateField($fieldName,$newValue,$pkVal,$fieldType);
	}
}

class uUsersList extends uListDataModule implements iAdminModule {
	public function GetTitle() { return 'Users'; }
	public function GetOptions() { return ALWAYS_ACTIVE | ALLOW_ADD | ALLOW_DELETE | ALLOW_EDIT; }

	public function GetTabledef() { return 'tabledef_Users'; }
	public function SetupFields() {
		$this->CreateTable('users');
		$this->CreateTable('roles','tabledef_UserRoles','users',array('role'=>'role_id'));
		
		$this->AddField('username','username','users','Username',itTEXT);
		$this->AddField('password','password','users','Password',itPASSWORD);
		$this->AddField('role','name','roles','Role',itCOMBO);
		$this->AddField('email_confirm','email_confirm','users','email_confirm');
	}

	public function SetupParents() {
		$this->AddParent('/');
	}

	public function RunModule() {
		$this->ShowData();
	}
	
	public function UpdateField($fieldAlias,$newValue,&$pkVal=NULL) {
		if ($fieldAlias == 'role' && $pkVal == $_SESSION['current_user']) {
			uNotices::AddNotice('You cannot edit your own role',NOTICE_TYPE_ERROR);
			return;
		}
		parent::UpdateField($fieldAlias,$newValue,$pkVal);
	}
}

class uAssertAdminUser extends uBasicModule {
	public function GetTitle() { return 'Create Admin User'; }
	public function SetupParents() {
		uEvents::AddCallback('InitComplete',array($this,'AssertAdminUser'));
		module_Offline::IgnoreClass(__CLASS__);
	}
	public function AssertAdminUser() {
		$obj = utopia::GetInstance('uUsersList');
		$rec = $obj->LookupRecord(array('_roles_pk'=>-1),true);
		if (!$rec) utopia::SetCurrentModule(__CLASS__);
	}
	public function RunModule() {
		$obj = utopia::GetInstance('uUsersList');
		$rec = $obj->LookupRecord(array('_roles_pk'=>-1),true);
		if ($rec) {
			header('Location: '.PATH_REL_CORE); die();
		}
		
		utopia::UseTemplate(TEMPLATE_ADMIN);
		
		if (isset($_POST['db_pw'])) {
			if ($_POST['db_pw'] === SQL_PASSWORD) $_SESSION['db_admin_authed'] = true;
			else uNotices::AddNotice('Sorry, you did not enter the database password correctly.',NOTICE_TYPE_WARNING);
		}
		if (!isset($_SESSION['db_admin_authed'])) {
			// first confirm db password
			echo '<p>No Site Administrator has been set up.</p><p>You can set one up now by confirming the database password:</p>';
			echo '<form action="" method="POST">';
			echo '<input type="password" name="db_pw" />';
			echo '<input type="submit" value="Confirm" />';
			echo '</form>';
			return;
		}
		
		// now register user
		$obj = utopia::GetInstance('uRegisterUser');
		$user_id = $obj->RegisterForm();
		// login as this user, then perform the update

		if ($user_id) {
			// now set this users role
			$obj->UpdateField('role',-1,$user_id);
			// and automatically verify the email
			$obj->UpdateField('email_confirm_code',true,$user_id);
			unset($_SESSION['db_admin_authed']);
			echo '<p>Admin user has now been set up.</p>';
		}
	}
}

class uRegisterUser extends uDataModule {
	public function GetOptions() { return ALLOW_ADD; }
	public function GetTitle() { return 'User Registration'; }
	public function GetTabledef() { return 'tabledef_Users'; }
	public function SetupFields() {
		$this->CreateTable('users');
		$this->CreateTable('roles','tabledef_UserRoles','users',array('role'=>'role_id'));
		
		$this->AddField('username','username','users','Username',itTEXT);
		$this->AddField('password','password','users','Password',itPASSWORD);
		$this->AddField('role','name','roles','Role',itCOMBO);

		$this->AddField('email_confirm_code','email_confirm_code','users');
	}
	public function SetupParents() {
		$this->SetRewrite(false);
		uEvents::AddCallback('LoginButtons',array($this,'RegisterLink'));
		modOpts::AddOption('open_user_registration','Allow User Registrations',NULL,false,itYESNO);
	}
	public function GetUUID() { return 'register'; }
	public function RegisterLink() {
		if (!modOpts::GetOption('open_user_registration')) return;
		echo '<a class="btn" href="'.$this->GetURL().'">Register</a>';
//		echo '<p>Don&apos;t have an account?  <a href="'.$this->GetURL().'">Register</a> now.</p>';
	}
	public function RunModule() {
		echo '<h1>User Registration</h1>';
		if (!modOpts::GetOption('open_user_registration')) {
			echo '<p>Sorry. User registrations have been disabled by the administrator.</p>';
			return;
		}
		// already logged in?
		if ($this->RegisterForm()) {
			echo '<p>Your account has now been created.</p>';
		}
	}
	public function RegisterForm() {
		if ($_POST && isset($_POST['username'])) {
			// validate user information
			do {
				// user invalid?
				if ($_POST['username'] === '') {
					uNotices::AddNotice('Username cannot be blank.',NOTICE_TYPE_ERROR);
					break;
				}
/*				if (preg_match('/[^a-z0-9\.\-]/i',$_POST['username']) > 0) {
					uNotices::AddNotice('Username contains invalid characters. Valid Characters are: A-Z, 0-9, hyphen (-) and fullstop (.)',NOTICE_TYPE_ERROR);
					break;
				}*/
				if (!preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i',$_POST['username'])) {
					uNotices::AddNotice('You must enter a valid email address.',NOTICE_TYPE_ERROR);
					break;
				}
				
				if ($_POST['password'] === '') {
					uNotices::AddNotice('Password cannot be blank.',NOTICE_TYPE_ERROR);
					break;
				}
				if ($_POST['password'] !== $_POST['password2']) {
					uNotices::AddNotice('Passwords do not match.',NOTICE_TYPE_ERROR);
					break;
				}
				
				// user already exists?
				//$this = utopia::GetInstance('uUsersList');
				$rec = $this->LookupRecord(array('username'=>$_POST['username']));
				if ($rec) {
					uNotices::AddNotice('Username already exists.',NOTICE_TYPE_ERROR);
					break;
				}
				
				// add record
				$pk = NULL;
				$this->UpdateFields(array('username'=>$_POST['username'],'password'=>$_POST['password']),$pk);

				return $pk;
			} while (false);
		}
		?>
		<p>To create an account, please enter the following details.</p>
		<p>You will be sent an email to confirm your details and activate your account.</p>
		<style>
			form.register-user label { float:left; clear:both; display:block; width:150px; }
			form.register-user input { float:left; width:150px; box-sizing: border-box; }
		</style>
		<form class="register-user left oh" action="" method="POST">
			<label for="username">Email:</label>
			<input type="text" name="username" id="username" value="<?php echo isset($_POST['username']) ? htmlentities(utf8_decode($_POST['username'])):''; ?>" />
			<label for="password">Password:</label>
			<input type="password" name="password" id="password" />
			<label for="password2">Confirm Password:</label>
			<input type="password" name="password2" id="password2" />
			<label>&nbsp;</label><input class="btn right" style="float:right;width:auto" type="submit" value="Register" />
		</form>
		<?php
	}
}

class uResetPassword extends uDataModule {
	public function GetTitle() { return 'Reset Password'; }
	public function GetTabledef() { return 'tabledef_Users'; }
	public function GetOptions() { return PERSISTENT; }

	public function GetUUID() { return 'reset-password'; }

	public function SetupParents() {
		$this->SetRewrite(array('{e}','{c}'));
		uEmailer::InitialiseTemplate('account_activate','Activate your account','Hi {email},<br/>Please activate your account by clicking the link below:<br/>{activate_link}');
		uEmailer::InitialiseTemplate('account_resetpw','Reset your password','Hi {email},<br/>You can reset your password by clicking the link below:<br/>{activate_link}');
		uEvents::AddCallback('LoginButtons',array($this,'forgottenPasswordButton'));
	}
	public function forgottenPasswordButton() {
		echo '<a href="'.$this->GetURL(array()).'" class="left">Forgotten Password?</a>';
	}

	public function SetupFields() {
		$this->CreateTable('users');
		$this->CreateTable('roles','tabledef_UserRoles','users',array('role'=>'role_id'));

		$this->AddField('user_id','user_id','users');
		$this->AddField('username','username','users','Username',itTEXT);
		$this->AddField('password','password','users','Password',itPASSWORD);
		$this->AddField('role','name','roles','Role',itCOMBO);
		$this->AddField('email_confirm_code','email_confirm_code','users');
	}

	public function ResetPW($user) {
		$rec = $this->LookupRecord(array('username'=>$user));
		if (!$rec) return FALSE; // user not found.

		$randKey = genRandom(20);
		$this->UpdateField('email_confirm_code',$randKey,$rec['user_id']);

		//email out verification
		$name = $rec['username'] ? ' '.$rec['username'] : '';
		if (empty($rec['password']))
			uEmailer::SendEmailTemplate('account_activate',array('email'=>$user,'contact_name'=>$name,'activate_link'=>'http://'.utopia::GetDomainName().$this->GetURL(array('e'=>$user,'c'=>$randKey))),'email');
		else
			uEmailer::SendEmailTemplate('account_resetpw',array('email'=>$user,'contact_name'=>$name,'activate_link'=>'http://'.utopia::GetDomainName().$this->GetURL(array('e'=>$user,'c'=>$randKey))),'email');
	}

	public function RunModule() {
		$noticeBox = '<div style="color:#b20000;background-color:#eee8e5; border:1px solid #b20000; padding:10px;"><span style="font-weight:bold;font-size:1.25em;">! Important Message</span><br>';
		$email = array_key_exists('e',$_REQUEST) ? $_REQUEST['e'] : '';
		$notice = '';

		$rec = $this->LookupRecord(array('username'=>$email));
		if (empty($email) || !$rec) {
			if (!$rec && !empty($email)) echo $noticeBox.'No account was found with this email address. Please try again.</div>';
			echo '<h1 style="color:#336600">Reset Password</h1><div style="margin-left:20px;">';
			echo '<form id="loginForm" action="'.$this->GetURL(array()).'" method="post">';
			echo '<div style="color:#336600;margin-top:10px">What is your email address?</div>';
			echo '<div style="margin-left:20px;">My e-mail address is '.utopia::DrawInput('e',itTEXT).'</div>';
			echo '<input type="submit" class="btn" value="Recover Password" />';
			echo '</form></div>';
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
		echo '<h1 style="color:#336600">'.$action.'</h1><div style="margin-left:20px;">';
		echo '<form id="loginForm" action="" method="post"><input type="hidden" name="e" value="'.$email.'"><input type="hidden" name="c" value="'.$_REQUEST['c'].'">';
		echo '<table style="margin-left:20px;margin-top:10px;" cellpadding="5">';
		echo '<tr><td align="right">New Password:</td><td>'.utopia::DrawInput('__newpass',itPASSWORD).'</td></tr>';
		echo '<tr><td align="right">Confirm Password:</td><td>'.utopia::DrawInput('__newpass_c',itPASSWORD).'</td></tr>';
		echo '<tr><td colspan="2" align="right"><input type="submit" class="btn" value="Set Password" /></td></tr>';
		echo '</table></form></div>';
	}
}


class uVerifyEmail extends uDataModule {
	public function GetTitle() { return 'Verify'; }
	public function GetTabledef() { return 'tabledef_Users'; }

	public function GetUUID() { return 'verify-email'; }

	public function SetupParents() {
		$this->SetRewrite(array('{c}'));
	}

	public function SetupFields() {
		$this->CreateTable('users');

		$this->AddField('username','username','users','Username',itTEXT);
		$this->AddField('password','password','users','Password',itPASSWORD);
		$this->AddField('email_confirm','email_confirm','users');
		$this->AddField('email_confirm_code','email_confirm_code');
	}

	public function RunModule() {
		echo '<h1>Email Verification</h1>';
		if (!isset($_GET['c']) || !($rec = $this->LookupRecord(array('email_confirm_code'=>$_GET['c'])))) { // reset pw
			// no code given or code not found.
			echo '<p>Could not validate your request.  If you are trying to change your email, please log in with your old credentials and re-submit the request.</p>';
		} else {
			echo '<p>Your email address has now been validated.</p>';
			$this->UpdateField('email_confirm_code',true,$rec['user_id']);
		}
	}
}

class uUserProfile extends uSingleDataModule {
	public function GetTitle() { return 'User Profile'; }
	public function GetOptions() { return ALLOW_EDIT | ALLOW_FILTER; }
	public function GetTabledef() { return 'tabledef_Users'; }
	public function SetupFields() {
		$this->CreateTable('users');
		
		$this->AddField('user_id','user_id','users');
		$this->AddField('username','username','users','Username',itTEXT);
		$this->AddField('password','password','users','Password',itPASSWORD);

		$l = uUserLogin::IsLoggedIn();
		$this->AddFilter('user_id',ctEQ,itNONE,$l);
	}
	public function GetUUID() { return 'user-profile'; }
	public function SetupParents() {
		$this->SetRewrite(true);
	}
	public function RunModule() {
		$l = uUserLogin::IsLoggedIn();
		if (!$l) {
			$obj = utopia::GetInstance('uUserLogin');
			$obj->_RunModule();
			return;
		}
		echo '<h1>User Profile</h1>';
		$this->ShowData();
	}
	public static function GetCurrentUser() {
		$o = utopia::GetInstance(__CLASS__);
		return $o->LookupRecord();
	}
}
