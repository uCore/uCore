<?php
utopia::SetVar('cms_root_id','_NO_ID_');
utopia::SetVar('cms_id','_NO_ID_');

class tabledef_CMS extends uTableDef {
	public function SetupFields() {
		$this->AddField('cms_id',ftVARCHAR,150);
		$this->AddField('parent',ftVARCHAR,150);
		$this->AddField('rewrite',ftVARCHAR,200);
		$this->AddField('position',ftNUMBER);
		$this->AddField('nav_text',ftVARCHAR,66);
		$this->AddField('template',ftVARCHAR,50);
		$this->AddField('hide',ftBOOL);
		$this->AddField('noindex',ftBOOL);
		$this->AddField('nofollow',ftBOOL);
		$this->AddField('title',ftVARCHAR,255);  // google only shows 66 chars in title
		$this->AddField('description',ftVARCHAR,255); // google only shows 150 chars in description

		$this->AddField('content',ftMEDIUMTEXT);
		$this->AddField('content_published',ftMEDIUMTEXT);

		$this->AddField('content_time',ftTIMESTAMP);
		$this->AddField('content_published_time',ftTIMESTAMP);

		$this->AddField('is_published',ftBOOL);

		$this->AddField('updated',ftTIMESTAMP);
		$this->SetFieldProperty('updated','extra','ON UPDATE CURRENT_TIMESTAMP');
		$this->SetFieldProperty('updated','default','current_timestamp');

		$this->SetPrimaryKey('cms_id');
		$this->SetIndexField('position');
		$this->SetIndexField(array('parent','position'));
	}
	public $auto_increment = 'position';
	
	public function UpdateField($fieldName,$newValue,&$pkVal=NULL,$fieldType=NULL) {
		// if pk is null, update the pk to random string
		if ($pkVal === null) parent::UpdateField('cms_id','newpage_'.rand(),$pkVal);
		
		// if field is pk and newvalue is empty, return
		if ($fieldName == 'cms_id' && !$newValue) return;
		
		parent::UpdateField($fieldName,$newValue,$pkVal,$fieldType);
	}
}

class uCMS_List extends uDataModule implements iAdminModule {
	public function GetTitle() { return 'Pages'; }
	public function GetOptions() { return ALLOW_DELETE | ALLOW_FILTER | ALLOW_EDIT; }
	public function GetTabledef() { return 'tabledef_CMS'; }
	public function GetSortOrder() { return -8900; }
	public function SetupFields() {
		$this->CreateTable('cms');
		$this->AddField('cms_id','cms_id','cms','Page ID');
//		$this->AddField('is_homepage','is_homepage','cms','Home',itCHECKBOX);
		$this->AddField('is_home','(({parent} = \'\' OR {parent} IS NULL) AND ({position} IS NULL OR {position} = 0))','cms');
		$this->AddField('parent','parent','cms','Parent');
		$this->AddField('position','position','cms','position');
		$this->AddField('title','title','cms','Page Title');
		$this->AddField('nav_text','nav_text','cms');
		$this->AddField('hide','hide','cms','Parent');
		$this->AddField('content','content','cms');
		$this->AddField('content_time','content_time','cms');
		$this->AddField('content_published','content_published','cms');
		$this->AddField('content_published_time','content_published_time','cms');
		$this->AddField('is_published','is_published','cms');
	}
	public function SetupParents() {
		uCSS::IncludeFile(utopia::GetRelativePath(dirname(__FILE__).'/cms.css'));

		$nTemplates = utopia::GetTemplates(false,true);
		$dTemplate = '/'.basename(PATH_REL_CORE).'/themes/default';
		modOpts::AddOption('default_template','Default Template',NULL,$dTemplate,itCOMBO,$nTemplates);

		$this->AddParent('/');
		utopia::RegisterAjax('reorderCMS',array($this,'reorderCMS'));
		
		uUserRoles::LinkRoles('Page Editor',array('uCMS_List','uCMS_Edit'));
	}
	public function DeleteRecord($pkVal) {
		parent::DeleteRecord($pkVal);
		AjaxEcho('window.location.reload();');
	}
	public function RunModule() {
		$tabGroupName = utopia::Tab_InitGroup();
		ob_start();

		$obj =& utopia::GetInstance('uCMS_Edit');
		$newUrl = $obj->GetURL(array('_n_'.$obj->GetModuleId()=>1));
		$relational = $this->GetNestedArray();
		echo '<table style="width:100%"><tr><td id="tree" style="position:relative;vertical-align:top">';
		echo '<div style="white-space:nowrap"><a class="btn" href="'.$newUrl.'">New Page</a>';

		$modOptsObj =& utopia::GetInstance('modOptsList');
		$row = $modOptsObj->LookupRecord('default_template');
		echo '<br>Default Template: '.$modOptsObj->GetCell('value',$row);

		echo '<hr>';
		echo '<div id="uCMS_List">'.self::GetChildren($relational).'</div>';
		echo '</div></td>';
		echo '<td style="width:100%;vertical-align:top; border-left:1px solid #333"><div style="width:100%" id="previewFrame"><div style="padding:10px">Click on a page to the left to edit it.</div></div></td></tr></table>';

		$editObj =& utopia::GetInstance('uCMS_Edit');
		$editLink = $editObj->GetURL();
		$fid = $editObj->FindFilter('cms_id');

		uJavascript::LinkFile(dirname(__FILE__).'/page_list.js');
		
		$c = ob_get_contents();
		ob_end_clean();
		utopia::Tab_Add('Page Editor',$c,$this->GetModuleId(),$tabGroupName,false);
		utopia::Tab_InitDraw($tabGroupName);
	}
	public static function RefreshList() {
		if (utopia::GetCurrentModule() !== __CLASS__) return;
		$obj =& utopia::GetInstance(__CLASS__);
		$relational = $obj->GetNestedArray();
		$r = $obj->GetChildren($relational);
		// javascript: find open folders (visible ui-treesort-folder with visible ul)
		AjaxEcho('var openfolders = $(\'.ui-treesort-folder:has(ul:visible)\');');
		utopia::AjaxUpdateElement('.uCMS_List',$r);
		AjaxEcho('InitialiseTree();');
		AjaxEcho('$(openfolders).each(function() {$(\'#\'+$(this).attr(\'id\')).children(\'ul\').show();});');
		AjaxEcho('RefreshIcons();');
	}
	static function GetChildren($children) {
		if (!$children) return '';
		array_sort_subkey($children,'position');
		$editObj =& utopia::GetInstance('uCMS_Edit');
		$listObj =& utopia::GetInstance('uCMS_List');
		$viewObj =& utopia::GetInstance('uCMS_View');

		$ret = '<ul class="cmsTree">';
		foreach ($children as $child) {
			$hide = $child['hide'] ? ' hiddenItem' : '';

			$info = (!$child['is_published'] || ($child['content_time'] != $child['content_published_time'])) ? '<span class="ui-icon ui-icon-info" title="Unpublished"></span>' : '';
			$editLink = $viewObj->GetURL(array('cms_id'=>$child['cms_id'],'edit'=>1));
			$delLink = $listObj->CreateSqlField('del',$child['cms_id'],'del');

			$ret .= '<li id="'.$child['cms_id'].'" class="cmsItem'.$hide.'">';
			$ret .= '<div class="cmsItemText">';
			$ret .= '<div class="cmsItemActions">';
			//echo '<a class="btn btn-edit" href="'.$editLink.'" title="Edit \''.$child['cms_id'].'\'"></a>';
			$ret .= $listObj->GetDeleteButton($child['cms_id']);
			$ret .= '</div>';
			
			$text = $child['title'] ? $child['title'] : $child['cms_id'];
			
			$ret .= '<a class="cmsItemLink" href="'.$editLink.'">'.$editObj->PreProcess('title',$text,$child).$info.'</a>';
			$ret .= '</div>';
			$ret .= self::GetChildren($child['children'],$child['cms_id']);
			$ret .= '</li>';
		}
		$ret .= '</ul>';
		return $ret;
	}

	public function GetNestedArray($parent='') {
		$this->BypassSecurity(true);
		$dataset = $this->GetDataset();
		$this->BypassSecurity(false);

		$relational = array();
		while ($row = $dataset->fetch()) {
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
		utopia::cancelTemplate();
		if (!$_POST['data']) return;
		$data = json_decode($_POST['data'],true);
		foreach ($data as $cms_id => $val) {
			list($newParent,$pos) = explode(':',$val);
			$obj =& utopia::GetInstance('uCMS_View');
			$oldURL = $obj->GetURL($cms_id);
			$this->UpdateFields(array('parent'=>$newParent,'position'=>$pos),$cms_id);
			$newURL = $obj->GetURL($cms_id);
		}
	}
}
class uCMS_Edit extends uSingleDataModule implements iAdminModule {
	public function GetTitle() { return 'Edit Content'; }
	public function GetOptions() { return ALLOW_ADD | ALLOW_EDIT | ALLOW_DELETE | ALLOW_FILTER; }
	public function GetTabledef() { return 'tabledef_CMS'; }
	public function SetupFields() {
		$this->CreateTable('cms');
		$this->AddField('cms_id','cms_id','cms','Page ID',itTEXT);
		$this->AddField('parent','parent','cms');
		$this->AddField('title','title','cms','Page Title',itTEXT);
		$this->AddField('nav_text','nav_text','cms','Menu Title',itTEXT);
		$templates = utopia::GetTemplates(true);
		$this->AddField('template','template','cms','Template',itCOMBO,$templates);
		$this->AddField('position','position','cms');
		$this->AddField('hide','hide','cms','Hide from Menus',itYESNO);
		$this->AddField('noindex','noindex','cms','noindex',itCHECKBOX);
		$this->AddField('nofollow','nofollow','cms','nofollow',itCHECKBOX);
		$this->FieldStyles_Set('title',array('width'=>'100%'));
		$this->AddField('description','description','cms','Meta Description',itTEXT);
		$this->FieldStyles_Set('description',array('width'=>'100%'));

		$this->AddField('publishing',array($this,'publishLinks'),'cms');

		$this->AddField('content','content','cms','Page Content',itHTML);
		$this->fields['content']['attr']['contenteditable'] = 'true';
//		$this->AddPreProcessCallback('content',array($this,'processWidget'));
		$this->FieldStyles_Set('content',array('width'=>'100%'));
		$this->AddField('content_published','content_published','cms');

		$this->AddField('content_time','content_time','cms');
		$this->AddField('content_published_time','content_published_time','cms');

		$this->AddField('is_published','is_published','cms');

		$this->AddFilter('cms_id',ctEQ);
	}
	public function publishLinks($field,$pkVal,$v,$rec) {
		if ($rec['is_published'] && $rec['content_time'] == $rec['content_published_time']) {
			return utopia::DrawInput('published',itBUTTON,'Published').$this->DrawSqlInput('unpublish','Unpublish',$pkVal,array('title'=>'Remove this page from public view','class'=>'page-unpublish'),itBUTTON);
		}

		// preview, publish, revert (red)
		$obj =& utopia::GetInstance('uCMS_View');
		$preview = CreateNavButton('Preview',$obj->GetURL(array('cms_id'=>$pkVal,'preview'=>1)),array('target'=>'_blank','title'=>'Preview this page'));
		$revert = $this->DrawSqlInput('revert','Revert',$pkVal,array('title'=>'Reset to published version','class'=>'page-revert'),itBUTTON);
		$publish = $this->DrawSqlInput('publish','Publish',$pkVal,array('title'=>'Make this page live','class'=>'page-publish btn-green'),itBUTTON);

		return $preview.$revert.$publish;
	}
	
	public function processWidget($originalValue,$pkVal,$value,$rec,$fieldName) {
			// replace pragma with uWidgetDiv
			if (preg_match_all('/\{widget.(.+)\}/Ui', $value, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$value = str_replace($match[0],$this->getWidgetPlaceholder($match[1]),$value);
				}
			}
			return $value;
	}
	public function getWidgetPlaceholder() {
		if (func_num_args() > 0) {
			$id = func_get_arg(0);
		} else {
			$id = $_GET['id'];
		}

		$obj =& utopia::GetInstance('uWidgets');
		$url = $obj->GetURL($id);

		$rep = uWidgets::DrawWidget($id);
		$ele = str_get_html('<div style="display:inline">'.$rep.'</div>');

		$delBtn = '<input type="button" value="Remove" onclick="var a = this.parentNode; while (a.className.indexOf(\'uWidgetPlaceholder\')==-1) { a = a.parentNode } a.parentNode.removeChild(a);">';
		$editBtn = uWidgets::IsStaticWidget($id) ? '' : '<input type="button" value="Edit" onclick="window.top.location = \''.$url.'\'">';
		$addition = '<div class="uWidgetHeader">'.$delBtn.$editBtn.$id.'</div>';

		$ele = $ele->root->children[0];
		$ele->class .= ' uWidgetPlaceholder';
		$ele->title = $id;
		$ele->innertext = $addition.$ele->innertext;

		if (func_num_args() > 0) return $ele;
		echo $ele;
	}
	
	public function UpdateField($fieldAlias,$newValue,&$pkVal=NULL) {
		if ($fieldAlias == 'cms_id' && !$newValue) return;
		if ($pkVal == NULL && $fieldAlias == 'title') $this->UpdateField('cms_id',UrlReadable($newValue),$pkVal);
		if ($fieldAlias == 'revert') {
			$rec = $this->LookupRecord($pkVal);
			$this->UpdateField('content',$rec['content_published'],$pkVal);
			$this->UpdateFieldRaw('content_published_time','`content_time`',$pkVal);
			AjaxEcho('window.location.reload();');
			return;
		}
		if ($fieldAlias == 'publish') {
			$rec = $this->LookupRecord($pkVal);
			$this->UpdateField('content_published',$rec['content'],$pkVal);
			return;
		}
		if ($fieldAlias == 'unpublish') {
			$this->UpdateField('is_published',0,$pkVal);
			$this->UpdateField('content_published_time',null,$pkVal);
			return;
		}
		if ($fieldAlias == 'cms_id') {
			$newValue = UrlReadable($newValue);
			// also update children's "parent" to this value
			if ($pkVal !== NULL) {
				$dataset = $this->GetDataset(array('parent'=>$pkVal),true);
				while ($child = $dataset->fetch()) $this->UpdateField('parent',$newValue,$child['cms_id']);
			}
		}
		if (substr($fieldAlias,0,8) == 'content:') {
			$rec = $this->LookupRecord($pkVal);
			$contentarr = utopia::jsonTryDecode($rec['content']);
			if (!is_array($contentarr)) $contentarr = array(''=>$contentarr);
			$id = substr($fieldAlias,8); if (!$id) $id = '';
			$contentarr[$id] = (string)$newValue;
			$fieldAlias = 'content';
			$newValue = $contentarr;

			$this->SetFieldType('content_time',ftRAW);
			$this->UpdateField('content_time','NOW()',$pkVal);
		}
		if ($fieldAlias == 'content_published') {
			$this->SetFieldType('content_published_time',ftRAW);
			$this->UpdateFieldRaw('content_published_time','`content_time`',$pkVal);
			$this->UpdateField('is_published',1,$pkVal);
		}

		$oPk = $pkVal;
		$ret = parent::UpdateField($fieldAlias,$newValue,$pkVal);
		
		if ($pkVal !== $oPk) {
			$o =& utopia::GetInstance('uCMS_View');
			$url = $o->GetURL(array('cms_id'=>$pkVal,'edit'=>1));
			AjaxEcho("window.location.replace('$url');");
		}

		// update cms list to reflect published status
		uCMS_List::RefreshList();

		return $ret;
	}
	public function getPossibleBlocks($val,$pk,$original) {
		$obj =& utopia::GetInstance('uWidgets_List');
		$ds = $obj->GetDataset();
		$rows = $ds->fetchAll();
		foreach (uWidgets::$staticWidgets as $widgetID => $callback) $rows[]['block_id'] = $widgetID;
		return '<span class="btn" onclick="ChooseWidget()">Insert Widget</span>';
	}
	public function SetupParents() {
		utopia::RegisterAjax('getWidgetPlaceholder',array($this,'getWidgetPlaceholder'));
		$this->AddParent('uCMS_List','cms_id');
	}
	public function getEditor($id = '') {
		$canEdit = uEvents::TriggerEvent('CanAccessModule',$this) !== FALSE;
		// get content
		$rec = uCMS_View::findPage();
		if(!$rec) return; // page not found

		$content = $rec['content_published'];
		if ($rec['content_time'] == 0)
			$content = $rec['content'];
		if ($canEdit && (isset($_GET['edit']) || isset($_GET['preview'])))
			$content = $rec['content'];

		$content = utopia::jsonTryDecode($content);
		
		if (!is_array($content)) $content = array( '' => $content);
		if (!isset($content[$id])) { $content[$id] = ''; }
		
		if ($canEdit && isset($_GET['edit'])) {
			$rec['content:'.$id] = $content[$id];
			return $this->GetCell('content:'.$id,$rec);
		}
		
		$content = $content[$id];
		$content = $this->PreProcess('content',$content,$rec);
		utopia::MergeVars($content);
			
		return $content;
	}
	public function ResetField($fieldAlias,$pkVal = NULL) {
		if ($fieldAlias == 'content' && $pkVal) return false; // dont resetfield for content
		return parent::ResetField($fieldAlias,$pkVal);
	}
	static $editCallbackDone = false;
	public function editPageCallback() {
		if (uEvents::TriggerEvent('CanAccessModule',$this) === FALSE) return;
		if (self::$editCallbackDone) return;
		self::$editCallbackDone = true;

		$rec = uCMS_View::findPage();
		if (!$rec) return;
		if (!isset($_GET['edit'])) {
			$obj =& utopia::GetInstance('uCMS_View');
			$editURL = $obj->GetURL(array('cms_id'=>$rec['cms_id'],'edit'=>1));
			uAdminBar::AddItem('<a class="btn" href="'.$editURL.'">Edit Page</a>',FALSE,null,'edit-page-link');
			return;
		}
		
		uJavascript::LinkFile(utopia::GetRelativePath(dirname(__FILE__).'/cms.js'),9999);

		ob_start();
		$this->ClearFilters();
		$this->AddFilter('cms_id',ctEQ,itNONE,$rec['cms_id']);
		$this->fields['content']['visiblename'] = NULL;
		$this->fields['publishing']['visiblename'] = NULL;
		$this->tabGroup = '_ADMIN_EDIT_';
		$this->ShowData();
		$this->tabGroup = NULL;
		$c = ob_get_contents();
		ob_end_clean();
		$pubCell = '<span class="right publish-buttons" style="padding-left:10px">'.$this->GetCell('publishing',$rec).'</span>';

		
		$obj =& utopia::GetInstance('uCMS_View');
		$url = $obj->GetURL(array('cms_id'=>$rec['cms_id']));

		uAdminBar::AddItem('<span class="left">Page Information</span>'.$pubCell,$c);
		uAdminBar::AddItem('<a class="btn" href="'.$url.'">Stop Editing</a>',FALSE,null,'edit-page-link');
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
	public static function DefaultPages() {
		$o = utopia::GetInstance(__CLASS__);
		$o->BypassSecurity(true);
		$pk = NULL;
		$o->UpdateField('cms_id','index',$pk);
		$o->UpdateField('title','Welcome Willkommen Bienvenue Bienvenido 歡迎光臨 ようこそ',$pk);
		$o->UpdateField('nav_text','Home',$pk);
		$o->UpdateField('content','<h1>Welcome Willkommen Bienvenue Bienvenido 歡迎光臨 ようこそ</h1><p>Hello and welcome to your new website.</p><p>We have marked your template as offline for now.  Once you have finished editing your pages you can turn it on in the <a href="'.PATH_REL_CORE.'">uCore Dashboard</a>.</p>',$pk);
		$o->UpdateField('position',0,$pk);
		$o->UpdateField('publish',true,$pk);
		$o->BypassSecurity(false);
	}
}
uEvents::AddCallback('TableCreated','uCMS_Edit::DefaultPages','tabledef_CMS');
utopia::AddTemplateParser('content',array(utopia::GetInstance('uCMS_Edit'),'getEditor'),'.*');
uEvents::AddCallback('BeforeRunModule',array(utopia::GetInstance('uCMS_Edit'),'editPageCallback'),null,-9999);

uEvents::AddCallback('BeforeRunModule',array(utopia::GetInstance('uCMS_View'),'assertContent'),null,9999);
class uCMS_View extends uSingleDataModule {
	public function GetOptions() { return ALLOW_FILTER; }
	public function GetTabledef() { return 'tabledef_CMS'; }
	static function last_updated() {
		$page = self::findPage();
		return $page['updated'];
	}
	private static $asserted = false;
	public function assertContent() {
		if (self::$asserted) return;
		self::$asserted = true;
		$rec = self::findPage();
		if (!$rec) {
			if (utopia::GetCurrentModule() == __CLASS__) utopia::PageNotFound();
			return;
		}
		$canEdit = (uEvents::TriggerEvent('CanAccessModule','uCMS_Edit') !== FALSE);
		if (!$canEdit && !$rec['is_published']) utopia::PageNotFound();
		if (!isset($_GET['preview']) &&!isset($_GET['edit']) && !$rec['is_published']) utopia::PageNotFound();

		echo '<div class="cms-'.$rec['cms_id'].'">{content}</div>';

		utopia::SetVar('cms_id',$rec['cms_id']);
		utopia::SetVar('cms_parent_id',$rec['parent']);
		$path = $this->GetCmsParents($rec['cms_id']);
		utopia::SetVar('cms_parents',$path);
		utopia::SetVar('cms_root_id',reset($path));
		utopia::SetDescription($rec['description']);

		if (!utopia::UsingTemplate() || utopia::UsingTemplate(TEMPLATE_BLANK)) return;
		utopia::UseTemplate(self::GetTemplate($rec['cms_id']));
	}
	public function GetURL($filters = NULL) {
		if (is_array($filters) && array_key_exists('uuid',$filters)) unset($filters['uuid']);
		if (!is_array($filters) && is_string($filters)) $filters = array('cms_id'=>$filters);
		$this->RewriteFilters($filters);

		if (!isset($filters['cms_id'])) {
			return $_SERVER['REQUEST_URI'];
		}
		
		$cms_id = $filters['cms_id'];
		$qs = '';
		if (is_array($filters)) {
			if (array_key_exists('cms_id',$filters)) unset($filters['cms_id']);
			if (array_key_exists('uuid',$filters)) unset($filters['uuid']);
			$qs = http_build_query($filters); if ($qs) $qs = "?$qs";
		}
		if ($this->IsHome($cms_id)) return PATH_REL_ROOT.$qs;
		$path = $this->GetCmsParents($cms_id);
		
		return PATH_REL_ROOT.implode('/',$path).$qs;
	}
	public function GetTitle() {
		$rec = self::findPage();
		if (!$rec) $rec = self::GetHomepage();
		if (!$rec) $rec = $this->LookupRecord();
		if (isset($_GET['preview']) && uEvents::TriggerEvent('CanAccessModule','uCMS_Edit') !== FALSE)
			return $rec['title'].' (Preview)';
		return $rec['title'];
	}
	public function SetupFields() {
		$this->CreateTable('cms');
		$this->AddField('cms_id','cms_id','cms','cms_id');
		$this->AddField('title','title','cms','title');
		$this->AddField('updated','updated','cms');
		$this->AddField('parent','parent','cms','Parent');
		$this->AddField('position','position','cms','position');
		$this->AddField('hide','hide','cms');
		$this->AddField('template','template','cms','template');
		$this->AddField('nav_text','nav_text','cms');
		$this->AddField('description','description','cms','description');
		$this->AddField('content','content','cms','content');
		$this->AddField('content_time','content_time','cms');
		$this->AddField('content_published','content_published','cms','content');
		$this->AddField('content_published_time','content_published_time','cms');
		$this->AddField('is_published','is_published','cms','published');
		$this->AddField('is_home','(({parent} = \'\' OR {parent} IS NULL) AND ({position} IS NULL OR {position} = 0))','cms');
		$this->AddField('noindex','noindex','cms','noindex');
		$this->AddField('nofollow','nofollow','cms','nofollow');
		//$this->AddFilter('cms_id',ctEQ);
	}

	public function SetupParents() {
		uEvents::AddCallback('AfterInit',array($this,'InitSitemap'));
		uEvents::AddCallback('ProcessDomDocument','uCMS_View::ProcessDomDocument');
		uWidgets::AddStaticWidget('page_updated','uCMS_View::last_updated');
		uSearch::AddSearchRecipient(__CLASS__,array('title','content_published'),'title','content_published');
		$this->SetRewrite(true);
	}
	static function ProcessDomDocument($obj,$event,$templateDoc) {
		$page = self::findPage();
		
		// set body class to cms_id
		if ($page) {		
			$body = $templateDoc->getElementsByTagName('body')->item(0);
			$cClass = $body->getAttribute('class');
			if ($cClass) $cClass .= ' '.$page['cms_id'];
			else $cClass = $page['cms_id'];
			$body->setAttribute('class',$cClass);
		}
		
		// include robots meta
		if ($page) {
			$robots = array();
			if ($page['nofollow']) $robots[] = 'nofollow';
			if ($page['noindex']) $robots[] = 'noindex';
			if (!empty($robots)) {
				$head = $templateDoc->getElementsByTagName('head')->item(0);
				$node = $templateDoc->createElement('meta');
				$node->setAttribute('name','robots'); $node->setAttribute('content',implode(', ',$robots));
				$head->appendChild($node);
			}
		}
	}
	function InitSitemap() {
		$ds = $this->GetDataset(NULL,true);
		while (($row = $ds->fetch())) {
			if ($row['noindex']) continue;

			// is published
			if ($row['content_time'] == '0000-00-00 00:00:00' && !$row['is_published']) continue;
			if (!$row['is_published']) continue;

			$title = $row['nav_text'] ? $row['nav_text'] : $row['title'];
			$url = $this->GetURL($row['cms_id']);

			// add to menu
			if (!$row['hide']) {
				uMenu::AddItem($row['cms_id'],$title,$url,$row['parent'],array('class'=>strtolower($row['cms_id'])),$row['position']);
			}

			// add to sitemap
			$additional = array();
			if ($row['is_home']) $additional['priority'] = 1;
			uSitemap::AddItem('http://'.utopia::GetDomainName().$url,$additional);
		}
	}

	static function GetHomepage() {
		$obj =& utopia::GetInstance('uCMS_View');
		$row = $obj->LookupRecord(array('is_home'=>'1'),true);
		if (!$row) $row = $obj->LookupRecord();
		if ($row) return $row;
		return FALSE;
	}
	
	private static $currentPage = null;
	static function findPage() {
		if (self::$currentPage) return self::$currentPage;
		$uri = $_SERVER['REQUEST_URI'];
		$uri = preg_replace('/(\?.*)?/','',$uri);

		if ($uri === PATH_REL_ROOT) return self::GetHomepage();
		if (strpos($uri,PATH_REL_CORE.'index.php') === 0) return FALSE;
		
		$obj =& utopia::GetInstance('uCMS_View');
		
		$cm = utopia::GetCurrentModule();
		if ($cm && $cm !== __CLASS__) {
			$o =& utopia::GetInstance(utopia::GetCurrentModule());
			$uuid = $o->GetUUID();
			$uuid = explode('/',$uuid);
			$uuid = end($uuid);
			$row = $obj->LookupRecord($uuid);
			if ($row) {
				self::$currentPage = $row;
				return $row;
			}
			return false;
		}
		
		preg_match('/([^\/]+)(\/)?(\.php)?$/Ui',$uri,$matches);
		if (array_key_exists(1,$matches)) {
			$row = $obj->LookupRecord($matches[1]);
			if ($row) {
				self::$currentPage = $row;
				return $row;
			}
		}

		return false;
	}

	public function RunModule() {
		// nothing is required here as the content is output by 'assertContent'
	}
	
	private static $parentsCache = array();
	public function GetCmsParents($cms_id,$includeSelf=true) {
		if (!self::$parentsCache) {
			$ds = $this->GetDataset();
			while (($row = $ds->fetch())) {
				$parent = $row['parent'];
				if ($row['is_home']) $parent = false;
				self::$parentsCache[$row['cms_id']] = $parent;
			}
		}
		
		$parents = array();
		if ($includeSelf) $parents[] = $cms_id;
		while ($cms_id && isset(self::$parentsCache[$cms_id]) && self::$parentsCache[$cms_id]) {
			$parents[] = self::$parentsCache[$cms_id];
			$cms_id = self::$parentsCache[$cms_id];
		}
		$parents = array_reverse($parents);
		return $parents;
	}
	public function IsHome($cms_id) {
		if (!self::$parentsCache) {
			$ds = $this->GetDataset();
			while (($row = $ds->fetch())) {
				$parent = $row['parent'];
				if ($row['is_home']) $parent = false;
				self::$parentsCache[$row['cms_id']] = $parent;
			}
		}
		if (!isset(self::$parentsCache[$cms_id])) return false;
		return self::$parentsCache[$cms_id] === false;
	}

	static function GetTemplate($id) {
		$template = NULL;
		while ($id != NULL) {
			$obj =& utopia::GetInstance('uCMS_View');
			$rec = $obj->LookupRecord($id);
			if ($rec['template']) { $template = $rec['template']; break; }
			$id = $rec['parent'];
		}
		if (!$template) return TEMPLATE_DEFAULT;

		return $template;
	}
}
