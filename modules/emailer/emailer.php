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
	public function GetTitle() { return 'Email Templates'; }
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
	public function GetTitle() { return 'Edit Email Template'; }
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
			$ret .= "<span onclick=\"CKEDITOR.currentInstance.insertText('{{$field}}');\" style=\"margin:0 5px;cursor:pointer\" class=\"btn btn-mce-insert\">{$field}</span>";
		}
		return $ret;
	}
	public function RunModule() {
		uJavascript::AddText('var mceDefaultOptions = $.extend({},mceDefaultOptions,{relative_urls:false,convert_urls:true});');
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
	public function GetTitle() { return ''; }
	public function GetOptions() { return ALLOW_FILTER | ALLOW_ADD | ALLOW_EDIT; }
	public function GetTabledef() { return 'tabledef_EmailTemplates'; }

	public function SetupParents() {
		modOpts::AddOption('smtp_host','SMTP Host','Emails','localhost');
		modOpts::AddOption('smtp_port','SMTP Port','Emails',25);
		modOpts::AddOption('smtp_user','SMTP Username','Emails','');
		modOpts::AddOption('smtp_pass','SMTP Password','Emails','',itPLAINPASSWORD);
		modOpts::AddOption('emailer_from','Mailer From','Emails',ADMIN_EMAIL);
		uEvents::AddCallback('AfterInit',array($this,'InitialiseTemplates'));
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
		$obj =& utopia::GetInstance(__CLASS__);
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

	public static function SendEmailTemplate($ident,$data,$emailField,$from=null,$attachments=null,$messageCallback=null) {
		$row = self::GetTemplate($ident);

		if (!array_key_exists(0,$data)) $data = array($data);

		if (!is_array($attachments)) $attachments = array($attachments);

		$obj =& utopia::GetInstance('uEmailTemplateAttachmentList');
		$obj->BypassSecurity(true);
		$templateAttachments = $obj->GetDataset(array('doc_id'=>$ident))->GetFirst();
		$obj->BypassSecurity(false);
		if ($templateAttachments) foreach ($templateAttachments as $attachment) {
			$attachments[] = Swift_Attachment::newInstance($attachment['attachment'], $attachment['attachment_filename'], $attachment['attachment_filetype']);
		}

		$failures = array();
		foreach ($data as $item) {
			$subject = self::ReplaceData($item,$row['subject']);
			$body = self::ReplaceData($item,$row['body']);
			$recip = explode(',',$item[$emailField]);

			$failures = array_merge($failures,self::SendEmail($recip,$subject,$body,$from,$attachments,$messageCallback));
		}
		return $failures;
	}

	private static $mailTransport = null;
	public static function SendEmail($to,$subject,$content,$from=null,$attachments=null,$messageCallback=null) {
		$to = self::ConvertEmails($to);
		if (!$to || !$content) return array_keys($to);
		$host = modOpts::GetOption('smtp_host'); $port = modOpts::GetOption('smtp_port');
		if (!$host) $host = 'localhost';
		if (!$port) $port = 25;
		
		if (!self::$mailTransport) {
			// setup mail transport
			$mailTransport = Swift_MailTransport::newInstance('');
			// setup smtp transport
			$smtpTransport = Swift_SmtpTransport::newInstance($host, $port);
			$user = modOpts::GetOption('smtp_user'); $pass = modOpts::GetOption('smtp_pass');
			if ($user) $smtpTransport->setUsername($user)->setPassword($pass);
			
			// create failover transport
			self::$mailTransport = Swift_FailoverTransport::newInstance(array($smtpTransport,$mailTransport));
		}
		
		$mailer = Swift_Mailer::newInstance(self::$mailTransport);
		$message = Swift_Message::newInstance();
		
		$sender = self::ConvertEmails(modOpts::GetOption('emailer_from'));
		$message->setSender($sender);

		$from = $from ? self::ConvertEmails($from) : $sender;
		if ($from) $message->setFrom($from);

		if (!is_array($attachments)) $attachments = array($attachments);
		if ($attachments) foreach ($attachments as $attachment) {
			if (!$attachment) continue;
			if ($attachment instanceof Swift_Attachment)
				$message->attach($attachment);
			else
				$message->attach(Swift_Attachment::fromPath($attachment));
		}

		while (utopia::MergeVars($subject));
		$message->setSubject($subject);
		
		while (utopia::MergeVars($content));
		$ishtml = ($content !== strip_tags($content));
		if ($ishtml) {
			// process content, make all relative links absolute
			libxml_use_internal_errors(true);
			$doc = new DOMDocument; $doc->loadHTML($content);
			$xpath = new DOMXPath($doc);
			$entries = $xpath->query('//*[@href]', $doc->documentElement);
			if ($entries) {
				$siteurl = modOpts::GetOption('site_url');
				foreach ($entries as $entry) {
					$v = $entry->getAttribute('href');
					if (preg_match('/^\//',$v)) {
						$v = rtrim($siteurl,'/').$v;
						$entry->setAttribute('href',$v);
					}
				}
				$content = $doc->saveHTML();
			}
		}
		$message->setBody($content, ($ishtml) ? 'text/html' : 'text/plain');

		$message->setTo($to);
		$failures = array();

		if (is_callable($messageCallback)) call_user_func_array($messageCallback,array(&$message));
		$mailer->send($message,$failures);

		return $failures;
	}

	public static function ReplaceData($pairs,$text,$encode=false) {
		foreach ($pairs as $field=>$value) {
			if (is_array($value)) $value = implode(', ',$value);
			if ($encode) $value = htmlspecialchars($value);
			$text = str_replace('{'.$field.'}',str_replace("\n",'<w:br/>',$value),$text);
		}
		return $text;
	}

	public static function ConvertEmails($string) {
		$output = array();
		if (!$string) return $output;
		if (is_array($string)) $emails = $string;
		else $emails = explode(',',$string);
		foreach ($emails as $email) {
			if (!preg_match('/^([^<]+)<?([^>]+)?/',$email,$matches)) continue;
			if (isset($matches[2]))	$output[$matches[2]] = $matches[1];
			else $output[] = $matches[1];
		}
		return $output;
	}
	public static function IsEmail($email) {
		return (bool)preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i',$email);
	}
}
