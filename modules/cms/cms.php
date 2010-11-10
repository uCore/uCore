<?php

class tabledef_CMS extends flexDb_TableDefinition {
	public $tablename = 'cms';
	public function SetupFields() {
		//$this->AddField('id',ftNUMBER);
		$this->AddField('cms_id',ftVARCHAR,150);
		$this->AddField('parent',ftVARCHAR,150);
		$this->AddField('rewrite',ftVARCHAR,200);
		$this->AddField('position',ftNUMBER);
		$this->AddField('nav_text',ftVARCHAR,66);
		$this->AddField('hide',ftBOOL);
		$this->AddField('noindex',ftBOOL);
		$this->AddField('nofollow',ftBOOL);
		$this->AddField('title',ftVARCHAR,66);  // google only shows 66 chars in title
		$this->AddField('description',ftVARCHAR,150); // google only shows 150 chars in description
		$this->AddField('content',ftTEXT);

		$this->SetPrimaryKey('cms_id');
		$this->SetFieldProperty('position','default',999);
		//$this->SetUniqueField('cms_id');
	}
}

class uCMS_List extends flexDb_DataModule {
	public function GetTitle() { return 'Page Editor'; }
	public function GetOptions() { return IS_ADMIN | ALLOW_DELETE | ALLOW_FILTER | ALLOW_EDIT; }
	public function GetTabledef() { return 'tabledef_CMS'; }
	public function SetupFields() {
		$this->CreateTable('cms');
		$this->AddField('cms_id','cms_id','cms','Page ID');
//		$this->AddField('is_homepage','is_homepage','cms','Home',itCHECKBOX);
		$this->AddField('parent','parent','cms','Parent');
		$this->AddField('position','position','cms','position');
		$this->AddField('title','title','cms','Page Title');
		$this->AddField('hide','hide','cms','Parent');
	}
	public function SetupParents() {
		$this->AddParent('internalmodule_Admin');
		$this->RegisterAjax('reorderCMS',array($this,'reorderCMS'));
	}
  public function ProcessUpdates_del($sendingField,$fieldAlias,$value,&$pkVal = NULL) {
    parent::ProcessUpdates_del($sendingField,$fieldAlias,$value,$pkVal);
    AjaxEcho('window.location.reload();');
  }
/*
	public function UpdateField($fieldAlias,$newValue,&$pkVal=NULL) {
		if ($fieldAlias == 'is_homepage') {
			$ds = $this->GetDataset();
			$rows = GetRows($ds);
			foreach ($rows as $row) {
				if ($pkVal === $row['id']) continue;
				parent::UpdateField($fieldAlias,'0',$row['id']);
			}
		}

		parent::UpdateField($fieldAlias,$newValue,$pkVal);
	}
*/
	public function ParentLoad($parent) {
	}
	public function RunModule() {
	  $m = FlexDB::ModuleExists('uCMS_Edit');
		$newUrl = CallModuleFunc('uCMS_Edit','GetURL',array($m['module_id'].'_new'=>1));
		$relational = $this->GetNestedArray();
		echo '<table style="width:100%"><tr><td id="tree" style="position:relative;vertical-align:top">';
		echo '<div style="font-size:0.8em;white-space:nowrap"><a class="btn" style="font-size:0.8em" href="'.$newUrl.'">New Page</a><a class="btn" style="font-size:0.8em" href="javascript:t()">Toggle Hidden</a>';

	//	if (array_key_exists('default_template',$_POST)) cubeCore::settingSet('default_template',$_POST['default_template']);
		//$dTemplate = cubeCore::settingGet('default_template');
		echo '<form id="dtForm" method="post">Default Template: ';
		//cubeDB::DrawField('default_template',array('type'=>'combo','values'=>cubeCore::GetTemplates(),'attr'=>array('onchange'=>'$(\'#dtForm\').submit()')),$dTemplate);
		//    echo 'Default Template: <input type="text" name="default_template" onchange="this.submit()" value="'.$dTemplate.'">';
		echo '</form>';

		echo '<hr><div style="font-size:0.8em">Click a page below to preview it.</div>';
		self::DrawChildren($relational);
		echo '</div></td>';
		echo '<td style="width:100%;height:100%;vertical-align:top"><iframe style="width:100%;height:100%;min-height:600px" id="previewFrame"></iframe></td></tr></table>';

		treeSort::Init();
		FlexDB::AddCSSFile(FlexDB::GetRelativePath(dirname(__FILE__).'/cms.css'));
		echo <<<FIN
		<script>
		var hidden=true;
		function t() {
			if (hidden) $('.hiddenItem').show();
			else $('.hiddenItem').hide();
			hidden = !hidden;
		}
		function RefreshIcons() {
//			$('.ui-treesort-item > .ui-icon').attr('class','');
			$('.ui-treesort-folder').each(function () {
				var icon = $('.ui-icon',this);
				if (!icon.length) icon = $('<span class="ui-icon" style="position:absolute;left:-16px;top:0;bottom:0;width:16px"></span>');
				if ($('ul:visible',this).length)
					icon.removeClass('ui-icon-triangle-1-e').addClass('ui-icon-triangle-1-s');
				else
					icon.removeClass('ui-icon-triangle-1-s').addClass('ui-icon-triangle-1-e');
				$(this).prepend(icon);
			});
		}
		function dropped() {
			RefreshIcons();
			data = serialiseTree();
			$.post('?__ajax=reorderCMS',{data:data});
		}
		function serialiseTree() {
			var data = {};
			$('#tree li').each(function () {
				parent = $(this).parents('.ui-treesort-item:first').attr('id');
				if (!parent) parent = '';
				data[$(this).attr('id')] = parent+':'+$(this).parents('ul:first').children('li').index(this);
			});
			return data;
		}
		$('#tree ul').not($('#tree ul:first')).hide();
		$('#tree').treeSort({init:RefreshIcons,change:dropped});
		$('.ui-icon-triangle-1-e, .ui-icon-triangle-1-s').live('click',function (e) {
			$(this).parent('li').children('ul').toggle();
			RefreshIcons();
			e.stopPropagation();
		});
		</script>
FIN;
	}
	static function DrawChildren($children) {
		if (!$children) return;
		array_sort_subkey($children,'position');
		echo '<ul>';
		foreach ($children as $child) {
			$hide = $child['hide'] ? 'hiddenItem' : '';
      $editLink = CallModuleFunc('uCMS_Edit','GetURL',array('cms_id'=>$child['cms_id'])); //'?_action=edit&id='.$child['id'];
      $delLink = CallModuleFunc('uCMS_List','CreateSqlField','del',$child['cms_id'],'del');// CallModuleFunc('uCMS_Edit','GetURL',array('cms_id'=>$child['cms_id'])); //'?_action=edit&id='.$child['id'];
			$data = '';//($child['dataModule']) ? ' <img title="Database Link ('.$child['dataModule'].')" style="vertical-align:bottom;" src="/CubeCore/styles/images/data16.png">' : '';
			echo '<li id="'.$child['cms_id'].'" class="'.$hide.'" style="position:relative;cursor:pointer">';
			echo '<div onclick="$(\'#previewFrame\').attr(\'src\',\''.CallModuleFunc('uCMS_View','GetURL',array('cms_id'=>$child['cms_id'])).'\')">'.$child['title'].$data;
			//			echo '<a href="?_action=edit" style="position:absolute;top:1px;right:3em;margin:0;width:16px;height:16px;padding:2px;background-repeat:no-repeat;background-image:url(\'/CubeCore/styles/images/add.png\')" class="btn"></a>';
      echo '<div style="float:right;padding-left:10px">';
      echo CallModuleFunc('uCMS_List','GetDeleteButton',$child['cms_id']);
//      echo '<a class="btn btn-del" name="'.$delLink.'" href="#" onclick="if (confirm(\'Are you sure you wish to delete this record?\')) uf(this); return false;" title="Delete \''.$child['cms_id'].'\'"></a>';
      echo '<a class="btn btn-edit" href="'.$editLink.'" title="Edit \''.$child['cms_id'].'\'"></a>';
      echo '</div>';
			echo '</div>';
			self::DrawChildren($child['children'],$child['cms_id']);
			echo '</li>';
		}
		echo '</ul>';
	}

	public function GetNestedArray($parent='') {
		$rows = $this->GetRows();

		$relational = array();
		foreach ($rows as $row) {
			$row['children'] = array();
			$relational[$row['cms_id']] = $row;
		}
		array_sort_subkey($relational,'position');
		$unset = array();
		foreach ($relational as $k=>$i) {
			if ($i['parent'] && array_key_exists($i['parent'],$relational)) {
				$unset[] = $k;
				$relational[$i['parent']]['children'][$k] =& $relational[$k];
			}
		}
		$relational = $relational;
		foreach ($unset as $u) {
			unset($relational[$u]);
		}

		return self::findkey($relational,$parent);
	}
	static function findKey($array,$key = '') {
		if (!$key) return $array;
		$key = strtolower($key);
		$array = array_change_key_case($array,CASE_LOWER);

		if (array_key_exists($key, $array)) return $array[$key];

		foreach ($array as $v) {
			$found = self::findkey($v['children'],$key);
			if ($found) return $found;
		}
		return false;
	}

	public function reorderCMS() {
		FlexDB::cancelTemplate();
		if (!$_POST['data']) return;
		foreach ($_POST['data'] as $cms_id => $val) {
			list($newParent,$pos) = explode(':',$val);
			$oldURL = CallModuleFunc('uCMS_View','GetURL',$cms_id);
			$this->UpdateFields(array('parent'=>$newParent,'position'=>$pos),$cms_id);
			$newURL = CallModuleFunc('uCMS_View','GetURL',$cms_id);

			if (!$oldURL || $oldURL == '/') continue;

			$qry = 'UPDATE '.CallModuleFunc('uCMS_View','GetPrimaryTable').' SET `content` = REPLACE(`content`,\''.$oldURL.'\',\''.$newURL.'\')';
			sql_query($qry);
			//print_r($rows);
//			$rows = cubeDB::lookupSimple(cubeDB::GetTable('cubeCMS'),'*','content LIKE \'%'.cubeDB::escape($oldURL).'%\'');
//			foreach ($rows as $row) {
//				$newVal = str_replace($oldURL,$newURL,$row['content']);
//				cubeDB::updateRecord(cubeDB::GetTable('cubeCMS'),array('content'=>$newVal),array(cubeCMS::GetPrimaryKey()=>$row[cubeCMS::GetPrimaryKey()]));
//			}
		}
	}
}
class uCMS_Edit extends flexDb_SingleDataModule {
	public function GetTitle() { return 'Edit Content'; }
	public function GetOptions() { return IS_ADMIN | ALLOW_ADD | ALLOW_EDIT | ALLOW_DELETE | ALLOW_FILTER; }
	public function GetTabledef() { return 'tabledef_CMS'; }
	public function SetupFields() {
		$this->CreateTable('cms');
		$this->AddField('cms_id','cms_id','cms','Page ID',itTEXT);
		$this->AddField('title','title','cms','Page Title',itTEXT);
//		$this->AddField('position','position','cms','Navigation Position',itTEXT);
//		$this->AddField('nav_text','nav_text','cms','Navigation Text',itTEXT);
		$this->AddField('hide','hide','cms','Hide from Menus',itCHECKBOX);
		$this->AddField('noindex','noindex','cms','noindex',itCHECKBOX);
		$this->AddField('nofollow','nofollow','cms','nofollow',itCHECKBOX);
		$this->FieldStyles_Set('title',array('width'=>'100%'));
		$this->AddField('description','description','cms','Meta Description',itTEXT);
		$this->FieldStyles_Set('description',array('width'=>'100%'));
		$this->AddField('content','content','cms','Page Content',itHTML);
		$this->FieldStyles_Set('content',array('width'=>'100%','height'=>'20em'));
	//	$this->AddPreProcessCallback('content',array($this,'getWithTemplate'));
		$this->AddFilter('cms_id',ctEQ);
	}
	public function getWithTemplate($val,$pk,$original) {
		return file_get_contents('http://'.FlexDB::GetDomainName().CallModuleFunc('uCMS_View','GetURL',$pk),FALSE);
	}
	public function SetupParents() {
		//$this->AddParent('uCMS_List');
		//$this->AddParent('uCMS_List','cms_id','*');
		//breadcrumb::AddModule('uCMS_List');
	}
	public function ParentLoad($parent) {
	}
	public function RunModule() {
		$this->ShowData();
	}
	public static function StartNoProcess() {
		echo '<!-- NoProcess -->';
	}
	public static function StopNoProcess() {
		echo '<!-- /NoProcess -->';
	}
}

FlexDB::AddTemplateParser('cms','uCMS_View::templateParser');
class uCMS_View extends flexDb_SingleDataModule {
	public function GetOptions() { return ALLOW_FILTER; }
	public function GetTabledef() { return 'tabledef_CMS'; }
	public function GetUUID() { return 'cms'; }

	static function templateParser($id) {
		$rec = CallModuleFunc('uCMS_View','GetRows',$id);
		$rec = $rec[0];
		return '<div class="mceEditable">'.$rec['content'].'</div>';
	}
	public function GetURL($filters = NULL, $encodeAmp = false) {
	  if (is_array($filters) && array_key_exists('uuid',$filters)) unset($filters['uuid']);
		$rec = NULL;
		if ($filters && (is_string($filters) || is_array($filters))) {
			$rec = $this->LookupRecord($filters);
    }
//		DebugMail('gu',array($_SERVER['REQUEST_URI'],$rec));
		if (!$rec)
			$rec = self::findPage();
//		DebugMail('gu',array($_SERVER['REQUEST_URI'],$rec));
		if (!$rec) return $_SERVER['REQUEST_URI'];
//		DebugMail('gu',array($_SERVER['REQUEST_URI'],$rec));
//			print_r($filters);
	//		die('not found');
	//	}
		//if (!$rec) $rec = $this->GetRecord($this->GetDataset(),0);

		// build QS
		$qs = '';
		if (is_array($filters)) {
			if (array_key_exists('cms_id',$filters)) unset($filters['cms_id']);
			if (array_key_exists('uuid',$filters)) unset($filters['uuid']);
			$qs = http_build_query($filters); if ($qs) $qs = "?$qs";
		}
		$cms_id = $rec['cms_id'];
    $ishome = $rec['is_home'];
		$path = array();
		while ($rec['parent']) {
			$path[] = $rec['parent'];
			$rec = $this->LookupRecord($rec['parent']);
			if (!$rec) break;
		}
		$path = array_reverse($path);
    //print_r($rec);
		if (!$ishome) $path[] = $cms_id.'.php';
//    header('test: '.json_encode($filters).'/'.implode('/',$path).$qs);
		return '/'.implode('/',$path).$qs;
	}
	public function GetTitle() {
		$rec = NULL;
		$fltr = $this->FindFilter('cms_id');
		if (!$fltr['value'] || $fltr['value'] == '{cms_id}') {
			$rec = self::GetHomepage();
		}
		if (!$rec) $rec = $this->GetRecord($this->GetDataset(),0);
		return $rec['title'];
		//return $rec['nav_text'] ? $rec['nav_text'] : $rec['title'];
	}
	public function SetupFields() {
		$this->CreateTable('cms');
		$this->AddField('cms_id','cms_id','cms','cms_id');
		$this->AddField('title','title','cms','title');
//		$this->AddField('is_homepage','is_homepage','cms');
		$this->AddField('parent','parent','cms','Parent');
		$this->AddField('position','position','cms','position');
//		$this->AddField('nav_position','nav_position','cms');
//		$this->AddField('nav_text','nav_text','cms');
		$this->AddField('description','description','cms','description');
		$this->AddField('content','content','cms','content');
    $this->AddField('is_home','(({parent} = \'\' OR {parent} IS NULL) AND ({position} IS NULL OR {position} = 0))','cms');
		$this->AddField('noindex','noindex','cms','noindex');
		$this->AddField('nofollow','nofollow','cms','nofollow');
		$this->AddFilter('cms_id',ctEQ);
//		$this->AddFilter('is_homepage',ctLIKE);
	}

	public function SetupParents() {
		$this->AddParent('/');
		//$this->SetRewrite('{cms_id}');
	//	$this->RegisterAjax('showCMS',array($this,'showCMS'));
	}
//	public function showCMS() {
//		FlexDB::UseTemplate();
//		$page = self::findPage();
//		echo '{cms.'.$page['cms_id'].'}';
//		FlexDB::Finish();
//	}

	static function GetHomepage() {
//	  header('gh: yes');
//		$rows = CallModuleFunc('uCMS_List','GetNestedArray');
//		$row = reset($rows);
      $row = CallModuleFunc('uCMS_View','LookupRecord',array('is_home'=>'1'));
		if ($row) return $row;
		return FALSE;
	}

	static function findPage() {
		$uri = $_SERVER['REQUEST_URI'];

		// if file is directly requested then don't use findPage. Only for CMS pages.
		$test = realpath($_SERVER['DOCUMENT_ROOT']).$uri;
		if (file_exists($test) && is_file($test)) return FALSE;

		if ($uri == '/') {
			return self::GetHomepage();
		}

		$uri = preg_replace('/(\?.*)?/','',$uri);
//    preg_match('/([^\/]+)\.php$/Ui',$uri,$matches);
    preg_match('/([^\/]+)(\.php)?$/Ui',$uri,$matches);

		if (array_key_exists(1,$matches)) {
//		  header('fp: yes');
			$row = CallModuleFunc('uCMS_View','LookupRecord',$matches[1]);
			if ($row) return $row;
		}

		return false;
	}

//	public function ParentLoadPoint() { return 0; }
	public function ParentLoad($parent) {
/*		$rows = $this->GetRows(array('cms_id'=>NULL));
		foreach ($rows as $row) {
			if ($row['nav_position'] !== '') {
				FlexDB::LinkList_Add('navbar', $row['nav_text'] ? $row['nav_text'] : $row['title'], $this->GetURL(array('cms_id'=>$row['cms_id'])),$row['nav_position']);
			}
		}*/
	}
	public function RunModule() {
		// custom home breadcrumb
		//breadcrumb::ShowHome(false);
		$rec = self::findPage();
		if (empty($rec)) {
			FlexDB::PageNotFound();
//			header("HTTP/1.0 404 Not Found");
//			echo 'Error 404: File not found'; return;
		}
		//breadcrumb::AddURL($rec['nav_text'] ? $rec['nav_text'] : $rec['title'],$this->GetURL(array('cms_id'=>$rec['cms_id'])),-1000);
		FlexDB::SetTitle($rec['title']);
		FlexDB::SetDescription($rec['description']);
		$robots = array();
		if ($rec['nofollow']) $robots[] = 'NOFOLLOW';
		if ($rec['noindex']) $robots[] = 'NOINDEX';
		if (!empty($robots)) FlexDB::AppendVar('<head>','<META NAME="ROBOTS" CONTENT="'.implode(', ',$robots).'">');
		echo '{cms.'.$rec['cms_id'].'}';
	}
}
?>