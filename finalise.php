<?php

$time_taken = timer_end('full process');
if (array_key_exists('admin_showT',$_SESSION) && $_SESSION['admin_showT'] === true) {// || array_key_exists('showT',$_REQUEST) && array_key_exists('timers',$GLOBALS)) {
	echo '<pre><table>';
	foreach ($GLOBALS['timers'] as $name => $info) {
		if (!is_array($info)) continue;
		$time = !array_key_exists('time_taken',$info) ? timer_end($name) : $info['time_taken'];
		echo "<tr><td style=\"vertical-align:top;border-top:1px solid black\">$time</td><td style=\"vertical-align:top;border-top:1px solid black\">$name</td></tr>";
	}
	echo '</table></pre>';
}

FlexDB::OutputTemplate();

?>