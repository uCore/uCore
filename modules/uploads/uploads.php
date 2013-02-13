<?php
define('PATH_UPLOADS',PATH_ABS_ROOT.'uUploads');
class uUploads extends uBasicModule {
	public static $uuid = 'uploads';
	function SetupParents() {
		$this->SetRewrite(TRUE);
		utopia::RegisterAjax('fileManagerAjax',array($this,'ajax'));
		utopia::AddInputType(itFILEMANAGER,array($this,'show_fileman'));

		jqFileManager::SetDocRoot(PATH_ABS_ROOT);
		jqFileManager::SetRelRoot(PATH_REL_ROOT);
		
		uJavascript::IncludeFile(jqFileManager::GetPathJS());
		uCSS::IncludeFile(jqFileManager::GetPathCSS());
	}
	function RunModule() {
		$uuid = $this->GetUUID(); if (is_array($uuid)) $uuid = reset($uuid);
		$sections = utopia::GetRewriteURL();
		$sections = preg_replace('/^'.preg_quote($uuid,'/').'\/?/','',$sections); // shift uuid off the start
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
		if ($relativeToUpload) $targetFile = PATH_UPLOADS.'/'.trim($targetFile,'/\\');
		$targetDir = dirname($targetFile);
		// make dir
		if (!file_exists($targetDir)) mkdir($targetDir,0755,true);
		// move file
		if (!move_uploaded_file($fileInfo['tmp_name'],$targetFile)) return FALSE;
		return $targetFile;
	}
	function show_fileman($fieldName,$inputType,$defaultValue='',$possibleValues=NULL,$attributes = NULL,$noSubmit = FALSE) {
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
	function ajax() {
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
		uJavascript::IncludeText(<<<FIN
	function FileManagerItemClick(event) {
		var item = $(this).data('item');
		if (item.type != 0) return;
		window.open(item.fullPath);
	}
FIN
);
		$obj =& utopia::GetInstance(__CLASS__);
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
			parse_str(parse_url($src,PHP_URL_QUERY),$qs);
		
			if (isset($qs['w']) !== false) continue;
			if (isset($qs['h']) !== false) continue;
			
			if ($img->hasAttribute('width')) $qs['w'] = $img->getAttribute('width');
			if ($img->hasAttribute('height')) $qs['h'] = $img->getAttribute('height');
			
			$qs = http_build_query($qs);
			if (strpos('?',$src) !== false) $src .= '&'.$qs;
			else $src .= '?'.$qs;
			$img->setAttribute('src',$src);
		}
	}
}

uEvents::AddCallback('ProcessDomDocument','uUploads::ProcessDomDocument','',99999999);