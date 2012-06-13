<?php

class tabledef_Widgets extends uTableDef {
	public $tablename = 'tabledef_DataBlocks';
	public function SetupFields() {
		$this->AddField('block_id',ftVARCHAR,150);
		$this->AddField('block_type',ftVARCHAR,150);
		$this->AddField('module',ftTEXT);
		$this->AddField('content',ftTEXT);
		$this->AddField('filter',ftTEXT);
		$this->AddField('order',ftVARCHAR,30);
		$this->AddField('limit',ftVARCHAR,10);

		$this->AddField('display',ftTEXT);

		$this->SetPrimaryKey('block_id');
	}
}

class uCustomWidgetConverter extends uDataModule {
	public function SetupParents() {
		$this->AddParentCallback('/',array($this,'RunModule'));
	}
	public function GetTabledef() { return 'tabledef_Widgets'; }
	public function SetupFields() {
		$this->CreateTable('blocks');
		$this->AddField('block_id','block_id','blocks','Block ID',itTEXT);
		$this->AddField('block_type','block_type','blocks','Type',itTEXT);
		$this->AddMetaField('module','Data Source',itTEXT);
		$this->AddMetaField('filter','Filter',itTEXT);
		$this->AddMetaField('order','Order',itTEXT);
		$this->AddMetaField('limit','Limit',itTEXT);
		$this->AddMetaField('content','Content',itHTML);

		$this->AddField('o_module','module','blocks','Data Source');
		$this->AddField('o_filter','filter','blocks','Filter');
		$this->AddField('o_order','order','blocks','Order');
		$this->AddField('o_limit','limit','blocks','Limit');
		$this->AddField('o_content','content','blocks','Content');

		$this->AddFilter('o_module',ctISNOT,itNONE,'NULL');
		$this->AddFilter('o_module',ctNOTEQ,itNONE,'');
	}
	public function RunModule() {
		// update old Datablocks to uCustomWidgets
		$rows = $this->GetRows();
		foreach ($rows as $row) {
			if (!$row['o_module']) continue;
			$this->UpdateFields(array(
				'block_type'=>'uCustomWidget',
				'module'=>$row['o_module'],
				'filter'=>$row['o_filter'],
				'order'=>$row['o_order'],
				'limit'=>$row['o_limit'],
				'content'=>$row['o_content'],

				'o_module'=>NULL,
				'o_filter'=>NULL,
				'o_order'=>NULL,
				'o_limit'=>NULL,
				'o_content'=>NULL,
			),$row['block_id']);
		}

		// update cms pages
		$obj = utopia::GetInstance('uCMS_Edit');
		$filter = "content LIKE '%{block.%' OR content_published LIKE '%{block.%'";
		$rows = $obj->GetRows(array($filter),true);
		$pk = $obj->GetPrimaryKey();
		foreach ($rows as $row) {
			$newVal = preg_replace('/{block\.(.+)}/Ui','{widget.$1}',$row['content']);
			if ($newVal != $row['content']) $obj->UpdateField('content',$newVal,$row[$pk]);

			$newVal = preg_replace('/{block\.(.+)}/Ui','{widget.$1}',$row['content_published']);
			if ($newVal != $row['content_published']) $obj->UpdateField('content_published',$newVal,$row[$pk]);
		}
	}
}

class uWidgets_List extends uListDataModule implements iAdminModule {
	public function GetTitle() { return 'Widgets'; }
	public function GetOptions() { return ALLOW_DELETE | ALLOW_FILTER; }
	public function GetSortOrder() { return -8800; }
	public function GetTabledef() { return 'tabledef_Widgets'; }
	public function SetupFields() {
		$this->CreateTable('blocks');
		$this->AddField('block_id','block_id','blocks','ID');
		$this->AddField('block_type','block_type','blocks','Type');
	}
	public function SetupParents() {
		$this->RegisterAjax('getWidgets',array($this,'getWidgets'));
		//$this->AddParentCallback('uCMS_List',array($this,'ShowData'));
		$this->AddParent('/');
	}
	public function getWidgets() {
		// static
		$rows = array();
		foreach(uWidgets::$staticWidgets as $name => $widget) {
			$rows[] = array('block_id'=>$name,'block_type'=>'Fixed Widgets');
		}
		// widgets
		$widgets = $this->GetRows();
		array_sort_subkey($widgets,'block_type');
		$rows = array_merge($rows,$widgets);

		$obj = utopia::GetInstance('uWidgets');
		$newUrl = $obj->GetURL();

		$rows = array($newUrl, $rows);
		header('Content-Type: application/json');
		echo json_encode($rows);
	}

	public function RunModule() {
		$this->ShowData();
		// show conclicts between static block ids and custom ids
		$rows = $this->GetRows();
		foreach ($rows as $row) {
			if (uWidgets::StaticWidgetExists($row['block_id'])) echo "Conflict: Widget ({$row['block_id']}) already exists as a static Widget.  Please rename it.";
		}
	}
}

class uCustomWidget implements iWidget {
	static function Initialise($sender) {
		$installed = array();
		$classes = get_declared_classes();
		foreach ($classes as $classname){ // install tables
			if ($classname == 'uDataModule' || $classname == 'uListDataModule' || $classname == 'uSingleDataModule' || !is_subclass_of($classname,'uDataModule')) continue;
			$installed[$classname] = $classname;
		}
		$sender->AddMetaField('module','Data Source',itCOMBO,$installed);
		$sender->AddMetaField('filter','Filter',itTEXT);
		$sender->AddMetaField('order','Order',itTEXT);
		$sender->AddMetaField('limit','Limit',itTEXT);
		$sender->AddField('content_info','"The content you enter below will be repeated for each row in the result.<br>If you want to repeat only a part of the content, give the element a class of _r (class=\"_r\"), or _ri to repeat contained elements only (innerHTML)."','','');
		$sender->AddField('fields',array(__CLASS__,'getPossibleFields'),'blocks','Possible Fields');
		$sender->AddMetaField('content','Content',itHTML);
		$sender->FieldStyles_Set('content',array('width'=>'100%','height'=>'20em'));
	}
	public static function getPossibleFields($originalVal,$pk,$processedVal,$rec) {
		$meta = json_decode($rec['__metadata'],true);
		if (!$meta || !isset($meta['module']) || !$meta['module']) return 'Please select a Data Source.';
		if (!class_exists($meta['module'])) return 'Data Source does not exist';
		$obj = utopia::GetInstance($meta['module']);
		if (!$obj) return '';
		$fields = $obj->fields;
		$ret = '';
		$ret .= '<span data-fieldname="total" class="btn widget-field">Total Records</span>';
		if (isset($meta['limit']) && strpos($meta['limit'],',')===FALSE && isset($meta['content']) && stripos($meta['content'],'{pagination}') !== FALSE) {
			$ret .= '<span data-fieldname="pages" class="btn widget-field">Total Pages</span>';
			$ret .= '<span data-fieldname="current_page" class="btn widget-field">Current Page</span>';
			$ret .= '<span data-fieldname="pagination" class="btn widget-field">Pagination</span>';
		}
		$ret .= '<span data-fieldname="field._module_url" class="btn widget-field">Module URL</span>';
		foreach ($fields as $field) {
			$visname = $field['visiblename'] ? $field['visiblename'] : $field['alias'];
			$ret .= '<span data-fieldname="field.'.$field['alias'].'" class="btn widget-field">'.$visname.'</span>';
		}
		return trim($ret);
	}
	static function DrawData($rec) {
		$meta = json_decode($rec['__metadata'],true);

		if (!isset($meta['module'])) $meta['module'] = null;
		if (!isset($meta['filter'])) $meta['filter'] = null;
		if (!isset($meta['order'])) $meta['order'] = null;
		if (!isset($meta['limit'])) $meta['limit'] = null;
		if (!isset($meta['content'])) $meta['content'] = null;

		if (!$meta['module'] || !class_exists($meta['module'])) return '';

		if (!($instance = utopia::GetInstance($meta['module'],false))) {
			echo 'Could not load Data Source';
			return;
		}
		
		$content = $append = $prepend = '';
    	
		$html = str_get_html($meta['content'],true,true,DEFAULT_TARGET_CHARSET,false);
		$ele = '';
		if ($html) {
			$ele = $html->find('._ri',0);
			if ($ele) $ele = $ele->innertext;
			else {
				$ele = $html->find('._r',0);
				if ($ele) $ele = $ele->outertext;
				else $ele = '';
			}
		} else $html = $meta['content'];

		$repeatable = $html;
		if ($ele) {
			// found a repeatable element
			// split content at this element. prepare for apend and prepend.
			list($append,$prepend) = explode($ele,$repeatable);
			$repeatable = $ele;
		}
	
		{ // get rows
			$instance->_SetupParents(); $instance->_SetupFields();
			foreach ($instance->fields as $fieldName => $fieldInfo) {
				if (isset($fieldInfo['ismetadata']) && !isset($meta[$fieldName])) $meta[$fieldName] = null;
			}
			
			// add filters
			utopia::MergeVars($meta['filter']);
			if ($meta['filter']) $instance->AddFilter($meta['filter'],ctCUSTOM);

			// add Order
			utopia::MergeVars($meta['order']);
			if ($meta['order']) $instance->AddOrderBy($meta['order']);

			// init limit
			$page = NULL;
			$limit = NULL;
			utopia::MergeVars($meta['limit']);
			$instance->GetLimit($meta['limit'],$_,$meta['limit']);
			$meta['limit'] = trim($meta['limit']);
			if ($meta['limit'] && strpos($meta['limit'],',')===FALSE && stripos($meta['content'],'{pagination}') !== FALSE) {
				$page = isset($_GET['_p_'.$rec['block_id']]) ? $_GET['_p_'.$rec['block_id']] : 0;
				$limit = $meta['limit'];
				$meta['limit'] = ($limit*$page).','.$meta['limit'];
			}
			
			// get rows
			$rows = array();
			try {
				$rows = $instance->GetRows();
			} catch (Exception $e) {
				uNotices::AddNotice('<p>There was a problem accessing the records, please check your filter and sorting options for invalid fields.</p>',NOTICE_TYPE_WARNING);
			}
		}

		// process limit
		$total = count($rows);
		$instance->ApplyLimit($rows,$meta['limit']);
		
		// process repeatable area
		$instance = utopia::GetInstance($meta['module']);
		$fields = $instance->fields;
		if (preg_match_all('/{([a-z])+\.([^}]+)}/Ui',$repeatable,$matches,PREG_PATTERN_ORDER)) {
			$searchArr = $matches[0];
			$typeArr = isset($matches[1]) ? $matches[1] : false;
			$varsArr = isset($matches[2]) ? $matches[2] : false;
			foreach ($rows as $row) {
				$row['_module_url'] = $instance->GetURL($row[$instance->GetPrimaryKey()]);
				$c = $repeatable;
				foreach ($searchArr as $k => $search) {
					$field = $varsArr[$k];
					$qs = null;
					if (strpos($field,'?') !== FALSE) list($field,$qs) = explode('?',$field,2);
					if (!array_key_exists($field,$row)) continue;
					if ($qs) {
						parse_str(html_entity_decode($qs),$qs);
						$instance->FieldStyles_Add($field,$qs);
					}
					switch ($typeArr[$k]) {
						case 'u':
							$replace = $instance->PreProcess($field,$row[$field],$row);
							$replace = UrlReadable($replace);
							break;
						case 'd':
							$replace = $instance->PreProcess($field,$row[$field],$row);
							break;
						default:
							$replace = $instance->GetCell($field,$row);
							break;
					}
					$c = str_replace($search,$replace,$c);
				}
				$content .= $c;
			}
		}
		$instance->fields = $fields;
		$ret = $append.$content.$prepend;
		
		// process full doc
		$ret = str_ireplace('{total}',$total,$ret);
		if ($page !== NULL && is_numeric($limit)) {
			ob_start();
			$pages = max(ceil($total / $limit),1);
			$cPage = utopia::OutputPagination($pages,'_p_'.$rec['block_id']);
			$ret = str_ireplace('{pagination}',ob_get_contents(),$ret);
			ob_end_clean();
			$ret = str_ireplace('{pages}',$pages,$ret);
			$ret = str_ireplace('{current_page}',$cPage,$ret);
		}
		
		while (utopia::MergeVars($ret));

		return $ret;
	}
}

utopia::AddTemplateParser('widget','uWidgets::DrawWidget');
class uWidgets extends uSingleDataModule implements iAdminModule {
	public function GetTitle() { return 'Edit Widget'; }
	public function GetOptions() { return ALLOW_DELETE | ALLOW_FILTER | ALLOW_EDIT | ALLOW_ADD; }
	public function GetTabledef() { return 'tabledef_Widgets'; }
	public function SetupFields() {
		$this->CreateTable('blocks');
		$this->AddField('block_id','block_id','blocks','Block ID',itTEXT);

		$installed = array();
		$classes = get_declared_classes();
		foreach ($classes as $classname) { // install tables
			$reflectionA = new ReflectionClass($classname);
			if ($reflectionA->implementsInterface('iWidget')) $installed[$classname] = $classname;
		}
		$this->AddField('block_type','block_type','blocks','Type',itCOMBO,$installed);

		$this->AddMetaField('width','Width',itTEXT);
		$this->AddMetaField('height','Height',itTEXT);
	}
	public function SetupParents() {
		uJavascript::IncludeFile(dirname(__FILE__).'/widget.js');
		$this->AddParent('uWidgets_List','block_id','*');
	}

	public function InitInstance($type) {
		if (class_exists($type)) call_user_func(array($type,'Initialise'),$this);
		$this->AddField('preview',array($this,'getPreview'),'blocks','Preview');
	}
	public function UpdateField($fieldAlias,$newValue,&$pkVal=NULL) {
		$rec = $this->LookupRecord($pkVal);
		$this->InitInstance($rec['block_type']);

		$ret = parent::UpdateField($fieldAlias,$newValue,$pkVal);
		if ($fieldAlias == 'block_type') AjaxEcho("window.location.reload(false);");
		return $ret;
	}
	public function RunModule() {
		$fltr = $this->FindFilter($this->GetPrimaryKey(),ctEQ,itNONE);
		$v = $this->GetFilterValue($fltr['uid']);
		if ($v) {
			$rec = $this->LookupRecord();
			$this->InitInstance($rec['block_type']);
		}

		$this->ShowData();
	}

	static function DrawWidget($rec) {
		if (!is_array($rec)) {
			if (self::StaticWidgetExists($rec)) return call_user_func(self::$staticWidgets[$rec]);

			$obj = utopia::GetInstance('uWidgets');
			$rec = $obj->LookupRecord($rec);
			if (!$rec) return '';
		}
		$content = '';
		if ($rec['block_type'] && class_exists($rec['block_type'])) $content = call_user_func(array($rec['block_type'],'DrawData'),$rec);

		$meta = json_decode($rec['__metadata'],true);

		// add container
		$w = isset($meta['width']) ? $meta['width'] : '';	if (is_numeric($w)) $w = $w.'px';	if ($w) $w = 'width:'.$w.';';
		$h = isset($meta['height'])?$meta['height'] : '';	if (is_numeric($h)) $h = $h.'px';	if ($h) $h = 'height:'.$h.';';
		$style = ($w || $h) ? ' style="'.$w.$h.'"' : '';
		$ret = '<div class="uWidget uWidget-'.$rec['block_id'].'" '.$style.'>'.$content.'</div>';

		return $ret;
	}

	static function getPreview($originalVal,$pk,$processedVal,$rec) {
		return self::DrawWidget($rec);
	}

	public static $staticWidgets = array();
	public static function AddStaticWidget($ident,$callback) {
		self::$staticWidgets[$ident] = $callback;
	}
	public static function StaticWidgetExists($id) {
		return isset(self::$staticWidgets[$id]);
	}
	public static function IsStaticWidget($id) {
		return isset(self::$staticWidgets[$id]);
	}
}
