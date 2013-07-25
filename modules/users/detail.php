<?php

class tabledef_UserProfile extends uTableDef {
	public function SetupFields() {
		$this->AddField('detail_id',ftNUMBER);
		$this->AddField('user_id',ftNUMBER);
		$this->AddField('first_name',ftVARCHAR,50);
		$this->AddField('last_name',ftVARCHAR,50);
		$this->AddField('phone',ftVARCHAR,50);
		$this->AddField('mobile',ftVARCHAR,50);
		
		$this->AddField('address1',ftVARCHAR,75);
		$this->AddField('address2',ftVARCHAR,75);
		$this->AddField('town',ftVARCHAR,75);
		$this->AddField('county',ftVARCHAR,75);
		$this->AddField('country',ftVARCHAR,75);
		$this->AddField('postcode',ftVARCHAR,75);
		
		$this->AddInputDate('last_update',true);
		
		$this->SetPrimaryKey('detail_id');
		$this->SetUniqueField('user_id');
		$this->SetIndexField('last_update');
	}
}
class UserProfileDetail extends uSingleDataModule {
	public function GetTitle() { return 'My Profile'; }
	public function GetTabledef() { return 'tabledef_Users'; }
	public function GetOptions() { return ALLOW_EDIT | ALLOW_ADD; }
	public function SetupParents() { }
	public function RunModule() {
		if (!($l = uUserLogin::IsLoggedIn())) return;
		$this->ShowData();
	}
	public function SetupFields() {
		$this->CreateTable('user');
		$this->CreateTable('detail','tabledef_UserProfile','user','user_id');
		$this->AddSpacer('<b style="font-size:1.2em">Personal Details</b>');
		
		$l = uUserLogin::IsLoggedIn();
		$this->AddFilter('user_id',ctEQ,itNONE,$l);
		
		$this->AddField('user_id_detail','user_id','detail');
		$this->SetDefaultValue('user_id_detail',$l);
		
		$this->AddField('username','username','user');
		$this->AddField('visible_name','(IF(TRIM(CONCAT(COALESCE({first_name},\'\'),\' \',COALESCE({last_name},\'\'))) != \'\',TRIM(CONCAT(COALESCE({first_name},\'\'),\' \',COALESCE({last_name},\'\'))),`user`.`username`))','detail');
		
		$this->AddField('first_name','first_name','detail','First Name',itTEXT);
		$this->AddField('last_name','last_name','detail','Last Name',itTEXT);
		$this->AddField('phone','phone','detail','Home Phone',itTEXT);
		$this->AddField('mobile','mobile','detail','Mobile Phone',itTEXT);
		$this->AddSpacer();
		$this->AddField('address1','address1','detail','Address',itTEXT);
		$this->AddField('address2','address2','detail','',itTEXT);
		$this->AddField('town','town','detail','Town',itTEXT);
		$this->AddField('county','county','detail','County',itTEXT);
		$this->AddField('country','country','detail','Country',itTEXT);
		$this->AddField('postcode','postcode','detail','Post Code',itTEXT);
	}
}
class UserDetailAdmin extends uSingleDataModule implements iAdminModule {
	public function GetTitle() { return 'User Details'; }
	public function GetTabledef() { return 'tabledef_Users'; }
	public function GetOptions() { return ALLOW_EDIT | ALLOW_ADD; }
	public function SetupParents() {
		$this->AddParent('uUsersList','user_id','*');
	}
	public function RunModule() {
		$this->ShowData();
	}
	public function SetupFields() {
		$this->CreateTable('user');
		$this->CreateTable('detail','tabledef_UserProfile','user','user_id');
		
		$this->NewSection('Account Details');
		
		$this->AddField('username','username','user','Email',itTEXT);
		$this->AddField('password','password','user','Password',itPASSWORD);
		$this->AddField('validated','({email_confirm} = \'\' OR {email_confirm} IS NULL)','user','Validation');
		$this->AddPreProcessCallback('validated',array($this,'ValidateButtons'));
		$this->SetFieldProperty('validated','nolink',true);
		
		$this->NewSection('Personal Details');
		
		$this->AddField('user_id_detail','user_id','detail');
		
		$this->AddField('first_name','first_name','detail','First Name',itTEXT);
		$this->AddField('last_name','last_name','detail','Last Name',itTEXT);
		$this->AddField('phone','phone','detail','Home Phone',itTEXT);
		$this->AddField('mobile','mobile','detail','Mobile Phone',itTEXT);
		$this->AddSpacer();
		$this->AddField('address1','address1','detail','Address',itTEXT);
		$this->AddField('address2','address2','detail','',itTEXT);
		$this->AddField('town','town','detail','Town',itTEXT);
		$this->AddField('county','county','detail','County',itTEXT);
		$this->AddField('country','country','detail','Country',itTEXT);
		$this->AddField('postcode','postcode','detail','Post Code',itTEXT);
	}
	
	public function ValidateButtons($originalValue,$pkVal,$value,$rec,$fieldName) {
		if ($originalValue == 1 || $pkVal === NULL) {
			return 'Done';
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
}
uEvents::AddCallback('AfterRunModule',array(utopia::GetInstance('UserProfileDetail'),'RunModule'),'uUserProfile',101);