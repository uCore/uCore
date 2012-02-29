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
			uEmailer::SendEmail('account_activate',array('email'=>$newValue,'activate_link'=>$url),'email');
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
	public function GetTitle() { return 'User Accounts'; }
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
			unset($_SESSION['db_admin_authed']);
			echo '<p>Admin user has now been set up.</p>';
		}
	}
}

class uRegisterUser extends uDataModule {
	public function GetOptions() { return ALLOW_ADD; }

	public function GetTabledef() { return 'tabledef_Users'; }
	public function SetupFields() {
		$this->CreateTable('users');
		$this->CreateTable('roles','tabledef_UserRoles','users',array('role'=>'role_id'));
		
		$this->AddField('username','username','users','Username',itTEXT);
		$this->AddField('password','password','users','Password',itPASSWORD);
		$this->AddField('role','name','roles','Role',itCOMBO);
	}
	public function SetupParents() {
		$this->SetRewrite(false);
		uEvents::AddCallback('LoginRequired',array($this,'RegisterLink'));
		modOpts::AddOption(__CLASS__,'open_user_registration','Allow User Registrations',false,itYESNO);
	}
	public function GetUUID() { return 'register'; }
	public function RegisterLink() {
		if (!modOpts::GetOption(__CLASS__,'open_user_registration')) return;
		echo '<p>Don&apos;t have an account?  <a href="'.$this->GetURL().'">Register</a> now.</p>';
	}
	public function RunModule() {
		echo '<h1>User Registration</h1>';
		if (!modOpts::GetOption(__CLASS__,'open_user_registration')) {
			echo '<p>Sorry. User registrations have been disabled by the administrator.</p>';
			return;
		}
		// already logged in?
		if ($this->RegisterForm()) {
			uNotices::AddNotice('Your account has now been created');
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
					return FALSE;
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
		<p>Please enter the following details to set up your account.</p>
		<style>
			form.register-user label { float:left; clear:both; display:block; width:150px; }
			form.register-user input { float:left; width:150px; box-sizing: border-box; }
		</style>
		<form class="register-user" action="" method="POST">
			<label for="username">Email:</label>
			<input type="text" name="username" id="username" value="<?php echo isset($_POST['username']) ? htmlentities(utf8_decode($_POST['username'])):''; ?>" />
			<label for="password">Password:</label>
			<input type="password" name="password" id="password" />
			<label for="password2">Confirm Password:</label>
			<input type="password" name="password2" id="password2" />
			<label>&nbsp;</label><input type="submit" value="Register" />
		</form>
		<?php
	}
}

class uResetPassword extends uDataModule {
	public function GetTitle() { return 'Reset Password'; }
	public function GetTabledef() { return 'tabledef_Users'; }

	public function GetUUID() { return 'reset-password'; }

	public function SetupParents() {
		$this->SetRewrite(array('{e}','{c}'));
	}

	public function SetupFields() {
		$this->CreateTable('users');
		$this->CreateTable('roles','tabledef_UserRoles','users',array('role'=>'role_id'));

		$this->AddField('username','username','users','Username',itTEXT);
		$this->AddField('password','password','users','Password',itPASSWORD);
		$this->AddField('username','username','users','Username',itTEXT);
		$this->AddField('username','username','users','Username',itTEXT);
		$this->AddField('role','name','roles','Role',itCOMBO);
	}

	public function ResetPW($user) {
		$rec = $this->LookupRecord(array('email'=>$user));
		if (!$rec) return FALSE; // user not found.

		$randKey = genRandom(20);
		$this->UpdateField('pw_reset',$randKey,$rec['account_id']);

		//email out verification
		$name = $rec['contact_name'] ? ' '.$rec['contact_name'] : '';
		if (empty($rec['password']))
			uDocuments::toEmail('account_activate',array('email'=>$user,'contact_name'=>$name,'activate_link'=>'http://'.utopia::GetDomainName()."/resetpw/$user/$randKey"),'email','Top4 Accounts');
		else
			uDocuments::toEmail('account_resetpw',array('email'=>$user,'contact_name'=>$name,'activate_link'=>'http://'.utopia::GetDomainName()."/resetpw/$user/$randKey"),'email','Top4 Accounts');
	}

	public function RunModule() {
		$noticeBox = '<div style="color:#b20000;background-color:#eee8e5; border:1px solid #b20000; padding:10px;"><span style="font-weight:bold;font-size:1.25em;">! Important Message</span><br>';
		$email = array_key_exists('e',$_REQUEST) ? $_REQUEST['e'] : '';
		$notice = '';

		$rec = $this->LookupRecord(array('email'=>$email));
		if (empty($email) || !$rec) {
			if (!$rec && !empty($email)) echo $noticeBox.'No account was found with this email address. Please try again.</div>';
			echo '<h1 style="color:#336600">Reset Password</h1><div style="margin-left:20px;">';
			echo '<form id="loginForm" action="" onsubmit="this.action = window.location;" method="post">';
			echo '<div style="color:#336600;margin-top:10px">What is your email address?</div>';
			echo '<div style="margin-left:20px;">My e-mail address is '.utopia::DrawInput('e',itTEXT).'</div>';
			echo '<input type="image" style="border:none" src="/uTemplates/top4/images/confirm.png" height="25px">';
			echo '</form></div>';
			return;
		}

		if (!array_key_exists('c',$_REQUEST)) { // reset pw
			echo '<p>An email has been sent to &quot;'.$email.'&quot; with your password reset link. Please click the link and enter a new password for your account.</p><p>Please be patient; the delivery of email may be delayed. Remember to confirm that the email above is correct and to check your junk or spam folder or filter if you do not receive this email.</p>';
			$this->ResetPW($email);
			return true;
		}

		if ($rec['pw_reset'] !== $_REQUEST['c']) {
			echo '<p>Unfortunately we could not validate this request.</p><p>If you are trying to activate your account or reset your password, please <a href="/resetpw">click here</a> for a new link.</p>';
			return;
		}
		if (array_key_exists('__newpass_c',$_POST)) {
			if ($_POST['__newpass'] !== $_POST['__newpass_c']) {
				$notice = $noticeBox.'Password confirmation did not match, please try again.</div>';
			} else {
				$this->UpdateFields(array('pw_reset'=>'','password'=>md5($_POST['__newpass'])),$rec['account_id']);
				$obj = utopia::GetInstance('module_AccountLogin');
				echo '<p>You have successfully reset your password.</p><p>You may now <a href="'.$obj->GetURL().'">Log In</a></p>';
				return;
			}
		}

		echo $notice;
		if (empty($rec['password'])) $action = 'Activate Account';
		else $action = 'Reset Password';
		echo '<h1 style="color:#336600">'.$action.'</h1><div style="margin-left:20px;">';
		echo '<form id="loginForm" action="" onsubmit="this.action = window.location;" method="post"><input type="hidden" name="e" value="'.$email.'"><input type="hidden" name="c" value="'.$_REQUEST['c'].'">';
		echo '<table style="margin-left:20px;margin-top:10px;" cellpadding="5">';
		echo '<tr><td align="right">New Password:</td><td>'.utopia::DrawInput('__newpass',itPASSWORD).'</td></tr>';
		echo '<tr><td align="right">Confirm Password:</td><td>'.utopia::DrawInput('__newpass_c',itPASSWORD).'</td></tr>';
		echo '<tr><td colspan="2" align="right"><input type="image" style="border:none" src="/uTemplates/top4/images/confirm.png" height="25px"></td></tr>';
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
		if (!isset($_GET['c']) || !($rec = $this->LookupRecord(array('email_confirm_code'=>$_GET['c'])))) { // reset pw
			// no code given or code not found.
			uNotices::AddNotice('Could not validate your request.  If you are trying to change your email, please log in with your old credentials and re-submit the request.',NOTICE_TYPE_ERROR);
			return;
		}

		$this->UpdateField('email_confirm_code',true,$rec['user_id']);
		$obj = utopia::GetInstance('uUserLogin');
		uNotices::AddNotice('Your email address has now been validated. You may now <a href="'.$obj->GetURL().'">Log In</a>');
	}
}
/*class tabledef_UserDetails extends uTableDef {
	public function SetupFields() {
		$this->AddField('detail_id',ftNUMBER);
		$this->AddField('user_id',ftNUMBER);
		$this->AddField('name',ftVARCHAR,50);

		$this->SetPrimaryKey('detail_id');
		
		uEvents::AddCallback('uUsersList','AfterSetupFields',array($this,'addfields'));
	}
	
	public function addfields($obj, $eventName) {
		$obj->CreateTable('detail','tabledef_UserDetails','users','user_id');
		$obj->AddField('name','name','detail','Name',itTEXT);
	}
}
*/
