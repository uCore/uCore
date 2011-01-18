<?php
error_reporting(E_ALL | E_STRICT);

ini_set('display_errors',1);

set_error_handler('myErrorHandler', E_ALL | E_STRICT);
set_exception_handler("exception_handler");
register_shutdown_function('fatalErrorShutdownHandler');
function myErrorHandler($code, $message, $file, $line) {
	try {
    $last_error = error_get_last();
    if ($last_error['type'] === E_ERROR && class_exists('FlexDB')) utopia::CancelTemplate();
		throw new ErrorException($message, 0, $code, $file, $line);
    } catch (Exception $e) {
    	EchoException($e);
    }
}
function exception_handler($e)
{
    try {
    	EchoException($e);
    } catch (Exception $e) {
        EchoException($e);
    }
}
function fatalErrorShutdownHandler()
{
	$last_error = error_get_last();
	if ($last_error['type'] === E_ERROR) {
		// fatal error
		myErrorHandler(E_ERROR, $last_error['message'], $last_error['file'], $last_error['line']);
	}
}
function EchoException($e) {
	$fullError = sprintf("<b>ERROR</b> [%s] %s<br />\n  Error on line %s in file %s<br />\n%s",$e->getCode(),$e->getMessage(),$e->getLine(),$e->getFile(),nl2br($e->getTraceAsString()));
	DebugMail('Server Error: '.$e->getCode(),$fullError);
	echo $fullError;
}
function DebugMail($subject,$message) {
    if (!defined('ERROR_EMAIL')) return;
//  if (isset($_SESSION)) {
    if (!array_key_exists('dm_time',$_SESSION)) $_SESSION['dm_time'] = time();
    if (!array_key_exists('dm_count',$_SESSION)) $_SESSION['dm_count'] = 0;

    if (time() > $_SESSION['dm_time'] + 300) { $_SESSION['dm_time'] = time(); $_SESSION['dm_count'] = 0; }
    $_SESSION['dm_count']++;
    if ($_SESSION['dm_count'] > 4) return;
//  }

  if (!is_string($message)) $message = print_r($message,true);
  $ref = array_key_exists('HTTP_REFERER',$_SERVER) ? 'Referrer: '.$_SERVER['HTTP_REFERER']."\n" : '';
  $ua = array_key_exists('HTTP_USER_AGENT',$_SERVER) ? 'User Agent: '.$_SERVER['HTTP_USER_AGENT']."\n" : '';
  $message = 'URL: http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."\n".$ref.$ua."$message";
  mail(ERROR_EMAIL,$subject,$message);
}
?>
