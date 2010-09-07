<?php
error_reporting(E_ALL & E_STRICT);

ini_set('display_errors',1);

set_error_handler('myErrorHandler', E_ALL | E_STRICT);
set_exception_handler("exception_handler");
register_shutdown_function('fatalErrorShutdownHandler');
function myErrorHandler($code, $message, $file, $line) {
	try {
		if (class_exists('FlexDB')) FlexDB::CancelTemplate();
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
?>