<?php

class tabledef_DataBlocks extends flexDb_TableDefinition {
  public $tablename = 'datablocks';
  public function SetupFields() {
    $this->AddField('block_id',ftVARCHAR,150);
    $this->AddField('module',ftTEXT);
    $this->AddField('content',ftTEXT);
    $this->AddField('filter',ftTEXT);
    $this->AddField('order',ftVARCHAR,30);
    $this->AddField('limit',ftVARCHAR,10);
    
    $this->AddField('display',ftTEXT);
    
    $this->SetPrimaryKey('block_id');
  }
}

class uDataBlocks_List extends flexDb_ListDataModule {
  public function GetTitle() { return 'Data Blocks'; }
  public function GetOptions() { return IS_ADMIN | ALLOW_DELETE | ALLOW_FILTER; }
  public function GetTabledef() { return 'tabledef_DataBlocks'; }
  public function SetupFields() {
    $this->CreateTable('blocks');
    $this->AddField('block_id','block_id','blocks','Block ID');
    $this->AddField('tabledef','tabledef','blocks','Table');
//    $this->AddField('where','where','blocks','Where');
//    $this->AddField('order','order','blocks','Order');
  }
  public function SetupParents() {
    $this->AddParent('internalmodule_Admin');
  }

  public function ParentLoad($parent) { }
  
  public function RunModule() {
    $this->ShowData();
  }
}

FlexDB::AddTemplateParser('block','uDataBlocks_Edit::DrawBlock');
class uDataBlocks_Edit extends flexDb_SingleDataModule {
  public function GetTitle() { return 'Edit Data Block'; }
  public function GetOptions() { return IS_ADMIN | ALLOW_DELETE | ALLOW_FILTER | ALLOW_EDIT | ALLOW_ADD; }
  public function GetTabledef() { return 'tabledef_DataBlocks'; }
  public function SetupFields() {
    $this->CreateTable('blocks');
    $this->AddField('block_id','block_id','blocks','Block ID',itTEXT);
    
    $installed = array();
    $classes = get_declared_classes();
    foreach ($classes as $classname){ // install tables
      if ($classname == 'flexDb_DataModule' || $classname == 'flexDb_ListDataModule' || $classname == 'flexDb_SingleDataModule' || !is_subclass_of($classname,'flexDb_DataModule')) continue;
      $installed[] = $classname;
    }
  
    $this->AddField('module','module','blocks','Data Source',itCOMBO,$installed);
    $this->AddField('filter','filter','blocks','Filter',itTEXT);
    $this->AddField('order','order','blocks','Order',itTEXT);
    $this->AddField('limit','limit','blocks','Limit',itTEXT);
    $this->AddField('fields',array($this,'getPossibleFields'),'blocks','Possible Fields');
    $this->AddField('content','content','blocks','Content',itHTML);
    $this->AddField('preview',array($this,'getPreview'),'blocks','Preview');
  }
  public function getPossibleFields($originalVal,$pk,$processedVal) {
    $rec = $this->LookupRecord($pk);
    $fields = GetModuleVar($rec['module'],'fields');
    $ret = '<div>Click a field to insert it.</div>';
    foreach ($fields as $field) {
      $ret .= "<span onclick=\"tinyMCE.execCommand('mceInsertContent',false,'{field.'+$(this).text()+'}');\" style=\"margin:0 5px\">{$field['alias']}</span>";
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

  public function ParentLoad($parent) { }
  public function RunModule() {
    $this->ShowData();
  }

  static function DrawBlock($id) {
    $rec = CallModuleFunc('uDataBlocks_Edit','LookupRecord',$id);
    if (!$rec) return NULL;
    
    // create module instance
    $instance = FlexDB::GetInstance($rec['module']);

    // add filters
    $instance->extraHaving = $rec['filter'];

    // add Order
    $instance->ordering = $rec['order'];

    // init limit
    $instance->limit = $rec['limit'];

    // get rows    
    $dataset = $instance->GetDataset(NULL);
    $rows = GetRows($dataset);
    
    $content = '';
    
    if (preg_match_all('/{field\.([^}]+)}/Ui',$rec['content'],$matches,PREG_PATTERN_ORDER)) {
      $searchArr = $matches[0];
      $varsArr = isset($matches[1]) ? $matches[1] : false;
      foreach ($rows as $row) {
        $c = $rec['content'];
        foreach ($searchArr as $k => $search) {
          $field = $varsArr[$k];
          if (!isset($row[$field])) continue;
          $c = str_replace($search,$row[$field],$c);
        }
        $content .= $c;
      }
    }
    
    return $content;
  } 
}

?>