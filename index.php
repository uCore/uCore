<?php
include_once('start.php');
if (!$_SERVER['QUERY_STRING'])
	return RunModule('internalmodule_Admin');
RunModule();
?>