<?php

utopia::OutputTemplate();

if (isset($GLOBALS['timers']) && isset($_SESSION['admin_showT']) && $_SESSION['admin_showT'] === true) {
	echo '<pre><table>';
	foreach ($GLOBALS['timers'] as $name => $info) {
		if (!is_array($info)) continue;
		$time = !array_key_exists('time_taken',$info) ? timer_end($name) : $info['time_taken'];
		echo "<tr><td style=\"vertical-align:top;border-top:1px solid black\">$time</td><td style=\"vertical-align:top;border-top:1px solid black\">$name</td></tr>";
	}
	echo '</table></pre>';
}

global $ucore_start_time;
header('X-Runtime: '.(microtime(true)-$ucore_start_time));

die();