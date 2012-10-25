<?php
define('PATH_UPLOADS',PATH_ABS_ROOT.'uUploads');
class uUploads extends uBasicModule {
	function SetupParents() {
		$this->SetRewrite(TRUE);
	}
	function GetUUID() { return 'uploads'; }
	function RunModule() {
		$sections = utopia::GetRewriteURL();
		$sections = preg_replace('/^'.preg_quote($uuid.'/','/').'/','',$sections); // shift uuid off the start
		$path = urldecode(PATH_UPLOADS.'/'.$sections);
		$path = parse_url($path,PHP_URL_PATH);
		$path = realpath($path);

		if (stripos($path,PATH_UPLOADS) === FALSE || !file_exists($path)) utopia::PageNotFound();

                utopia::CancelTemplate();

                $cType = utopia::GetMimeType($path);

		$fileName = pathinfo($path,PATHINFO_BASENAME);
                $fileMod = filemtime($path);
                $etag = sha1($fileMod.'-'.$_SERVER['REQUEST_URI']);
                utopia::Cache_Check($etag,$cType,$fileName);

		$output = file_get_contents($path);

		if (stripos($cType,'image/') !== FALSE && (isset($_GET['w']) || isset($_GET['h']))) {
			// check w and h
			$w = isset($_GET['w']) ? $_GET['w'] : NULL;
			$h = isset($_GET['h']) ? $_GET['h'] : NULL;
			$img = imagecreatefromstring($output);
			$img = utopia::constrainImage($img,$w,$h);
			$ext = pathinfo($path,PATHINFO_EXTENSION);

			ob_start();
			if (function_exists(strtolower("image$ext")))
				call_user_func("image$ext",$img);
			else {
				imagepng($img);
				$cType = 'image/png';
				$fileName = str_replace(".$ext",'.png',$fileName);
			}
			$output = ob_get_contents();
			ob_end_clean();
			imagedestroy($img);
		}

		utopia::Cache_Output($output,$etag,$cType,$fileName);
	}
	static function UploadFile($fileInfo,$targetFile,$relativeToUpload=true) {
		// build dir path
		if ($relativeToUpload) $targetFile = PATH_ABS_ROOT.'uUploads/'.trim($targetFile,'/\\');
		$targetDir = dirname($targetFile);
		// make dir
		if (!file_exists($targetDir)) mkdir($targetDir,0755,true);
		// move file
		if (!move_uploaded_file($fileInfo['tmp_name'],$targetFile)) return FALSE;
		return $targetFile;
	}
}
