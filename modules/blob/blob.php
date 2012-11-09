<?php
class uBlob extends uBasicModule {
	function SetupParents() {
		$this->SetRewrite(array('{module}','{field}','{pk}','{filename}'));
	}
	function GetUUID() { return 'blob'; }
	function RunModule() {
		if (!isset($_REQUEST['module']) || !class_exists($_REQUEST['module']) || !isset($_REQUEST['pk']) || !isset($_REQUEST['field'])) utopia::PageNotFound();

		$obj =& utopia::GetInstance($_REQUEST['module']);
		if (uEvents::TriggerEvent('CanAccessModule',$obj) === FALSE) return FALSE;
		
		$rec = $obj->LookupRecord($_REQUEST['pk'],true);
		if (!$rec || !isset($rec[$_REQUEST['field']])) utopia::PageNotFound();
		utopia::CancelTemplate();
		$data = $rec[$_REQUEST['field']];

		$filename = isset($_REQUEST['filename']) ? $_REQUEST['filename'] : $rec[$_REQUEST['field'].'_filename'];
		$filetype = $rec[$_REQUEST['field'].'_filetype'];
		$width = isset($_GET['w']) ? $_GET['w'] : NULL;
		$height = isset($_GET['h']) ? $_GET['h'] : NULL;
		$isImg = preg_match('/^image\//',$filetype);

		if ($isImg && ($width || $height)) $filetype = 'image/png';
		
		$etag = sha1($_SERVER['REQUEST_URI'].'-'.$data);
		utopia::Cache_Check($etag,$filetype,$filename);

		if ($isImg && ($width || $height)) {
			$src = imagecreatefromstring($data);
			$img = utopia::constrainImage($src,$width,$height);
			//    Image output
			ob_start();
			imagepng($img);
			imagedestroy($img);
			$data = ob_get_contents();
			ob_end_clean();
		}
		
		utopia::Cache_Output($data,$etag,$filetype,$filename);
	}
	static function GetLink($module,$field,$pk,$filename=NULL) {
		if ($filename === NULL) {
			$obj =& utopia::GetInstance($module);
			$rec = $obj->LookupRecord($pk);
			$filename = $rec[$field.'_filename'];
		}
		$o =& utopia::GetInstance(__CLASS__);
		return $o->GetURL(array(
			'module'=>$module,
			'field'=>$field,
			'pk'=>$pk,
			'filename'=>$filename,
		));
	}
}
