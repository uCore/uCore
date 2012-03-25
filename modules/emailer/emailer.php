<?php
// include SwiftMailer library
include('lib/swift_required.php');

class tabledef_EmailTemplates extends uTableDef {
	public $tablename = 'tabledef_Documents';

	public function SetupFields() {
		// add all the fields in here
		// AddField($name, $type, $length, $collation='', $attributes='', $null='not null', $default='', $extra='', $comments='')
		// SetPrimaryKey($name);

		$this->AddField('identifier',ftVARCHAR,60);
		$this->AddField('subject',ftVARCHAR,100);
		$this->AddField('body',ftLONGTEXT);

		$this->SetPrimaryKey('identifier');
	}
}
class tabledef_EmailTemplateAttachments extends uTableDef {
	public function SetupFields() {
		$this->AddField('attachment_id',ftNUMBER);
		$this->AddField('doc_id',ftVARCHAR,60);
		$this->AddField('attachment',ftFILE);

		$this->SetPrimaryKey('attachment_id');
	}
}

class uEmailTemplateList extends uListDataModule implements iAdminModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return 'Email Templates'; } //$row = $this->GetRecord($this->GetDataset(),0); return $row['name']; }
	public function GetOptions() { return ALLOW_FILTER | ALLOW_DELETE; }
	public function GetTabledef() { return 'tabledef_EmailTemplates'; }

	public function SetupParents() {
		$this->AddParent('');
	}

	public function SetupFields() {
		$this->CreateTable('docs');

		$this->AddField('ident','identifier','docs','Ident');
		$this->AddField('subject','subject','docs','Subject');
	}

	public function RunModule() {
		$this->ShowData();
	}
}
class uEmailTemplateDetails extends uSingleDataModule implements iAdminModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return 'Edit Email Template'; } //$row = $this->GetRecord($this->GetDataset(),0); return $row['name']; }
	public function GetOptions() { return ALLOW_FILTER | ALLOW_ADD | ALLOW_EDIT; }
	public function GetTabledef() { return 'tabledef_EmailTemplates'; }

	public function SetupParents() {
		$this->AddParent('uEmailTemplateList','ident','*');
	}

	public function SetupFields() {
		$this->CreateTable('docs');

		$this->AddField('ident','identifier','docs','Ident',itTEXT);
		$this->AddField('subject','subject','docs','Subject',itTEXT);
		$this->AddField('fields',array($this,'getTemplateFields'),NULL,'Fields Available');
		$this->AddField('body','body','docs','Body',itHTML);
		$this->FieldStyles_Set('body',array('width'=>'100%','height'=>'20em'));
	}
	public function getTemplateFields($_,$pk) {
		if (!isset(uEmailer::$init[$pk])) return 'Unknown';
		if (!uEmailer::$init[$pk]['fields']) return 'None';
		$ret = '';
		foreach (uEmailer::$init[$pk]['fields'] as $field) {
			$ret .= "<span onclick=\"tinyMCE.execCommand('mceInsertContent',false,'{'+$(this).text()+'}');\" style=\"margin:0 5px;cursor:pointer\" class=\"btn btn-mce-insert\">{$field}</span>";
		}
		return $ret;
	}
	public function RunModule() {
		$this->ShowData();
	}
}
class uEmailTemplateAttachmentList extends uListDataModule implements iAdminModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return 'Attachments'; }
	public function GetOptions() { return ALLOW_ADD | ALLOW_EDIT | ALLOW_DELETE; }
	public function GetTabledef() { return 'tabledef_EmailTemplateAttachments'; }

	public function SetupParents() {
		$this->AddParent('uEmailTemplateDetails',array('ident'=>'doc_id'));
		$this->AddParentCallback('uEmailTemplateDetails',array($this,'ParentLoad'),1);
	}

	public function ParentLoad($p) {
		$this->ShowData();
	}
	public function SetupFields() {
		$this->CreateTable('docs');

		$this->AddField('doc_id','doc_id','docs','docid');
		$this->AddField('attachment','attachment','docs','Attachment',itFILE);
	}

	public function RunModule() {
		$this->ShowData();
	}
}
class uEmailer extends uDataModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return ''; } //$row = $this->GetRecord($this->GetDataset(),0); return $row['name']; }
	public function GetOptions() { return ALWAYS_ACTIVE | ALLOW_FILTER; }
	public function GetTabledef() { return 'tabledef_EmailTemplates'; }

	public function SetupParents() {
		modOpts::AddOption('smtp_host','SMTP Host','Emails');
		modOpts::AddOption('smtp_port','SMTP Port','Emails',25);
		modOpts::AddOption('smtp_user','SMTP Username','Emails','yourname@yourdomain.tld');
		modOpts::AddOption('smtp_pass','SMTP Password','Emails','',itPLAINPASSWORD);
		modOpts::AddOption('emailer_from','Mailer From','Emails',utopia::GetDomainName().' Mailer <mailer@'.preg_replace('/^www./','',utopia::GetDomainName()).'>');
		uEvents::AddCallback('InitComplete',array($this,'InitialiseTemplates'));
	}

	public function SetupFields() {
		$this->CreateTable('docs');

		$this->AddField('ident','identifier','docs','Ident');
		$this->AddField('subject','subject','docs','Subject');
		$this->AddField('body','body','docs','Body');
	}

	public function RunModule() { }

	public function ShowData() { }

	public function InitialiseTemplates() {
		foreach (self::$init as $ident => $data) {
			self::GetTemplate($ident);
		}
	}
	
	public static $init = array();
	public static function InitialiseTemplate($ident,$subject,$content,$fields=NULL) {
		self::$init[$ident] = array(
			'subject'=>$subject,
			'content'=>$content,
			'fields' =>$fields,
		);
	}

	public static function GetTemplate($ident) {
		$obj = utopia::GetInstance(__CLASS__);
		$row = $obj->LookupRecord(array('ident'=>$ident));
		// if no doc, create it and alert admin
		if (!$row) {
			$pk = null;
			$obj->UpdateField('ident',$ident,$pk);
			if (isset(self::$init[$ident])) {
				$obj->UpdateField('subject',self::$init[$ident]['subject'],$pk);
				$obj->UpdateField('body',self::$init[$ident]['content'],$pk);
			}
			uNotices::AddNotice('No email template found called '.$ident.'.  This has been created automatically.',NOTICE_TYPE_WARNING);
			$row = $obj->LookupRecord($pk);
		}

		return $row;
	}

	public static function SendEmailTemplate($ident,$data,$emailField,$from=null,$attachments = null) {
		$row = self::GetTemplate($ident);

		if (!array_key_exists(0,$data)) $data = array($data);

		if (!is_array($attachments)) $attachments = array($attachments);

		$obj = utopia::GetInstance('uEmailTemplateAttachmentList');
		$templateAttachments = $obj->GetRows(array('doc_id'=>$ident));
		foreach ($templateAttachments as $attachment) {
			$attachments[] = Swift_Attachment::newInstance($attachment['attachment'], $attachment['attachment_filename'], $attachment['attachment_filetype']);
		}

		$failures = array();
		foreach ($data as $item) {
			$subject = self::ReplaceData($item,$row['subject']);
			$body = self::ReplaceData($item,$row['body']);
			$recip = explode(',',$item[$emailField]);

			$failures = array_merge($failures,self::SendEmail($subject,$body,$recip,$from,$attachments));
		}
		return $failures;
	}

	public static function SendEmail($subject,$content,$to,$from=null,$attachments=null) {
		if (modOpts::GetOption('smtp_host')) {
			$transport = Swift_SmtpTransport::newInstance(modOpts::GetOption('smtp_host'), modOpts::GetOption('smtp_port'))
				->setUsername(modOpts::GetOption('smtp_user'))
				->setPassword(modOpts::GetOption('smtp_pass'));
		} else {
			// mail
			//$transport = Swift_MailTransport::newInstance();
			// sendmail
			$transport = Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs');
		}
		
		$from = $from ? $from : modOpts::GetOption('emailer_from');
		$from = self::ConvertEmails($from);

		$mailer = Swift_Mailer::newInstance($transport);
		$message = Swift_Message::newInstance()->setFrom($from);

		foreach ($attachments as $attachment) {
			if (!$attachment) continue;
			if ($attachment instanceof Swift_Attachment)
				$message->attach($attachment);
			else
				$message->attach(Swift_Attachment::fromPath($attachment));
		}

		$message->setSubject($subject);
		$message->setBody($content, 'text/html');

		$to = self::ConvertEmails($to);
		$message->setTo($to);
		try {
			$mailer->send($message,$failures);
		} catch (Exception $e) {
			 DebugMail('Email Error',$e->getMessage());
		}
		return $failures;
	}

	public static function ReplaceData($pairs,$text,$encode=false) {
		foreach ($pairs as $field=>$value) {
			if ($encode) $value = htmlspecialchars($value);
			$text = str_replace('{'.$field.'}',str_replace("\n",'<w:br/>',$value),$text);
		}
		while (utopia::MergeVars($text));
		return $text;
	}

	public static function ConvertEmails($string) {
		$output = array();
		if (!$string) return $output;
		if (is_array($string)) $emails = $string;
		else $emails = explode(',',$string);
		foreach ($emails as $email) {
			preg_match('/^([^<]+)<?([^>]+)?/',$email,$matches);
			if (isset($matches[2]))	$output[$matches[2]] = $matches[1];
			else $output[] = $matches[1];
		}
		return $output;
	}
}
