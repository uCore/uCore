<?php

$time_taken = timer_end('full process');
$toggleT = 'Show Timers';
if (array_key_exists('admin_showT',$_SESSION) && $_SESSION['admin_showT'] === true) {// || array_key_exists('showT',$_REQUEST) && array_key_exists('timers',$GLOBALS)) {
	$toggleT = 'Hide Timers';
	echo '<pre>';
	foreach ($GLOBALS['timers'] as $name => $info)
		echo nl2br($info['time_taken']."\t$name\n");
	echo '</pre>';
}
$toggleQ = 'Show Queries';
if (array_key_exists('admin_showQ',$_SESSION) && $_SESSION['admin_showQ'] === true) {
	$toggleQ = 'Hide Queries';
	echo '<pre>';
	foreach ($GLOBALS['sql_queries'] as $info)
		echo nl2br(print_r($info,true)."\n\n");
	echo '</pre>';
}

FlexDB::OutputTemplate();

?>