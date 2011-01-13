<?php
define('ADMIN_USER',flag_gen());

class internalmodule_AdminLogin extends uBasicModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return 'Admin Login'; }
	public function GetOptions() { return ALWAYS_ACTIVE | NO_HISTORY | PERSISTENT_PARENT | IS_ADMIN | NO_NAV; }

	public function SetupParents() {
		$this->RegisterAjax('adminLogout',array($this,'AdminLogout'));
		$this->AddParent('/');

		// admin account has not been set up, redirect to config.
		if (!constant('admin_user')) {
			utopia::cancelTemplate();
			echo 'No admin user has been set up.';
			uConfig::ShowConfig();
			die();
		}

		// login not attempted.
		if (!array_key_exists('__admin_login_u',$_REQUEST)) return;

		$un = $_REQUEST['__admin_login_u']; $pw = $_REQUEST['__admin_login_p'];
		if ( $un==constant('admin_user') && $pw===constant('admin_pass') ) {
			$_SESSION['admin_auth'] = ADMIN_USER;
		} else {
			ErrorLog('Username and password do not match.');
		}

		if (self::IsLoggedIn() && ((GetCurrentModule() == get_class($this)) || (array_key_exists('adminredirect',$_REQUEST) && $_REQUEST['adminredirect'] == 1))) {
			header('Location: '.CallModuleFunc('internalmodule_Admin','GetURL')); die();
		}
	}
  
	public function AdminLogout() {
		unset($_SESSION['admin_auth']);
		die('window.location.reload();');
	}

	public static function IsLoggedIn($authType = ADMIN_USER, $orHigher=true) {
		if ($orHigher)
			return array_key_exists('admin_auth',$_SESSION) && ($_SESSION['admin_auth'] >= $authType);
		else
			return array_key_exists('admin_auth',$_SESSION) && ($_SESSION['admin_auth'] == $authType);
	}
/*
	private $map = array();
	public static function RequireLogin($module,$authType = true, $orHigher=true) {
		self::$map[$module] = array($authType,$orHigher);
	}

	public static function IsAuthed($module) {
		if (!array_key_exists($module,self::$map)) return true;
		return self::IsLoggedIn(self::$map[$module][0],self::$map[$module][1]);
		//return array_key_exists('admin_auth',$_SESSION) && ($_SESSION['admin_auth'] >= $authType);
	}*/

	public function ParentLoadPoint() { return 0; }
	public function ParentLoad($parent) {
		// if auth not required, return
		if (!flag_is_set(CallModuleFunc($parent,'GetOptions'),IS_ADMIN)) return true;
		if ($parent === get_class($this)) return true;

		// if authed, dont show the login
		if (!self::IsLoggedIn()) {
			if (!AjaxEcho('window.location.reload();') && $parent == GetCurrentModule()) {
				$this->_RunModule();
			}
			return FALSE;
		}
	}

	public function GetAdminPanel() {
		$errs = utopia::GetVar('error_log'); if (!$errs) $errs = 'No Errors';
		if (!self::IsLoggedIn()) {
			return '<form id="loginForm" action="" onsubmit="this.action = window.location;" method="post"><input type="hidden" name="adminredirect" value="1">'.
			'U:'.utopia::DrawInput('__admin_login_u',itTEXT,'',NULL,array('style'=>'width:100px')).' P:'.utopia::DrawInput('__admin_login_p',itPASSWORD,'',NULL,array('style'=>'width:100px')).
			' '.utopia::DrawInput('',itSUBMIT,'Log In').'</form>';
		}

		return '[ <a href="'.CallModuleFunc('internalmodule_Admin','GetURL').'">Admin Home</a> ] [ <a href="#" onclick="javascript:$.getScript(\'?__ajax=adminLogout\')">Logout</a> ] [ <a href="#" onclick="javascript:$.getScript(\'?__ajax=toggleT\')">Toggle Timers</a> ]'.// [ <a href="#" onclick="javascript:$(\'#errFrame\').toggle()">Show Errors</a> ]'.
				'<div id="errFrame" style="display:none; max-height:500px; overflow:scroll;">'.$errs.'</div>';
	}
  
  static function RequireLogin($accounts=NULL) {
//    if ()
  }

	public function RunModule() {
		//__admin_login_u
		//__admin_login_p
		// perform login
		echo 'Please log in';
		echo '<form id="loginForm" action="" onsubmit="this.action = window.location;" method="post"><table>';
		echo '<tr><td align="right">Username:</td><td>'.utopia::DrawInput('__admin_login_u',itTEXT).'</td></tr>';
		echo '<tr><td align="right">Password:</td><td>'.utopia::DrawInput('__admin_login_p',itPASSWORD).'</td></tr>';
		echo '<tr><td></td><td align="right">'.utopia::DrawInput('',itSUBMIT,'Log In').'</td></tr>';
		echo '</table></form><script type="text/javascript">$(document).ready(function (){$(\'#lu\').focus()})</script>';
	}
}
?>
