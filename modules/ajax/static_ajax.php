<?php

// dependancies
// check dependancies exist - Move to install?

class internalmodule_StaticAjax extends flexDb_BasicModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return ''; }
	public function GetOptions() { return ALWAYS_ACTIVE | PERSISTENT_PARENT; }

	public function SetupParents() {
		uJavascript::IncludeFile(dirname(__FILE__).'/static_ajax.js');
		//$this->AddParent('*');
		// register ajax
		$this->RegisterAjax('updateField',array($this,'UpdateField'));
		$this->RegisterAjax('filterText',array($this,'FilterText'));
		$this->RegisterAjax('Suggest',array($this,'getComboVals'));
		$this->RegisterAjax('showQueries',array($this,'showQueries'));
		$this->RegisterAjax('getImage',array($this,'getImage'));
		$this->RegisterAjax('getFile',array($this,'getFile'));
		$this->RegisterAjax('getUpload',array($this,'getUpload'));
		$this->RegisterAjax('getCompressed',array($this,'getCompressed'));
	}

//	public function ParentLoadPoint() { return 0; }
	public function ParentLoad($parent) {
		//if (!is_subclass_of($parent,'flexDb_ListDataModule')) return true;

		//$url = BuildQueryString($_SERVER['REQUEST_URI'],array('__ajax'=>'printable'));
		//FlexDB::LinkList_Add('admin_buttons','Printable',$url,50);//,NULL,array('onclick'=>"window.location = qsUpdate(window.location.href,{__ajax:'printable'});"));
		//,'class'=>'linklist-options'
	}

	public function RunModule() {
	}

	public function getCompressed() {
		if (!array_key_exists('file',$_GET)) die();
		$filename = PATH_ABS_ROOT.ltrim($_GET['file'],'/');
		$contents = file_get_contents($filename);

		$ext = pathinfo($filename,PATHINFO_EXTENSION);
		switch ($ext) {
			case 'js':
				header('Content-Type: text/javascript');
				$contents = JSMin::minify($contents);
				break;
			case 'css':
				header('Content-Type: text/css');
				//$contents = CssMin::minify($contents);
				break;
			default:
		}

		die($contents);
	}

	public function getUpload() {
		//$module = FlexDB::UUIDExists($_GET['uuid']);
		//print_r($module);
		$rec = CallModuleFunc(GetCurrentModule(),'LookupRecord',$_GET['p']);
		//print_r($rec);
		if (!$rec) {
			echo '<h1>404 File Not Found</h1>';
			header('HTTP/1.1 404 File Not Found',true,404);
			die();
		//	die('<h1>404 File Not Found</h1>');
		}
		list($path,$username,$password) = explode('¦',$rec[$_GET['f']]);
		if ($username) {
			die('auth');
		}
		$path = realpath($path);

		$fileMod = filemtime($path);
		$etag = sha1($fileMod.'-'.filesize($path));

		$cType = NULL;
		if (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$cType = finfo_file($finfo,$path);
		} elseif (function_exists('mime_content_type')) {
			$cType = mime_content_type($path);
		} else {
			ob_start();system("file -bi '$path'",$cType);ob_end_clean();
		}

		FlexDB::Cache_Check($etag,$cType,basename($path),$fileMod);

		FlexDB::Cache_Output(file_get_contents($path),$etag,$cType,basename($path),$fileMod);
	}
	public function getFile() {
		$last_load    =  isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? strtotime(trim($_SERVER['HTTP_IF_MODIFIED_SINCE'])) : false;
		if ($last_load) {
			header('Pragma: public');
			header('HTTP/1.0 304 Not Modified', true, 304); die();
		}

		$qry = "SELECT ".mysql_real_escape_string($_GET['f'])." as file, ".mysql_real_escape_string($_GET['f'])."_filetype as filetype, ".mysql_real_escape_string($_GET['f'])."_filename as filename FROM ".mysql_real_escape_string($_GET['t'])." WHERE ".mysql_real_escape_string($_GET['k'])." = ".mysql_real_escape_string($_GET['p']);
		$result = sql_query($qry);
		if ($result === FALSE || mysql_num_rows($result) <= 0) { die('No File Found.'); }
		$data = mysql_result($result,0,'file');
		$type = mysql_result($result,0,'filetype');
		$name = mysql_result($result,0,'filename');

		echo CachedOutput($data, sha1($data), $type, $name, NULL, 86400, $_GET['a']);
	}

	public function getImage() {
		$qry = "SELECT ".mysql_real_escape_string($_GET['f'])." as img FROM ".mysql_real_escape_string($_GET['t'])." WHERE ".mysql_real_escape_string($_GET['k'])." = ".mysql_real_escape_string($_GET['p']);
		$data = '';
		$result = sql_query($qry);
		if ($result !== FALSE && mysql_num_rows($result) > 0)
			$data = mysql_result($result,0,'img');
//		else
//			$data = file_get_contents(PATH_ABS_ROOT.'no-img.png');

		$etag = sha1($_SERVER['REQUEST_URI'].'-'.strlen($data));
		//FlexDB::Cache_Check($etag,'image/png',$_GET['p'].$_GET['f'].'.png');

		try {
			$src = imagecreatefromstring($data);
		} catch (Exception $e) {
			$src = imagecreatefromstring(file_get_contents(PATH_ABS_ROOT.'no-img.png'));
		}
		//mail('oridan82@gmail.com','err',print_r(error_get_last(),true));

		if (imageistruecolor($src)) {
			imageAlphaBlending($src, true);
			imageSaveAlpha($src, true);
		}
		$srcW = imagesx($src);
		$srcH = imagesy($src);

		$width = array_key_exists('w',$_GET) ? $_GET['w'] : $srcW;
		$height = array_key_exists('h',$_GET) ? $_GET['h'] : $srcH;
		$maxW = $width; $maxH = $height;

		$ratio_orig = $srcW/$srcH;
		if ($width/$height > $ratio_orig) {
			$width = $height*$ratio_orig;
		} else {
			$height = $width/$ratio_orig;
		}
		if (!array_key_exists('w',$_GET)) $maxW = $width;
		if (!array_key_exists('h',$_GET)) $maxH = $height;

		$img = imagecreatetruecolor($maxW,$maxH);
		$trans_colour = imagecolorallocatealpha($img, 0, 0, 0, 127);
		imagefill($img, 0, 0, $trans_colour);

		$offsetX = ($maxW - $width) /2;
		$offsetY = ($maxH - $height) /2;

		//fastimagecopyresampled($img,$src,$offsetX,$offsetY,0,0,$width,$height,$srcW,$srcH,1);
		imagecopyresampled($img,$src,$offsetX,$offsetY,0,0,$width,$height,$srcW,$srcH);
		imagealphablending($img, true);
		imagesavealpha($img, true);

		//    Image output
		//if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) ob_end_clean();
		ob_end_clean();
		header('Content-Encoding: none',true);
		ob_start();
		//imagejpeg($img,NULL,1);
		imagepng($img);
		imagedestroy($img);
		$c = ob_get_contents();
		ob_end_clean();

		FlexDB::Cache_Output($c,$etag,'image/png',$_GET['p'].$_GET['f'].'.png');
		die();
	}

	public function showQueries() {
		print_r($GLOBALS['sql_queries']);
	}

	public function FilterText() {
		$font   = 2;
		$width  = ImageFontWidth($font) * strlen($_GET['t']);
		$height = ImageFontHeight($font);

		$img = imagecreate($width,$height);
		$bg = imagecolorallocate($img, 0, 255, 0);
		$bg = imagecolortransparent($img,$bg);
		$textcolor = imagecolorallocate($img, 200, 200, 200);
		imagefill($img, 0, 0, $bg);
		imagestring($img,$font,0,0,$_GET['t'],$textcolor);


		function output_handler($img) {
			return FlexDB::Cache_Output($img,sha1($img),'image/gif',"fltrText_".strip_tags($_GET['t']).".gif");
		}

		//    Image output
		ob_start("output_handler");
		imagegif($img);
		imagedestroy($img);
		ob_end_flush();
		die();
		/*
		 // always modified
		 $expires = 60 * 60 * 24 * 5;
		 $exp_gmt = gmdate("D, d M Y H:i:s", time() + $expires )." GMT";
		 $mod_gmt = gmdate("D, d M Y H:i:s", time() + (3600 * -5 * 24 * 365) )." GMT";
		 $exp_gmt = gmdate("D, d M Y H:i:s", filemtime($_SERVER['SCRIPT_FILENAME'])+ $expires);
		 $mod_gmt = gmdate("D, d M Y H:i:s", filemtime($_SERVER['SCRIPT_FILENAME']));

		 ini_set('zlib.output_compression','off');
		 header("Content-Encoding: ");

		 header('Content-Type: image/gif');
		 header("Content-Disposition: inline; filename=fltrText_".strip_tags($_GET['t']).".gif");
		 header("Expires: $exp_gmt");
		 header("Last-Modified: $mod_gmt");
		 header("Cache-Control: public, max-age=$expires");

		 imagegif($im);
		 die();*/
	}

	public function UpdateField() {
		$filter = '';
		$pkVal = NULL;
		$requireReload = false;
		$updatesDone = false;

		if (!empty($_FILES)) { // process file upload
			$files = array();
			foreach ($_FILES['sql']['name'] as $function => $funcArr) {
				foreach ($funcArr as $id => $fileName) {
					$files[$id] = array('name'=>$fileName,
						'function'=>$function,
						'type'=>$_FILES['sql']['type'][$function][$id],
						'tmp_name'=>$_FILES['sql']['tmp_name'][$function][$id],
						'size'=>$_FILES['sql']['size'][$function][$id]
					);
				}
			}

			foreach ($files as $enc_name => $fileInfo) {
				$string = cbase64_decode($enc_name);

				InterpretSqlString($string, $module, $field, $pkVal);
				CallModuleFunc($module, 'ProcessUpdates',$fileInfo['function'],$enc_name,$field,$fileInfo,$pkVal);
			}
		}

		if (array_key_exists('sql',$_POST)) {
			foreach ($_POST['sql'] as $function => $vars) {
				$pkVal = NULL;
				foreach ($vars as $enc_name => $value) {
					$string = cbase64_decode($enc_name); // cbase adds/subtracts the missing = padding (to keep html compliance with fieldnames)
					$value = cbase64_decode($value);
					if (get_magic_quotes_gpc()) $value = stripslashes($value);

					InterpretSqlString($string, $module, $field, $pkVal);
					CallModuleFunc($module,'ProcessUpdates',$function,$enc_name,$field,$value,$pkVal);
				}
			}
		}
		AjaxEcho("InitJavascript.run();\n");
	}

	public function getComboVals() {
		if (!array_key_exists('term',$_GET)) die('[]');
		if (!array_key_exists('gv',$_GET)) die('[]');

		$tmp = cbase64_decode($_GET['gv']);
		//if (!$tmp) return;
		if (strpos($tmp,':') !== FALSE) {
			list($module,$field) = explode(':',$tmp);
			CallModuleFunc($module,'_SetupFields');
			$vals = CallModuleFunc($module,'GetFieldProperty',$field,'values');
		} elseif (strpos($tmp,'|') !== FALSE) {
			list($module,$field) = explode('|',$tmp);
			CallModuleFunc($module,'_SetupFields');
			$fltr = CallModuleFunc($module,'FindFilter',$field);
			$vals = $fltr['values'];
		}
		$out = ''; $justNumeric = true;
		$linebreaks = array("\n\r","\n","\r\r");
		$found = array();
		if (!$_GET['term']) $found[] = array('value'=>'','label'=>'-');
		if (is_array($vals)) foreach ($vals as $key=>$value) {
			if (empty($key) && empty($value)) continue;
			if (!is_numeric($value)) $justNumeric = false;
			if (empty($_GET['term']) || strpos(strtolower($key), strtolower($_GET['term'])) !== false) {
				// old cases are \n\r,
				// new cases are \n
				// replace \n\r
				//				$key = preg_replace('/(\r\n|\n|\r|\f)/',"\r",$key);
				//				$value = preg_replace('/(\r\n|\n|\r|\f)/',"\r",$value);
				$found[] = array(
					'value'	=> $value,
					'label'	=> $key.($key == $value ? '' : ' ('.$value.')'),
					'desc' => 'ID: '.$value,
				);
				//$out .= preg_replace('/(\r\n|\n|\r|\f)/',"\r","$key|$value")."\n";
				//$out .= preg_replace('/(\r\n|\n|\r|\f)/',"\r","$key,");
			}
		}
		// value, label, desc, icon;

		echo json_encode($found);
		//echo ($justNumeric) ? "|0\n$out" : "|\n$out";
	}
}

?>