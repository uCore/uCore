<?php

class tabledef_CustomWidget extends uTableDef {
	public function SetupFields() {
		$this->AddField('widget_id',ftNUMBER);
		$this->AddField('module',ftVARCHAR,255);
		$this->AddField('content',ftTEXT);
		$this->AddField('no_rows',ftTEXT);
		$this->AddField('clear_filter',ftVARCHAR,255);
		$this->AddField('filter',ftVARCHAR,255);
		$this->AddField('order',ftVARCHAR,255);
		$this->AddField('limit',ftVARCHAR,50);
		
		$this->SetPrimaryKey('widget_id');
	}
}

class uCustomWidget implements iWidget {
	static function Initialise($sender) {
		$installed = array();
		$classes = utopia::GetModulesOf('uDataModule');
		foreach ($classes as $classname=>$info) { // install tables
			if (is_subclass_of($classname,'iAdminModule')) continue;
			$o = utopia::GetInstance($classname);
			if (!$o->HasRewrite()) continue;
			$installed[$classname] = $classname;
		}
		
		$sender->CreateTable('custom','tabledef_CustomWidget','blocks','widget_id');

		$sender->AddField('module','module','custom','Data Source',itCOMBO,$installed);
		
		$sender->AddSpacer();
		$sender->AddField('content_info',"'The content you enter below will be repeated for each row in the result.<br>If you want to repeat only a part of the content, give the element a class of _r (class=\"_r\"), or _ri to repeat contained elements only (innerHTML).'",'','');
		$sender->AddField('fields',array(__CLASS__,'getPossibleFields'),'blocks','Possible Fields');
		$sender->AddField('content','content','custom','Content',itHTML);
		$sender->FieldStyles_Set('content',array('width'=>'100%','height'=>'20em'));
		
		$sender->AddSpacer();
		$sender->AddField('nr_info',"'The content below will be shown if no rows exist.'",'','');
		$sender->AddField('no_rows','no_rows','custom','No Rows Found',itHTML);
		$sender->FieldStyles_Set('content',array('height'=>'20em'));

		$sender->NewSection('Filters');
		$sender->AddField('clear_filter','clear_filter','custom','Remove Filters',itCHECKBOX,'uCustomWidget::ListFilters');
		$sender->AddField('filter','filter','custom','Add Filter',itTEXT);
		$sender->AddField('order','order','custom','Order',itTEXT);
		$sender->AddField('limit','limit','custom','Limit',itTEXT);
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
					$text = $filter['fieldName'];
					if (is_array($text)) $text = (is_string($text[0])?$text[0]:get_class($text[0])).'->'.$text[1];
					if (is_callable($filter['fieldName'])) $text = 'Function: '.$text;
					$arr[$filter['uid']] = $text;
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
		$ret .= '<span data-fieldname="total" class="btn widget-field">Total Records</span> ';
		if (isset($rec['limit']) && strpos($rec['limit'],',')===FALSE && isset($rec['content']) && stripos($rec['content'],'{pagination}') !== FALSE) {
			$ret .= '<span data-fieldname="pages" class="btn widget-field">Total Pages</span> ';
			$ret .= '<span data-fieldname="current_page" class="btn widget-field">Current Page</span> ';
			$ret .= '<span data-fieldname="pagination" class="btn widget-field">Pagination</span> ';
		}
		$ret .= '<span data-fieldname="field._module_url" class="btn widget-field">Module URL</span> ';
		foreach ($fields as $field) {
			$visname = $field['visiblename'] ? $field['visiblename'] : $field['alias'];
			$ret .= '<span data-fieldname="field.'.$field['alias'].'" class="btn widget-field">'.$visname.'</span> ';
		}
		return trim($ret);
	}
	static function DrawData($rec) {
		if (!$rec['module'] || !class_exists($rec['module'])) return $rec['no_rows'];

		if (!($instance = utopia::GetInstance($rec['module'],false))) {
			return 'Could not load Data Source';
		}
	
		$instance->_SetupParents(); $instance->_SetupFields();
		
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
		if ($offset > $total) return $rec['no_rows'];
		
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
		
		$dataset->GetOffset($offset,$limit);
		while (($row = $dataset->fetch())) {
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
