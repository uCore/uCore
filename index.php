<?php
include_once('start.php');
if ($_SERVER['REQUEST_URI'] == PATH_REL_CORE || $_SERVER['REQUEST_URI'] == CallModuleFunc('internalmodule_Admin','GetURL',$_GET))
	return RunModule('internalmodule_Admin');
RunModule();
?>
