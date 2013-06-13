<?php
class tabledef_Users extends uTableDef {
	public $tablename = 'tabledef_AdminUsers';
	public function SetupFields() {
		$this->AddField('user_id',ftNUMBER);
		$this->AddField('username',ftVARCHAR,100);
		$this->AddField('password',ftVARCHAR,250);
		$this->AddField('role',ftNUMBER);
		
		$this->AddField('email_confirm',ftVARCHAR,150);
		$this->AddField('email_confirm_code',ftVARCHAR,100);
		
		$this->AddField('last_login',ftDATE);
		
		// require verification
		// start with random email field - maybe hash the email?
		// 

		$this->SetPrimaryKey('user_id');
		$this->SetIndexField(array('username','email_confirm_code','role'));
	}
	public function UpdateField($fieldName,$newValue,&$pkVal=NULL,$fieldType=NULL) {
		if ($fieldName == 'username') {
			$newValue = trim($newValue);
			// does this email already exist?
			$r = database::query('SELECT * FROM `'.$this->tablename.'` WHERE `username` = ?',array($newValue));
			if ($r->fetch()) {
				uNotices::AddNotice('This email is already registered to an account.',NOTICE_TYPE_ERROR);
				return FALSE;
			}
			
			if (preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i',$newValue)) { // email address has been updated - set email_confirm and email_confirm_code
				if ($pkVal === NULL) parent::UpdateField('username',$newValue,$pkVal);
				parent::UpdateField('email_confirm',$newValue,$pkVal);
			} else {
				parent::UpdateField('username',$newValue,$pkVal);
			}
			return TRUE;
		}
		if ($pkVal === NULL) parent::UpdateField('username','unverified_'.uCrypt::GetRandom(75),$pkVal);
		if ($fieldName == 'email_confirm_code' && $newValue === true) {
			// get old username
			$r = database::query('SELECT username, email_confirm FROM `'.$this->tablename.'` WHERE user_id = ?',array($pkVal));
			if ($r && ($row = $r->fetch()) && $row['email_confirm']) {
				$old = $row['username']; $new = $row['email_confirm'];
				// set username to email_confirm
				parent::UpdateField('username','email_confirm',$pkVal,ftRAW);
				// clear email_confirm + code
				parent::UpdateField('email_confirm','',$pkVal);
				parent::UpdateField('email_confirm_code','',$pkVal);
				if ($old != $new) uEvents::TriggerEvent('UsernameChanged',NULL,array($old,$new));
			}
		}
		parent::UpdateField($fieldName,$newValue,$pkVal,$fieldType);
	}
}

class uUsersList extends uListDataModule implements iAdminModule {
	public function GetTitle() { return 'Users'; }
	public function GetOptions() { return ALWAYS_ACTIVE | ALLOW_ADD | ALLOW_DELETE | ALLOW_EDIT | ALLOW_FILTER; }

	public function GetTabledef() { return 'tabledef_Users'; }
	public function SetupFields() {
		$this->CreateTable('users');
		$this->CreateTable('roles','tabledef_UserRoles','users',array('role'=>'role_id'));
		
		$fld =& $this->AddField('gravatar','username','users',''); $fld['size'] = 24;
		$this->AddPreProcessCallback('gravatar',array('uGravatar','GetImageField'));
		
		$this->AddField('username','username','users','Username',itTEXT);
		$this->AddField('role','role','users','Role',itCOMBO,'SELECT role_id,name FROM '.TABLE_PREFIX.'tabledef_UserRoles ORDER BY role_id');
		$this->AddField('last_login','last_login','users','Last Login');
		$this->AddField('password','password','users','Change Password',itPASSWORD);
		$this->AddField('email_confirm','email_confirm','users');
		$this->AddField('email_confirm_code','email_confirm_code','users');
		$this->AddField('validated','({email_confirm} = \'\' OR {email_confirm} IS NULL)','users','Validate');
		$this->AddPreProcessCallback('validated',array($this,'ValidateButtons'));
	}

	public function SetupParents() {
		$this->AddParent('/');
	}

	public function RunModule() {
		$this->ShowData();
	}
	
	public function ValidateButtons($originalValue,$pkVal,$value,$rec,$fieldName) {
		if ($originalValue == 1 || $pkVal === NULL) {
			return '';
		}
		return $this->DrawSqlInput('_validate_user','Force Validate',$pkVal,NULL,itBUTTON).$this->DrawSqlInput('_validate_send','Send Validation',$pkVal,NULL,itBUTTON);
	}
	
	public function UpdateField($fieldAlias,$newValue,&$pkVal=NULL) {
		if ($fieldAlias == 'role' && isset($_SESSION['current_user']) && $pkVal == $_SESSION['current_user']) {
			uNotices::AddNotice('You cannot edit your own role',NOTICE_TYPE_ERROR);
			return;
		}
		if ($fieldAlias == '_validate_user') return $this->UpdateField('email_confirm_code',true,$pkVal);
		if ($fieldAlias == '_validate_send') { uVerifyEmail::VerifyAccount($pkVal); return; }
		parent::UpdateField($fieldAlias,$newValue,$pkVal);
	}
	
	public static function TestCredentials($username,$password) {
		$obj =& utopia::GetInstance(__CLASS__);
		$obj->BypassSecurity(true);
		$rec = $obj->LookupRecord(array('username'=>$username));
		$obj->BypassSecurity(false);
		if (!$rec) return false;
		if (!uCrypt::Test($password,$rec['password'])) return false;
		
		return $rec['user_id'];
	}
}

class uAssertAdminUser extends uBasicModule {
	public function GetTitle() { return 'Create Admin User'; }
	public function SetupParents() {
		uEvents::AddCallback('AfterInit',array($this,'AssertAdminUser'));
		module_Offline::IgnoreClass(__CLASS__);
	}
	public function AssertAdminUser() {
		// admin user exists?
		$obj =& utopia::GetInstance('uUsersList');
		$obj->BypassSecurity(true);
		$rec = $obj->LookupRecord(array('role'=>-1,'validated'=>1),true);
		$obj->BypassSecurity(false);
		if ($rec) return;

		// module is persist?
		$curr =& utopia::GetInstance(utopia::GetCurrentModule());
		if (flag_is_set($curr->GetOptions(),PERSISTENT)) return;

		// redirect to this module
		$this->AssertURL(307,false);
	}
	public function RunModule() {
		$obj =& utopia::GetInstance('uUsersList');
		$obj->BypassSecurity(true);
		$rec = $obj->LookupRecord(array('role'=>-1,'validated'=>1),true);
		$obj->BypassSecurity(false);
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
		$regObj =& utopia::GetInstance('uRegisterUser');
		$user_id = $regObj->RegisterForm();
		// login as this user, then perform the update

		if ($user_id) {
			// now set this users role
			$regObj->UpdateField('role',-1,$user_id);
			unset($_SESSION['db_admin_authed']);
			uNotices::AddNotice('Admin user has now been set up.');
			
			uVerifyEmail::VerifyAccount($user_id);
			
			header('Location: '.PATH_REL_CORE); die();
		}

		if ($_POST && isset($_POST['username'])) {
			$rec = $obj->LookupRecord(array('username'=>$_POST['username']),true);
			uVerifyEmail::VerifyAccount($rec['user_id']);
		}
	}
}

class uRegisterUser extends uDataModule {
	public function GetOptions() { return ALLOW_ADD | ALLOW_EDIT; }
	public function GetTitle() { return 'User Registration'; }
	public function GetTabledef() { return 'tabledef_Users'; }
	public function SetupFields() {
		$this->CreateTable('users');
		
		$this->AddField('username','username','users','Username',itTEXT);
		$this->AddField('password','password','users','Password',itPASSWORD);
		$this->AddField('role','role','users');

		$this->AddField('email_confirm_code','email_confirm_code','users');

		uEmailer::InitialiseTemplate('account_activate','Confirm your email address','<p>Please verify your email by clicking the link below:</p><p><a href="{home_url_abs}/{activate_link}">{home_url_abs}/{activate_link}</a></p>',array('email','active_link'));
	}
	public function SetupParents() {
		$this->SetRewrite(false);
		uEvents::AddCallback('AfterShowLogin',array($this,'RegisterLink'));
		modOpts::AddOption('open_user_registration','Allow User Registrations',NULL,false,itYESNO);
	}
	public static $uuid = 'register';
	public function RegisterLink() {
		if (!modOpts::GetOption('open_user_registration')) return;
		if ($usr = $this->RegisterForm()) {
			uVerifyEmail::VerifyAccount($usr);
			echo '<p>Thank you for creating an account.  We need to verify your email before you can continue.</p>';
			echo '<p>Please check your inbox, and follow the instructions we have sent you.</p>';
		}
//		echo '<p>Don&apos;t have an account?  <a href="'.$this->GetURL().'">Register</a> now.</p>';
	}
	public function RunModule() {
		if (!modOpts::GetOption('open_user_registration')) {
			echo '<h1>User Registration</h1>';
			echo '<p>Sorry. User registrations have been disabled by the administrator.</p>';
			return;
		}
		// already logged in?
		if (uUserLogin::IsLoggedIn()) {
			echo '<p>You are already logged in.</p>';
			if (uEvents::TriggerEvent('CanAccessModule','uCMS_Edit') === FALSE) {
				$o =& utopia::GetInstance('uUserProfile');
				header('Location: '.$o->GetURL());
			}
			return;
		}
		
		if ($usr = $this->RegisterForm()) {
			uVerifyEmail::VerifyAccount($usr);
			echo '<p>Thank you for creating an account.  We need to verify your email before you can continue.</p>';
			echo '<p>Please check your inbox, and follow the instructions we have sent you.</p>';
		}
	}
	public function UpdateField($fieldAlias,$newValue,&$pkVal=NULL) {
		if ($fieldAlias == 'username') {
			$newValue = trim($newValue);
			if (!preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i',$newValue)) {
				uNotices::AddNotice('You must enter a valid email address.',NOTICE_TYPE_ERROR);
				return FALSE;
			}
		}
		return parent::UpdateField($fieldAlias,$newValue,$pkVal);
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
				if ($_POST['username'] !== $_POST['username2']) {
					uNotices::AddNotice('Emails do not match.',NOTICE_TYPE_ERROR);
					break;
				}
				
				if ($_POST['password'] === '') {
					uNotices::AddNotice('Password cannot be blank.',NOTICE_TYPE_ERROR);
					break;
				}
				
				// user already exists?
				//$this =& utopia::GetInstance('uUsersList');
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
		<div id="register-wrap">
		<h1>Create an Account</h1>
		<form class="register-user oh" action="{home_url}register" method="POST">
			<div class="form-field"><label for="username">Email:</label>
			<input type="text" name="username" id="username" value="<?php echo isset($_POST['username']) ? htmlentities(utf8_decode($_POST['username'])):''; ?>" /></div>
			<div class="form-field"><label for="username2">Confirm Email:</label>
			<input type="text" name="username2" id="username2" value="<?php echo isset($_POST['username2']) ? htmlentities(utf8_decode($_POST['username2'])):''; ?>" /></div>
			<div class="form-field"><label for="password">Password:</label>
			<input type="password" name="password" id="password" /></div>
			<p>You will be sent an email to confirm your details and activate your account.</p>
			<input class="btn right" type="submit" value="Register" />
		</form>
		<script>
		function regValidate(){
			$('#username2').css('border-color','');
			if ($('#username2').val() == '') return;
			if ($('#username2').val() !== $('#username').val()) {
				$('#username2').css('border-color','#f00');
			} else {
				$('#username2').css('border-color','#0f0');
			}
		};
		$('#username').change(regValidate).change();
		$('#username2').change(regValidate).change();
		</script>
		</div>
		<?php
	}
}

class uVerifyEmail extends uDataModule {
	public function GetTitle() { return 'Verify'; }
	public function GetTabledef() { return 'tabledef_Users'; }
	public function GetOptions() { return ALLOW_EDIT | PERSISTENT; }

	public static $uuid = 'verify-email';

	public function SetupParents() {
		$this->SetRewrite(array('{c}'));
		uEvents::AddCallback('AfterUpdateField',array($this,'UpdatedEmail'),'uUserProfile');
	}
	public function UpdatedEmail($o,$e,$f,$v,$pk) {
		if ($f !== 'username') return;
		uVerifyEmail::VerifyAccount($pk);
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
			if (!uUserLogin::IsLoggedIn()) uNotices::AddNotice('Could not validate your request.  This could be because you have already validated your email.  Please log in.');
		} else {
			$this->UpdateField('email_confirm_code',true,$rec['user_id']);
			uUserLogin::SetLogin($rec['user_id']);
			uNotices::AddNotice('Thank you!  Your email address has been successfully validated.');
		}
		$o =& utopia::GetInstance('uUserProfile');
		header('Location: '.$o->GetURL());
	}
	public static function VerifyAccount($user_id) {
		$o =& utopia::GetInstance(__CLASS__);
		$rec = $o->LookupRecord($user_id);

		// already verified
		if (!$rec['email_confirm']) return true;

		// account email changed, send 
		$randKey = uCrypt::GetRandom(20);
		$o->UpdateField('email_confirm_code',$randKey,$user_id);
		$url = $o->GetURL(array('c'=>$randKey));
		$url = preg_replace('/^'.preg_quote(PATH_REL_ROOT,'/').'/','',$url);
		uNotices::AddNotice('Please check '.$rec['email_confirm'].' for a validation link.');
		uEmailer::SendEmailTemplate('account_activate',array('email'=>$rec['email_confirm'],'activate_link'=>$url),'email');
		return false;
	}
}

class uUserProfile extends uSingleDataModule {
	public function GetTitle() { return 'User Profile'; }
	public function GetOptions() { return ALLOW_EDIT | ALLOW_FILTER; }
	public function GetTabledef() { return 'tabledef_Users'; }
	public function GetSortOrder() { return 10000000; }
	public $isAjax = false;
	public function SetupFields() {
		$this->CreateTable('users');
		
		$this->NewSection('Account Details');
		$this->AddField('user_id','user_id','users');
		$this->AddSpacer('<b style="font-size:1.1em">Change Email</b>');
		$this->AddSpacer('We will send a message to your new email address.  You must click the verification link to complete this process.');
		$this->AddField('username','username','users','Email',itTEXT);
		$this->AddField('current_password_email','','','Password',itPASSWORD);
		$this->AddField('submit_email',"'Change Email'",'','',itSUBMIT);
		$this->AddSpacer();
		$this->AddSpacer('<b style="font-size:1.1em">Change Password</b>');
		$this->AddField('password','password','users','New Password',itPASSWORD);
		$this->AddField('confirm_password','','','Confirm New Password',itPASSWORD);
		$this->AddField('current_password','','','Old Password',itPASSWORD);
		$this->AddField('submit',"'Change Password'",'','',itSUBMIT);

		$l = uUserLogin::IsLoggedIn();
		$this->AddFilter('user_id',ctEQ,itNONE,$l);
	}
	public static $uuid = 'user-profile';
	public function SetupParents() {
		$this->SetRewrite(true);
	}
	public function RunModule() {
		$l = uUserLogin::IsLoggedIn();
		if (!$l) {
			$obj =& utopia::GetInstance('uUserLogin');
			$obj->_RunModule();
			return;
		}
		echo '<h1>User Profile</h1>';
		$this->ShowData();
	}
	public static function GetCurrentUser() {
		$o =& utopia::GetInstance(__CLASS__);
		return $o->LookupRecord();
	}
	public function UpdateField($fieldAlias,$newValue,&$pkVal=NULL) {
		$cUser = $this->LookupRecord(array('user_id'=>uUserLogin::IsLoggedIn()));
		if ($fieldAlias == 'username') {
			$newValue = trim($newValue);
			if ($newValue === $cUser['username']) return;
			if (!preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i',$newValue)) {
				uNotices::AddNotice('You must enter a valid email address.',NOTICE_TYPE_ERROR);
				return;
			}
			if (uUsersList::TestCredentials($cUser['username'],$_POST[$this->CreateSqlField('current_password_email',$pkVal)]) === false) {
				uNotices::AddNotice('The password you entered does not match our records.',NOTICE_TYPE_ERROR);
				return;
			}
			uNotices::AddNotice('You must validate your new email address before you are able to log in with it.');
		}
		if ($fieldAlias == 'password') {
			if (!$newValue) return;
			if ($newValue !== $_POST[$this->CreateSqlField('confirm_password',$pkVal)]) {
				uNotices::AddNotice('Password confirmation did not match, please try again.',NOTICE_TYPE_WARNING);
				return;
			}
			if (uUsersList::TestCredentials($cUser['username'],$_POST[$this->CreateSqlField('current_password',$pkVal)]) === false) {
				uNotices::AddNotice('The password you entered does not match our records.',NOTICE_TYPE_ERROR);
				return;
			}
			uNotices::AddNotice('Your password has been updated.');
		}
		return parent::UpdateField($fieldAlias,$newValue,$pkVal);
	}
}
