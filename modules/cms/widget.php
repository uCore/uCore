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
		$this->AddField('block_type','block_type','blocks','Type',itCOMBO,$installed);
		$this->AddMetaField('module','Data Source',itCOMBO);
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
	}
}

class uWidgets_List extends uListDataModule implements iAdminModule {
	public function GetTitle() { return 'Widgets'; }
	public function GetOptions() { return ALLOW_DELETE | ALLOW_FILTER; }
	public function GetSortOrder() { return 1; }
	public function GetTabledef() { return 'tabledef_Widgets'; }
	public function SetupFields() {
		$this->CreateTable('blocks');
		$this->AddField('block_id','block_id','blocks','ID');
		$this->AddField('block_type','block_type','blocks','Type');
	}
	public function SetupParents() {
		$this->AddParentCallback('uCMS_List',array($this,'ShowData'));
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

interface iWidget {
	// for adding custom fields etc
	static function Initialise($sender);
	static function DrawData($data);
}

class uTwitterWidget implements iWidget {
	static function Initialise($sender) {
		$sender->SetFieldType('width',ftNUMBER);
		$sender->SetFieldType('height',ftNUMBER);
		$sender->AddMetaField('twitter_id','Twitter ID',itTEXT);
	}
	static function DrawData($data) {
		$meta = json_decode($data['__metadata'],true);
		$id = $meta['twitter_id'];
		$width = $meta['width'] ? $meta['width'] : 250;
		$height = $meta['height'] ? $meta['height'] : 350;
		return <<<FIN
<script src="http://widgets.twimg.com/j/2/widget.js"></script>
<div id="twitter_$id"></div>
<script>
new TWTR.Widget({
  id: 'twitter_$id',
  version: 2,
  type: 'profile',
  rpp: 4,
  interval: 6000,
  width: $width,
  height: $height,
  theme: {
    shell: {
      background: '#333333',
      color: '#ffffff'
    },
    tweets: {
      background: '#000000',
      color: '#ffffff',
      links: '#4aed05'
    }
  },
  features: {
    scrollbar: false,
    loop: false,
    live: false,
    hashtags: true,
    timestamp: true,
    avatars: false,
    behavior: 'all'
  }
}).render().setUser('$id').start();
</script>
FIN;
	}
}

class uCustomWidget implements iWidget {
	static function Initialise($sender) {
		$installed = array();
		$classes = get_declared_classes();
		foreach ($classes as $classname){ // install tables
			if ($classname == 'uDataModule' || $classname == 'uListDataModule' || $classname == 'uSingleDataModule' || !is_subclass_of($classname,'uDataModule')) continue;
			$installed[] = $classname;
		}
		$sender->AddMetaField('module','Data Source',itCOMBO,$installed);
		$sender->AddMetaField('filter','Filter',itTEXT);
		$sender->AddMetaField('order','Order',itTEXT);
		$sender->AddMetaField('limit','Limit',itTEXT);
		$sender->AddField('content_info','"The content you enter below will be repeated for each row in the result.<br>If you want to repeat only a part of the content, give the element a class of _r (class=\"_r\"), or _ri to repeat contained elements only (innerHTML)."','','');
		$sender->AddField('fields',array(get_class($this),'getPossibleFields'),'blocks','Possible Fields');
		$sender->AddMetaField('content','Content',itHTML);
		$sender->FieldStyles_Set('content',array('width'=>'100%','height'=>'20em'));
	}
	public static function getPossibleFields($originalVal,$pk,$processedVal,$rec) {
		$meta = json_decode($rec['__metadata'],true);
		if (!$meta || !$meta['module']) return 'Please select a module.';
		$obj = utopia::GetInstance($meta['module']);
		if (!$obj) return '';
		$fields = $obj->fields;
		$ret = '';
		foreach ($fields as $field) {
			$ret .= "<span onclick=\"tinyMCE.execCommand('mceInsertContent',false,'{field.'+$(this).text()+'}');\" style=\"margin:0 5px;cursor:pointer\" class=\"btn\">{$field['alias']}</span>";
		}
		return trim($ret);
	}
	static function DrawData($rec) {
		$meta = json_decode($rec['__metadata'],true);
		if ($meta['module'] && ($instance = utopia::GetInstance($meta['module']))) {
			// create module instance
			$instance->ClearFilters();

			// add filters
			utopia::MergeVars($meta['filter']);
			$instance->extraHaving = $meta['filter'];

			// add Order
			utopia::MergeVars($meta['order']);
			$instance->ordering = $meta['order'];

			// init limit
			$instance->limit = $meta['limit'];

			// get rows    
			$dataset = $instance->GetDataset(NULL);
			$rows = GetRows($dataset);
		} else $rows = array();

		$content = $append = $prepend = '';
    	
		$html = str_get_html($meta['content']);
		if ($html) {
			$ele = $html->find('._ri',0)->innertext;
			if (!$ele) $ele = $html->find('._r',0)->outertext;
		} else $html = $meta['content'];

		$repeatable = $html;
		if ($ele) {
			// found a repeatable element
			// split content at this element. prepare for apend and prepend.
			list($append,$prepend) = explode($ele,$repeatable);
			$repeatable = $ele;
		}
	
		if (preg_match_all('/{([a-z])+\.([^}]+)}/Ui',$repeatable,$matches,PREG_PATTERN_ORDER)) {
			$searchArr = $matches[0];
			$typeArr = isset($matches[1]) ? $matches[1] : false;
			$varsArr = isset($matches[2]) ? $matches[2] : false;
			foreach ($rows as $row) {
				$c = $repeatable;
				foreach ($searchArr as $k => $search) {
					$field = $varsArr[$k];
					if (!isset($row[$field])) continue;
					$obj = utopia::GetInstance($meta['module']);
					switch ($typeArr[$k]) {
						case 'u':
							$replace = $obj->PreProcess($field,$row[$field],$row);
							$replace = UrlReadable($replace);
							break;
						case 'd':
							$replace = $obj->PreProcess($field,$row[$field],$row);
							break;
						default:
							$replace = $obj->GetCell($field,$row);
							break;
					}
					$c = str_replace($search,$replace,$c);
				}
				$content .= $c;
			}
		}

		$ret = $append.$content.$prepend;
		while (utopia::MergeVars($ret));

		// add container
		$w = $meta['width']; if ($w == intval($w)) $w = $w.'px';
		$h = $meta['height']; if ($h == intval($h)) $h = $h.'px';
		$ret = '<div style="width:'.$w.';height:'.$h.';">'.$ret.'</div>';
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
			if ($reflectionA->implementsInterface('iWidget')) $installed[] = $classname;
		}
		$this->AddField('block_type','block_type','blocks','Type',itCOMBO,$installed);

		$this->AddMetaField('width','Width',itTEXT);
		$this->AddMetaField('height','Height',itTEXT);
	}
	public function SetupParents() {
		$this->AddParent('uWidgets_List');
		$this->AddParent('uWidgets_List','block_id','*');
	}
	public function InitInstance($type) {
		if (class_exists($type)) $type::Initialise($this);
		$this->AddField('preview',array($this,'getPreview'),'blocks','Preview');
	}

	public function UpdateField($field,$newValue,$pkVal=null) {
		$rec = $this->LookupRecord($pkVal);
		$this->InitInstance($rec['block_type']);

		$ret = parent::UpdateField($field,$newValue,$pkVal);
		if ($field == 'block_type') AjaxEcho("window.location.reload(false);");
		return $ret;
	}
	public function RunModule() {
		$rec = $this->LookupRecord();
		$this->InitInstance($rec['block_type']);

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
		if ($rec['block_type']) $content = $rec['block_type']::DrawData($rec);

		$meta = json_decode($rec['__metadata'],true);
		$w = $meta['width']; if ($w == intval($w) && $w > 0) $w = $w.'px';
		$h = $meta['height']; if ($h == intval($h) && $h > 0) $h = $h.'px';

		$w = $w ? 'width:'.$w.';' : '';
		$h = $h ? 'height:'.$h : '';
		$rep = '<div style="'.$w.$h.'">'.$content.'</div>';

		return $rep;
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
}

?>
