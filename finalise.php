<?php

timer_start('Output Template');
utopia::OutputTemplate();
timer_end('Output Template');

if (isset($GLOBALS['timers']) && utopia::DebugMode()) {
	echo '<pre class="uDebug"><table>';
	foreach ($GLOBALS['timers'] as $name => $info) {
		if (!is_array($info)) continue;
		$time = !array_key_exists('time_taken',$info) ? timer_end($name) : $info['time_taken'];
		echo '<tr><td style="vertical-align:top;border-top:1px solid black">'.$time.'</td><td style="vertical-align:top;border-top:1px solid black">'.$name.PHP_EOL.$info['info'].'</td></tr>';
	}
	echo '</table></pre>';
}

global $ucore_start_time;
header('X-Runtime: '.(microtime(true)-$ucore_start_time));

die();
