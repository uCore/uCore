<?php

function contains_html($string) {
	return (strlen($string) != strlen(strip_tags($string)));
}

function html2txt($document) {
	$search = array('@<script[^>]*?>.*?</script>@si',	// Strip out javascript
		'@<[\/\!]*?[^<>]*?>@si',			// Strip out HTML tags
		'@<style[^>]*?>.*?</style>@siU',	// Strip style tags properly
		'@<![\s\S]*?--[ \t\n\r]*>@',		// Strip multi-line comments including CDATA
		'@\{.*\}@siU'						// strip pragma
	);
	$text = preg_replace($search, '', $document);
	return strip_tags($text);
}

function word_trim($string, $count, $ellipsis = FALSE) {
	$words = explode(' ', $string);
	if (count($words) > $count){
		array_splice($words, $count);
		$string = implode(' ', $words);
		if (is_string($ellipsis)){
			$string .= $ellipsis;
		}
		elseif ($ellipsis){
			$string .= '&hellip;';
		}
	}
	return $string;
}

function curl_get_contents($url) {
	$ch = curl_init();
	$timeout = 5; // set to zero for no timeout
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$file_contents = curl_exec($ch);
	curl_close($ch);

	return $file_contents;
}

function htmlentities_skip($str,$toSkip = '') {
	$tt = get_html_translation_table(HTML_ENTITIES);

	for ($i = 0,$strlen = strlen($toSkip); $i < $strlen; $i++)
	unset($tt[$toSkip[$i]]);

	$UTFChars = array();
	foreach($tt as $charkey => $char)
	{
		$charkey = utf8_encode($charkey);
		$UTFChars[$charkey]= utf8_encode($char);
	}

	return strtr($str,$UTFChars);
}
function fastimagecopyresampled (&$dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h, $quality = 3) {
  // Plug-and-Play fastimagecopyresampled function replaces much slower imagecopyresampled.
  // Just include this function and change all "imagecopyresampled" references to "fastimagecopyresampled".
  // Typically from 30 to 60 times faster when reducing high resolution images down to thumbnail size using the default quality setting.
  // Author: Tim Eckel - Date: 09/07/07 - Version: 1.1 - Project: FreeRingers.net - Freely distributable - These comments must remain.
  //
  // Optional "quality" parameter (defaults is 3). Fractional values are allowed, for example 1.5. Must be greater than zero.
  // Between 0 and 1 = Fast, but mosaic results, closer to 0 increases the mosaic effect.
  // 1 = Up to 350 times faster. Poor results, looks very similar to imagecopyresized.
  // 2 = Up to 95 times faster.  Images appear a little sharp, some prefer this over a quality of 3.
  // 3 = Up to 60 times faster.  Will give high quality smooth results very close to imagecopyresampled, just faster.
  // 4 = Up to 25 times faster.  Almost identical to imagecopyresampled for most images.
  // 5 = No speedup. Just uses imagecopyresampled, no advantage over imagecopyresampled.

  if (empty($src_image) || empty($dst_image) || $quality <= 0) { return false; }
  if ($quality < 5 && (($dst_w * $quality) < $src_w || ($dst_h * $quality) < $src_h)) {
    $temp = imagecreatetruecolor ($dst_w * $quality + 1, $dst_h * $quality + 1);
    imagecopyresized ($temp, $src_image, 0, 0, $src_x, $src_y, $dst_w * $quality + 1, $dst_h * $quality + 1, $src_w, $src_h);
    imagecopyresampled ($dst_image, $temp, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h, $dst_w * $quality, $dst_h * $quality);
    imagedestroy ($temp);
  } else imagecopyresampled ($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
  return true;
}
function cbase64_encode($str) {
	return rtrim(base64_encode($str),'=');
}
function cbase64_decode($str) {
	$remainder = strlen($str) % 4;
	for ($i = 1; $i <= $remainder; $i++)
	$str = $str.'=';
	return base64_decode($str);
}

function ReplacePragma($string,$tbl='') {
	$tbl = ($tbl) ? "$tbl." : '';
  $retF = "$string";
	if (preg_match_all('/{[^}]+}/',$string,$matches) > 0 && !empty($tbl)) {
		$retF = "$string";
		foreach ($matches[0] as $match) {
			$retF = str_replace($match,"$tbl".trim($match,'{}'),$retF);
		}
	}
    
	return $retF;
}

function CreateConcatString($string,$tbl='') {
	$tbl = ($tbl) ? "`$tbl`." : '';
  $retF = "$tbl`$string`";
	if (preg_match_all('/{[^}]+}/',$string,$matches) > 0 && !empty($tbl)) {
		$retF = "CONCAT('$string')";
		foreach ($matches[0] as $match) {
			$retF = str_replace($match,"',$tbl".trim($match,'{}').",'",$retF);
		}
	}

	return $retF;
}

function ParseFieldComments($comments) {
	preg_match_all('/([a-zA-Z0-9_]+)\(([a-zA-Z0-9_]+):([a-zA-Z0-9_]+)\)/',$comments,$matches,PREG_SET_ORDER);
	return $matches[0];
}

function CreateButton($btnText,$attrs = NULL) {
	if (!is_array($attrs)) $attrs = array();
	if (array_key_exists('class',$attrs)) $attrs['class'] .= ' btn';
	else  $attrs['class'] = 'btn';
	$attrStr = BuildAttrString($attrs);
	return "<a $attrStr>$btnText</a>";
	//return "<input type=\"button\" value=\"$btnText\"$attrStr />";
}

function CreateNavButton($btnText,$url,$attrs=array()){
	$attrs['href'] = $url;
	return CreateButton($btnText,$attrs);
}

/*function CreateNavButtonOnClick($btnText,$url,$onClick,$vals=array()){
 return
 return;
 $vals['next_url'] = htmlspecialchars($url);
 return CreateSubmitButton($btnText,$onClick,$vals);
 }*/

// a submit button will not forward to a different module, it simply submits the changes, and sets any extra values
function CreateSubmitButton($btnText, $vals=array()) {
	return CreateNavButton($btnText,'',$vals);

	/*return;
	 if (empty($vals['next_url'])) $vals['next_url'] = htmlspecialchars($_SERVER['REQUEST_URI']);
	 $out = "";
	 $sets = "";
	 foreach ($vals as $key => $val) {
		AddHiddenField($key);
		$sets .= " SetElementValue(\"$key\",\"$val\");";
		}
		$sets = trim($sets);
		$onClick = trim($onClick);
		if (!empty($onClick) && substr($onClick, -0) != ';') $onClick = $onClick.';';
		$out .= " <input type=submit value='$btnText' onclick='$onClick$sets' />";
		return $out;*/
}

function ErrorLog($text) { return;
	AjaxEcho('ErrorLog("'.addcslashes(str_replace("\n",'',$text),'"').'")');
	if (utopia::UsingTemplate()) { echo $text; }
	$cLog = utopia::GetVar('error_log');

	if (array_key_exists('__ajax',$_REQUEST)) {
		if (!empty($cLog)) utopia::AppendVar('error_log',"\n");
		utopia::AppendVar('error_log','ErrorLog("'.$text.'");');
	} else {
		if (!empty($cLog)) utopia::AppendVar('error_log','<br/>');
		utopia::AppendVar('error_log','<span><b>*</b> '.$text.'</span>');
		//    utopia::AppendVar('error_log','<span style="color:#EE3333; border-style:solid; border-width:1px; background-color:#DDDDDD; padding:3px">'.$text.'</span>');
	}

}

function DebugOutput() {
	return utopia::GetVar('error_log');
}

function get_include_contents($filename) {
    if (is_file($filename)) {
        ob_start();
        include $filename;
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }
    return false;
}

function array_sort_subkey(&$array,$key,$direction='<') {
	$key = is_string($key) ? "'$key'" : $key;
	$func = create_function('$a,$b','$c1 = $a['.$key.']; $c2 = $b['.$key.']; if ($c1 == $c2) return 0;  return $c1 '.$direction.' $c2 ? -1 : 1;');
	uasort($array,$func);
}

// MODULE FUNCTIONS

function GetFilterArray() {
	$arr = array();
	foreach ($_GET as $key=>$val) {
		if (substr($key,0,3) != '_f_') continue;
		$newKey = substr($key,3);
		$arr[$newKey] = $val;
	}
	return $arr;
}

function FilterArrayToString($filters) {
	$parts = array();
	foreach ($filters as $fieldName => $data) {
		if (is_array($data)) {
			$ct = array_key_exists('ct',$data) ? '~'.$data['ct'] : '';
			$parts[] =  urlencode("_f_$fieldName$ct").'='.$data['value'];
		} else if (is_string($data)) {
			$parts[] = urlencode("_f_$fieldName").'='.$data;
		}
	}
	return join('&amp;',$parts);
}

// MISC FUNCTIONS

function find_constant($value,$prefix = '')
{
	$constants = get_defined_constants();
	foreach ($constants as $constant => $val) {
		if (substr($constant,0,strlen($prefix)) !== $prefix) continue;
		if ($value == $val) return $constant;
	}
	return NULL;
}

function BuildAttrString($attrArray) {
	if ($attrArray == NULL or empty($attrArray)) return '';
	$attrStrings = array();
	foreach ($attrArray as $name => $val) {
		if (!$val) continue;
		$attrStrings[] = "$name=\"$val\"";
	}

	return ' '.join(' ',$attrStrings);
}

function GetQSPairs($url) {
	$pairs = array();
	$qsPos = strpos($url,'?');
	if ($qsPos === FALSE) return $pairs;
	$currentQS = substr($url,$qsPos+1);
	$arr = explode('&',$currentQS);
	foreach ($arr as $pair) {
		if (strpos($pair,'=') === FALSE) {
			$pairName = $pair; $pairVal = NULL;
		} else
		list($pairName,$pairVal) = explode('=',$pair);
		$pairs[$pairName] = urldecode($pairVal);
	}
	return $pairs;
}

function UrlReadable($string) {
	$string = preg_replace('/[^0-9a-z\-_\.]+/i','-',$string);
	$string = preg_replace('/[\-]{2,}/i','-',$string);
	return trim($string,'-');
}

function DONT_USE_uuid()
{
	return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
	mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
	mt_rand( 0, 0x0fff ) | 0x4000,
	mt_rand( 0, 0x3fff ) | 0x8000,
	mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
}

function timer_start($timerName,$info='') {
	if (!isset($_SESSION['admin_showT'])) return;
	$timer = &$GLOBALS['timers'][$timerName];

	if (!is_string($info)) $info = print_r($info,true);

	$timer['info'] = $info;
//	if (!empty($GLOBALS['timer_parent'])) $timer['parent'] = $GLOBALS['timer_parent'];
//	else $timer['parent'] = '';
	$timer['start_time'] = microtime(true)*1000;

//	$GLOBALS['timer_parent'] = $timerName;
}
function timer_end($timerName) {
	if (!isset($_SESSION['admin_showT'])) return;
	$timer = &$GLOBALS['timers'][$timerName];
	if (!isset($timer['start_time'])) { /*echo "Timer ($timerName) not started.";*/ return; }

	$timer['end_time'] = microtime(true)*1000;
	$timer['time_taken'] = round($timer['end_time'] - $timer['start_time'],3);
//	if (array_key_exists('parent',$timer) && array_key_exists($timer['parent'],$GLOBALS['timers']) && array_key_exists('parent',$GLOBALS['timers'][$timer['parent']]))
//		$GLOBALS['timer_parent'] = $GLOBALS['timers'][$timer['parent']]['parent'];
//	else
//		$GLOBALS['timer_parent'] = NULL;

	return $timer['time_taken'];
	//	if (isset($timer['parent'])) $GLOBALS['timer_parent'] = $timer['parent'];
}
function timer_findtime($timerName,$end=FALSE) {
	if (!isset($_SESSION['admin_showT'])) return;
	$timer = &$GLOBALS['timers'][$timerName];
	if (isset($timer['time_taken']))
		return $timer['time_taken'];

	$currentTime = microtime(true)*1000;
	$tt = round($currentTime - $timer['start_time'],3);

	return $tt;
	//timer_end($timerName);

	// still not set? must be a problem
	if (!isset($timer['time_taken'])) return;

	return $timer['time_taken'];
}

function flag_gen($set='global') {
	if (!array_key_exists('flagcount',$GLOBALS)) $GLOBALS['flagcount'] = array();
	if (!array_key_exists($set,$GLOBALS['flagcount'])) $GLOBALS['flagcount'][$set] = 1;
	else $GLOBALS['flagcount'][$set] = $GLOBALS['flagcount'][$set] * 2;

	return $GLOBALS['flagcount'][$set];
}
function flag_set(&$var,$flag) {
	$var = $var | $flag;
}
function flag_unset(&$var,$flag) {
	$var = $var & ~$flag;
}
function flag_is_set($var,$flag) {
	return ($var & $flag) === $flag;
}

function genRandom($length,$charset = "abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789") {
	srand((double)microtime()*1000000); // start the random generator
	$str=""; // set the inital variable
	for ($i=0;$i<$length;$i++)  // loop and create password
	$str .= substr ($charset, rand() % strlen($charset), 1);

	return $str;
}

function is_empty( $var ) {
	return empty($var);
}

function AjaxEcho($text) {
	// only reset field if this is performed inside an ajax routine (javascript)
	//if (utopia::UsingTemplate()) return;
	if (!array_key_exists('__ajax',$_REQUEST)) return false;
	$text = trim($text,"\n;");
	echo "\n$text;";
	return true;
}

function browser_detection( $which_test )
{
	// initialize variables
	$browser_name = '';
	$browser_number = '';
	// get userAgent string
	$browser_user_agent = ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) ? strtolower( $_SERVER['HTTP_USER_AGENT'] ) : '';
	//pack browser array
	// values [0]= user agent identifier, lowercase, [1] = dom browser, [2] = shorthand for browser,
	$a_browser_types[] = array('opera', true, 'op' );
	$a_browser_types[] = array('msie', true, 'ie' );
	$a_browser_types[] = array('konqueror', true, 'konq' );
	$a_browser_types[] = array('safari', true, 'saf' );
	$a_browser_types[] = array('gecko', true, 'moz' );
	$a_browser_types[] = array('mozilla/4', false, 'ns4' );

	for ($i = 0,$typeCount=count($a_browser_types) ; $i < $typeCount; $i++)
	{
		$s_browser = $a_browser_types[$i][0];
		$b_dom = $a_browser_types[$i][1];
		$browser_name = $a_browser_types[$i][2];
		// if the string identifier is found in the string
		if (stristr($browser_user_agent, $s_browser))
		{
			// we are in this case actually searching for the 'rv' string, not the gecko string
			// this test will fail on Galeon, since it has no rv number. You can change this to
			// searching for 'gecko' if you want, that will return the release date of the browser
			if ( $browser_name == 'moz' )
			{
				$s_browser = 'rv';
			}
			$browser_number = browser_version( $browser_user_agent, $s_browser );
			break;
		}
	}
	// which variable to return
	if ( $which_test == 'browser' )
	{
		return $browser_name;
	}
	elseif ( $which_test == 'number' )
	{
		return $browser_number;
	}

	/* this returns both values, then you only have to call the function once, and get
	 the information from the variable you have put it into when you called the function */
	elseif ( $which_test == 'full' )
	{
		$a_browser_info = array( $browser_name, $browser_number );
		return $a_browser_info;
	}
}

// function returns browser number or gecko rv number
// this function is called by above function, no need to mess with it unless you want to add more features
function browser_version( $browser_user_agent, $search_string )
{
	$string_length = 8;// this is the maximum  length to search for a version number
	//initialize browser number, will return '' if not found
	$browser_number = '';

	// which parameter is calling it determines what is returned
	$start_pos = strpos( $browser_user_agent, $search_string );

	// start the substring slice 1 space after the search string
	$start_pos += strlen( $search_string ) + 1;

	// slice out the largest piece that is numeric, going down to zero, if zero, function returns ''.
	for ( $i = $string_length; $i > 0 ; $i-- )
	{
		// is numeric makes sure that the whole substring is a number
		if ( is_numeric( substr( $browser_user_agent, $start_pos, $i ) ) )
		{
			$browser_number = substr( $browser_user_agent, $start_pos, $i );
			break;
		}
	}
	return $browser_number;
}

function ProtectedScript() {
	if ($_SERVER['SCRIPT_NAME'] == $_SERVER['REQUEST_URI']) { utopia::CancelTemplate(); die("Protected Script\nCannot access directly"); }
}

//register_shutdown_function('showTemplate');
/*
 function showTemplate() {
 if (array_key_exists('NO_TEMPLATE',$GLOBALS) && $GLOBALS['NO_TEMPLATE'] === TRUE) return;
 $time_taken = timer_findtime('full process');
 if (utopia::GetVar('footer_right') != '')
 utopia::AppendVar('footer_right',"<br/>Page processed in: {$time_taken}s");
 else
 utopia::SetVar('footer_right',"Page processed in: {$time_taken}s");

 utopia::AppendVar('footer_right','<br/>Total Queries: '.$GLOBALS['sql_query_count']);

 //ses_gc(0);

 ob_end_flush();
 // load template
 //	include('../../template.php');
 }*/



///  EVENT CODE

// Hook Event code within uBasicModule

function TriggerEvent($eventName) {
	if (!array_key_exists('events',$GLOBALS)) return;
	if (!array_key_exists($eventName,$GLOBALS['events'])) return;
	$args = array_slice(func_get_args(),1);
	foreach ($GLOBALS['events'][$eventName] as $fullName) {
		list($module,$funcName) = explode('.',$fullName);
		$obj = utopia::GetInstance($module);
		$obj->$funcName($args);
	}
}

function unserializesession($data) {
	$vars=preg_split(
             '/([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\|/',
	$data,-1,PREG_SPLIT_NO_EMPTY |
	PREG_SPLIT_DELIM_CAPTURE
	);
	for($i=0; $i < count($vars); $i++) {
		$result[$vars[$i++]]=unserialize($vars[$i]);
	}
	return $result;
}

function useful_backtrace($start = 0,$count = 2) {
	$backtrace = debug_backtrace();
	$found = array();
	for ($i = 1+$start,$traceCount = count($backtrace); $i < $traceCount; $i++) {
		if (count($found) >= $count) break;
		if (!array_key_exists('function',$backtrace[$i]) || $backtrace[$i]['function'] == 'eval' || $backtrace[$i]['function'] == 'call_user_func' || $backtrace[$i]['function'] == 'call_user_func_array') continue;

		$found[] = array('obj'=>!array_key_exists('object',$backtrace[$i]) ? '' : get_class($backtrace[$i]['object']), 'func'=>$backtrace[$i]['function'], 'args' => !array_key_exists('args',$backtrace[$i]) ? '' : $backtrace[$i]['args']);
	}

	return $found;
}

function findext ($filename) {
	$filename = strtolower($filename);
	$exts = preg_split("[/\\.]", $filename);
	return $exts[count($exts)-1];
}

function fixdateformat($string) {
	if (preg_match("/([0-9]{1,2}).{1}([0-9]{1,2}).{1}([0-9]{2,4})/",$string,$array) != 0)
	return strftime(FORMAT_DATE,mktime(0,0,0,$array[2],$array[1],$array[3]));

	$time = strtotime($string);
	if ($time !== FALSE && $time !== -1)
	return strftime(FORMAT_DATE,$time);

	return $string;
}

function IsSelectStatement($str) {
	return (strtolower(substr(trim($str,'('),0,6)) == 'select');
}

/* before an ajax script use the following:

if (!RunAjaxScript(__FILE__)) return;

This will prevent the file from being run without all children
being loaded (security), and will prevent it from being 'included' */
function RunAjaxScript($path) {
	if (strpos($_SERVER['REQUEST_URI'],'?') !== FALSE)
	$requestPath = substr($_SERVER['REQUEST_URI'],0,strpos($_SERVER['REQUEST_URI'],'?'));
	else $requestPath = $_SERVER['REQUEST_URI'];

	if ($requestPath !== str_replace($_SERVER['DOCUMENT_ROOT'],'',$path)) return FALSE; // is being included

	LoadChildren('*'); // to ensure that security is passed on all ajax scripts
	//utopia::CancelTemplate();
	return true;
}


/**
 * Recursively copy file or folder from source to destination
 * @param $source //file or folder
 * @param $dest ///file or folder
 * @param $mode //set file/folder permissions
 * @return boolean
 */
function rcopy($source,$dest,$mode=0755) {
	$source = rtrim($source,'/'); $dest = rtrim($dest,'/');
        $Directory = new RecursiveDirectoryIterator($source);
        $Iterator = new RecursiveIteratorIterator($Directory);
        $files = array_keys(iterator_to_array($Iterator));
	array_unshift($files,$source);
        foreach ($files as $sFile) {
		$sBase = basename($sFile);
		if ($sBase === '.' || $sBase === '..') continue;
		$dFile = str_replace($source,$dest,$sFile);
		if (file_exists($dFile)) continue;
		if (is_dir($sFile))
			mkdir($dFile,$mode,true);
		else
			copy($sFile,$dFile);
		chmod($dFile,$mode);
	}
}

function is_assoc($array) {
    return (is_array($array) && count(array_diff_key($array, array_keys(array_keys($array)))) !== 0);
}
