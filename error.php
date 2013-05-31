<?php
ini_set('html_errors','Off');
set_error_handler('uErrorHandler::ThrowException');
set_exception_handler('uErrorHandler::EchoException');

class uErrorHandler {
	static function ThrowException($code, $message, $file=null, $line=null, $errcontext=null) {
		if (error_reporting() === 0) return;
		// Convert Errors to Exceptions
		throw new ErrorException($message, $code, 0, $file, $line);
	}
	static function EchoException($e) {
		$fullError = sprintf("<b>ERROR</b> [%s] %s<br />\n  Error on line %s in file %s<br />\n%s",$e->getCode(),$e->getMessage(),$e->getLine(),$e->getFile(),nl2br(htmlentities($e->getTraceAsString())));
		DebugMail('Server Error: '.$e->getCode(),$fullError);
		error_log($fullError);

		$display = ini_get('display_errors');
		if ($display == 'off' || !$display) $fullError = 'An error has occurred.  The system administrator has been notified.';
		
		echo $fullError;
		return $fullError;
	}
}

function DebugMail($subject,$message) {
	if (!defined('ADMIN_EMAIL')) return;

	if (!array_key_exists('dm_time',$_SESSION)) $_SESSION['dm_time'] = time();
	if (!array_key_exists('dm_count',$_SESSION)) $_SESSION['dm_count'] = 0;

	if (time() > $_SESSION['dm_time'] + 300) { $_SESSION['dm_time'] = time(); $_SESSION['dm_count'] = 0; }
	$_SESSION['dm_count']++;
	if ($_SESSION['dm_count'] > 4) return;

	if (!is_string($message)) $message = print_r($message,true);
	$ip = isset($_SERVER['REMOTE_ADDR']) ? 'Remote IP: '.$_SERVER['REMOTE_ADDR']."\n" : '';
	$ref = array_key_exists('HTTP_REFERER',$_SERVER) ? 'Referrer: '.$_SERVER['HTTP_REFERER']."\n" : '';
	$ua = array_key_exists('HTTP_USER_AGENT',$_SERVER) ? 'User Agent: '.$_SERVER['HTTP_USER_AGENT']."\n" : '';
	$url = 'URL: http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."\n";

	$message = "$url$ref$ip$ua\n$message";
	try {
		mail(ADMIN_EMAIL,$subject,$message);
	} catch (Exception $e) {
		echo $e->getMessage();
	}
}
