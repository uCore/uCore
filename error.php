<?php
ini_set('display_errors','On');
set_error_handler('uErrorHandler::ThrowException');
set_exception_handler('uErrorHandler::exception_handler');
register_shutdown_function('uErrorHandler::fatalErrorShutdownHandler');

class uErrorHandler {
	static function ThrowException($code, $message, $file, $line, $args=NULL) {
		// Convert Errors to Exceptions
		throw new ErrorException($message, $code, 0, $file, $line);
	}
	static function exception_handler($e) {
	        self::EchoException($e);
	}
	static function fatalErrorShutdownHandler()
	{
		$last_error = error_get_last();
		if ($last_error['type'] !== E_PARSE) return;
//		while (ob_get_level()>1) ob_end_clean();
		throw new ErrorException($last_error['message'], $last_error['type'], 0, $last_error['file'], $last_error['line']);
	}
	static function EchoException($e) {
		$fullError = sprintf("<b>ERROR</b> [%s] %s<br />\n  Error on line %s in file %s<br />\n%s",$e->getCode(),$e->getMessage(),$e->getLine(),$e->getFile(),nl2br($e->getTraceAsString()));
		DebugMail('Server Error: '.$e->getCode(),$fullError);

		$role = null;
		if (class_exists('uUserRoles') && !uUserRoles::IsAdmin()) $fullError = 'An error occurred. The site administrator has been notified.';
		if (!AjaxEcho('alert("'.$fullError.'")')) echo $fullError;
	}
}

function DebugMail($subject,$message) {
	if (!defined('ERROR_EMAIL')) return;

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
		mail(ERROR_EMAIL,$subject,$message);
	} catch (Exception $e) {
		echo $e->getMessage();
	}
}
