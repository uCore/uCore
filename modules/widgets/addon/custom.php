<?php

class uCustomWidget implements iWidget {
	static function Initialise($sender) {
		$installed = array();
		$classes = get_declared_classes();
		foreach ($classes as $classname){ // install tables
			if ($classname == 'uDataModule' || $classname == 'uListDataModule' || $classname == 'uSingleDataModule' || !is_subclass_of($classname,'uDataModule')) continue;
			$installed[$classname] = $classname;
		}
		$sender->AddMetaField('module','Data Source',itCOMBO,$installed);
		
		$sender->AddSpacer();
		$sender->AddField('content_info',"'The content you enter below will be repeated for each row in the result.<br>If you want to repeat only a part of the content, give the element a class of _r (class=\"_r\"), or _ri to repeat contained elements only (innerHTML).'",'','');
		$sender->AddField('fields',array(__CLASS__,'getPossibleFields'),'blocks','Possible Fields');
		$sender->AddMetaField('content','Content',itHTML);
		$sender->FieldStyles_Set('content',array('width'=>'100%','height'=>'20em'));
		
		$sender->AddSpacer();
		$sender->AddField('nr_info',"'The content below will be shown if no rows exist.'",'','');
		$sender->AddMetaField('no_rows','Default',itHTML);
		$sender->FieldStyles_Set('content',array('width'=>'100%','height'=>'20em'));

		$sender->NewSection('Filters');
		$sender->AddMetaField('clear_filter','Remove Filters',itCHECKBOX,'uCustomWidget::ListFilters');
		$sender->AddMetaField('filter','Add Filter',itTEXT);
		$sender->AddMetaField('order','Order',itTEXT);
		$sender->AddMetaField('limit','Limit',itTEXT);
	}
	public static function ListFilters($obj,$field,$pkVal) {
		$rec = $obj->LookupRecord($pkVal);
		if (!$rec || !isset($rec['module']) || !$rec['module']) return NULL;
		if (!class_exists($rec['module'])) return NULL;
		$obj = utopia::GetInstance($rec['module']);
		if (!$obj) return NULL;
		
		$arr = array();
		$f = $obj->filters;
		foreach ($f as $t) {
			foreach ($t as $fs) {
				foreach ($fs as $filter) {
					$arr[$filter['uid']] = $filter['fieldName'];
				}
			}
		}
		return $arr;
	}
	public static function getPossibleFields($originalVal,$pk,$processedVal,$rec) {
		if (!$rec || !isset($rec['module']) || !$rec['module']) return 'Please select a Data Source.';
		if (!class_exists($rec['module'])) return 'Data Source does not exist';
		$obj = utopia::GetInstance($rec['module']);
		if (!$obj) return '';
		$fields = $obj->fields;
		$ret = '';
		$ret .= '<span data-fieldname="total" class="btn widget-field">Total Records</span>';
		if (isset($rec['limit']) && strpos($rec['limit'],',')===FALSE && isset($rec['content']) && stripos($rec['content'],'{pagination}') !== FALSE) {
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
		if (!$rec['module'] || !class_exists($rec['module'])) return $rec['no_rows'];

		if (!($instance = utopia::GetInstance($rec['module'],false))) {
			echo 'Could not load Data Source';
			return;
		}
	
		{ // get rows
			$instance->_SetupParents(); $instance->_SetupFields();
			foreach ($instance->fields as $fieldName => $fieldInfo) {
				if (isset($fieldInfo['ismetadata']) && !isset($rec[$fieldName])) $rec[$fieldName] = null;
			}
			
			// clear filters
			$rec['clear_filter'] = utopia::jsonTryDecode($rec['clear_filter']);
			if (!is_array($rec['clear_filter'])) $rec['clear_filter'] = array($rec['clear_filter']);
			foreach ($rec['clear_filter'] as $uid) {
				$instance->RemoveFilter($uid);
			}
			
			// add filters
			utopia::MergeVars($rec['filter']);
			if ($rec['filter']) $instance->AddFilter($rec['filter'],ctCUSTOM);

			// add Order
			utopia::MergeVars($rec['order']);
			if ($rec['order']) {
				$instance->ordering = NULL;
				$instance->AddOrderBy($rec['order']);
			}

			// init limit
			$page = NULL;
			$limit = NULL;
			utopia::MergeVars($rec['limit']);
			$instance->GetLimit($rec['limit'],$_,$rec['limit']);
			$rec['limit'] = trim($rec['limit']);
			if ($rec['limit'] && strpos($rec['limit'],',')===FALSE && stripos($rec['content'],'{pagination}') !== FALSE) {
				$page = isset($_GET['_p_'.$rec['block_id']]) ? $_GET['_p_'.$rec['block_id']] : 0;
				$limit = $rec['limit'];
				$rec['limit'] = ($limit*$page).','.$rec['limit'];
			}
			
			// get rows
			$rows = array();
			$rows = $instance->GetRows();
		}
		
		if (!$rows) return $rec['no_rows'];

		// process limit
		$total = count($rows);
		$instance->ApplyLimit($rows,$rec['limit']);
		
		// get content
		$content = $append = $prepend = '';
    	
		$html = str_get_html($rec['content'],true,true,DEFAULT_TARGET_CHARSET,false);
		$ele = '';
		if ($html) {
			$ele = $html->find('._ri',0);
			if ($ele) $ele = $ele->innertext;
			else {
				$ele = $html->find('._r',0);
				if ($ele) $ele = $ele->outertext;
				else $ele = '';
			}
		} else $html = $rec['content'];

		$repeatable = $html;
		if ($ele) {
			// found a repeatable element
			// split content at this element. prepare for apend and prepend.
			list($append,$prepend) = explode($ele,$repeatable);
			$repeatable = $ele;
		}
		
		foreach ($rows as $row) {
			$c = $repeatable;
			$instance->MergeFields($c,$row);
			$content .= $c;
		}
		
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