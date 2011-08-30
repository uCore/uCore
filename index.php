<?php
include('start.php');
if (!isset($_GET['uuid']))
	RunModule('internalmodule_Admin');
else
	RunModule();
?>
