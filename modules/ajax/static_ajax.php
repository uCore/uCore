<?php

// dependancies
// check dependancies exist - Move to install?

class internalmodule_StaticAjax extends uBasicModule {
	// title: the title of this page, to appear in header box and navigation
	public function GetTitle() { return ''; }
	public function GetOptions() { return ALWAYS_ACTIVE | PERSISTENT_PARENT; }

	public function SetupParents() {
		uJavascript::IncludeFile(dirname(__FILE__).'/static_ajax.js');
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

	public function RunModule() {
	}

	public function getCompressed() {
		ob_end_clean();
		header('Content-Encoding: none');
		
		if (!array_key_exists('file',$_GET)) die();
		$filename = PATH_ABS_ROOT.ltrim($_GET['file'],'/');
		$contents = file_get_contents($filename);
		
		$etag = sha1($filename.$contents);
		
		$ext = pathinfo($filename,PATHINFO_EXTENSION);
		switch ($ext) {
			case 'js':
				$type = 'text/javascript';
				$contents = JSMin::minify($contents);
				break;
			case 'css':
				$type = 'text/css';
				//$contents = CssMin::minify($contents);
				break;
			default:
				$type = 'text/html';
		}
//		utopia::Cache_Check($etag,$type);

		utopia::Cache_Output($contents,$etag,$type);
		die($contents);
	}

	public function getUpload() {
		//$module = utopia::UUIDExists($_GET['uuid']);
		//print_r($module);
		$obj = utopia::GetInstance(GetCurrentModule());
		$rec = $obj->LookupRecord($_GET['p']);
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

		utopia::Cache_Check($etag,$cType,basename($path),$fileMod);

		utopia::Cache_Output(file_get_contents($path),$etag,$cType,basename($path),$fileMod);
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
		else
			die('Image Not Found');//$data = file_get_contents(PATH_ABS_ROOT.'no-img.png');

		utopia::CancelTemplate();

		$etag = sha1($_SERVER['REQUEST_URI'].'-'.strlen($data));
		utopia::Cache_Check($etag,'image/png',$_GET['p'].$_GET['f'].'.png');

		$src = imagecreatefromstring($data);

		$width = isset($_GET['w']) ? $_GET['w'] : NULL;
		$height = isset($_GET['h']) ? $_GET['h'] : NULL;

		$img = utopia::constrainImage($src,$width,$height);

		//    Image output
		ob_start();
		imagepng($img);
		imagedestroy($img);
		$c = ob_get_contents();
		ob_end_clean();

		utopia::Cache_Output($c,$etag,'image/png',$_GET['p'].$_GET['f'].'.png');
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
			return utopia::Cache_Output($img,sha1($img),'image/gif',"fltrText_".strip_tags($_GET['t']).".gif");
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
		ignore_user_abort(true);
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
				$obj = utopia::GetInstance($module);
				$obj->ProcessUpdates($fileInfo['function'],$enc_name,$field,$fileInfo,$pkVal);
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
					$obj = utopia::GetInstance($module);
					$obj->ProcessUpdates($function,$enc_name,$field,$value,$pkVal);
				}
			}
		}
	}

	public function getComboVals() {
		if (!array_key_exists('term',$_GET)) die('[]');
		if (!array_key_exists('gv',$_GET)) die('[]');

		$tmp = cbase64_decode($_GET['gv']);
		//if (!$tmp) return;
		if (strpos($tmp,':') !== FALSE) {
			list($module,$field) = explode(':',$tmp);
			$obj = utopia::GetInstance($module);
			$obj->_SetupFields();
			$vals = $obj->GetValues($field);
		} elseif (strpos($tmp,'|') !== FALSE) {
			list($module,$field) = explode('|',$tmp);
			$obj = utopia::GetInstance($module);
			$obj->_SetupFields();
			$fltr = $obj->FindFilter($field);
			$vals = $fltr['values'];
		}
		$out = '';
		$linebreaks = array("\n\r","\n","\r\r");
		$found = array();
		if (!$_GET['term']) $found[] = array('value'=>'','label'=>'-','key'=>'');
		if (is_array($vals)) foreach ($vals as $key=>$value) {
			if (empty($key) && empty($value)) continue;
			if (empty($_GET['term']) || strpos(strtolower($key), strtolower($_GET['term'])) !== false) {
				$f = array(
					'value'	=> $value,
					'label' => $key.($key == $value ? '' : ' ('.$value.')'),
					'key' => $key,
				);
				if ($value !== $key) $f['desc'] = 'ID: '.$value;
				$found[] = $f;
			}
		}
		// value, label, desc, icon;

		if (!is_assoc($vals)) {
			// this is an array of values, so make 'value' = 'key' and remove 'desc' and 'label'
			foreach ($found as $k=>$v) {
				if (!isset($v['key'])) print_r($v);
				$found[$k]['value'] = $v['key'];
				unset($found[$k]['label']);
				unset($found[$k]['desc']);
			}
		}

		echo json_encode($found);
	}
}

?>
