<?php

class uStaticAjax implements iUtopiaModule {
	static function Initialise() {
		// register ajax
		utopia::RegisterAjax('updateField','uStaticAjax::UpdateField');
		utopia::RegisterAjax('Suggest','uStaticAjax::getComboVals');
		utopia::RegisterAjax('getUpload','uStaticAjax::getUpload');
		utopia::RegisterAjax('getCompressed','uStaticAjax::getCompressed');
		utopia::RegisterAjax('getParserContent','uStaticAjax::getParserContent');

		uEvents::AddCallback('AfterInit','uStaticAjax::RunAjax',null,MAX_ORDER+MAX_ORDER);
	}
	static function RunAjax() {
		uJavascript::IncludeFile(dirname(__FILE__).'/static_ajax.js');
		
		// process ajax function
		if (array_key_exists('__ajax',$_REQUEST)) {
			$ajaxIdent	= $_REQUEST['__ajax'];
			utopia::RunAjax($ajaxIdent);
		}
	}

	static function InterpretSqlString($sqlString, &$module, &$field, &$pkVal) {
		$matches = null;
		if (!preg_match('/([^:]+):([^\(]+)(\(.*\))?/',$sqlString,$matches)) return false;
		
		$module = $matches[1];
		$field = $matches[2];

		if (!isset($matches[3]) || $matches[3] === '')
			$pkVal = $pkVal;
		elseif ($matches[3] == '()') {
			$pkVal = '';
		} else
			$pkVal = substr($matches[3],1,-1);

		return true;
	}

	public static function getParserContent() {
		$ident = isset($_GET['ident']) ? $_GET['ident'] : null;
		$data = isset($_GET['data']) ? '.'.$_GET['data'] : null;
		echo '{'.$ident.$data.'}';
		utopia::Finish();
	}

	public static function getCompressed() {
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

	public static function getUpload() {
		//$module = utopia::UUIDExists($_GET['uuid']);
		//print_r($module);
		$obj = utopia::GetInstance(utopia::GetCurrentModule());
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

		$cType = utopia::GetMimeType($path);

		utopia::Cache_Check($etag,$cType,basename($path),$fileMod);

		utopia::Cache_Output(file_get_contents($path),$etag,$cType,basename($path),$fileMod);
	}

	public static function UpdateField() {
		ignore_user_abort(true);
		$filter = '';
		$pkVal = NULL;
		$requireReload = false;
		$updatesDone = false;

		if (!empty($_FILES)) { // process file upload
			$files = array();
			foreach ($_FILES as $name => $info) {
				if (!preg_match('/^usql\-(.+)/',$name,$match)) continue;
				$infoArr = array();
				if (is_array($info['name'])) {
					foreach ($info['name'] as $id => $val) {
						$infoArr[] = array(
							'name'=>$_FILES['name'][$id],
							'type'=>$_FILES['type'][$id],
							'tmp_name'=>$_FILES['tmp_name'][$id],
							'error'=>$_FILES['error'][$id],
							'size'=>$_FILES['size'][$id],
						);
					}
				} else { // multiple
					$infoArr[] = $info;
				}
				
				foreach ($infoArr as $fileInfo) {
					$enc_name = $match[1];
					$string = cbase64_decode($enc_name);

					self::InterpretSqlString($string, $module, $field, $pkVal);
					$obj = utopia::GetInstance($module);
					$obj->ProcessUpdates($enc_name,$field,$fileInfo,$pkVal,true);
				}
			}
		}

		if (!empty($_POST)) {
			foreach ($_POST as $name => $value) {
				if (!preg_match('/^usql\-(.+)/',$name,$match)) continue;
				$enc_name = $match[1];
				$string = cbase64_decode($enc_name); // cbase adds/subtracts the missing = padding (to keep html compliance with fieldnames)
				
				self::InterpretSqlString($string, $module, $field, $pkVal);
				$obj = utopia::GetInstance($module);
				$obj->ProcessUpdates($enc_name,$field,$value,$pkVal);
			}
		}
	}

	public static function getComboVals() {
		if (!array_key_exists('term',$_GET)) die('[]');
		if (!array_key_exists('gv',$_GET)) die('[]');

		$tmp = cbase64_decode($_GET['gv']);
		//if (!$tmp) return;
		if (strpos($tmp,':') !== FALSE) {
			list($module,$field) = explode(':',$tmp);
			$obj =& utopia::GetInstance($module);
			$obj->_SetupFields();
			$vals = $obj->GetValues($field);
		} elseif (strpos($tmp,'|') !== FALSE) {
			list($module,$field) = explode('|',$tmp);
			$obj =& utopia::GetInstance($module);
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
			$label = $value.($key == $value ? '' : ' ('.$key.')');
			if (empty($_GET['term']) || stripos($label, $_GET['term']) !== false) {
				$f = array(
					'value'	=> $key,
					'label' => $label,
				);
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
