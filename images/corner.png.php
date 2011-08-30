<?php
include_once('../start.php');

$etag = sha1($_SERVER['REQUEST_URI']);
Cache_Check($etag,'image/png');

$size = 1000;
$nsize = array_key_exists('s',$_GET) ? $_GET['s'] : 20;
$sizeDiff = $size/$nsize;

$img = imagecreatetruecolor($size,$size);
imagealphablending($img, false);
imagesavealpha($img, true);

//transparent
$transparentColor = imagecolorallocatealpha($img, 200, 200, 200, 127);

// background colour
$c = array_key_exists('c',$_GET) ? $_GET['c'] : 'FF0000';
$rgbcol = hex2rgb($c);
$bgCol = imagecolorallocate($img,$rgbcol['r'],$rgbcol['g'],$rgbcol['b']);

$brdrCol = $bgCol;
if (array_key_exists('b',$_GET)) {
	$rgbcol = hex2rgb($_GET['b']);
	$brdrCol = imagecolorallocate($img,$rgbcol['r'],$rgbcol['g'],$rgbcol['b']);
}

// fill transparancy
imagefill($img, 0, 0, $transparentColor);

if (array_key_exists('t',$_GET)) imagesetthickness($img,$_GET['t'] * $sizeDiff);
imagearc($img,0,0,$size,$size,-1,359,$brdrCol);
imagearc($img,0,$size,$size,$size,-1,359,$brdrCol);
imagearc($img,$size,$size,$size,$size,-1,359,$brdrCol);
imagearc($img,$size,0,$size,$size,-1,359,$brdrCol);

imagefill($img,$size/2,$size/2,$bgCol);


$rimg = imagecreatetruecolor($nsize,$nsize);
imagetruecolortopalette($rimg,false,255);
//imagealphablending($rimg, true);
imagesavealpha($rimg, true);

imagecopyresampled($rimg,$img,0,0,0,0,$nsize,$nsize,$size,$size);


ob_start();
imagepng($rimg);
$contents = ob_get_contents();
ob_end_clean();
imagedestroy($rimg);

Cache_Output($contents,$etag,'image/png');


function hex2rgb($c){
	if(!$c) return false;
	$c = trim($c);
	$out = false;
	if (preg_match("/^[0-9ABCDEFabcdef\#]+$/i", $c)) {
		$c = str_replace('#','', $c);
		$l = strlen($c) == 3 ? 1 : (strlen($c) == 6 ? 2 : false);

		if ($l) {
			unset($out);
			$out[0] = $out['r'] = $out['red'] = hexdec(substr($c, 0,1*$l));
			$out[1] = $out['g'] = $out['green'] = hexdec(substr($c, 1*$l,1*$l));
			$out[2] = $out['b'] = $out['blue'] = hexdec(substr($c, 2*$l,1*$l));
		} else $out = $c;
	} else $out = $c;

	return $out;
}


function Cache_Check($etag, $contentType,$filename='',$modified=NULL,$age=2592000,$disposition='inline') {
	header('Content-Type: '.$contentType,true);
	header("Etag: $etag",true);
	header("Expires: ".gmdate("D, d M Y H:i:s",time()+$age) . " GMT",true);
	header("Cache-Control: public, max-age=$age",true);
	$fn = empty($filename) ? '' : "; filename=$filename";
	header("Content-Disposition: ".$disposition.$fn,true);

	if (array_key_exists('HTTP_IF_NONE_MATCH',$_SERVER) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
		header('HTTP/1.1 304 Not Modified', true, 304); die();
	}

	if (!$modified) $modified = 0;
	$lm = gmdate('r',$modified);
	header("Last-Modified: ".$lm,true);
	if (array_key_exists('HTTP_IF_MODIFIED_SINCE',$_SERVER) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $lm) {
		header('HTTP/1.1 304 Not Modified', true, 304); die();
	}
}

function Cache_Output($data,$etag,$contentType,$filename='',$modified=NULL,$age=2592000,$disposition='inline') {
	Cache_Check($etag,$contentType,$filename,$modified,$age,$disposition);
	header('Content-Length: ' . strlen($data),true);
	die($data);
}

?>