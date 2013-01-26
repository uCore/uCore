<?php

class uCustomWidget implements iWidget {
	static function Initialise($sender) {
		$installed = array();
		$classes = utopia::GetModulesOf('uDataModule');
		foreach ($classes as $classname=>$info) { // install tables
			if (is_subclass_of($classname,'iAdminModule')) continue;
			$o =& utopia::GetInstance($classname);
			if (!$o->HasRewrite()) continue;
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
		$obj =& utopia::GetInstance($rec['module']);
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
		$obj =& utopia::GetInstance($rec['module']);
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

		if (!($instance =& utopia::GetInstance($rec['module'],false))) {
			echo 'Could not load Data Source';
			return;
		}
	
		$instance->_SetupParents(); $instance->_SetupFields();
		foreach ($instance->fields as $fieldName => $fieldInfo) {
			if (isset($fieldInfo['ismetadata']) && !isset($rec[$fieldName])) $rec[$fieldName] = null;
		}
		
		// clear filters
		$rec['clear_filter'] = (array)utopia::jsonTryDecode($rec['clear_filter']);
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
		
		$dataset = $instance->GetDataset();
		
		// init limit
		utopia::MergeVars($rec['limit']);
		$rec['limit'] = trim($rec['limit']);
		$instance->GetLimit($limit,$page); // page is governed by a different query arg for widgets, below
		$page = (stripos($rec['content'],'{pagination}') !== FALSE) && isset($_GET['_p_'.$rec['block_id']]) ? $_GET['_p_'.$rec['block_id']] : 0;
		$offset = $limit * $page;
		if ($rec['limit']) {
			if (strpos($rec['limit'],',')===FALSE) {
				$limit = $rec['limit'];
				$offset = $limit * $page;
			} else {
				list($offset,$limit) = explode(',',$rec['limit']);
				$offset = trim($offset); $limit = trim($limit);
			}
		}
		
		if (!($total = $dataset->CountRecords())) return $rec['no_rows'];

		// get rows
		$rows = $dataset->GetOffset($offset,$limit);
		
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
