<?php
class uUploads extends uBasicModule {
	function SetupParents() {
		$this->SetRewrite(TRUE);
	}
	function GetUUID() { return 'uploads'; }
	function ParentLoad($parent) {}
	function RunModule() {
		$path = urldecode(PATH_UPLOADS.substr($_SERVER['REQUEST_URI'],10));
		$path = parse_url($path,PHP_URL_PATH);
		$path = realpath($path);

		if (stripos($path,PATH_UPLOADS) === FALSE || !file_exists($path)) utopia::PageNotFound();

                utopia::CancelTemplate();

                $cType = NULL;
                if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $cType = finfo_file($finfo,$path);
                } elseif (function_exists('mime_content_type')) {
                        $cType = mime_content_type($path);
                } else {
                        ob_start();system("file -bi '$path'",$cType);ob_end_clean();
                }

		$fileName = pathinfo($path,PATHINFO_BASENAME);
                $fileMod = filemtime($path);
                $etag = sha1($fileMod.'-'.$_SERVER['REQUEST_URI']);
//                utopia::Cache_Check($etag,$cType,$fileName);

		$output = file_get_contents($path);

		if (stripos($cType,'image/') !== FALSE && (isset($_GET['w']) || isset($_GET['h']))) {
			// check w and h
			$w = isset($_GET['w']) ? $_GET['w'] : NULL;
			$h = isset($_GET['h']) ? $_GET['h'] : NULL;
			$img = imagecreatefromstring($output);
			$img = utopia::constrainImage($img,$w,$h);
			$ext = pathinfo($path,PATHINFO_EXTENSION);

			ob_start();
			if (function_exists("image$ext"))
				call_user_func("image$ext",$img);
			else
				imagepng($img);
			$output = ob_get_contents();
			ob_end_clean();
			imagedestroy($img);
		}

		header("Content-Type: $cType");

		echo $output;
//		utopia::Cache_Output($output,$etag,$cType,$fileName);
	}
}

?>
