<?php

class tabledef_DataBlocks extends uTableDef {
  public $tablename = 'datablocks';
  public function SetupFields() {
    $this->AddField('block_id',ftVARCHAR,150);
    $this->AddField('module',ftTEXT);
    $this->AddField('content',ftTEXT);
    $this->AddField('filter',ftTEXT);
    $this->AddField('order',ftVARCHAR,30);
    $this->AddField('limit',ftVARCHAR,10);
    $this->AddField('editable',ftBOOL);
    
    $this->AddField('display',ftTEXT);
    
    $this->SetPrimaryKey('block_id');
  }
}

class uDataBlocks_List extends uListDataModule {
  public function GetTitle() { return 'Data Blocks'; }
  public function GetOptions() { return IS_ADMIN | ALLOW_DELETE | ALLOW_FILTER; }
  public function GetSortOrder() { return 1; }
  public function GetTabledef() { return 'tabledef_DataBlocks'; }
  public function SetupFields() {
    $this->CreateTable('blocks');
    $this->AddField('block_id','block_id','blocks','Block ID');
    $this->AddField('module','module','blocks','Table');
  }
  public function SetupParents() {
	$this->AddParentCallback('uCMS_List',array($this,'ShowData'));
  }

  public function RunModule() {
    $this->ShowData();
    // show conclicts between static block ids and custom ids
    $rows = $this->GetRows();
    foreach ($rows as $row) {
      if (uDataBlocks::StaticBlockExists($row['block_id'])) echo "Conflict: DataBlock ({$row['block_id']}) already exists as a static DataBlock.  Please 
rename it.";
    }
  }
}

utopia::AddTemplateParser('block','uDataBlocks::DrawBlock');
class uDataBlocks extends uSingleDataModule {
  public function GetTitle() { return 'Edit Data Block'; }
  public function GetOptions() { return IS_ADMIN | ALLOW_DELETE | ALLOW_FILTER | ALLOW_EDIT | ALLOW_ADD; }
  public function GetTabledef() { return 'tabledef_DataBlocks'; }
  public function SetupFields() {
    $this->CreateTable('blocks');
    $this->AddField('block_id','block_id','blocks','Block ID',itTEXT);
    
    $installed = array();
    $classes = get_declared_classes();
    foreach ($classes as $classname){ // install tables
      if ($classname == 'uDataModule' || $classname == 'uListDataModule' || $classname == 'uSingleDataModule' || !is_subclass_of($classname,'uDataModule')) continue;
      $installed[] = $classname;
    }
  
    $this->AddField('module','module','blocks','Data Source',itCOMBO,$installed);
    $this->AddField('filter','filter','blocks','Filter',itTEXT);
    $this->AddField('order','order','blocks','Order',itTEXT);
    $this->AddField('limit','limit','blocks','Limit',itTEXT);
    $this->AddField('editable','editable','blocks','Editable',itCHECKBOX);
    $this->AddField('content_info','"The content you enter below will be repeated for each row in the result.<br>If you want to repeat only a part of the content, give the element an id of _r (id=\"_r\") or _ri to repeat contained elements only (innerHTML)."','','');
    $this->AddField('fields',array($this,'getPossibleFields'),'blocks','Possible Fields');
    $this->AddField('content','content','blocks','Content',itHTML);
	$this->FieldStyles_Set('content',array('width'=>'100%','height'=>'20em'));
    $this->AddField('preview',array($this,'getPreview'),'blocks','Preview');
  }
  public function getPossibleFields($originalVal,$pk,$processedVal) {
    $rec = $this->LookupRecord($pk);
    if (!$rec || !$rec['module']) return 'Please select a module.';
    $obj = utopia::GetInstance($rec['module']);
    $fields = $obj->fields;
    $ret = '';
    foreach ($fields as $field) {
      $ret .= "<span onclick=\"tinyMCE.execCommand('mceInsertContent',false,'{field.'+$(this).text()+'}');\" style=\"margin:0 5px;cursor:pointer\" class=\"btn\">{$field['alias']}</span>";
    }
    return trim($ret);
  }
  public function getPreview($originalVal,$pk,$processedVal) {
    return $this->DrawBlock($pk);
  }
  public function SetupParents() {
    $this->AddParent('uDataBlocks_List');
    $this->AddParent('uDataBlocks_List','block_id','*');
  }

  public function RunModule() {
    $this->ShowData();
  }

  static function DrawBlock($id) {
    if (isset(self::$staticBlocks[$id])) return call_user_func(self::$staticBlocks[$id]);

    $obj = utopia::GetInstance('uDataBlocks');
    $rec = $obj->LookupRecord($id);
    if (!$rec) return NULL;

    if ($rec['module']) {
      // create module instance
      $instance = utopia::GetInstance($rec['module']);
	$instance->ClearFilters();

      // add filters
	  utopia::MergeVars($rec['filter']);
      $instance->extraHaving = $rec['filter'];

      // add Order
	  utopia::MergeVars($rec['order']);
      $instance->ordering = $rec['order'];

      // init limit
      $instance->limit = $rec['limit'];

      // get rows    
      $dataset = $instance->GetDataset(NULL);
      $rows = GetRows($dataset);
    } else $rows = array();
    
    $content = $append = $prepend = '';
    	
	$repeatable = $rec['content'];
	
	$html = str_get_html($repeatable);
	$ele = ($e = $html->find('#_ri',0)) ? $e->innertext : NULL;
	if (!$ele) $ele = ($e = $html->find('#_r',0)) ? $e->outertext : NULL;

	if ($ele) {
		// found a repeatable element
		// split content at this element. prepare for append and prepend.
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
		  $replace = $typeArr[$k] == 'u' ? UrlReadable($row[$field]) : $row[$field];
		  if ($rec['editable']) {
		    $obj = utopia::GetInstance($rec['module']);
		    $replace = $obj->GetCell($field,$row);
		  }
		  $c = str_replace($search,$replace,$c);
        }
        $content .= $c;
      }
    }
	    
	$ret = $append.$content.$prepend;
	while (utopia::MergeVars($ret));	
    return $ret;
  }

  public static $staticBlocks = array();
  public static function AddStaticBlock($ident,$callback) {
    self::$staticBlocks[$ident] = $callback;
  }
  public static function StaticBlockExists($id) {
    return isset(self::$staticBlocks[$id]);
  }
}

?>
