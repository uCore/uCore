<?php
define('PATH_UPLOADS',PATH_ABS_ROOT.'uUploads');
class uUploads extends uBasicModule {
	public static function Initialise() {
		utopia::RegisterAjax('fileManagerAjax','uUploads::ajax');
		utopia::AddInputType(itFILEMANAGER,'uUploads::show_fileman');

		jqFileManager::SetDocRoot(PATH_ABS_ROOT);
		jqFileManager::SetRelRoot(PATH_REL_ROOT);
		
		uJavascript::IncludeFile(jqFileManager::GetPathJS());
		uCSS::IncludeFile(jqFileManager::GetPathCSS());
	}
	public static $uuid = 'uploads';
	function SetupParents() {
		$this->SetRewrite(TRUE);
	}
	function RunModule() {
		$uuid = $this->GetUUID(); if (is_array($uuid)) $uuid = reset($uuid);
		$sections = utopia::GetRewriteURL();
		$sections = preg_replace('/^'.preg_quote($uuid,'/').'\/?/','',$sections); // shift uuid off the start
		if (!$sections) utopia::PageNotFound();
		$path = urldecode(PATH_UPLOADS.'/'.$sections);
		$path = parse_url($path,PHP_URL_PATH);
		$path = realpath($path);

		if (stripos($path,PATH_UPLOADS) === FALSE || !file_exists($path)) utopia::PageNotFound();

		utopia::CancelTemplate();

		$cType = utopia::GetMimeType($path);

		$fileName = pathinfo($path,PATHINFO_BASENAME);
		$fileMod = filemtime($path);
		$fileSize = filesize($path);
		$w = isset($_GET['w']) ? $_GET['w'] : NULL;
		$h = isset($_GET['h']) ? $_GET['h'] : NULL;

		if (!is_numeric($w) || !is_numeric($h)) {
			utopia::PageNotFound();
		}

		if (stripos($cType,'image/') !== FALSE && ($w || $h)) {
			$idents = array($_SERVER['REQUEST_URI'],$fileMod,$fileSize,$w,$h);
			$etag = utopia::checksum($idents);
			utopia::Cache_Check($etag,$cType,$fileName);
			$cacheFile = uCache::retrieve($idents);
			if ($cacheFile) $output = file_get_contents($cacheFile);
			else $output = file_get_contents($path);
		
			// check w and h
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
			$output = ob_get_clean();
			imagedestroy($img);
			
			// only need to cache the resized versions
			uCache::store($idents,$output);
			utopia::Cache_Output($output,$etag,$cType,$fileName);
		} else {
			header('Content-Type: ' . $cType);
			self::resumableOutput($path);
		}

	}
	static function resumableOutput($path) {
		session_write_close();
		while (ob_get_level()) ob_end_clean();
		header('Content-Encoding: identity');
		header('Accept-Ranges: bytes');
		header("Content-Transfer-Encoding: binary");
		header("Connection: close" );
		$filesize = filesize($path);
		$f = fopen($path,'r');

		//header('Content-Type: text/plain',true);
		if (isset($_SERVER['HTTP_RANGE'])) {
			preg_match('/bytes=(\d+)\-(\d+)?/', $_SERVER['HTTP_RANGE'], $matches);
			$offset = intval($matches[1]);
			$length = (isset($matches[2]) ? intval($matches[2]) : $filesize - 1) - $offset;
			header('HTTP/1.1 206 Partial Content');
			header('Content-Range: bytes ' . $offset . '-' . ($offset + $length) . '/' . $filesize);
			header("Content-Length: " . $length);
			fseek($f,$offset);
		} else {
			$length = $filesize;
			header("Content-Length: " . $filesize);
			rewind($f);
		}

		while ($length) { // Read in blocks of 8KB so we don't chew up memory on the server
			$read = ($length > 8192) ? 8192 : $length;
			$length -= $read;
			echo fread($f,$read);
		}
		fclose($f);
		exit;
	}
	static function UploadFile($fileInfo,$targetFile,$relativeToUpload=true) {
		// build dir path
		if ($relativeToUpload) $targetFile = PATH_UPLOADS.'/'.trim($targetFile,'/\\');
		$targetDir = dirname($targetFile);
		// make dir
		if (!file_exists($targetDir)) mkdir($targetDir,0755,true);
		// move file
		if (!move_uploaded_file($fileInfo['tmp_name'],$targetFile)) return FALSE;
		return $targetFile;
	}
	static function show_fileman($fieldName,$inputType,$defaultValue='',$possibleValues=NULL,$attributes = NULL,$noSubmit = FALSE) {
		list($path) = self::Init();
		//if (!is_array($attributes)) $attributes = array();
		//$attributes['onclick'] = 'alert("moo");return false;';
		uJavascript::AddText(<<<FIN
	function filesel(id,item) {
		if (item.type != 0) return;
		$('#fileMan').dialog('close');
		uf(id,item.fullPath,'$fieldName');
	}
//	$(document).ready(function() {
//		$('#fileMan').dialog({autoOpen: false});
//	}
FIN
);
		return '<div id="fileMan"></div>'.utopia::DrawInput($fieldName,itTEXT,$defaultValue,$possibleValues,$attributes,$noSubmit).
			'<input id="'.$fieldName.'" type="button" onclick="$(\'#fileMan\').fileManager({ajaxPath:\''.$path.'\'}).on(\'dblclick\',\'.fmFile\',function(event) {filesel(\''.$fieldName.'\',$(this).data(\'item\'))}).dialog();" value="Choose File">';
		//return $out.$defaultValue.utopia::DrawInput($fieldName,itBUTTON,'Choose File',$possibleValues,$attributes,$noSubmit);
	}
	static function ajax() {
		header("X-Robots-Tag: noindex", true);

		utopia::CancelTemplate();
		if (isset($_GET['upload'])) return jqFileManager::ProcessUpload(jqFileManager::GetPath(PATH_UPLOADS));
		jqFileManager::ProcessAjax(PATH_UPLOADS,null,'uUploads::OnRename','uUploads::GetIcon');
	}
	static function GetIcon($path) {
		$type = utopia::GetMimeType($path);
		if (strpos($type,'image/') !== 0) return false;
		$path = str_replace(PATH_UPLOADS,PATH_REL_ROOT.'uploads',$path);
		return $path.'?w=64&h=64';
	}
	static function OnRename($from,$to) {
		// has been renamed.. fix in CMS
		$from = jqFileManager::GetRelativePath($from);
		$to = jqFileManager::GetRelativePath($to);
		// find cms pages
		// replace "$from" with "$to"
	}
	static function Init() {
		$obj = utopia::GetInstance(__CLASS__);
		return array($obj->GetAjaxPath(),$obj->GetAjaxUploadPath());
	}
	function GetAjaxPath() {
		return $this->GetURL(array('__ajax'=>'fileManagerAjax'));
	}
	function GetAjaxUploadPath() {
		return $this->GetURL(array('__ajax'=>'fileManagerAjax','upload'=>1));
	}
	static function ProcessDomDocument($event,$obj,$templateDoc) {
		/* IMAGES */
		$images = $templateDoc->getElementsByTagName('img');
		foreach ($images as $img) {
			$src = $img->getAttribute('src');
			if (!preg_match('/^'.preg_quote(PATH_REL_ROOT,'/').'uploads\//',$src)) continue;
			
			$p = $img; $inEditable = false;
			while (($p = $p->parentNode)) if ($p instanceof DOMElement && $p->hasAttribute('contenteditable')) { $inEditable = true; break; }
			if ($inEditable) continue;
			
			parse_str(parse_url($src,PHP_URL_QUERY),$qs);
		
			if (isset($qs['w']) !== false) continue;
			if (isset($qs['h']) !== false) continue;
			
			if ($img->hasAttribute('width')) $qs['w'] = $img->getAttribute('width');
			if ($img->hasAttribute('height')) $qs['h'] = $img->getAttribute('height');
			
			// inline styles second as they have priority over attributes
			if ($img->hasAttribute('style')) {
				$s = $img->getAttribute('style');
				$s = explode(';',$s);
				foreach ($s as $prop) {
					if (preg_match('/\s*(width|height)\s*:\s*([0-9]+)px/',$prop,$matches)) {
						$qs[$matches[1][0]] = intval($matches[2]);
					}
				}
			}
			
			$qs = http_build_query($qs);
			if (!$qs) continue;
			if (strpos('?',$src) !== false) $src .= '&'.$qs;
			else $src .= '?'.$qs;
			$img->setAttribute('src',$src);
		}
	}
}

uEvents::AddCallback('ProcessDomDocument','uUploads::ProcessDomDocument','',MAX_ORDER);
