<?php

class tabledef_CMS extends uTableDef {
  public $tablename = 'cms';
  public function SetupFields() {
    //$this->AddField('id',ftNUMBER);
    $this->AddField('cms_id',ftVARCHAR,150);
    $this->AddField('parent',ftVARCHAR,150);
    $this->AddField('rewrite',ftVARCHAR,200);
    $this->AddField('position',ftNUMBER);
    $this->AddField('nav_text',ftVARCHAR,66);
    $this->AddField('template',ftVARCHAR,50);
    $this->AddField('hide',ftBOOL);
    $this->AddField('noindex',ftBOOL);
    $this->AddField('nofollow',ftBOOL);
    $this->AddField('title',ftVARCHAR,66);  // google only shows 66 chars in title
    $this->AddField('description',ftVARCHAR,150); // google only shows 150 chars in description
    $this->AddField('content',ftTEXT);
    $this->AddField('content_time',ftTIMESTAMP);
    $this->AddField('content_published',ftTEXT);
    $this->AddField('content_published_time',ftTIMESTAMP);

    $this->AddField('updated',ftTIMESTAMP);
    $this->SetFieldProperty('updated','extra','ON UPDATE CURRENT_TIMESTAMP');
    $this->SetFieldProperty('updated','default','current_timestamp');

    $this->SetPrimaryKey('cms_id');
    $this->SetFieldProperty('position','default',999);
  }
}

class uCMS_List extends uDataModule implements iAdminModule {
	public function GetTitle() { return 'Page Editor'; }
	public function GetOptions() { return ALLOW_DELETE | ALLOW_FILTER | ALLOW_EDIT; }
	public function GetTabledef() { return 'tabledef_CMS'; }
	public function GetSortOrder() { return GetCurrentModule() == 'uCMS_Edit' ? -500 : parent::GetSortOrder(); }
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
		$this->AddField('published','(IF(STRCMP({content},{content_published}),1,0))','cms');
	}
	public function SetupParents() {
		$templates = glob(PATH_ABS_TEMPLATES.'*'); // find all templates
		$nTemplates = array('Default Template'=>TEMPLATE_DEFAULT,'No Template'=>TEMPLATE_BLANK);
		if (is_array($templates)) foreach ($templates as $k => $v) {
		        if ($v == '.' || $v == '..' || !is_dir($v)) {
		                unset($templates[$k]);
		                continue;
		        }
		        $nTemplates[basename($v)] = basename($v);
		        //unset($templates[$k]);
		        //$templates[$k] = basename($v);
		}
		//$templates = is_array($templates) ? array_values($templates) : array();
		utopia::SetVar('TEMPLATE_LIST',$nTemplates);
		unset($nTemplates['Default Template']);
		$nTemplates['No Template'] = '';
		modOpts::AddOption('CMS','default_template','Default Template','',itCOMBO,$nTemplates);


		$this->AddParent('internalmodule_Admin');
		$this->AddParent('uCMS_Edit');
		$this->RegisterAjax('reorderCMS',array($this,'reorderCMS'));
	}
	public function ProcessUpdates_del($sendingField,$fieldAlias,$value,&$pkVal = NULL) {
		parent::ProcessUpdates_del($sendingField,$fieldAlias,$value,$pkVal);
		AjaxEcho('window.location.reload();');
	}
	public function RunModule() {
		$tabGroupName = utopia::Tab_InitGroup();
		ob_start();

		$m = utopia::ModuleExists('uCMS_Edit');
		$obj = utopia::GetInstance('uCMS_Edit');
		$newUrl = $obj->GetURL(array($m['module_id'].'_new'=>1));
		$relational = $this->GetNestedArray();
		echo '<table style="width:100%"><tr><td id="tree" style="position:relative;vertical-align:top">';
		echo '<div style="white-space:nowrap"><a class="btn" style="font-size:0.8em" href="'.$newUrl.'">New Page</a><a class="btn" style="font-size:0.8em" href="javascript:t()">Toggle Hidden</a>';

		$modOptsObj = utopia::GetInstance('modOpts');
		$modOptsObj->_SetupFields();
		$row = $modOptsObj->LookupRecord('CMS::default_template');//$this->GetCell($fieldName,$row,$targetUrl)
		echo '<br>Default Template: '.$modOptsObj->GetCell('value',$row,NULL);

		echo '<hr>';
		self::DrawChildren($relational);
		echo '</div></td>';
		echo '<td style="width:100%;vertical-align:top; border-left:1px solid #333"><div style="width:100%" id="previewFrame"><div style="padding:10px">Click on a page to the left to edit it.</div></div></td></tr></table>';

		utopia::AddCSSFile(utopia::GetRelativePath(dirname(__FILE__).'/cms.css'));
		$editObj = utopia::GetInstance('uCMS_Edit');
		$editLink = $editObj->GetURL();
		$fid = $editObj->FindFilter('cms_id');
		echo <<<FIN
		<script type="text/javascript">
		var hidden=true;
		function t() {
			if (hidden) $('.hiddenItem').not('#ui-treesort-placeholder').show();
			else $('.hiddenItem').hide();
			hidden = !hidden;
		}
		function RefreshIcons() {
			$('.ui-treesort-item:not(.ui-treesort-folder) > .cmsParentToggle').remove();
			$('.ui-treesort-folder').each(function () {
				var icon = $('.cmsParentToggle',this);
				if (!icon.length) icon = $('<span class="cmsParentToggle ui-widget ui-icon" style="width:16px; float:left"></span>').prependTo(this);
				if ($('ul:visible',this).length)
					icon.removeClass('ui-icon-triangle-1-e').addClass('ui-icon-triangle-1-s');
				else
					icon.removeClass('ui-icon-triangle-1-s').addClass('ui-icon-triangle-1-e');
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
				var parent = $(this).parents('.ui-treesort-item:first').attr('id');
				if (!parent) parent = '';
				data[$(this).attr('id')] = parent+':'+$(this).parents('ul:first').children('li').index(this);
			});
			return data;
		}
		$('#tree ul').not($('#tree ul:first')).hide();
		$('#tree').treeSort({init:RefreshIcons,change:dropped});
		$('.cmsParentToggle').live('click',function (e) {
			$(this).parent('li').children('ul').toggle();
			RefreshIcons();
			e.stopPropagation();
		});
		$('.cmsItemText').click(function (e) {
			$('#previewFrame').load('$editLink&inline=1&_f_{$fid['uid']}='+$(this).closest('.cmsItem').attr('id'), function() {
				InitJavascript.run();
			});
			e.stopPropagation();
		});
		</script>
FIN;
		$c = ob_get_contents();
		ob_end_clean();
		utopia::Tab_Add('Page Editor',$c,$tabGroupName,false);
		utopia::Tab_InitDraw($tabGroupName);
	}
	static function DrawChildren($children) {
		if (!$children) return;
		array_sort_subkey($children,'position');
		$editObj = utopia::GetInstance('uCMS_Edit');
		$listObj = utopia::GetInstance('uCMS_List');
		$viewObj = utopia::GetInstance('uCMS_View');

		echo '<ul class="cmsTree">';
		foreach ($children as $child) {
			$hide = $child['hide'] ? ' hiddenItem' : '';

			$info = (!$child['published']) ? '<span class="ui-icon ui-icon-info" title="Unpublished"></span>' : '';
			$editLink = $editObj->GetURL(array('cms_id'=>$child['cms_id']));
			$delLink = $listObj->CreateSqlField('del',$child['cms_id'],'del');
			$data = '';//($child['dataModule']) ? ' <img title="Database Link ('.$child['dataModule'].')" style="vertical-align:bottom;" src="styles/images/data16.png">' : '';

			echo '<li id="'.$child['cms_id'].'" class="cmsItem'.$hide.'">';
			echo '<div class="cmsItemText">';
			echo '<div class="cmsItemActions">';
			//echo '<a class="btn btn-edit" href="'.$editLink.'" title="Edit \''.$child['cms_id'].'\'"></a>';
			echo $listObj->GetDeleteButton($child['cms_id']);
			echo '</div>';
			echo $child['title'].$info.$data;
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
		utopia::cancelTemplate();
		if (!$_POST['data']) return;
		foreach ($_POST['data'] as $cms_id => $val) {
			list($newParent,$pos) = explode(':',$val);
			$obj = utopia::GetInstance('uCMS_View');
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
		$this->AddField('link','<a target="_blank" href="'.PATH_REL_ROOT.'{cms_id}.php">'.PATH_REL_ROOT.'{cms_id}.php</a>','cms','View Page');
		$this->AddField('title','title','cms','Page Title',itTEXT);
		$this->AddField('nav_text','nav_text','cms','Menu Title',itTEXT);
		$templates = utopia::GetVar('TEMPLATE_LIST');
		$templates['Default Template'] = '';
		$this->AddField('template','template','cms','Template',itCOMBO,$templates);
//		$this->AddField('position','position','cms','Navigation Position',itTEXT);
		$this->AddField('hide','hide','cms','Hide from Menus',itCHECKBOX);
		$this->AddField('noindex','noindex','cms','noindex',itCHECKBOX);
		$this->AddField('nofollow','nofollow','cms','nofollow',itCHECKBOX);
		$this->FieldStyles_Set('title',array('width'=>'100%'));
		$this->AddField('description','description','cms','Meta Description',itTEXT);
		$this->FieldStyles_Set('description',array('width'=>'100%'));
		$this->AddField('blocks',array($this,'getPossibleBlocks'),'cms','Add Widget');

		$this->AddField('content','content','cms','Page Content',itHTML);
		$this->AddPreProcessCallback('content',array($this,'processWidget'));
		$this->FieldStyles_Set('content',array('width'=>'100%','height'=>'20em'));
		$this->AddField('content_published','content_published','cms');

		$this->AddField('content_time','content_time','cms','Last Saved');
		$this->AddField('content_published_time','content_published_time','cms','Last Published');
		$this->AddField('publishing',array($this,'publishLinks'),'cms','Publish');
		$this->AddFilter('cms_id',ctEQ);
	}
	
	public function publishLinks($field,$pkVal,$v,$rec) {
		if ($rec['content'] === $rec['content_published'])
			return utopia::DrawInput('published',itBUTTON,'Published',null,array('disabled'=>'disabled'));

		// preview, publish, revert (red)
		$obj = utopia::GetInstance('uCMS_View');
		$preview = CreateNavButton('Preview',$obj->GetURL(array('cms_id'=>$pkVal,'preview'=>1)),array('target'=>'_blank','title'=>'Preview this page'));
		$publish = $this->DrawSqlInput('publish','Publish',$pkVal,array('title'=>'Make this page live','class'=>'page-publish'),itBUTTON);
		$revert = $this->DrawSqlInput('revert','Revert',$pkVal,array('title'=>'Reset to published version','class'=>'page-revert'),itBUTTON);

		$script = <<<EOF
<script type="text/javascript">
$('.page-publish').click(function() {
	return confirm('Any changes you have made will become visible to the public.  Do you wish to continue?');
});
$('.page-revert').click(function() {
	return confirm('Reverting this page will reset all of your changes to the last published version.  Do you wish to continue?');
});
</script>
EOF;

		return $preview.$publish.$revert.$script;
	}
	
	public function processWidget($field,$pkVal,$value) {
			// replace pragma with uWidgetDiv
//			$value = preg_replace('/\{widget\.(.+)\}/Ui','<div class="uWidgetPlaceholder mceNonEditable" title="$0"><h1>$1</h1> options here (including delete) <input type="button" onclick="window.top.location = \'$0\'" value="EDIT"></div>',$value);

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

		$obj = utopia::GetInstance('uWidgets');
		$url = $obj->GetURL($id);

		$rep = uWidgets::DrawWidget($id);
		$ele = str_get_html($rep);

		$editBtn = '';
		$delBtn = '<input type="button" value="Remove" onclick="var a = this.parentNode; while (a.className.indexOf(\'uWidgetPlaceholder\')==-1) { a = a.parentNode } a.parentNode.removeChild(a);">';
		$addition = '';
		if (!$ele->root->children) {
			$ele = str_get_html('<span>'.$id.'</span>');
			$addition = $delBtn;
		} else {
			$editBtn = '<input type="button" value="Edit" onclick="window.top.location = \''.$url.'\'">';
			$addition = '<div class="uWidgetHeader">'.$delBtn.$editBtn.$id.'</div>';
		}

		$ele = $ele->root->children[0];
		$ele->class .= ' uWidgetPlaceholder mceNonEditable';
		$ele->title = $id;
		$ele->innertext = $addition.$ele->innertext;

		if (func_num_args() > 0) return $ele;
		die($ele);
	}
	
	public function UpdateField($fieldAlias,$newValue,&$pkVal=NULL) {
		if ($fieldAlias == 'revert') {
			$rec = $this->LookupRecord($pkVal);
			$this->UpdateField('content',$rec['content_published'],$pkVal);
			return;
		}
		if ($fieldAlias == 'publish') {
			$rec = $this->LookupRecord($pkVal);
			$this->UpdateField('content_published',$rec['content'],$pkVal);
			return;
		}
		if ($fieldAlias == 'cms_id') $newValue = UrlReadable($newValue);
		if ($fieldAlias == 'content') {
			// replace uWidgetDiv with pragma
			$html = str_get_html(stripslashes($newValue));
			if ($html) {
				foreach ($html->find('.uWidgetPlaceholder') as $ele) {
					if ($ele->plaintext == '') $ele->class = null;
					else $ele->outertext = '{widget.'.$ele->title.'}';
				}
				$newValue = addslashes($html);
			}

			$this->SetFieldType('content_time',ftRAW);
			$this->UpdateField('content_time','NOW()',$pkVal);
		}
		if ($fieldAlias == 'content_published') {
			$this->SetFieldType('content_published_time',ftRAW);
			$this->UpdateField('content_published_time','NOW()',$pkVal);
		}
		return parent::UpdateField($fieldAlias,$newValue,$pkVal);
	}
	public function getPossibleBlocks($val,$pk,$original) {
		$obj = utopia::GetInstance('uWidgets_List');
		$rows = $obj->GetRows();
		foreach (uWidgets::$staticWidgets as $widgetID => $callback) $rows[]['block_id'] = $widgetID;
		return '<span class="btn" onclick="ChooseWidget()">Insert Widget</span>';
	}
	public function SetupParents() {
		$this->RegisterAjax('getWidgetPlaceholder',array($this,'getWidgetPlaceholder'));
	}
	public function RunModule() {
		if (isset($_REQUEST['inline']))
			utopia::CancelTemplate();
		$this->ShowData();
	}
	public static function StartNoProcess() {
		echo '<!-- NoProcess -->';
	}
	public static function StopNoProcess() {
		echo '<!-- /NoProcess -->';
	}
}

utopia::AddTemplateParser('cms','uCMS_View::templateParser');
class uCMS_View extends uSingleDataModule {
	public function GetOptions() { return ALLOW_FILTER; }
	public function GetTabledef() { return 'tabledef_CMS'; }
	public function GetUUID() { return 'cms'; }

	static function last_updated() {
		$page = self::findPage();
		return $page['updated'];
	}
	static function templateParser($id) {
		$obj = utopia::GetInstance('uCMS_View');
		$rec = $obj->GetRows($id);
		$rec = $rec[0];
		$content = $rec['content_published'];
		if (isset($_GET['preview']) && internalmodule_AdminLogin::IsLoggedIn())
			$content = $rec['content'];
		if ($rec['content_time'] == 0)
			$content = $rec['content'];
		return '<div class="cms-'.$id.'">'.$content.'</div>';
	}
	public function GetURL($filters = NULL, $encodeAmp = false) {
		if (is_array($filters) && array_key_exists('uuid',$filters)) unset($filters['uuid']);
		if (!is_array($filters) && is_string($filters)) $filters = array('cms_id'=>$filters);

		if (isset($filters['cms_id']))
			$rec = $this->LookupRecord($filters);
		else
			$rec = self::findPage();

		if (!$rec) return $_SERVER['REQUEST_URI'];

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

		if (!$ishome) $path[] = $cms_id.'.php';

		return '/'.implode('/',$path).$qs;
	}
	public function GetTitle() {
		$rec = self::findPage();
		if (!$rec) $rec = self::GetHomepage();
		if (!$rec) $rec = $this->GetRecord($this->GetDataset(),0);
		return $rec['title'];
	}
	public function SetupFields() {
		$this->CreateTable('cms');
		$this->AddField('cms_id','cms_id','cms','cms_id');
		$this->AddField('title','title','cms','title');
		$this->AddField('updated','updated','cms');
		$this->AddField('parent','parent','cms','Parent');
		$this->AddField('position','position','cms','position');
		$this->AddField('template','template','cms','template');
		$this->AddField('nav_text','nav_text','cms');
		$this->AddField('description','description','cms','description');
		$this->AddField('content','content','cms','content');
		$this->AddField('content_time','content_time','cms');
		$this->AddField('content_published','content_published','cms','content');
		$this->AddField('is_home','(({parent} = \'\' OR {parent} IS NULL) AND ({position} IS NULL OR {position} = 0))','cms');
		$this->AddField('noindex','noindex','cms','noindex');
		$this->AddField('nofollow','nofollow','cms','nofollow');
		$this->AddFilter('cms_id',ctEQ);
	}

	public function SetupParents() {
		uWidgets::AddStaticWidget('page_updated','uCMS_View::last_updated');
	}

	static function GetHomepage() {
		$obj = utopia::GetInstance('uCMS_View');
		$row = $obj->LookupRecord(array('is_home'=>'1'));
		if (!$row) $row = $obj->LookupRecord();
		if ($row) return $row;
		return FALSE;
	}

	static function findPage() {
		$uri = $_SERVER['REQUEST_URI'];
		$uri = preg_replace('/(\?.*)?/','',$uri);

		if ($uri == '/') return self::GetHomepage();    

		preg_match('/([^\/]+)(\.php)?$/Ui',$uri,$matches);
		if (array_key_exists(1,$matches)) {
			$obj = utopia::GetInstance('uCMS_View');
			$row = $obj->LookupRecord($matches[1]);
			if ($row) return $row;
		}

		return false;
	}

	public function RunModule() {
		// custom home breadcrumb
		//breadcrumb::ShowHome(false);
		$rec = self::findPage();
		if (empty($rec)) {
			utopia::PageNotFound();
//			header("HTTP/1.0 404 Not Found");
//			echo 'Error 404: File not found'; return;
		}
		
		utopia::UseTemplate(self::GetTemplate($rec['cms_id']));
		
		//breadcrumb::AddURL($rec['nav_text'] ? $rec['nav_text'] : $rec['title'],$this->GetURL(array('cms_id'=>$rec['cms_id'])),-1000);
		utopia::SetTitle($rec['title']);
		utopia::SetDescription($rec['description']);
		$robots = array();
		if ($rec['nofollow']) $robots[] = 'NOFOLLOW';
		if ($rec['noindex']) $robots[] = 'NOINDEX';
		if (!empty($robots)) utopia::AppendVar('<head>','<META NAME="ROBOTS" CONTENT="'.implode(', ',$robots).'">');
		echo '{cms.'.$rec['cms_id'].'}';
	}

	static function GetTemplate($id) {
		$template = NULL;
		while ($id != NULL) {
			$obj = utopia::GetInstance('uCMS_View');
			$rec = $obj->LookupRecord($id);
			if ($rec['template']) { $template = $rec['template']; break; }
			$id = $rec['parent'];
		}
		if (!$template) $template = modOpts::GetOption('CMS','default_template');
		return $template;
	}
}