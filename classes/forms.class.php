<?php

//-- debugging
//define('SHOW_QUERY'		,false);

//--  InputType
define('itNONE'		,'');
define('itBUTTON'	,'button');
define('itSUBMIT'	,'submit');
define('itRESET'	,'reset');
define('itCHECKBOX'	,'checkbox');
define('itOPTION'	,'option');

define('itMD5'		,'password');
define('itPASSWORD'	,'password');
define('itPLAINPASSWORD','plain_password');

define('itTEXT'		,'text');
define('itTEXTAREA'	,'textarea');
define('itSUGGEST'	,'suggest');
define('itSUGGESTAREA'	,'suggestarea');
define('itCOMBO'	,'combo');
define('itLISTBOX'	,'listbox');
define('itFILE'		,'file');
define('itDATE'		,'date');
define('itTIME'		,'time');
define('itDATETIME'	,'datetime');
define('itSCAN'		,'scan');
define('itCUSTOM'	,'~~custom~~');

//--  FilterCompareType
define('ctCUSTOM'   ,'{custom}');
define('ctANY'		,'');
define('ctEQ'		,'=');
define('ctNOTEQ'	,'!=');
define('ctLT'		,'<');
define('ctGT'		,'>');
define('ctLTEQ'		,'<=');
define('ctGTEQ'		,'>=');
define('ctLIKE'		,'LIKE');
define('ctNOTLIKE'	,'NOT LIKE');
define('ctIN'		,'IN');
define('ctIS'		,'IS');
define('ctISNOT'	,'IS NOT');
define('ctREGEX'	,'REGEXP');
define('ctBETWEEN'  ,'BETWEEN');

//--  Filter Sections
define('FILTER_HAVING'	,'having');
define('FILTER_WHERE'	,'where');

//--  Date Formats
// date
//	define("SQL_FORMAT_DATE"			,'%Y-%m-%d');
//	define("SQL_FORMAT_TIME"			,'%H:%i:%s');
//	define("SQL_FORMAT_DATETIME"		,SQL_FORMAT_DATE.' '.SQL_FORMAT_TIME);
//	define("SQL_FORMAT_EMPTY_DATE"		,'00/00/0000');
//	define("PHP_FORMAT_DATE"			,'d/m/Y'); // used with date(), but strftime() uses the SQL format
// timestamp
//	define("SQL_FORMAT_TIMESTAMP"		,'%d/%m/%Y %H:%i:%s');
//	define("SQL_FORMAT_EMPTY_TIMESTAMP"	,'00/00/0000 00:00:00');
//	define("PHP_FORMAT_TIMESTAMP"		,'d/m/Y H:i:s'); // used with date(), but strftime() uses the SQL format
// Javascript date/time regex:
// datetimeRegex = new RegExp("([0-9]{2})/([0-9]{2})/([0-9]{4})( ([0-9]{2}):([0-9]{2}):([0-9]{2})(\.[0-9]+)?)?");
// datetimeRegex = new RegExp("([0-9]{2})/([0-9]{2})/([0-9]{4}) ([0-9]{2}):([0-9]{2}):([0-9]{2})(\.[0-9]+)?|([0-9]{2})/([0-9]{2})/([0-9]{4})|([0-9]{2}):([0-9]{2}):([0-9]{2})(\.[0-9]+)?");
//	define('SQL_DATETIME_REGEX'			,"(([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})(\.[0-9]+)?)");
//	define('SQL_DATE_REGEX'				,"(([0-9]{4})-([0-9]{2})-([0-9]{2}))");
//	define('SQL_TIME_REGEX'			,"(([0-9]{2}):([0-9]{2}):([0-9]{2})(\.[0-9]+)?)");
//	define('DATETIME_REGEX'			,"((([0-9]{2})/([0-9]{2})/([0-9]{4}) ([0-9]{2}):([0-9]{2}):([0-9]{2})(\.[0-9]+)?)|(([0-9]{2})/([0-9]{2})/([0-9]{4}))|(([0-9]{2}):([0-9]{2}):([0-9]{2})(\.[0-9]+)?))");

// MODULE OPTIONS
define('ALWAYS_ACTIVE',flag_gen());
define('INSTALL_INACTIVE',flag_gen());
define('NO_HISTORY',flag_gen());
define('DISTINCT_ROWS',flag_gen());
define('ALLOW_FILTER',flag_gen());
define('ALLOW_EDIT',flag_gen());
define('ALLOW_ADD',flag_gen());
define('ALLOW_DELETE',flag_gen());
define('NO_NAV',flag_gen());
define('PERSISTENT_PARENT',flag_gen());
define('SHOW_FUNCTIONS',flag_gen());
define('SHOW_TOTALS',flag_gen());
define('LIST_HIDE_HEADER',flag_gen());

define('DEFAULT_OPTIONS',ALLOW_FILTER);

// START CLASSES

/**
 * Basic Utopia module. Enables use of adding parents and module to be installed and run.
 * No field or data access is available, use uDataModule and its decendants.
 */
abstract class uBasicModule implements iUtopiaModule {
/*  static $singleton = NULL;
  public static function &call($method) {
    $null = NULL;
    if (self::$singleton == NULL) { ErrorLog('Singleton not configured'); }//ErrorLog("Error Calling {$classname}->{$funcname}"); return $null;}
    if (!method_exists(self::$singleton,$method)) { return $null; }
        
    $stack = debug_backtrace();
    $args = array();
    if (isset($stack[0]["args"]))
      for($i=2; $i < count($stack[0]["args"]); $i++)
        $args[$i-2] = & $stack[0]["args"][$i];
    
    $call = array(self::$singleton,$funcname);
    $return = call_user_func_array($call,$args);
  
    return $return;
  }
	public static function __callStatic($name, $arguments) {
		// Note: value of $name is case sensitive.
		$instance = utopia::GetInstance(get_class($this));
		return call_user_func_array(array($instance,$name),$arguments);
	}*/

	public function GetOptions() { return DEFAULT_OPTIONS; }

	public function GetTitle() { return get_class($this); }
	public function GetDescription() {}
	public function GetKeywords() {}

	private $isSecurePage = false;
	public function SetSecure() {
		$this->isSecurePage = true;
	}

	private $isInitialised = false;
	public function Initialise() {
		if ($this->isInitialised === TRUE) return false;
		$this->isInitialised = true;

		// setup parents
		$this->_SetupParents();
		return true;
	}

	public $isDisabled = false;
	public function DisableModule($message='') {
		if (!$message) $message = true;
		$this->isDisabled = $message;
	}

	private $loaded = array();
	public function LoadChildren($loadpoint) {
		$class = get_class($this);
		$children = utopia::GetChildren($class);

		$keys = array_keys($children);
		$size = sizeof($keys);

		foreach ($children as $child) {
			foreach ($child as $info) {
				if (!isset($info['callback'])) continue;
				if ($info['loadpoint'] !== $loadpoint) continue;

				if (!isset($this->loaded[$info['moduleName']])) {
					$this->loaded[$info['moduleName']] = true;
					$obj = utopia::GetInstance($info['moduleName']);
					$result = $obj->LoadChildren(0);
					if ($result === FALSE) continue;
				}

				$result = call_user_func($info['callback'],$class);
				if ($result === FALSE) return FALSE;
			}
		}
		return true;
	}

	public $hasRun = false;
	public function _RunModule() {
		if (get_class($this) == utopia::GetCurrentModule()) {
			$url = $this->GetURL($_GET);
			$checkurl = $_SERVER['REQUEST_URI'];
			if (($this->isSecurePage && !utopia::IsRequestSecure()) || $checkurl !== $url) {
				$abs = '';
				if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') != $this->isSecurePage) {
					$layer = 'http';
					if ($this->isSecurePage) $layer .= 's';
					$abs = $layer.'://'.utopia::GetDomainName();
				}
				header('Location: '.$abs.$url,true,301); die();
			}
		}
		if ($this instanceof iAdminModule) utopia::UseTemplate(TEMPLATE_ADMIN);

		if ($this->isDisabled) { echo $this->isDisabled; return; }

		$lc = $this->LoadChildren(0);
		if ($lc !== TRUE && $lc !== NULL) return $lc;

		timer_start('Run Module');
		ob_start();
		$result = $this->RunModule();
		$c = ob_get_contents();
		ob_end_clean();
		timer_end('Run Module');
		if (utopia::UsingTemplate()) $c = '<div class="'.get_class($this).'">'.$c.'</div>';
		echo $c;
		if ($result === FALSE) return false;
		$this->hasRun = true;

		$lc = $this->LoadChildren(1);
		if ($lc !== TRUE && $lc !== NULL) return $lc;
	}

	public $parentsAreSetup = false;
	public abstract function SetupParents();
	public function _SetupParents() {
		if ($this->parentsAreSetup) return;
		$this->SetupParents();
		$this->parentsAreSetup = true;
	}

	public $parents = array();
	public function HasParent($parentModule) {
		$children = utopia::GetChildren($parentModule);
		return array_key_exists(get_class($this),$children);
	}
	public function HasChild($childModule) {
		$children = utopia::GetChildren(get_class($this));
		return array_key_exists($childModule,$children);
	}

	public function AddParentCallback($parentModule,$callback,$loadpoint=1) {
		$info = array('moduleName'=>get_class($this), 'callback' => $callback, 'loadpoint' => $loadpoint);
		utopia::AddChild($parentModule,get_class($this),$info);
	}
	public function AddChildCallback($child,$callback,$loadpoint=1) {
		$info = array('moduleName'=>get_class($this), 'callback' => $callback, 'loadpoint' => $loadpoint);
		utopia::AddChild(get_class($this),$child,$info);
	}

	// parentModule =
	// sourceField = the field on the parent which the filter is taken from (generally the PK value of the current module)
	// destinationField = the field on the current module which the filter is set to.
	// parentField = the field which will be "linked from", if null or empty, a child button is created. allows easy injection to list forms.
	// EG:  'client list', array('client id'=>'client id'), 'client name' : applies a link from the "client name" field on the "client list" to open the current form where "client id" = "client id"
	// EG:  'client list', 'title', 'title id', 'title' : applies a link from the "title" field on the "client list" to open the current form where "title id" = "title"
	// EG:  'client form', 'client id', 'client id' : creates a button on the "client form" page to open the current form and where "client id" = "client id"

	// TODO: AddParent - overriden in data module
	// suggested change:  sourceField, destinationField become an array of "filters", eg: array("title" => "title id")
	/**
	 * Add a parent of this module, linked by a field.
	 *
	 * @param string $parentModule The classname of the parent
	 * @param string|array optional $fieldLinks
	 * @param string optional $parentField
	 * @param string optional $text
	 */
	public function AddParent($parentModule,$fieldLinks=NULL,$parentField=NULL,$text=NULL) {
		if (is_string($fieldLinks)) $fieldLinks = array(array('fromField'=>$fieldLinks,'toField'=>$fieldLinks,'ct'=>ctEQ));
		if (is_array($fieldLinks) && !array_key_exists(0,$fieldLinks)) {
			if (array_key_exists('fromField',$fieldLinks) && array_key_exists('toField',$fieldLinks)) {
				$fieldLinks = array($fieldLinks);
			} else {
				// change from array(from>to) to new format
				$newFL = array();
				foreach ($fieldLinks as $from => $to) {
					$newFL[] = array('fromField'=>$from,'toField'=>$to,'ct'=>ctEQ);
				}
				$fieldLinks = $newFL;
			}
		}

		if (is_array($fieldLinks)) {
			foreach ($fieldLinks as &$linkInfo) {
				if (empty($linkInfo['ct'])) $linkInfo['ct'] = ctEQ;
				if (is_subclass_of($this,'uDataModule')) {
					$fltr =& $this->FindFilter($linkInfo['toField'],$linkInfo['ct'],itNONE,FILTER_WHERE);
					if ($fltr === NULL) {
						$fltr =& $this->AddFilterWhere($linkInfo['toField'],$linkInfo['ct']);
						$uid = $fltr['uid'];
						//$fltr =& $this->GetFilterInfo($uid);
					} else $uid = $fltr['uid'];
					$fltr['linkFrom'] = $parentModule.':'.$linkInfo['fromField'];
					$linkInfo['_toField'] = $linkInfo['toField'];
					$linkInfo['toField'] = $uid;
				}
				//				if (is_numeric($key)) {
				//					unset($fieldLinks[$key]);
				//					$fieldLinks[$val] = $val;
			}	}//	}

	/*	if (utopia::GetCurrentModule() == get_class($this)) {
			if ($parentModule === '/') $pm = utopia::GetCurrentModule();
			else $pm = $parentModule;
			$filters = NULL;
			if ($fieldLinks && !$parentField) {
				$filters = array();
				foreach ($fieldLinks as $link) {
					//print_r($link);
					if (array_key_exists('_f_'.$link['toField'],$_GET))
						$filters[$link['fromField']] = $_GET['_f_'.$link['toField']];
				}
			}
			breadcrumb::AddModule($pm,$filters,0,utopia::GetCurrentModule());
		}*/

		//if (!array_key_exists('children',$GLOBALS)) $GLOBALS['children'] = array();
/*		$children = utopia::GetChildren($parentModule);
		// check parent field hasnt already been selected
		if ($parentField !== NULL && $parentField !== '*') {
			//if (array_key_exists($parentModule,$children))
			foreach ($children as $childName => $links) {
				foreach ($links as $link) {
					//if ($child['moduleName'] !== get_class($this)) continue;
					if (!isset($link['parentField'])) continue;
					if ($link['parentField'] == $parentField) {
						//trigger_error('Cannot add parent ('.$parentModule.') of '.get_class($this).', parentField ('.$parentField.') has already been defined in '.$child['moduleName'].'.',E_USER_ERROR);
						return;
					}
				}
			}
		}
*/

		if (!is_null($fieldLinks) && !is_array($fieldLinks)) // must be null or array
			trigger_error('Cannot add parent ('.$parentModule.') of '.get_class($this).', fieldLinks parameter is an invalid type.',E_USER_ERROR);

		$info = array('moduleName'=>get_class($this), 'parentField'=>$parentField, 'fieldLinks' => $fieldLinks, 'text' => $text);
		$this->parents[$parentModule][] = $info;
		utopia::AddChild($parentModule, get_class($this), $info);

		return $fieldLinks;
	}

	public function AddChild($childModule,$fieldLinks=NULL,$parentField=NULL,$text=NULL) {
		//$childModule = (string)$childModule;
		//echo "addchild $childModule<br/>";
		$obj = utopia::GetInstance($childModule);
		$obj->AddParent(get_class($this),$fieldLinks,$parentField,$text);
	}

	/**
	 * Register this module to receive Ajax calls. Normal execution will be terminated in order to process the specified callback function.
	 *
	 * @param string $ajaxIdent
	 * @param string  $callback
	 * @param (bool|function) $requireLogin Either True or False for admin login, or a custom callback function.
	 * @return bool
	 */
	public function RegisterAjax($ajaxIdent, $callback, $requireAdmin = null) {
		if (!array_key_exists('ajax',$GLOBALS)) $GLOBALS['ajax'] = array();
		if (array_key_exists($ajaxIdent,$GLOBALS['ajax'])) {
			//ErrorLog(get_class($this)." cannot register ajax identifier '$ajaxIdent' because it is already registered.");
			return FALSE;
		}

		$GLOBALS['ajax'][$ajaxIdent]['callback'] = $callback;
		$GLOBALS['ajax'][$ajaxIdent]['class'] = get_class($this);
		if ($requireAdmin === NULL)
		$requireAdmin = $this instanceof iAdminModule;
		$GLOBALS['ajax'][$ajaxIdent]['req_admin'] = $requireAdmin;
		return true;
	}

	public function GetVar($varname) {
		return $this->$varname;
	}
	public function GetUUID() {
		$uuid = preg_replace('((.{8})(.{4})(.{4})(.{4})(.+))','$1-$2-$3-$4-$5',md5(get_class($this)));
		return $uuid;
	}
  private $mID = NULL;
  public function GetModuleId() {
    if ($this->mID !== NULL) return $this->mID;
    $m = utopia::ModuleExists(get_class($this));
    $this->mID = $m['module_id'];
    return $this->mID;
  }
  
	public $rewriteMapping=NULL;
	public $rewriteURLReadable=NULL;
	public $rewritePersistPath=FALSE;
	public function HasRewrite() { return $this->rewriteMapping !== NULL; }

	
	/**
	 * Indicate that this module should rewrite its URL
	 * @param mixed $mapping NULL to turn off rewriting. FALSE to strip all but uuid. TRUE to allow longer path.  ARRAY( '{fieldName}' , 'some-random-text' , '{another_field}' )
	 * @param bool $URLReadable specifies that all segments of the url should be stripped of non-alphanumeric characters.
	 */
	public function SetRewrite($mapping,$URLReadable = false) {
		if (getenv('HTTP_MOD_REWRITE')!='On') return false;
		if ($mapping === NULL) {
			$this->rewriteMapping = NULL; return;
		}
		if (is_string($mapping) && $mapping !== '') $mapping = array($mapping);
		if ($mapping === true) $this->rewritePersistPath = true;
		if (!is_array($mapping)) $mapping = array();
		array_unshift($mapping,'{uuid}');

		// defaults?

		$this->rewriteMapping = $mapping;
		$this->rewriteURLReadable = $URLReadable;

		$this->ParseRewrite();
	}

	public function ParseRewrite($caseSensative = false) {
		if ($this->rewriteMapping === NULL) return FALSE;
		if (get_class($this) !== utopia::GetCurrentModule()) return FALSE;

		$sections = utopia::GetRewriteSections();
		if (!$sections) return FALSE;

		foreach ($sections as $key => $value) {
			$replace = array();
			if (!array_key_exists($key,$this->rewriteMapping)) continue;
			$map = $this->rewriteMapping[$key];
			// generate preg for section
			if (preg_match_all('/{([a-zA-Z0-9_]+)}/',$map,$matches)) {
				foreach ($matches[1] as $match) {
					$map = str_replace('{'.$match.'}','(.+)',$map);
					$replace[] = $match;
				}
			}

			if (preg_match('/'.$map.'/',$value,$matches)) {
				unset($matches[0]);
				foreach($matches as $key => $match) {
				//	if ($match[0] == '{') continue;
					$return[$replace[$key-1]] = $match;
				}
			}
		}

		// TODO: named filters not being picked up
		$_GET = array_merge($_GET,$return);
		$_REQUEST = array_merge($_REQUEST,$return);
		return $return;
	}

	public function RewriteURL(&$filters) {
		$mapped = $this->rewriteMapping;
		foreach ($mapped as $key => $val) {
			if (preg_match_all('/{([a-zA-Z_]+)}/',$val,$matches)) {
				foreach ($matches[1] as $fieldName) {
					$newVal = '';
					if (array_key_exists($fieldName,$filters)) $newVal = $filters[$fieldName];
					elseif (array_key_exists('_f_'.$fieldName,$filters)) $newVal = $filters['_f_'.$fieldName];

					unset($filters[$fieldName]);
					unset($filters['_f_'.$fieldName]);

					$mapped[$key] = str_replace('{'.$fieldName.'}',$newVal,$mapped[$key]);
				}
			}
		}

		foreach ($mapped as $key => $val) {
			$URLreadable = is_array($this->rewriteURLReadable) ? $this->rewriteURLReadable[$key] : $this->rewriteURLReadable;
			$mapped[$key] = ($URLreadable) ? urlencode(UrlReadable($val)) : urlencode($val);
		}

		if (isset($filters['uuid'])) unset($filters['uuid']);
		$uuid = $this->GetUUID(); if (is_array($uuid)) $uuid = reset($uuid);

		$newPath = PATH_REL_ROOT.join('/',$mapped);
		$oldPath = parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);

                if ($this->rewritePersistPath && utopia::GetCurrentModule() == get_class($this)) $newPath .= str_replace($newPath,'',$oldPath);

		// DONE: ensure all rewrite segments are accounted for (all '/' are present)
		return $newPath;
	}

	public function GetURL($filters = NULL, $encodeAmp = false) {
		if (!is_array($filters)) $filters = array();

		$uuid = $this->GetUUID(); if (is_array($uuid)) $uuid = reset($uuid);
		$filters['uuid'] = $uuid;
		if (isset($filters['uuid']) && $filters['uuid'] !== $uuid) {
			$m = utopia::UUIDExists($filters['uuid']);
			if ($m) {
				$obj = utopia::GetInstance($m['module_name']);
				return $obj->GetURL($filters);
			}
		}

		$url = DEFAULT_FILE;
		if ($this->rewriteMapping !== NULL)
			$url = $this->RewriteURL($filters);

		return BuildQueryString($url,$filters,$encodeAmp);
	}
	public function IsInstalled() {
		//check if its installed in the db
		//$result = sql_query("SELECT * FROM internal_modules WHERE (`module_name` = '". get_class($this) ."')");
		return utopia::ModuleExists(get_class($this));
		//$row = $GLOBALS['modules'][get_class($this)];
		//$row = GetRow($result);
		//if ($row !== NULL) return $row;
		//return false;
	}
	public function InstallModule() {
		// module should be installed
		// create a new record in "db_modules" - with UUID, module_parent, tablename, title
		$uuids = $this->GetUUID();
		if (!is_array($uuids)) $uuids = array($uuids);
		foreach ($uuids as $uuid) {
			//echo $uuid.'<br/>';
			$row = utopia::UUIDExists($uuid);
			if ($row === FALSE) {
				//echo "not installed:".get_class($this);
				//if (($row = $this->IsInstalled()) == FALSE) {
				//DebugMail('not installed',get_class($this));
				$active = flag_is_set($this->GetOptions(),INSTALL_INACTIVE) ? '0' : '1';
				sql_query("INSERT INTO internal_modules (`uuid`,`module_name`,`module_active`) VALUES ('$uuid','". get_class($this) ."','$active')",false);
			} else {
				//			$qry = "UPDATE db_modules SET `module_parent` = '".$this->module_parent."', `module_name` = '". get_class($this) ."'"; // removed because parent no longer used
				$qry = "UPDATE internal_modules SET `uuid` = '".$uuid."', `module_name` = '". get_class($this) ."', `sort_order` = '".$this->GetSortOrder()."'";
				if (flag_is_set($this->GetOptions(),ALWAYS_ACTIVE))
					$qry .= ", `module_active` = '1'";
				//			else
				//				$qry .= ", `module_active` = '0'";

				//echo "$qry WHERE `module_name` = '{$row['module_name']}'\n";
				sql_query("$qry WHERE `uuid` = '{$row['uuid']}'");
			}
		}
		//		$GLOBALS['modules'][$this->GetUUID()] = get_class($this);
	}

	public function GetFileFromTable($field,$table,$key,$pkVal,$att = 'inline') {
		return PATH_REL_CORE."index.php?__ajax=getFile&f=$field&t=$table&k=$key&p=$pkVal&a=$att";
	}

	public function GetImageLinkFromTable($field,$table,$key,$pkVal,$width=NULL,$height=NULL) {
		if ($width) $width = "&w=$width";
		if ($height) $height = "&h=$height";
		return PATH_REL_CORE."index.php?__ajax=getImage&f=$field&t=$table&k=$key&p=$pkVal$width$height";
	}

	public function DrawImageFromTable($field,$table,$key,$pkVal,$width=NULL,$height=NULL,$attr=NULL,$link=false,$linkW=NULL,$linkH=NULL,$linkAttr=NULL) {
		if (!is_array($attr)) $attr = array();
		if (!array_key_exists('alt',$attr)) $attr['alt'] = '';
		$attr['width'] = $width; $attr['height'] = $height;
		$attr = BuildAttrString($attr);

		$url = $this->GetImageLinkFromTable($field,$table,$key,$pkVal,$width,$height);
		if (!$link) return "<img$attr src=\"$url\">";

		if ($link === TRUE) $linkUrl = $this->GetImageLinkFromTable($field,$table,$key,$pkVal,$linkW,$linkH);
		else $linkUrl = $link;

		$linkAttr = BuildAttrString($linkAttr);
		return "<a$linkAttr href=\"$linkUrl\" target=\"_blank\"><img$attr src=\"$url\"></a>";
	}

        public function GetImageLink($fieldAlias,$pkVal,$width=NULL,$height=NULL) {
                if ($pkVal == NULL) return '';
                $field = $this->GetFieldProperty($fieldAlias ,'field');
                $setup = $this->sqlTableSetupFlat[$this->GetFieldProperty($fieldAlias,'tablename')];

                $table = $setup['table'];
                $key = $setup['pk'];
		return $this->GetImageLinkFromTable($field,$table,$key,$pkVal,$width,$height);
        }

	public function DrawSqlImage($fieldAlias,$rec,$width=NULL,$height=NULL,$attr=NULL,$link=FALSE,$linkAttr=NULL) {
		if ($rec == NULL) return '';
		$field = $this->GetFieldProperty($fieldAlias ,'field');
		$tableAlias = $this->GetFieldProperty($fieldAlias ,'tablename');
		$setup = $this->GetFieldProperty($fieldAlias ,'vtable');
		$table = $setup['table'];

		$key = $this->GetPrimaryKey($fieldAlias);
		$pkVal = $table !== $this->GetPrimaryTable() ? $rec['_'.$tableAlias.'_pk'] : $rec[$key];

		return $this->DrawImageFromTable($field,$table,$key,$pkVal,$width,$height,$attr,$link,NULL,NULL,$linkAttr);
	}

	public function HookEvent($eventName,$funcName) {
		$GLOBALS['events'][$eventName][] = get_class($this).".$funcName";
	}

	public function GetSortOrder() {
		//if (is_object($module)) $module = get_class($module);
//		if (get_class($this) == utopia::GetCurrentModule()) return 1;
		return 10000;
	}
	//	public function __construct() { $this->_SetupFields(); } //$this->SetupParents(); }
	public abstract function RunModule();  // called when current_path = parent_path/<module_name>/


	public function CreateParentNavButtons($parentName) {
//		if ($this->navCreated) return;
		//ErrorLog(get_class($this).' making buttons on '.$parentName);
		if ($this->isDisabled) return;
		if (!is_array($this->parents)) return;
		if ($parentName !== utopia::GetCurrentModule()) return;
		if (!array_key_exists($parentName,$this->parents)) return;

//		$this->navCreated = true;
		$sortOrder = $this->GetSortOrder();
		$listDestination =  'child_buttons';
		if ($this instanceof iAdminModule) {
			if (get_class($this) == utopia::GetCurrentModule()) utopia::LinkList_Add($listDestination,'',NULL,-500);
			$sortOrder = $sortOrder - 1000;
		}

			if ($parentName == '/') return;
	//	$lm = utopia::GetVar('loadedModules',array());
	//	foreach ($this->parents as $parentName => $linkArray) {
//			$parentName = $linkArray['moduleName'];
			if (flag_is_set($this->GetOptions(),NO_NAV)) return;
	//		if (array_search($this,$lm,true) === FALSE) continue;

			$cModuleObj = utopia::GetInstance(utopia::GetCurrentModule());
			if (($parentName != 'uDashboard' && ($obj instanceof iAdminModule)) && $parentName != utopia::GetCurrentModule()) return;
			//echo get_class($this).' '.$parentName.'<br/>';

			$parentObj = utopia::GetInstance($parentName);
			if (($parentObj instanceof iAdminModule) && !($cModuleObj instanceof iAdminModule)) return;

			$linkArray = $this->parents[$parentName];
			foreach ($linkArray as $linkInfo) {
				if ($linkInfo['parentField'] !== NULL) continue; // has a parentField?  if so, ignore
				$btnText = !empty($linkInfo['text']) ? $linkInfo['text'] : $this->GetTitle();
				if (isset($linkInfo['fieldLinks']) && utopia::GetCurrentModule()) { // is linked to fields in the list
					$cr = $cModuleObj->GetCurrentRecord();
					if (is_array($linkInfo['fieldLinks']) && is_array($cr)) { // this link uses filters
						$filters = array();
						/*foreach ($linkInfo['fieldLinks'] as $fromField => $toField) {
							if ($this->GetFilterValue($toField)) // if existing record --
							$filters["filters[$toField]"] = $this->GetFilterValue($toField);
							}*/
						//print_r($linkInfo['fieldLinks']);
						foreach ($linkInfo['fieldLinks'] as $li) {
							//if ($this->GetFilterValue($this->FindFilter($li['fromField'],$li['ct']))) // if existing record --
							if (array_key_exists($li['fromField'],$cr))
							$filters["_f_".$li['toField']] = $cr[$li['fromField']];
						}
						utopia::LinkList_Add($listDestination,$btnText,$this->GetURL($filters),$sortOrder,NULL,array('class'=>'btn'));
						//echo "<a id=\"fhtest\" href=\"".BuildQueryString($this->GetURL(),$filters)."\" class=\"draggable {tabTitle:'$btnText', tabPosition:'".$GLOBALS['modules'][get_class($this)]['sort_order']."'}\">$btnText</a>";
						//		utopia::AppendVar('child_buttons',CreateNavButton($linkInfo['text'],BuildQueryString($this->GetURL(),$filters)));
					}
				} else { // not linked to fields (so no filters)
					utopia::LinkList_Add($listDestination,$btnText,$this->GetURL(),$sortOrder,NULL,array('class'=>'btn'));
					//	utopia::AppendVar('child_buttons',CreateNavButton($linkInfo['text'],$this->GetURL()));
				}
			}
		//}
	}
}

/**
 * Abstract class extending the basic module, adding data access and filtering.
 *
 */
abstract class uDataModule extends uBasicModule {
	public $fields = array();
	public $filters = array(FILTER_WHERE=>array(),FILTER_HAVING=>array());
	public $pk = NULL;
	public $pt = NULL;
	public $sqlTableSetupFlat = NULL;
	public $sqlTableSetup = NULL;
	public $dataset = NULL;
	public $currentRecord = NULL;

	public $hasEditableFilters = FALSE;

	public abstract function GetTabledef();

	public abstract function SetupFields();
	//public abstract function ShowData();//$customFilter = NULL);

	private $makeSortable = array();
	public function MakeSortable($updateField,$selector) {
		$this->makeSortable[] = array($selector,$updateField);
	}

	public $fieldsSetup = FALSE;
	public function _SetupFields() {
		if ($this->fieldsSetup == TRUE) return;
		$this->fieldsSetup = TRUE;

		$this->SetupFields();
		$this->SetupUnionFields();
		if (is_array($this->UnionModules)) foreach ($this->UnionModules as $modulename) {
			$obj = utopia::GetInstance($modulename);
			$obj->_SetupFields();
		}
	}

	public function ParseRewrite($caseSensative = false) {
		$parsed = parent::ParseRewrite($caseSensative);
		if (!$parsed) return FALSE;
		foreach ($parsed as $key => $val) {
			// check for filter with key
			$filter =& $this->FindFilter($key);
			if ($filter) $filter['value'] = $val;
		}
		return $parsed;
	}
	/*	public function RewriteURL($filters) {
		foreach ($filters as $key => $val) {
		$fltr = $this->FindFilter($key);
		if ($fltr) {
		$filters['_f_'.$fltr['uid']] = $val;
		unset($filters[$key]);
		}
		}
		//print_r($filters);
		return parent::RewriteURL($filters);
		}*/
	public function GetURL($filters = NULL, $encodeAmp = false) {
		$this->_SetupParents();
		$this->_SetupFields();
		if (!is_array($filters) && $filters !== NULL) $filters = array($this->GetPrimaryKey()=>$filters);

		if ($this->HasRewrite() && is_array($filters) && array_key_exists($this->GetPrimaryKey(), $filters)) {
			$fields = array();
			foreach ($this->rewriteMapping as $seg) {
				if (preg_match_all('/{([a-zA-Z0-9_]+)}/',$seg,$matches)) {
					foreach ($matches[1] as $match) {
						if (array_key_exists($match,$this->fields) && !array_key_exists($match,$filters)) $fields[] = $match;
					}
				}
			}
			array_unique($fields);
			if ($fields) {
				$rec = $this->LookupRecord($filters);
				//print_r($rec);
				foreach ($fields as $field) $filters[$field] = $rec[$field];
			}
		}

		$filArr = array();
		foreach ($this->filters as $filterType) {
			foreach ($filterType as $filterSet) {
				foreach ($filterSet as $filter) {
					$val = $this->GetFilterValue($filter['uid']);
					//echo $val;
					//print_r($filter);
					if (!empty($filter['default']) && $val == $filter['default']) {
						//echo 'is_default: '.$filter['fieldName'];
						unset($filters[$filter['fieldName']]);
						continue;
					}
					//print_r($filter);
					if (is_array($filters) && array_key_exists($filter['fieldName'],$filters)) {
						$filArr['_f_'.$filter['uid']] = $filters[$filter['fieldName']];
						unset($filters[$filter['fieldName']]);
						continue;
					}
					//if (!empty($filter['default'])) continue;
		//			$val = $this->GetFilterValue($filter['uid']);
					//ErrorLog($val);
				//	$isNew = is_array($filters) && array_key_exists($this->GetModuleId().'_new',$filters);
				//	if (!empty($val) && !($filter['fieldName'] == $this->GetPrimaryKey() && $isNew))
				//		$filArr['_f_'.$filter['uid']] = $val;
				}
			}
		}

		// TODO: remove 'if' if rewrite not working
		if ($this->HasRewrite()) foreach ($filArr as $uid => $val) {
			$fltr = $this->GetFilterInfo(substr($uid,3));
			$filArr[$fltr['fieldName']] = $val;
			unset($filArr[$uid]);
		}

		if (is_array($filters)) {
			//print_r($filters);
			foreach ($filters as $fieldName => $val) {
				//	$filter = $this->FindFilter($fieldName);
				//	if ($filter)
				//		$filArr['_f_'.$filter['uid']]=$val;
				//	else
				$filArr[$fieldName] = $val;
			}
		}
//print_r($filArr);
		return parent::GetURL($filArr,$encodeAmp);
		//return BuildQueryString($url,$filArr);
		/*		if (empty($filArr)) return $url;

		if (strpos($return,'?') === FALSE)
		$return = "$return?$filters";
		else
		$return = "$return&$filters";

		return $return;*/
	}

	public function Initialise() {
		if (!parent::Initialise()) return false;
		$this->_SetupFields();
		return true;
	}

	public $forceNewRec = false;
	public function ForceNewRecord() {
		$this->forceNewRec = true;
	}

	public function IsNewRecord() {
		if ($this->forceNewRec === TRUE) return true;
		if (isset($_REQUEST[$this->GetModuleId().'_new'])) return true;
		return false;
	}

	public function EnforceNewRec() {
		return;
	}

	public function _RunModule() {
		parent::_RunModule();
		if ($this->makeSortable) {
			$classname = get_class($this);
			foreach ($this->makeSortable as $sortable) {
				list($selector,$updateField) = $sortable;
				echo <<<FIN
<script language="javascript">
$('.$classname $selector').sortable({
	update: function (event,ui) {
		var parent = $(this);
		$(parent).children().each(function (i) {
			var pk = $(this).attr('rel');
			if (!pk) return;
			var b = Base64.encode('$classname:$updateField('+pk+')');
			while (b.substr(-1,1) == '=') {
				b = b.substr(0,b.length-1);
			}
			uf('sql[add]['+b+']',i);
		});
	}
});
</script>
FIN;
			}
		}
	}

	public function GetEncodedFieldName($field,$pkValue=NULL) {
		$pk = is_null($pkValue) ? '' : "($pkValue)";
		return cbase64_encode(get_class($this).":$field$pk");
	}

	public function CreateSqlField($field,$pkValue,$prefix=NULL) {
		if ($prefix == NULL) $prefix = 'add';
		return "sql[{$prefix}][".$this->GetEncodedFieldName($field,$pkValue)."]";
	}

  
  public function GetDeleteButton($pk,$btnText = NULL,$title = NULL) {
    $title = $title ? "Delete '$title'" : 'Delete Record';
    if ($btnText)
      return '<a name="'.$this->CreateSqlField('del',$pk,'del').'" class="btn redbg" onclick="if (confirm(\'Are you sure you wish to delete this record?\')) uf(this); return false;" title="'.$title.'">'.$btnText.'</a>';
    return '<a class="btn btn-del" name="'.$this->CreateSqlField('del',$pk,'del').'" href="#" onclick="if (confirm(\'Are you sure you wish to delete this record?\')) uf(this); return false;" title="'.$title.'"></a>';
  }
//	public function CreateSqlDeleteField($where) {
//		$prefix = 'del';
//		return "sql[{$prefix}][".cbase64_encode(get_class($this).":".$this->GetPrimaryTable()."($where)")."]";
//	}

	public function DrawSqlInput($field,$defaultValue='',$pkValue=NULL,$attributes=NULL,$inputTypeOverride=NULL,$valuesOverride=NULL) {
		if ($attributes==NULL) $attributes = array();
		if (isset($this->fields[$field]['attr']))
			$attributes = array_merge($this->fields[$field]['attr'],$attributes);
		$inputType = $inputTypeOverride ? $inputTypeOverride : $this->fields[$field]['inputtype'];
		$length = $this->GetFieldProperty($field,'length') ? $this->GetFieldProperty($field,'length') : $this->GetTableProperty($field,'length');
		$values = $valuesOverride ? $valuesOverride : $this->GetValues($field);

		$prefix = NULL;

		switch ($inputType) {
			case itMD5:
			case itPASSWORD:	$prefix = 'md5';
			case itTEXT:
			case itSUGGEST:
				//if (is_numeric($length) && $length > 0) $attributes['size'] = floor($length*0.75);
				break;
				//case itRICHTEXT:
			case itTEXTAREA:
			case itSUGGESTAREA:
				//$attributes['style'] = "width:100%";
				//if (is_numeric($length) && $length > 0) { $attributes['cols'] = floor($length*0.12); $attributes['rows'] = floor($length*0.01); }
				break;
			case itFILE:		$prefix = 'file'; break;
			break;
		}

		// its a suggest, so lv should be information to lookup with ajax
		// cannot place in switch due to spliting of shared properties (size/cols+rows)

		if ($inputType == itSUGGEST || $inputType == itSUGGESTAREA)
		$values = cbase64_encode(get_class($this).':'.$field);
		//		else // dont want to set onchange for suggestions
		//if (!array_key_exists('onchange',$attributes)) $attributes['onchange']='uf(this);';

		// styles
		$attributes['style'] = $this->FieldStyles_Get($field,$defaultValue);

		if (!array_key_exists('class',$attributes)) $attributes['class'] = '';
		$attributes['class'] .= ' uf';

		$fieldName = $this->CreateSqlField($field,$pkValue,$prefix);
		if ($inputType == itFILE) $attributes['id'] = $fieldName;
		return utopia::DrawInput($fieldName,$inputType,$defaultValue,$values,$attributes);
	}

	public function GetPrimaryKeyField($fieldAlias=NULL) {
		if (!is_null($fieldAlias)) {
			return '_'.$this->GetFieldProperty($fieldAlias,'tablename').'_pk';
		}
		return '_'.$this->sqlTableSetup['alias'].'_pk';
	}
	public function GetPrimaryKey($fieldAlias=NULL) {
		if (!is_null($fieldAlias)) {
			$setup = $this->sqlTableSetupFlat[$this->GetFieldProperty($fieldAlias,'tablename')];
			return $setup['pk'];
		}
		if ($this->pk == NULL && $this->GetTabledef() != NULL) {
			$obj = utopia::GetInstance($this->GetTabledef());
			$this->pk = $obj->GetPrimaryKey();
		}
		return $this->pk;
	}

	public function GetPrimaryTable($fieldAlias=NULL) {
		if (!is_null($fieldAlias)) {
			$setup = $this->sqlTableSetupFlat[$this->GetFieldProperty($fieldAlias,'tablename')];
			return $setup['table'];
		}
		if ($this->pt == NULL)
		$this->pt = TABLE_PREFIX.$this->GetTabledef();
		return $this->pt;
	}

	public $UnionModules = NULL;
	public $UNION_MODULE = FALSE;
	public function AddUnionModule($modulename) {
		// check fields match! Union modules MUST have identical fields
		$this->UNION_MODULE = TRUE;
		if ($this->UnionModules == NULL) {
			$this->UnionModules = array();
			//			$this->_SetupFields();
			//			$this->AddField('__module__',"'".get_class($this)."'",'');
			//			$tbl = is_array($this->sqlTableSetup) ? $this->sqlTableSetup['alias'] : '';
			//			$this->AddField('__module_pk__',$this->GetPrimaryKey(),$tbl);
		}

		$this->UnionModules[] = $modulename;
		$obj = utopia::GetInstance($modulename);
		$obj->UNION_MODULE = TRUE;
	}

	public function AddUnionParent($parentModule) {
		$obj = utopia::GetInstance($parentModule);
		$obj->AddUnionModule(get_class($this));
	}

	public function SetupUnionFields() {
		if ($this->UNION_MODULE !== TRUE) return;
		$this->AddField('__module__',"'".get_class($this)."'",'');
		$tbl = is_array($this->sqlTableSetup) ? $this->sqlTableSetup['alias'] : '';
		$this->AddField('__module_pk__',$this->GetPrimaryKey(),$tbl);
	}

	// this value will be used on new records or field updates
	public function SetDefaultValue($name,$moduleOrValue,$getField=NULL,$valField=NULL,$onlyIfNull=FALSE) { //,$whereField=NULL,$rowField=NULL) {
		if ($getField==NULL || $valField==NULL) {
			$this->SetFieldProperty($name,'default_value',$moduleOrValue);
		} else {
			$this->SetFieldProperty($name,'default_lookup',array('module'=>$moduleOrValue,'getField'=>$getField,'valField'=>$valField));
			// create a callback, when valField is updated, to set value of $name to the new DefaultValue (IF that value is empty?)
			if (!array_key_exists($valField,$this->fields) && get_class($this) != utopia::GetCurrentModule() && utopia::GetCurrentModule()) {
				$obj = utopia::GetInstance(utopia::GetCurrentModule());
				$obj->AddOnUpdateCallback($valField,array($this,'RefreshDefaultValue'),$name,$onlyIfNull);
			} else
				$this->AddOnUpdateCallback($valField,array($this,'RefreshDefaultValue'),$name,$onlyIfNull);
		}
	}

	protected function RefreshDefaultValue($pkVal, $fieldName,$onlyIfNull) {
		AjaxEcho("// RefreshDefaultValue($fieldName,$onlyIfNull)\n");
		$row = $this->LookupRecord($pkVal);
		if ($row == NULL) return;
		if ($onlyIfNull && !empty($row[$fieldName])) return;
		//$pkVal = $row[$this->GetPrimaryKey()];
		$dVal = $this->GetDefaultValue($fieldName);
		//		echo "update $fieldName on $pkVal to:  $dVal";
		$this->UpdateField($fieldName,$dVal,$pkVal);
	}

	public function GetDefaultValue($name) {
		//		echo "GetDefaultValue($name)";
		// default value should take implicit value as priority over filter value?
		// NO implicit value takes priority
		// is d_v set?
		$dv = $this->GetFieldProperty($name,'default_value');
		if (!empty($dv)) return $dv;

		// is d_l set?  lookup value... lookup field from module,,, what about WHERE?
		$dl = $this->GetFieldProperty($name,'default_lookup');
		if (!empty($dl)) {
			// find the value of valField
			$row = $this->GetCurrentRecord();
			$lookupVal = $this->GetRealValue($dl['valField'],$row[$this->GetPrimaryKey()]);
			$obj = utopia::GetInstance($dl['module']);
			$value = $obj->GetRealValue($dl['getField'],$lookupVal);

			return $value; //  process so only to return a string.... TO DO
		}

		// is filter set?
		//print_r($this->filters);
		//AjaxEcho("//no predifined default set, searching filters");
		$fltr = $this->FindFilter($name,ctEQ);
		if ($fltr === NULL) return '';

		$uid = $fltr['uid'];
		$value = $this->GetFilterValue($uid);
		if (!empty($value)) return $value;

		return '';
		// not processed
		/*		foreach ($this->filters[FILTER_WHERE] as $filterset) {
			if (!array_key_exists($name,$filterset))continue;
			$filterData = $filterset[$name];
			if (empty($filterData)) continue;
			if ($filterData['ct'] != ctEQ) continue;
			return $filterData['value'];
			}

			foreach ($this->filters[FILTER_HAVING] as $filterset) {
			if (!array_key_exists($name,$filterset))continue;
			$filterData = $filterset[$name];
			if (empty($filterData)) continue;
			if ($filterData['ct'] != ctEQ) continue;
			return $filterData['value'];
			}*/
	}

	public function GetRootField($alias) {
		$fieldData = $this->fields[$alias];
		if (!array_key_exists('vtable',$fieldData) || !array_key_exists('joins',$fieldData['vtable'])) return $fieldData['field'];

		$vtable = $fieldData['vtable'];
		foreach ($vtable['joins'] as $fromField => $toField) {
			$obj = utopia::GetInstance($vtable['tModule']);
			if ($toField == $obj->GetPrimaryKey()) return $fromField;
		}
	}

	//	private $rvCache = array();
	public function GetRealValue($alias,$pkVal, $useCache=true) {
		//echo "GetRealValue($alias,$pkVal)<br/>";
		//return "$alias:$pkVal";
		$this->_SetupFields();

		$field = $this->GetRootField($alias);
		if ($field == $this->fields[$alias]['field']) {
			//return "$alias";
			//$rec = $this->GetCurrentRecord();
			$rec = $this->LookupRecord($pkVal);
			return $rec;
			//return "grv $alias:$pkVal<br/>";
			//if ($rec[$this->GetPrimaryKey()] == $pkVal)
			return $rec[$alias];
		}


		//print_r($this->fields[$alias]);
		$table = $this->sqlTableSetup['table'];
		$pk = $this->sqlTableSetup['pk'];

		//$field = $this->fields[$alias]['field'];
		//$table = $this->fields[$alias]['vtable']['table'];
		//$pk = $this->fields[$alias]['vtable']['pk'];

		//		if (!$useCache || (!array_key_exists($table,$this->rvCache) || !array_key_exists($pkVal,$this->rvCache[$table]))) {
		$query = "SELECT $field FROM $table WHERE $pk = '$pkVal'";
		$row = GetRow(sql_query($query));
		//			if ($useCache) $this->rvCache[$table][$pkVal] = $row;
		//		} else
		//			$row = $this->rvCache[$table][$pkVal];
		return $row[$field];
	}

	public function GetLookupValue($alias,$pkValue) {
		if (empty($pkValue)) return;
		$this->_SetupFields();
		$fieldData = $this->fields[$alias];
		$str = $this->GetFieldLookupString($alias,$this->fields[$alias]);

		$table = $fieldData['vtable']['table'];
		$pk = $fieldData['vtable']['pk'];

		$qry = "SELECT $str FROM $table as {$fieldData['tablename']} WHERE $pk = '$pkValue'";
		$row = GetRow(sql_query($qry));

		if (empty($row[$alias])) return $pkValue;

		return $row[$alias];
	}

	// fromField is localField, toField is parentField -- pending global rename
	//	public function CreateTable($alias, $tableModule=NULL, $parent=NULL, $fromField=NULL, $toField=NULL, $joinType='LEFT JOIN') {
	public function CreateTable($alias, $tableModule=NULL, $parent=NULL, $joins=NULL, $joinType='LEFT JOIN') {
		// nested array
		// first create the current alias
		if ($tableModule == NULL) $tableModule = $this->GetTabledef();

		//		$alias = strtolower($alias);
		//		$parent = strtolower($parent);
		//		$fromField = strtolower($fromField);
		//		$toField = strtolower($toField);
		if (!$this->sqlTableSetupFlat) $this->sqlTableSetupFlat = array();
		if (array_key_exists($alias,$this->sqlTableSetupFlat)) { ErrorLog("Cannot create table with alias ($alias).  A table with this alias already exists."); return; }

		$tableObj = utopia::GetInstance($tableModule);

		$newTable = array();
		$this->sqlTableSetupFlat[$alias] =& $newTable;
		$newTable['alias']	= $alias;
		$newTable['table']	= TABLE_PREFIX.$tableModule;
		$newTable['pk']		= $tableObj->GetPrimaryKey();
		$newTable['tModule']= $tableModule;
		$this->AddField('_'.$alias.'_pk',&$newTable['pk'],$alias);
		if ($parent==NULL) {
			if ($this->sqlTableSetup != NULL) {
				ErrorLog('Can only have one base table');
				return;
			}
			$this->sqlTableSetup = $newTable;

			$this->AddField($this->GetPrimaryKey(),$this->GetPrimaryKey(),$alias);
			$this->AddField('_module',"'".get_class($this)."'",$alias);
			return;
		} else {
			$newTable['parent'] = $parent;
		}

		// $fromField in $this->sqlTableSetupFlat[$parent]['tModule']
		if (is_string($joins)) $joins = array($joins=>$joins);
		if (is_array($joins)) foreach ($joins as $fromField => $toField) {
			$tModObj = utopia::GetInstance($this->sqlTableSetupFlat[$parent]['tModule']);
			if ($fromField[0] !== "'" && $fromField[0] !== '"' && stristr($fromField,'.') === FALSE &&
				$tModObj->GetFieldProperty($fromField,'pk') !== true &&
				$tModObj->GetFieldProperty($fromField,'unique') !== true &&
				$tModObj->GetFieldProperty($fromField,'index') !== true)
			ErrorLog("Field ($fromField) used as lookup but NOT an indexed field in table (".$this->sqlTableSetupFlat[$parent]['tModule'].").");
		}
		//$newTable['fromField'] = $fromField;
		//$newTable['toField'] = $toField;
		$newTable['parent']	= $parent;
		$newTable['joins'] = $joins;
		$newTable['joinType'] = $joinType;
		//		$this->sqlTableSetupFlat[$alias] = $newTable;

		// search through the table setup looking for the $linkFrom alias
		if (($srchParent =& recurseSqlSetupSearch($this->sqlTableSetup,$parent))) {
			// found, add it
			if (!array_key_exists('children',$srchParent)) $srchParent['children'] = array();
			$srchParent['children'][] = $newTable;
		} else {
			// not found.. throw error
			ErrorLog("Cannot find $parent");
		}
	}

	public function GetValues($alias) {
		if (!isset($this->fields[$alias])) {
                        $fltr = $this->FindFilter($alias);
                        return $fltr['values'];
		}

		if (isset($this->fields[$alias]['values_cache'])) return $this->fields[$alias]['values_cache'];

		return $this->SetValuesCache($alias,$this->FindValues($alias,$this->fields[$alias]['values']));
	}

	public function SetValuesCache($alias,$vals) {
		return $this->fields[$alias]['values_cache'] = $vals;
	}

	public function FindValues($aliasName,$values,$stringify = FALSE) {
		$arr = NULL;
		$sort = true;
		// if string field, strigify
		if (strpos($this->GetFieldType($aliasName), 'text') !== FALSE || strpos($this->GetFieldType($aliasName), 'char') !== FALSE) $stringify = true;
		
		if (is_array($values)) {
			if (!is_assoc($values)) { // assume we want the key = val
				$values = array_flip($values);
				$sort = false;
			}
			$arr = $values;
		} elseif (IsSelectStatement($values)) {
			$arr = array();
			$result = sql_query($values);
			while ($result != false && (($row = mysql_fetch_row($result)) !== FALSE)) {
				$r = array();
				if (isset($row[1])) {
					// key value pair
					$r[$row[1]] = $row[0];
				} else {
					$r[$row[0]] = $row[0];
				}
				$arr = $r;
			}
		} elseif (($values===true || is_string($values)) && $this->fields[$aliasName]['vtable']) {
			$tbl = $this->fields[$aliasName]['vtable'];
			$obj = utopia::GetInstance($tbl['tModule']);
			$pk = $obj->GetPrimaryKey();
			$table = $tbl['table'];
			$arr = GetPossibleValues($table,$pk,$this->fields[$aliasName]['field'],$values);
			if ($table === TABLE_PREFIX.$this->GetTabledef() && $arr) $arr = array_combine(array_keys($arr),array_keys($arr));
		}

		if ($stringify && is_array($arr) && !is_assoc($arr))
		foreach ($arr as $key=>$val) {
			$arr[$key]=$key;
		}
		if (is_array($arr) && $sort) ksort($arr);
		return $arr;
	}

	public function SetFieldOptions($alias,$newoptions) {
		$this->SetFieldProperty($alias,'options',$newoptions);
	}

	private $spacerCount = NULL;
	public function AddSpacer($text = '',$titleText = '&nbsp;') {
		if ($this->spacerCount === NULL) $this->spacerCount = 0;
		$this->AddField("__spacer_{$this->spacerCount}__","'$text'",'',"$titleText");
		$this->spacerCount = $this->spacerCount + 1;
	}

	public function CharAlign($alias,$char) {
		$this->fields[$alias]['charalign'] = $char;
	}

	public function SetFormat($alias,$format,$condition = TRUE) {
		$this->fields[$alias]['formatting'][] = array('format' =>$format, 'condition' => $condition);
	}

	public $layoutSections = array();
	public function NewSection($secName = '') {
		$this->layoutSections[] = $secName;
	}

	private $defaultStyles = array();
	public function FieldStyles_SetDefault($inputType,$style) {
		if (!is_array($style)) { ErrorLog("Field Style is not an array ($field)"); return; }
		$this->defaultStyles[$inputType] = $style;
	}

	public function FieldStyles_Set($field,$style) {
		if (!is_array($style)) { ErrorLog("Field Style is not an array ($field)"); return; }
		$this->fields[$field]['style'] = $style;
	}

	public function FieldStyles_Add($field,$style) {
		if (!is_array($style)) { ErrorLog("Field Style is not an array ($field)"); return; }
		foreach ($style as $key=>$val)
		$this->fields[$field]['style'][$key] = $val;
	}

	public function FieldStyles_Unset($field,$key) {
		unset($this->fields[$field]['style'][$key]);
	}

	public function FieldStyles_Get($field,$value=NULL) {
		if (!isset($this->fields[$field])) return null;
		$inputType = $this->fields[$field]['inputtype'];
		$defaultStyles = array_key_exists($inputType,$this->defaultStyles) ? $this->defaultStyles[$inputType] : array();
		$specificStyles = $this->GetFieldProperty($field,'style'); if (!$specificStyles) $specificStyles = array();
		$conditionalStyles = array();

		if (array_key_exists('style_fn',$this->fields[$field]) && is_callable($this->fields[$field]['style_fn'][0])) {
			$arr = $this->fields[$field]['style_fn'][1];
			if (is_array($arr)) array_unshift($arr,$value); else $arr = array($value);
			$conditionalStyles = call_user_func_array($this->fields[$field]['style_fn'][0],$arr);
		}
		if (!$conditionalStyles) $conditionalStyles = array();

		$styles = array_merge($defaultStyles,$specificStyles,$conditionalStyles);
		if ($inputType == itDATE && !array_key_exists('width',$styles)) $styles['width'] = '8.5em';
		return $styles;
	}

	public function ConditionalStyle_Set($field,$callback) {
		$numargs = func_num_args();
		$arr = array();
		for ($i = 2; $i < $numargs; $i++)
		$arr[] = func_get_arg($i);
		$this->fields[$field]['style_fn'] = array($callback,$arr);
	}

	public function ConditionalStyle_Unset($field) {
		unset($this->fields[$field]['style_fn']);
	}

	public function GetMetaValue($original,$pk,$value,$rec,$name) {
		if ($pk === NULL) return NULL;
		if (!$this->includeMeta) return NULL;
		$metadata = json_decode($rec['__metadata'],true);
		if (isset($metadata[$name])) return $metadata[$name];
		return NULL;
	}
	public function SetMetaValue($fieldAlias,$newValue,&$pkVal=NULL) {
		if ($pkVal == NULL) {
			$newValue = json_encode(array($fieldAlias=>$newValue));
		} else {
			$rec = $this->LookupRecord($pkVal);
			$metadata = json_decode($rec['__metadata'],true);
			$metadata[$fieldAlias] = $newValue;
			$newValue = json_encode($metadata);
		}
		AjaxEcho('//'.$newValue);
		return $this->UpdateField('__metadata',$newValue,$pkVal);
	}

	private $includeMeta = false;
	public function AddMetaField($name,$visiblename=NULL,$inputtype=itNONE,$values=NULL) {
		if (!$this->includeMeta) {
			$this->includeMeta = true;
			$this->AddField('__metadata','__metadata');
		}
		$this->AddField($name,array($this,'GetMetaValue',$name),NULL,$visiblename,$inputtype,$values);
		$this->fields[$name]['ismetadata'] = true;
	}

	public function AddField($aliasName,$fieldName,$tableAlias=NULL,$visiblename=NULL,$inputtype=itNONE,$values=NULL) {//,$options=0,$values=NULL) {
		$this->_SetupFields();
		if ($tableAlias === NULL) $tableAlias = $this->sqlTableSetup['alias'];
		
		$this->fields[$aliasName] = array(
		  'alias'       => $aliasName,
      'tablename'   => $tableAlias,
      'visiblename' => $visiblename,
      'inputtype'   => $inputtype,
      'options'     => ALLOW_ADD | ALLOW_EDIT, // this can be re-set using $this->SetFieldOptions
      'field'       => $fieldName,
    );
    if (is_array($fieldName)) {
      $this->fields[$aliasName]['field'] = "";
      $this->AddPreProcessCallback($aliasName, $fieldName);
    }
    if ($tableAlias) $this->fields[$aliasName]['vtable'] = $this->sqlTableSetupFlat[$tableAlias];

	switch ($this->GetFieldType($aliasName)) {
		case ftFILE:
		case ftIMAGE:
			$this->AddField($aliasName.'_filename', $fieldName.'_filename', $tableAlias);
			$this->AddField($aliasName.'_filetype', $fieldName.'_filetype', $tableAlias);
			break;
		case ftDATE:
			$this->AddPreProcessCallback($aliasName,array('utopia','convDate'));
			break;
		case ftTIME:
			$this->AddPreProcessCallback($aliasName,array('utopia','convTime'));
			break;
		case ftDATETIME:
			$this->AddPreProcessCallback($aliasName,array('utopia','convDateTime'));
			break;
	}
		// values here
		if ($values === NULL) switch ($inputtype) {
			case itCOMBO:
			case itOPTION:
			case itSUGGEST:
			case itSUGGESTAREA:
				$values = true;
			default:
				break;
		}
		$this->fields[$aliasName]['values'] = $values;

		if ($visiblename !== NULL) {
			if (!$this->layoutSections) $this->NewSection();
			$this->fields[$aliasName]['layoutsection'] = count($this->layoutSections)-1;
		}
		return TRUE;
	}

	public function GetFields($visibleOnly=false,$layoutSection=NULL) {
		$arr = $this->fields;
		foreach ($arr as $fieldName=>$fieldInfo) {
			if ($visibleOnly && $fieldInfo['visiblename']===NULL) unset($arr[$fieldName]);
			if ($layoutSection !== NULL && array_key_exists('layoutsection',$fieldInfo) && $fieldInfo['layoutsection'] !== $layoutSection) unset($arr[$fieldName]);
		}
		return $arr;
	}

	public function AssertField($field,$reference = NULL) {
		if (isset($this->fields[$field])) return TRUE;
		if (IsSelectStatement($field)) return FALSE;

		$found = useful_backtrace(0);

		ErrorLog("Field ($field) does not exist for {$found[0][0]}:{$found[0][1]} called by {$found[1][0]}:{$found[1][1]}.".($reference ? " Ref: $reference" : ''));
	}

	public function AddOnUpdateCallback($alias,$callback) {
		$this->_SetupFields();
		if (!$this->AssertField($alias)) return FALSE;
		//        echo "add callback ".get_class($this).":$alias";
		$numargs = func_num_args();
		$arr = array();
		for ($i = 2; $i < $numargs; $i++)
		$arr[] = func_get_arg($i);
		$this->fields[$alias]['onupdate'][] = array($callback,$arr);
		return TRUE;
	}

	/** Instructs the core to send information to a custom function.
	 * Accepts any number of args, passed to the callback function when the PreProcess function is called.
	 * --  callback($fieldValue,...)
	 * @param string Field Alias to attach the callback to.
	 * @param function Reference to the function to call.
	 * @param ... additional data
	 */
	public function AddPreProcessCallback($alias,$callback) {
		if (!$this->FieldExists($alias)) return FALSE;
		if (count($callback) > 2) {
			$arr = array_splice($callback,2);
		} else {
			$numargs = func_num_args();
			$arr = array();
			for ($i = 2; $i < $numargs; $i++)
				$arr[] = func_get_arg($i);
		}
		$this->fields[$alias]['preprocess'][] = array($callback,$arr);
	}

	public function AddDistinctField($alias) {
		if (!$this->AssertField($alias)) return FALSE;
		// get original field
		$original = $this->fields[$alias];
		$this->AddField("distinct_$alias",'count('.$original['tablename'].'.'.$original['field'].')','',"count($alias)");
		$this->AddFilter("distinct_$alias",ctGTEQ,itNONE,1);
		//	$this->AddGrouping("$alias");
	}

	public $grouping = NULL;
	public function AddGrouping($alias,$direction = 'ASC') {
		if ($this->grouping === NULL) $this->grouping = array();
		//	$this->grouping[] = (array_key_exists($alias,$this->fields) ? $this->fields[$alias]['tablename'].'.' : '')."$alias $direction";
		$this->grouping[] = "`$alias` $direction";
	}

	public $ordering = NULL;
	public function AddOrderBy($alias,$direction = 'ASC') {
		if ($this->ordering === NULL) $this->ordering = array();
		/*
		 switch ($this->GetFieldType($alias)) {
			case 'date': $field = "STR_TO_DATE($alias,'".FORMAT_DATE."')"; break;
			case 'time': $field = "STR_TO_DATE($alias,'".FORMAT_TIME."')"; break;
			case 'datetime':
			case 'timestamp': $field = "STR_TO_DATE($alias,'".FORMAT_DATETIME."')"; break;
			default: $field = $alias; break;
			}*/

		$fieldName = $alias;
		if ($this->FieldExists($alias))
			$fieldName = "`$alias`";

//		switch ($this->GetFieldType($alias)) {
//			case ftDATE: $fieldName = "STR_TO_DATE($fieldName,'".FORMAT_DATE."')"; break;
//			case ftTIME: $fieldName = "STR_TO_DATE($fieldName,'".FORMAT_TIME."')"; break;
//			case ftDATETIME:
//			case ftTIMESTAMP: $fieldName = "STR_TO_DATE($fieldName,'".FORMAT_DATETIME."')"; break;
//			default: $fieldName = $fld;
//		}

		$this->ordering[] = "$fieldName $direction";
	}

	/*  FILTERS */
	// Filters work on a set/rule basis,  each set contains rules which are AND'ed, and each set is then OR'd together this creates full flexibility with filtering the results.
	// EG: (set1.rule1 and set1.rule2) or (set2.rule1)
	// create a new set by calling $this->NewFilterset();
	public function NewFiltersetWhere() {
		$this->filters[FILTER_WHERE][] = NULL;
	}
	public function NewFiltersetHaving() {
		$this->filters[FILTER_HAVING][] = NULL;
	}
	public function ClearFilters() {
		$this->filters = array(FILTER_WHERE=>array(),FILTER_HAVING=>array());
	}

	public function &AddFilterWhere($fieldName,$compareType,$inputType=itNONE,$value=NULL,$values=NULL,$title=NULL) {
		//	if (!array_key_exists($fieldName,$this->fields) && $inputType !== itNONE) { ErrorLog("Cannot add editable WHERE filter on field '$fieldName' as the field does not exist."); return; }
		if (!isset($this->filters[FILTER_WHERE]) || count(@$this->filters[FILTER_WHERE]) == 0) $this->NewFiltersetWhere();
		return $this->AddFilter_internal($fieldName,$compareType,$inputType,$value,$values,FILTER_WHERE,$title);
	}

	public function &AddFilter($fieldName,$compareType,$inputType=itNONE,$value=NULL,$values=NULL,$title=NULL) {
		if (array_key_exists($fieldName,$this->fields) && stripos($this->fields[$fieldName]['field'],' ') === FALSE && !$this->UNION_MODULE)
			return $this->AddFilterWhere($fieldName,$compareType,$inputType,$value,$values,$title);

		//	if (!array_key_exists($fieldName,$this->fields)) { ErrorLog("Cannot add HAVING filter on field '$fieldName' as the field does not exist.");ErrorLog(print_r(useful_backtrace(),true)); return; }
		if (!isset($this->filters[FILTER_HAVING]) || count(@$this->filters[FILTER_HAVING]) == 0) $this->NewFiltersetHaving();
		return $this->AddFilter_internal($fieldName,$compareType,$inputType,$value,$values,FILTER_HAVING,$title);
	}

	private $filterUID = 0;
	public function GetNewUID() {
		$this->filterUID = $this->filterUID +1;
		return $this->GetModuleId().'_'.($this->filterUID - 1);
	}

	// private - must use addfilter or addfilterwhere.
	private function &AddFilter_internal($fieldName,$compareType,$inputType=itNONE,$dvalue=NULL,$values=NULL,$filterType=NULL,$title=NULL) {
		//		if (!isset($value) || empty($value)) return;
		$uid = $this->GetNewUID();
		$value = $dvalue;

		if ($filterType == NULL) // by default, filters are HAVING unless otherwise specified
		$filterset =& $this->filters[FILTER_HAVING];
		else
		$filterset =& $this->filters[$filterType];

		if ($filterset == NULL) $filterset = array();  // - now manually called NewFilterset####()

		if ($values === NULL) switch ($inputType) {
			case itCOMBO:
			case itOPTION:
			case itSUGGEST:
			case itSUGGESTAREA:
				//				ErrorLog("filter on $fieldName setting values to ''");
				$values = $this->FindValues($fieldName,true); //'';
			default:
				break;
		}

		if (is_string($values)) {
			$values = $this->FindValues($fieldName,$values);
		}
		//$vals = $this->FindValues($fieldName,$values,!is_array($values));
		//if ($value !== NULL && is_array($vals) && !array_key_exists($value,$vals)) {
		//	$checkVals = $this->FindValues($fieldName,$values);
		//	if (!array_key_exists($value,$checkVals)) $checkVals = array_flip($checkVals);
		//	if (array_key_exists($value,$checkVals)) $value = $checkVals[$value];
		//}


		/*		if ($inputType == itCOMBO) {
			$flipValues = FALSE;
			if ($values !== NULL) {
			if (is_string($values)) {
			$values = $this->FindValues($fieldName,$values);
			$flipValues = TRUE;
			} else if (is_array($values)) {
			$gotStr = false;
			foreach ($values as $key => $val)
			if (!is_int($key)) { $gotStr = true; break;}

			if (!$gotStr) { // assume we want the key = val
			foreach ($values as $key => $val) {
			$values[$val] = $val;
			unset($values[$key]);
			}
			}
			}
			} else {
			if (array_key_exists($fieldName,$this->fields) && array_key_exists('values',$this->fields[$fieldName]))
			$values = $this->fields[$fieldName]['values'];
			else
			$values = $this->FindValues($fieldName);
			$flipValues = TRUE;
			}

			if ($flipValues && $filterset == $this->filters[FILTER_HAVING]) {
			// values for the filter are repopulated using key => key - this is because Having filters are always on the end result (the text) and not the lookup value
			$valFlip = array_flip($values); if (array_key_exists($value,$valFlip)) $value = $valFlip[$value];
			if (is_array($values) && !empty($values) && !isset($this->fields[$fieldName]['customValues'])) {
			foreach ($values as $key => $val)
			$values[$key] = $key;
			}
			}
			}*/

		if ($inputType == itNONE && !$value && array_key_exists($fieldName,$_GET))
			$value = $_GET[$fieldName];

		$fieldData = array();
		$fieldData['fieldName'] = $fieldName;
		$fieldData['type'] = $filterType;

		$fieldData['ct'] = $compareType;
		$fieldData['it'] = $inputType;
		$fieldData['uid'] = $uid;

		$fieldData['title']= $title;
		$fieldData['values']= $values;
		$fieldData['default'] = $dvalue;
		$fieldData['value'] = $value;

		if ($inputType != itNONE) $this->hasEditableFilters = true;

		$filterset[count($filterset)-1][] =& $fieldData;
		return $fieldData;//['uid'];
	}

	// returns false if filter not found, otherwise returns filter information as array
	public function HasFilter($field) {
		if (isset($this->filters[FILTER_HAVING]))
		foreach ($this->filters[FILTER_HAVING] as $filterset) {
			if (isset($filterset[$field])) return $filterset[$field];
		}
		if (isset($this->filters[FILTER_WHERE]))
		foreach ($this->filters[FILTER_WHERE] as $filterset) {
			if (isset($filterset[$field])) return $filterset[$field];
		}

		return false;
	}

	public function FieldExists($fieldName) {
		return isset($this->fields[$fieldName]);
	}

	public function SetFieldProperty($fieldName,$propertyName,$propertyValue) {
		if (!isset($this->fields[$fieldName])) { ErrorLog(get_class($this)."->SetFieldProperty($fieldName,$propertyName). Field does not exist."); return; }
		$this->fields[$fieldName][$propertyName] = $propertyValue;
	}

	public function GetFieldProperty($fieldName,$propertyName) {
		//		if (!$this->AssertField($fieldName)) return NULL;
		if (!isset($this->fields[$fieldName])) return NULL;
		if (!isset($this->fields[$fieldName][$propertyName])) return NULL;

		return $this->fields[$fieldName][$propertyName];//[strtolower($propertyName)];
	}

	public function SetFieldType($alias,$type) {
		$this->_SetupFields();
		$this->SetFieldProperty($alias,'datatype',$type);
	}

	public function GetFieldType($alias) {
		$type = $this->GetFieldProperty($alias,'datatype');
		if (!$type) $type = $this->GetTableProperty($alias,'type');

		return $type;
	}

	public $hideFilters = FALSE;
	public function HideFilters() {
		$this->hideFilters = TRUE;
	}

	public function GetFieldLookupString($alias,$fieldData) {
		$fieldName = $fieldData['field'];
		if (empty($fieldName)) return "'' as `$alias`";
		if ($fieldData['tablename'] === NULL) return;

		/* THIS FUNCTION HAS BEEN SIMPLIFIED!
		 * field is ReplacePragma if field has "is_function" property, or starts with "(", single quote, or double quote
		 * else, field is CONCAT
		 */
		
		// first replace pragmas
//		echo 'check for pragma: '.$fieldName;
//		if (preg_match('/{[^}]+}/',$fieldName) > 0) { // has pragma code
//			echo 'yes';
//			$fieldName = ReplacePragma($fieldName, $fieldData['tablename']);
//		}
//		echo '<br>';
    $chr1 = substr($fieldName,0,1);
    if (!preg_match('/{[^}]+}/',$fieldData['field'])) {
      if ($chr1 == '(' || $chr1 == "'" || $chr1 == '"')
        $toAdd = $fieldData['field'];
      else
        $toAdd = "`{$fieldData['tablename']}`.`{$fieldData['field']}`";
    } elseif ($this->GetFieldProperty($alias, 'is_function') || $chr1 == '(' || $chr1 == "'" || $chr1 == '"') {
			$toAdd = ReplacePragma($fieldData['field'], $fieldData['tablename']);
//		} elseif (preg_match('/{[^}]+}/',$fieldData['field']) > 0) { // has pragma code
//			if (substr($fieldData['field'],0,1) === '{') // starts with a pragma, so assume we need to concat
//				$toAdd = CreateConcatString($fieldData['field'], $fieldData['tablename']);
//			else // doesnt start with a pragma, so it could well be a function, only replace the pragmas with the fields, dont make it concat
//				$toAdd = ReplacePragma($fieldData['field'], $fieldData['tablename']);
		} else {
			// is it a date?
			// is it a timestamp?
			// use DATE_FORMAT
			$toAdd = CreateConcatString($fieldName, $fieldData['tablename']);
			//$flds[] = "$concat as $alias";
		}
//		switch ($this->GetFieldType($alias)) {
//			case 'date': $toAdd = "IF($toAdd = 0,'',DATE_FORMAT($toAdd,'".FORMAT_DATE."'))"; break;
//			case 'time': $toAdd = "IF($toAdd = 0,'',TIME_FORMAT($toAdd,'".FORMAT_TIME."'))"; break;
//			case 'datetime':
//			case 'timestamp': $toAdd = "IF($toAdd = 0,'',DATE_FORMAT($toAdd,'".FORMAT_DATETIME."'))"; break;
//		}

		return "$toAdd as `$alias`";
	}

	public function GetFromClause() {
		$from = "{$this->sqlTableSetup['table']} AS {$this->sqlTableSetup['alias']}";
		$paraCount = parseSqlTableSetupChildren($this->sqlTableSetup,$from);
		//		for ($i = 0; $i < $paraCount; $i++)
		//			$from = '('.$from;
		if ($from == ' AS ') return '';
		return $from;
	}

	public $rowcount = false;
	public function GetSelectStatement() {//$filter = '',$sortColumn='') {
		// init fields, get primary key, its required by all tables anyway so force it...
		//grab the table alias and primary key from the alias's tabledef

		$flds = array();//$this->sqlTableSetup['alias'].".".$this->sqlTableSetup['pk']);

		//		$tblJoins = array();
		//		$tblInc = 1;

		foreach ($this->fields as $alias => $fieldData) {
			$str = $this->GetFieldLookupString($alias,$fieldData);
			if (!empty($str)) $flds[] = $str;
		}

		// table joins should be grouped by the link field.... eg:
		// if table1.field1 is linked to table2.field2 in 2 lookups
		// there is no need to create 2 table aliases, as they will return the same result set.


		//		$joins = '';
		//		foreach ($tblJoins as $tblJoin) {
		//			$joins .= " LEFT OUTER JOIN {$tblJoin['table']} {$tblJoin['ident']} ON {$tblJoin['ident']}.{$tblJoin['lookup']} = ".$this->GetPrimaryTable().".{$tblJoin['linkField']} ";
		//		}

		// now create function to turn the sqlTableSetup into a FROM clause
		$from = $this->GetFromClause();

		$distinct = flag_is_set($this->GetOptions(),DISTINCT_ROWS) ? ' DISTINCT' : '';
		if ($this->rowcount) $flds[] = 'COUNT(*) as row_count';
		$qry = "SELECT$distinct ".join(",\n",$flds);
		if ($from) $qry .= " \nFROM ".$from;//$this->GetPrimaryTable().$joins;
		return $qry;
	}

	public function &FindFilter($fieldName, $compareType=NULL, $inputType = NULL, $set = NULL) {
		//$this->SetupParents();
		//		$this->_SetupFields();
		//		echo "FindFilter($fieldName, $compareType, $inputType): ";
		//print_r($this->filters);
		foreach ($this->filters as $ftypeID => $filterType) {
			if ($set != NULL && $ftypeID !== $set) continue;
			foreach ($filterType as $fsetID => $filterset) {
				if (is_array($filterset)) foreach ($filterset as $arrID => $filterInfo) {
					if ($filterInfo['fieldName'] === $fieldName && ($compareType === NULL || $filterInfo['ct'] === $compareType) && ($inputType === NULL || $filterInfo['it'] === $inputType)) {
						//						echo "found filter matching ($fieldName $compareType $inputType) at ($uid)<br/>";
						return $this->filters[$ftypeID][$fsetID][$arrID];
					}
				}
			}
		}
		//		echo "not found<br/>";
		$null = NULL;
		return $null;
	}

	public function &GetFilterInfo($uid) {
		//		echo get_class($this).".GetFilterInfo($uid)<br/>";
		foreach ($this->filters as &$filterTypeArray) {
			foreach ($filterTypeArray as &$filterset) {
				if (!is_array($filterset)) continue;
				foreach ($filterset as &$filterInfo) {
					if ($filterInfo['uid'] == $uid)	return $filterInfo;
				}
			}
		}
		$null = NULL;
		return $null;
	}

	public function GetFilterValue($uid, $refresh = FALSE) {
		//		ErrorLog(get_class($this).".GetFilterValue($uid)");
		$filterData = $this->GetFilterInfo($uid);
		//        ErrorLog(print_r($filterData,true));

		// ptime static filter value
		// this line grabs STATIC filters (filters set by code), this enforced if the input type is null
		$defaultValue = (is_array($filterData) && array_key_exists('value',$filterData)) ? $filterData['value'] : NULL;

		if (is_array($filterData) && $filterData['it'] == itNONE) {
			// for union modules, we cannot get a value form currentmodule because it is itself, part of the query
			if (utopia::GetCurrentModule() !== get_class($this) && (!isset($this->UNION_MODULE) || $this->UNION_MODULE !== TRUE)) {
				if (array_key_exists('linkFrom',$filterData)) {
					list($linkParent,$linkFrom) = explode(':',$filterData['linkFrom']);
					// linkparent is loaded?  if not then we dont really want to use it as a filter.....
					if ($linkParent == utopia::GetCurrentModule()) {
						$linkParentObj = utopia::GetInstance($linkParent);
						$row = $linkParentObj->GetCurrentRecord($refresh);
						if (!$row && !$refresh) $row = $linkParentObj->GetCurrentRecord(true);

						if (is_array($row) && array_key_exists($linkFrom,$row)) {
							return $row[$linkFrom];
							//					else {
							//						ErrorLog(get_class($this).': Cant find '.$filterData['linkFrom']);
							//						errorLog(print_r(useful_backtrace(1,5),true));
							//					}
						} else {// if the filter value of the parent is null (if we're updating for example), then we want to get the value of the filter
							$fltrLookup = $linkParentObj->FindFilter($linkFrom,ctEQ);
							$val = NULL;
							// stop lookup callbacks
							if (is_array($fltrLookup) && array_key_exists('linkFrom',$fltrLookup) && stristr($fltrLookup['linkFrom'],get_class($this)) === FALSE )
								$val = $linkParentObj->GetFilterValue($fltrLookup['uid']);
							//ErrorLog($val);
							if ($val!==NULL) return $val;
							//if ($fltrLookup['value']) return $fltrLookup['value'];

							//$uid = $uid['uid'];
						}
					}
				} //else
				//						ErrorLog('Cant find linkFrom in GetFilterValue: '.print_r($filterData,true));
			}
			if (!empty($defaultValue)) return $defaultValue;
		}

		$filters = GetFilterArray();
		if (!is_array($filters) || !array_key_exists($uid,$filters)) {
			return $defaultValue;
		}

		return urldecode($filters[$uid]);
	}

	public function GetTableProperty($alias,$property) {
		//		if (!$this->AssertField($alias,$alias.'.'.$property)) return NULL;
		if (!isset($this->fields[$alias])) return NULL;
		if (!isset($this->fields[$alias]['vtable'])) return NULL;

		$tabledef = $this->fields[$alias]['vtable']['tModule'];
		//		$fieldName = $this->GetRootField($alias);
		$fieldName = $this->fields[$alias]['field'];
		//echo "finding prop $property for field $fieldName in $tabledef<br/>";
		$obj = utopia::GetInstance($tabledef);
		return $obj->GetFieldProperty($fieldName,$property);
	}

	// filterSection = [where|having]

	public function FormatFieldName($fieldName, $fieldType = NULL) {
//	return $fieldName;
		if ($fieldType === NULL)
		$fieldType = $this->GetFieldType($fieldName);

		// do field
		switch ($fieldType) {
			case ('date'): $fieldName = "(STR_TO_DATE($fieldName,'".FORMAT_DATE."'))"; break;
			case ('time'): $fieldName = "(STR_TO_DATE($fieldName,'".FORMAT_TIME."'))"; break;
			case ('datetime'):
			case ('timestamp'): $fieldName = "(STR_TO_DATE($fieldName,'".FORMAT_DATETIME."'))"; break;
			/*            case ('date'): $fieldName = "DATE($fieldName)"; break;
			 case ('time'): $fieldName = "TIME($fieldName)"; break;
			 case ('datetime'):
			 case ('timestamp'): $fieldName = "TIMESTAMP($fieldName)"; break;*/
			default: break;
		}
		return $fieldName;
	}

	public function GetFilterString($uid,$fieldNameOverride=NULL,$fieldTypeOverride=NULL){//,$filterSection) {
		//		echo get_class($this).".GetFilterString($uid)\n";
		$filterData = $this->GetFilterInfo($uid);
		if (!is_array($filterData)) return '';
		$fieldName = $fieldNameOverride ? $fieldNameOverride : $filterData['fieldName'];
		$compareType=$filterData['ct'];
		$inputType=$filterData['it'];
		if ($compareType == ctCUSTOM) return $filterData['fieldName'];

		$fieldToCompare = NULL;
		if ($filterData['type'] == FILTER_WHERE) {
			if (array_key_exists($fieldName,$this->fields)) {
				//				ErrorLog($fieldName.' '.($this->fields[$fieldName]['field'] > 0 ? 'pragma' : 'no pragma'));
				if (preg_match('/{[^}]+}/',$this->fields[$fieldName]['field']) > 0)
				$fieldToCompare = '`'.$this->fields[$fieldName]['tablename'].'`.`'.$this->fields[$fieldName]['vtable']['pk'].'`'; // PRAGMA,  use tables PK
				else
				$fieldToCompare = '`'.$this->fields[$fieldName]['tablename'].'`.`'.$this->fields[$fieldName]['field'].'`'; // NOT PRAGMA, use field
			} else if (!IsSelectStatement($fieldName))
			return '';
			//	ErrorLog("Unable to clearly define where statement for field ($fieldName)");
		}

		$value = $this->GetFilterValue($uid);//$filterData['value'];
		//echo "$uid::$value<br/>";
		if ($value === NULL && (!isset($filterData['linkFrom']) || !$filterData['linkFrom'] || (isset($filterData['allow_empty'])))) return '';
		// set filter VALUE
		if ($compareType == ctLIKE && strpos($value,'%') === FALSE ) $value = "%$value%";

		// find field type from tabledef
		// set filter NAME

		// if where, ignore type
		$value = mysql_real_escape_string($value);
		$fieldName = $this->FormatFieldName($fieldName, $fieldTypeOverride);

		// do value
		switch (true) {
			case $compareType == ctANY:
				$constants = get_defined_constants(true);
				foreach ($constants['user'] as $cName => $cVal) {
					if (strtolower(substr($cName,0,2))=='ct' && stripos($value,$cVal) !== FALSE) {
						$val = "$value";
						break;
					}
				}
				$val = $val ? $val : "= '$value'";
				break;
			case $compareType == ctIS:
			case $compareType == ctISNOT:
			case is_numeric($value):
				$val = $value; break;
				// convert dates to mysql version for filter
			case ($inputType==itDATE): $val = "(STR_TO_DATE('$value', '".FORMAT_DATE."'))"; break;
			case ($inputType==itTIME): $val = "(STR_TO_DATE('$value', '".FORMAT_TIME."'))"; break;
			case ($inputType==itDATETIME): $val = "(STR_TO_DATE('$value', '".FORMAT_DATETIME."'))"; break;
			default:
				$val = "'$value'";
				break;
		}

		$fieldToCompare = $fieldToCompare ? $fieldToCompare : $fieldName;
		if ($compareType == ctIN) {
			if (IsSelectStatement($fieldName))
				return trim("$val $compareType $fieldName");
			$vals = explode(',',$value);
			$val = "('".join("','",$vals)."')";
			return trim("$fieldToCompare $compareType $val");
		}

		return trim("$fieldToCompare $compareType $val");
	}

  public $extraWhere = NULL;
	public function GetWhereStatement() {
		//		echo get_class($this).".GetWhereStatement()\n";
		$filters = $this->filters;
		//print_r($filters);
		//		ErrorLog('moo'.print_r($filters[FILTER_WHERE],true));
		$where = array();
		if (isset($filters[FILTER_WHERE]))
		foreach ($filters[FILTER_WHERE] as $filterset) {
			$setParts = array();
			if (!is_array($filterset)) continue;
			foreach ($filterset as $fData) { // loop each field in set
				if ($fData['type'] !== FILTER_WHERE) continue;
				$fieldName = $fData['fieldName'];

				// if the field doesnt exist in the primary table. -- should be ANY table used. and if more than one, should be specific.

				if (($filterString = $this->GetFilterString($fData['uid'])) !== '')
					$setParts[] = "($filterString)";
			}
			if (count($setParts) >0) $where[] = '('.join(' AND ',$setParts).')';
		}
    $ret = join(' AND ',$where);
    if (empty($this->extraWhere)) return $ret;
    
		if (is_array($this->extraWhere)) {
      $extraWhere = array();
			foreach ($this->extraWhere as $field => $value) {
				$value = is_numeric($value) ? $value : "'$value'";
				$extraWhere[] = "($field = $value)";
			}
      return "($ret) AND (".implode(' AND ',$extraWhere).")";
		} elseif (is_string($this->extraWhere)) {
		  return "($ret) AND (".$this->extraWhere.")";
		}

    return $ret;

/*		if (empty($where) && empty($extraWhere)) return '';

		if (count($where) > 0)
		array_push($extraWhere,join(' OR ',$where));

		$state = join(' AND ',$extraWhere);

		return $state;*/
	}

  public $extraHaving = NULL;
	public function GetHavingStatement($onlyFilters = FALSE) {
		$filters = $this->filters;

		$having = array();
		if (isset($filters[FILTER_HAVING]))
		foreach ($filters[FILTER_HAVING] as $filterset) {
			$setParts = array();
			if (!is_array($filterset)) continue;
			foreach ($filterset as $fData) { // loop each field in set
				if ($onlyFilters && $fData['it'] == itNONE) continue;
				if ($fData['type'] !== FILTER_HAVING) continue;
				$fieldName = $fData['fieldName'];
				// perhaps its a subquery?
				if (!isset($this->fields[$fieldName]) && $this->GetPrimaryKey() != $fieldName && !IsSelectStatement($fieldName) && ($fData['ct'] != ctCUSTOM)) continue;
				//				if (empty($fData['value'])) continue;
				//				if ($fData['value'] == "%%" && $fData['ct'] == ctLIKE) continue;

				if (($filterString = $this->GetFilterString($fData['uid'])) !== '')
					$setParts[] = "($filterString)";
			}
			if (count($setParts) >0) $having[] = '('.join(' AND ',$setParts).')';
		}
		$ret = join(' OR ',$having);

		if (empty($this->extraHaving)) return $ret;
		if ($ret) $ret = "($ret) AND ";

		if (is_array($this->extraHaving)) {
			$extraWhere = array();
			foreach ($this->extraHaving as $field => $value) {
				$value = is_numeric($value) ? $value : "'$value'";
				$extraWhere[] = "($field = $value)";
			}
			if (count($extraWhere) > 0) return "$ret (".implode(' AND ',$extraWhere).")";
		} elseif (is_string($this->extraHaving) && $this->extraHaving) {
			return "$ret (".$this->extraHaving.")";
		}

		return $ret;
	}

	public function GetOrderBy() {
		$sortKey = '_s_'.$this->GetModuleId();
		if (isset($_GET[$sortKey])) {
			$this->ordering = NULL;
			$arr = explode(',',$_GET[$sortKey]);
			foreach ($arr as $sorter) {
				$s = explode(' ',$sorter);
				$this->AddOrderBy($s[0],isset($s[1]) ? $s[1] : NULL);
			}
		}
		if (empty($this->ordering)) return 'NULL';
		if (is_array($this->ordering)) return join(', ',$this->ordering);

		return $this->ordering;
	}

	public function GetGrouping() {
		if (empty($this->grouping)) return '';
		if (is_array($this->grouping)) return join(', ',$this->grouping);
		return $this->grouping;
	}

	public $limit = NULL;

	private $queryChecksum = NULL;
	private $explainQuery = false;
	/**
	 * Get a dataset based on setup.
	 * @param (bool|null) NULL to return a fresh dataset, TRUE to refresh the internal dataset, FALSE to return the cached dataset.
	 * @returns MySQL Dataset
	 */
	public function &GetDataset($refresh = FALSE) {
		$this->_SetupFields();

		// GET SELECT
		$select = $this->GetSelectStatement();
		// GET WHERE
		$where = $this->GetWhereStatement(); $where = $where ? " WHERE $where" : ''; // uses WHERE modifier
		// GET GROUPING
		$group = $this->GetGrouping(); $group = $group ? " GROUP BY $group" : '';
		// GET HAVING
		$having = $this->GetHavingStatement(); $having = $having ? " HAVING $having" : ''; // uses HAVING modifier to account for aliases
		// GET ORDER
		$order = $this->GetOrderBy(); $order = $order ? " ORDER BY $order" : '';

		$having1 = $this->GetFromClause() ? $having : '';
		$order1 = $this->GetFromClause() ? $order : '';
		$query = "($select$where$group$having1$order1)";

		if (is_array($this->UnionModules)) {
			//		$havingFilters = $this->GetHavingStatement(TRUE); $havingFilters = $havingFilters ? " HAVING $having" : ''; // uses HAVING modifier to account for aliases
			foreach ($this->UnionModules as $moduleName) {
				$obj = utopia::GetInstance($moduleName);
				$obj->_SetupFields();
				$select2 = $obj->GetSelectStatement();
				$where2 = $obj->GetWhereStatement(); $where2 = $where2 ? " WHERE $where2" : '';
				$group2 = $obj->GetGrouping(); $group2 = $group2 ? " GROUP BY $group2" : '';
				$having2 = $obj->GetHavingStatement();
				$having2 = $having2 ? $having.' AND ('.$having2.')' : $having;
				//				if (!empty($having2)) $having2 = $having.' AND ('.$having2.')';
				//				else $having2 = $having;
				$order2 = $obj->GetOrderBy(); $order2 = $order2 ? " ORDER BY $order2" : '';
				$query .= "\nUNION\n($select2$where2$group2$having2$order2)";
			}
			$query .= " $order";
		}

		$limitKey = '_p_'.$this->GetModuleId();
		if (empty($this->limit) && isset($_GET[$limitKey])) $this->limit = $_GET[$limitKey];
		if (!empty($this->limit)) $query .= ' LIMIT '.$this->limit;

		$checksum = sha1(print_r($query,true));
		if ($this->queryChecksum === $checksum && $this->dataset !== NULL && $refresh === FALSE) return $this->dataset;
		$this->queryChecksum = $checksum;

		if ($this->explainQuery) print_r(GetRows(sql_query("EXPLAIN EXTENDED $query")));

		$this->dataset = sql_query($query);
		$this->EnforceNewRec();

		return $this->dataset;
	}

	public function GetRowCount() {
		$this->rowcount = true;
		$old_limit = $this->limit;
		$this->limit = 1;
		$result = $this->GetDataset(true);
		$row = mysql_fetch_assoc($result);
		$this->limit = $old_limit;
		$this->rowcount = false;
		return $row['row_count'];
	}

	private $lastRowNum = array();
	public function GetLastRowNum() { return $this->lastRowNum; }
	/**
	 * Get a record from a specified dataset.
	 * @param resource Dataset from which to get a record
	 * @param int Row number to retrieve
	 * @returns Array containing Field=>Value key pairs
	 */
	public function GetRecord($dataset, $rowNum) {
	  //static $aaaa = 0; $aaaa++;
		//		ErrorLog(get_class($this).".GetRecord($rowNum)");
		//        if (is_bool($refresh)) {
		//
		//            $dataset = $this->GetDataset($refresh);
		//        } else
		//            $dataset = $refresh;

		if (!is_resource($dataset)) return NULL;
		if ($dataset === $this->dataset && $this->IsNewRecord()) return NULL;
		$num_rows = mysql_num_rows($dataset);
		//        if ($rowNum === NULL) {
		//			$rowNum = $this->internalRowNum;
		//			$this->internalRowNum++;
		//		}
		if ($rowNum < 0) { // negative rowNum means find record from end of the set (-1 = last record)
			$rowNum = $num_rows + $rowNum;
		}

		if ($rowNum > $num_rows-1 || $rowNum < 0 || $num_rows == 0) { // requested row is greater than total rows or less than 0 or no rows exist
			$row = NULL;
		} else {
			//mysql_data_seek($dataset,$rowNum);
    //timer_start('zz'.$aaaa);
			$row = GetRow($dataset,$rowNum);
    //timer_end('zz'.$aaaa);
		}

		if ($dataset === $this->dataset) {
			$this->currentRecord = $row;//mysql_fetch_assoc($dataset);
			$this->lastRowNum = $rowNum;
			//$_SESSION['datastore'][get_class($this)] = $this->currentRecord;
		}
		if (isset($row['__metadata'])) $row['__metadata'] = stripslashes($row['__metadata']);
		return $row;
	}

	public function GetCurrentRecord($refresh = FALSE) {

		if ($refresh === TRUE) {
			if ($this->currentRecord !== NULL)
				return $this->LookupRecord($this->currentRecord[$this->GetPrimaryKey()]);
			else
				return $this->GetRecord($this->GetDataset(),0);
		}

		return $this->currentRecord;
	}

	public function GetRows($filter=NULL,$clearFilters=false) {
		//if (is_null($filter)) return NULL;
		if (!$filter) $filter = array();
		if (!is_array($filter)) $filter = array($this->GetPrimaryKey()=>$filter);
		$class = get_class($this);
		$instance = new $class();
		$instance->_SetupParents();
		$instance->_SetupFields();
		if ($clearFilters) $instance->ClearFilters();
		foreach ($filter as $field => $val) {
			// does filter exist already?
			if ($fltr =& $instance->FindFilter($field)) {
				$fltr['value'] = $val;
			} else {
				if (!is_numeric($field))
					$instance->AddFilter($field,ctEQ,itNONE,$val);
				else
					$instance->AddFilter($val,ctCUSTOM);
			}
		}
		$dataset = $instance->GetDataset(NULL);

		$rows = array();

		$i = 0;
		while (($row = $this->GetRecord($dataset,$i))) {
			$rows[] = $row;
			$i++;
		}

		return $rows;
	}

	public function LookupRecord($filter=NULL,$clearFilters=false) {
		$rows = $this->GetRows($filter,$clearFilters);
		if (is_array($rows)) return reset($rows);
		return NULL;
	}

	public function GetRowWhere($pkValue = NULL) {
		if (!empty($pkValue)) return '`'.$this->GetPrimaryKey()."` = '".$pkValue."'";
		return '';
	}
	
	public function GetRawData($filters=null) {
		$this->_SetupFields();
		$title = $this->GetTitle();

		//TODO:sections	
		$layoutSections = $this->layoutSections;
		
		$pk = $this->GetPrimaryKey();
		
		$fieldDefinitions = array();
		foreach ($this->fields as $fieldAlias => $fieldData) {
			$fieldDefinitions[$fieldAlias] = array('title'=>$fieldData['visiblename'],'type'=>$fieldData['inputtype']);
		}
		
		$data = $this->GetRows($filters);
		
		return array(
			'definition' => array('title'=>$title,'fields'=>$fieldDefinitions,'pk'=>$pk),
			'data' => $data,
		);
	}	

//	private $navCreated = FALSE;

  // sends ARGS,originalValue,pkVal,processedVal
	public function PreProcess($fieldName,$value,$rec=NULL,$forceType = NULL) {
		$pkVal = !is_null($rec) ? $rec[$this->GetPrimaryKey()] : NULL;
		$originalValue = $value;
		if (isset($this->fields[$fieldName]['ismetadata'])) {
			$value = json_decode($value);
			if (json_last_error() !== JSON_ERROR_NONE) $value = $originalValue;
		}
		$suf = ''; $pre = ''; $isNumeric=true;
		if ($forceType === NULL) $forceType = $this->GetFieldType($fieldName);
		switch ($forceType) {
			case ftFILE:
				$filename = '';
				$link = $this->GetFileFromTable($fieldName,TABLE_PREFIX.$this->GetTabledef(),$this->GetPrimaryKey(),$pkVal);
				if ($rec && array_key_exists($fieldName.'_filename',$rec) && $rec[$fieldName.'_filename']) $filename = '<b><a href="'.$link.'">'.$rec[$fieldName.'_filename'].'</a></b> - ';
				if (!strlen($value)) $value = '';
				else $value = $filename.round(strlen($value)/1024,2).'Kb<br/>';
				break;
			case ftIMAGE:
				if (!$value) break;
				$size = $this->GetFieldProperty($fieldName,'length');
				$value = $this->DrawSqlImage($fieldName,$rec,$size,$size);
				break;
			case ftCURRENCY:
				$dp = $this->GetFieldProperty($fieldName,'length');
				if (strpos($dp,',') != FALSE) $dp = substr($dp,strpos($dp,',')+1);
				else $dp = 2;
				if (is_numeric($value))
					$value = DEFAULT_CURRENCY.number_format($value,$dp);
				break;
			case ftPERCENT:
				$dp = $this->GetFieldProperty($fieldName,'length');
				if (strpos($dp,',') != FALSE) $dp = substr($dp,strpos($dp,',')+1);
				else $dp = 2;
				if (is_numeric($value))
					$value = number_format($value,$dp).'%';
				break;
			case ftFLOAT:
				$dp = $this->GetFieldProperty($fieldName,'length');
				if (is_numeric($value) && strpos($dp,',') != FALSE)
					$value = number_format($value,substr($dp,strpos($dp,',')+1));
				break;
			case ftUPLOAD:
				if ($value) {
					$value = $this->GetUploadURL($fieldName,$pkVal);
					//$value = utopia::GetRelativePath($value);
					$value = "<a href=\"$value\">Download</a>";
				}
				break;
			default: $isNumeric = false;
		}

		if (array_key_exists($fieldName,$this->fields) && array_key_exists('preprocess',$this->fields[$fieldName])) {
			foreach ($this->fields[$fieldName]['preprocess'] as $callback) {
				$args = $callback[1];
				$callback = $callback[0];

				if (is_string($callback)) {
					$method = new ReflectionFunction($callback);
				} elseif (is_array($callback)) {
					$method = new ReflectionMethod($callback[0],$callback[1]);
				}
				$num = $method->getNumberOfParameters();

				if ($args == NULL) $args = array();
				array_unshift($args,$originalValue,$pkVal,$value,$rec,$fieldName);
				array_splice($args,$num); // strip out any extra args than the function can take (stops 'wrong param count' error)

				$value = call_user_func_array($callback,$args);
			}
		}

		return $value;
	}

	public function GetUploadURL($fieldAlias,$pkVal) {
//		$field = $this->GetFieldProperty($fieldAlias ,'field');
//		$setup = $this->sqlTableSetupFlat[$this->GetFieldProperty($fieldAlias,'tablename')];
//		$table = $setup['table'];
//		$key = $setup['pk'];

//		$uuid = $this->GetUUID();

		$filters = array(
			'__ajax'=>'getUpload',
			//'m'		=>$this->GetUUID(),
			'f'		=>$fieldAlias,
			'p'		=>$pkVal
		);

		//return BuildQueryString(PATH_REL_SELF,$filters);
		return $this->GetURL($filters);
//		return PATH_REL_ROOT.DEFAULT_FILE."?__ajax=getUpload&f=$fieldAlias&m=$uuid&p=$pkVal";
	}

	// TODO: Requests for XML data (ajax)
	// to be used later with full AJAX implimentation
	public function CreateXML() {
		// call parent createxml
		// parse and inject child links into 'dataset' xml object
	}

	public function GetFilterBox($filterInfo,$attributes=NULL,$spanAttributes=NULL) {
		if (is_string($filterInfo))
			$filterInfo = $this->GetFilterInfo($filterInfo);
		// already filtered?

		if ($filterInfo['it'] === itNONE) return '';
		$fieldName = $filterInfo['fieldName'];

		$default = $this->GetFilterValue($filterInfo['uid']);

		$pre = '';
		if (!empty($filterInfo['title'])) {
//			$pre = $filterInfo['title'].' ';
			$emptyVal = $filterInfo['title'].' '.htmlentities($filterInfo['ct']);
		} else
			$emptyVal = $this->fields[$fieldName]['visiblename'].' '.htmlentities($filterInfo['ct']);

		if ($filterInfo['it'] == itSUGGEST || $filterInfo['it'] == itSUGGESTAREA)
		$vals = cbase64_encode(get_class($this)."|$fieldName");
		else
		$vals = $filterInfo['values'];

		if (!$attributes) $attributes = array();
		$attributes['title'] = strip_tags($emptyVal);
		if (array_key_exists('class',$attributes)) $attributes['class'] .= 'uFilter';
		else $attributes['class'] = 'uFilter';

		if ($filterInfo['it'] == itDATE) {
			if (!array_key_exists('style',$attributes)) $attributes['style'] = array();
			if (is_array($attributes['style']) && !array_key_exists('width',$attributes['style'])) {
				$attributes['style']['width'] = '8.5em';
			}
		}

		$spanAttr = BuildAttrString($spanAttributes);

		return '<span '.$spanAttr.'>'.$pre.utopia::DrawInput('_f_'.$filterInfo['uid'],$filterInfo['it'],$default,$vals,$attributes,false).'</span>';
	}

	public function ProcessUpdates($function,$sendingField,$fieldAlias,$value,&$pkVal = NULL) {
		$this->_SetupFields();

		$func = 'ProcessUpdates_'.$function;
		$this->$func($sendingField,$fieldAlias,$value,$pkVal);
    
		// reset all fields with preprocess.
		foreach ($this->fields as $alias => $field) {
			if (isset($field['preprocess'])) $this->ResetField($alias,$pkVal);
		}
	}

	public function ProcessUpdates_add($sendingField,$fieldAlias,$value,&$pkVal = NULL) {
		if (!flag_is_set($this->GetOptions(),ALLOW_EDIT)) { AjaxEcho('//Module does not allow record editing'); return; }
		$this->UpdateField($fieldAlias,$value,$pkVal);
	}

	function ProcessUpdates_md5($sendingField,$fieldAlias,$value,&$pkVal = NULL) {
		if (empty($value)) return FALSE;
		return $this->UpdateField($fieldAlias,md5($value),$pkVal);
	}

	public function ProcessUpdates_del($sendingField,$fieldAlias,$value,&$pkVal = NULL) {
		if (!flag_is_set($this->GetOptions(),ALLOW_DELETE)) { AjaxEcho('//Module does not allow record deletion'); return; }
		AjaxEcho('//'.get_class($this)."@ProcessUpdates_del($fieldAlias,$value,$pkVal)");

		$table = TABLE_PREFIX.$this->GetTabledef();
		$where = $this->GetPrimaryKey()." = '$pkVal'";

		sql_query("DELETE FROM $table WHERE $where");

		return false;
	}

	public function ProcessUpdates_file($sendingField,$fieldName,$value,&$pkVal = NULL) {
		$this->UploadFile($fieldName,$value,$pkVal);
		//$this->ResetField($fieldName,$pkVal);
	}

	public function UploadFile($fieldAlias,$fileInfo,&$pkVal = NULL) {
		//$allowedTypes = $this->GetFieldProperty($fieldAlias, 'allowed');
		if (!file_exists($fileInfo['tmp_name'])) { AjaxEcho('alert("File too large. Maximum File Size: '.utopia::ReadableBytes(utopia::GetMaxUpload()).'");'); return; }
		$value = file_get_contents($fileInfo['tmp_name']);
		if ($this->GetFieldType($fieldAlias) === ftUPLOAD) {
			$this->UpdateField($fieldAlias,'',$pkVal);
			// build dir path
			$targetDir = PATH_ABS_CORE.'uploads/'.date('Y-m-d').'/'.$pkVal;
			// make dir
			if (!file_exists($targetDir)) mkdir($targetDir,0755,true);
			// copy file
			file_put_contents($targetDir.'/'.$fileInfo['name'],$value);
			chmod($targetDir.'/'.$fileInfo['name'],0755);
			// set value to path.
			$this->UpdateField($fieldAlias,$targetDir.'/'.$fileInfo['name'],$pkVal);
		} else {
			$this->UpdateField($fieldAlias,$value,$pkVal);
			$this->UpdateField($fieldAlias.'_filename',$fileInfo['name'],$pkVal);
			$this->UpdateField($fieldAlias.'_filetype',$fileInfo['type'],$pkVal);
		}
	}

	public function OnNewRecord($pkValue) {}
	public function OnParentNewRecord($pkValue) {}

	public function UpdateFields($fieldsVals,&$pkVal=NULL) {
		foreach ($fieldsVals as $field => $val) {
			if ($this->UpdateField($field,$val,$pkVal) === FALSE) return FALSE;
		}
	}

	// returns a string pointing to a new url, TRUE if the update succeeds, false if it fails, and null to refresh the page
	private $noDefaults = FALSE;
	public function UpdateField($fieldAlias,$newValue,&$pkVal=NULL) {
		AjaxEcho('//'.str_replace("\n",'',get_class($this)."@UpdateField($fieldAlias,,$pkVal)\n"));
		$this->_SetupFields();
		if (!array_key_exists($fieldAlias,$this->fields)) return FALSE;
		$tableAlias	= $this->fields[$fieldAlias]['tablename'];

		if (!isset($tableAlias)) return FALSE; // cannot update a field that has no table

		if (uEvents::TriggerEvent($this,'BeforeUpdateField',array($fieldAlias)) === FALSE) return FALSE;
		
		$oldPkVal = $pkVal;
		$fieldPK = $this->GetPrimaryKey($fieldAlias);
		
		$tbl		= $this->fields[$fieldAlias]['vtable'];
		$values		= $this->GetValues($fieldAlias);

		if ($newValue !== NULL && $newValue !== '' && is_numeric($newValue) && $this->fields[$fieldAlias]['inputtype'] == itSUGGEST || $this->fields[$fieldAlias]['inputtype'] == itSUGGESTAREA) {
			$valSearch = (is_assoc($values)) ? array_flip($values) : $values;
			$srch = array_search($newValue, $valSearch);
			if ($srch !== FALSE) $newValue = $srch;
		}
		$originalValue = $newValue;

		$field = $this->fields[$fieldAlias]['field'];
		$table		= $tbl['table'];
		$tablePk	= $tbl['pk'];
		
		if (array_key_exists('parent',$tbl)) {
			foreach ($tbl['joins'] as $fromField=>$toField) {
				if ($fromField == $this->sqlTableSetupFlat[$tbl['parent']]['pk']) {
					// find target PK value
					$row = $this->LookupRecord($pkVal);
					$pkVal = $row[$this->GetPrimaryKeyField($fieldAlias)];
					
					// initialise a row if needed
					$tableObj = utopia::GetInstance($table);
					$tableObj->UpdateField($toField,$oldPkVal,$pkVal);

					break; // if linkFrom is the primary key of our main table then we don't update the parent table.
				}
			}
			foreach ($tbl['joins'] as $fromField=>$toField) {
				if ($toField == $tablePk) {
					$field = $fromField;
					$tbl = $this->sqlTableSetupFlat[$tbl['parent']];
					$table		= $tbl['table'];
					$tablePk	= $tbl['pk'];
					break;
				}
			}
		}
				
		if ((preg_match('/{[^}]+}/',$field) > 0) || IsSelectStatement($field) || is_array($field)) {
			$this->ResetField($fieldAlias,$pkVal);
			return FALSE; // this field is a pragma or select statement
		}

		// preformat the value
		if (is_array($newValue))
			$newValue = json_encode($newValue);
		else
			$newValue = trim($newValue);
		$pfVal = $newValue;
		if ($this->GetFieldType($fieldAlias) != ftRAW) $newValue = mysql_real_escape_string($newValue);
		if ($newValue) switch ($this->GetFieldType($fieldAlias)) {      //"STR_TO_DATE('$newValue','".FORMAT_DATE."')"; break;
			case ftRAW: break;
			case ftDATE:		$newValue = $newValue == '' ? 'NULL' : "(STR_TO_DATE('".fixdateformat($newValue)."','".FORMAT_DATE."'))"; break;
			case ftTIME:		$newValue = $newValue == '' ? 'NULL' : "(STR_TO_DATE('$newValue','".FORMAT_TIME."'))"; break;
			case ftDATETIME:	// datetime
			case ftTIMESTAMP:	$newValue = $newValue == '' ? 'NULL' : "(STR_TO_DATE('$newValue','".FORMAT_DATETIME."'))"; break;
			case ftCURRENCY:	// currency
			case ftPERCENT:		// percent
			case ftFLOAT:		// float
			case ftDECIMAL:		$newValue = floatval(preg_replace('/[^0-9\.-]/','',$newValue)); break;
			case ftBOOL:		// bool
			case ftNUMBER:		$newValue = ($newValue==='' ? '' : intval(preg_replace('/[^0-9\.-]/','',$newValue))); break;
		}

		if (isset($this->fields[$fieldAlias]['ismetadata']) && $this->fields[$fieldAlias]['ismetadata']) {
			return $this->SetMetaValue($fieldAlias,$newValue,$pkVal);
		}

		if ($newValue === '' || $newValue === NULL)
			$newValue = 'NULL';
		else {
			$dontQuoteTypes = array(ftRAW,ftDATE,ftTIME,ftDATETIME,ftTIMESTAMP,ftCURRENCY,ftPERCENT,ftFLOAT,ftDECIMAL,ftBOOL,ftNUMBER);
			if (!in_array($this->GetFieldType($fieldAlias),$dontQuoteTypes)) {
				$newValue = "'$newValue'";
			}
		}

		// lets update the field
		$ret = true;
		
		$tableObj = utopia::GetInstance($table);
		$tableObj->UpdateField($field,$newValue,$pkVal);
		
		
		$this->ResetField($fieldAlias,$pkVal);
		$this->ResetField($fieldAlias,$oldPkVal);

		if ($oldPkVal === NULL) {
			// new record added
			// update default values
			if (!$this->noDefaults) {
				$this->noDefaults = true;
				foreach ($this->fields as $dalias => $fieldData) {
					if ($fieldAlias == $dalias) continue; // dont update the default for the field which is being set.
					$default = $this->GetDefaultValue($dalias);
					//echo "//getting value of $dalias : $default \n";
					if (!empty($default)) {
						//echo "//setting default for $dalias to $default PK $pkVal\n";
						$this->UpdateField($dalias,$default,$pkVal);
					}
				}
				$this->noDefaults = false;
			}

			// new record has been created.  pass the info on to child modules, incase they need to act on it.
			$this->OnNewRecord($pkVal);
			$children = utopia::GetChildren(get_class($this));
			foreach ($children as $child => $links) {
				$obj = utopia::GetInstance($child);
				if (method_exists($obj,'OnParentNewRecord')) $obj->OnParentNewRecord($pkVal);
			}
		}
		
		if ($oldPkVal !== $pkVal) {
			// updated PK
			if ($this->FindFilter($fieldAlias)) {
				$pkVal = $pfVal;
				$ret = $this->GetURL($pkVal);
			}
		}
		
		if (array_key_exists('onupdate',$this->fields[$fieldAlias])) {
			foreach ($this->fields[$fieldAlias]['onupdate'] as $callback) {
				list($callback,$arr) = $callback;
				//echo "$callback,".print_r($arr,true);
				if (is_string($callback))// $callback = array($this,$callback);
				$callback = array($this,$callback);
				array_unshift($arr,$pkVal);
				$newRet = call_user_func_array($callback,$arr);
				if ($ret === TRUE) $ret = $newRet;
			}
		}

		$this->ResetField($fieldAlias,$pkVal);

		if ($ret === NULL)
			AjaxEcho("window.location.reload(false);");
		elseif (is_string($ret)) {
			AjaxEcho("window.location.replace('$ret');");
		}
		
		if (uEvents::TriggerEvent($this,'AfterUpdateField',array($fieldAlias)) === FALSE) return FALSE;

		return $ret;
	}

	public function GetCell($fieldName, $row, $url = '', $inputTypeOverride=NULL, $valuesOverride=NULL) {
		if (is_array($row) && array_key_exists('__module__',$row) && $row['__module__'] != get_class($this)) {
			$obj = utopia::GetInstance($row['__module__']);
			return $obj->GetCell($fieldName,$row,$url,$inputTypeOverride,$valuesOverride);
		}
		if ($this->UNION_MODULE)
			$pkField = '__module_pk__';
		else
			$pkField = $this->GetPrimaryKey();
		$fldId = $this->GetEncodedFieldName($fieldName,$row[$pkField]);
		$celldata = $this->GetCellData($fieldName,$row,$url,$inputTypeOverride,$valuesOverride);
		return "<!-- NoProcess --><span id=\"$fldId\">$celldata</span><!-- /NoProcess -->";
	}

	public function GetCellData($fieldName, $row, $url = '', $inputTypeOverride=NULL, $valuesOverride=NULL) {
		if (is_array($row) && array_key_exists('__module__',$row) && $row['__module__'] != get_class($this)) {
			$obj = utopia::GetInstance($row['__module__']);
			return $obj->GetCellData($fieldName,$row,$url,$inputTypeOverride,$valuesOverride);
		}
		$pkVal = NULL;
		if (is_array($row)) {
			if ($this->UNION_MODULE)
				$pkField = '__module_pk__';
			else
				$pkField = $this->GetPrimaryKey();
			$pkVal = $row[$pkField];
		}

		//		echo "// start PP for $fieldName ".(is_array($row) && array_key_exists($fieldName,$row) ? $row[$fieldName] : '')."\n";
		$value = $this->PreProcess($fieldName,(is_array($row) && array_key_exists($fieldName,$row)) ? $row[$fieldName] : '',$row);

		$fieldData = $this->fields[$fieldName];
		//$url = htmlentities($url);
		// htmlentities moved here from the to do.
		$inputType = !is_null($inputTypeOverride) ? $inputTypeOverride : (isset($fieldData['inputtype']) ? $fieldData['inputtype'] : itNONE);
		if ((array_key_exists('htmlencode',$fieldData) && $fieldData['htmlencode']) && $inputType != itTEXTAREA) $value = htmlentities($value,ENT_COMPAT,CHARSET_ENCODING);
		if ($inputType !== itNONE && (($row !== NULL && flag_is_set($this->GetOptions(),ALLOW_EDIT)) || ($row === NULL  && flag_is_set($this->GetOptions(),ALLOW_ADD)))) {
			$attr = !empty($url) ? array('ondblclick'=>'javascript:nav(\''.$url.'\')') : NULL;
			$ret = $this->DrawSqlInput($fieldName,$value,$pkVal,$attr,$inputType,$valuesOverride);
		} else {
			//possible problems where value contains html? (html will be displayed in full)
			if (empty($url) || ($value != '' && $value[0] == '<'))
				$ret = $value;
			else {
				$class = $this->GetFieldProperty($fieldName,'button') ? ' class="btn"' : '';
				$ret = "<a$class href=\"$url\">$value</a>";
			}
		}
		return $ret;
	}

  static $targetChildren = array();
	public function GetTargetURL($field,$row,$includeFilter = true) {
		$fURL = $this->GetFieldProperty($field,'url');
		if ($fURL) return $fURL;

		// check union module
		$searchModule = is_array($row) && array_key_exists('__module__',$row) ? $row['__module__'] : get_class($this);
		//		print_r($GLOBALS['children']);
		//echo "$searchModule<br/>";
		$children = isset(self::$targetChildren[$searchModule]) ? self::$targetChildren[$searchModule] : utopia::GetChildren($searchModule);

		$info = NULL;
		// get specific field
		foreach ($children as $links) {
			foreach ($links as $link) {
				if (!isset($link['parentField'])) continue;
				if ($link['parentField'] == $field) { $info = $link; break; }
			}
		}
		// if not found, check for fallback
		if (!$info) {
			if ($field !== '*') return $this->GetTargetURL('*',$row,$includeFilter);
			return NULL;
		}

		if ($includeFilter) {
			$targetFilter = $this->GetTargetFilters($field,$row);
			//if (!$targetFilter) $targetFilter = $this->GetTargetFilter('*',$row);
		} else
		$targetFilter = NULL;
		//print_r($targetFilter);
		//print_r($this->filters);
		$obj = utopia::GetInstance($info['moduleName']);
		return $obj->GetURL($targetFilter);

		//return (!$targetFilter) ? $targetUrl : "$targetUrl&amp;$targetFilter";
	}

	public function GetTargetFilters($field,$row) {
		//        ErrorLog("GTF($field,$row)");
		if ($row == NULL) return NULL;
		$searchModule = is_array($row) && array_key_exists('__module__',$row) ? $row['__module__'] : get_class($this);

		$children = isset(self::$targetChildren[$searchModule]) ? self::$targetChildren[$searchModule] : utopia::GetChildren($searchModule);

		$info = NULL;
		// get specific field
		foreach ($children as $links) {
			foreach ($links as $link) {
				if (!isset($link['parentField'])) continue;
				if ($link['parentField'] == $field) { $info = $link; break; }
			}
		}
		// if not found, check for fallback
		if (!$info) {
			if ($field !== '*') return $this->GetTargetFilters('*',$row);
			return NULL;
		}

		//echo "<br/>$field:";
		// fieldLinks: array: parentField => childField
		// need to replace the values
		$targetModule = $info['moduleName'];
		$newFilter = array();
//		$additional = array();
		//print_r($info['fieldLinks']);
		foreach ($info['fieldLinks'] as $linkInfo) {
			// fromfield == mortgage_id
			// module_pk == VAL:note_id
			if (!$this->FieldExists($linkInfo['fromField']) and ($fltr = $this->FindFilter($linkInfo['fromField']))) {
				// no field exists, but we do have a filter, we should move that over
				$value = $this->GetFilterValue($fltr['uid']);

				// if its a union AND the fromfield equals the modules primarykey then show the module_pk
				// NO,  if its a union, loop thru that modules fields to find the number of the fromField. then get the value of the corresponding number in this union parent module
			} elseif (array_key_exists('__module__',$row)) {
				$obj = utopia::GetInstance($row['__module__']);
				$unionFields = $obj->fields;
				$uFieldCount = 0;
				$keys = array_keys($unionFields);
				foreach ($keys as $uFieldAlias) {
					if ($uFieldAlias == $linkInfo['fromField']) break;
					$uFieldCount++;
				}

				$ourKeys = array_keys($this->fields);
				$correspondingKey = $ourKeys[$uFieldCount];
				$value = $row[$correspondingKey];
			} else {
				//$value = $row[$linkInfo['fromField']]; // use actual value, getting the real value on every field causes a lot of lookups, the requested field must be the field that stores the actual value
				/* */
				$tableModule = $this->fields[$linkInfo['fromField']]['vtable']['tModule'];
				if ($this->GetRootField($linkInfo['fromField']) == $this->fields[$linkInfo['fromField']]['field']) {
					//if ($tableModule == $this->GetTabledef()) {
					$value = $row[$linkInfo['fromField']];
				} else {
					$value = $this->GetRealValue($linkInfo['fromField'],$row[$this->GetPrimaryKey()]);
				} /* */
				//ErrorLog(print_r($linkInfo,true));
			}
			//echo $value."<br/>";
			if (!empty($value))
				$newFilter['_f_'.$linkInfo['toField']] = $value;
		}
    return $newFilter;
		//print_r(array_merge($newFilter,$additional));
		return array_merge($newFilter,$additional);
		// now returns an array
		$extra = array();
		if (is_array($additional)) foreach ($additional as $key => $val)
		$extra[] = "$key=$val";
		array_unshift($extra,FilterArrayToString($newFilter));
		return join('&amp;',$extra);
	}

	public function ResetField($fieldAlias,$pkVal = NULL) {
		if (!$this->FieldExists($fieldAlias)) return;
		AjaxEcho("//".get_class($this)."@ResetField($fieldAlias~$pkVal)\n");
		// reset the field.

		$enc_name = $this->GetEncodedFieldName($fieldAlias,$pkVal);
		$newRec = is_null($pkVal) ? NULL : $this->LookupRecord($pkVal);

		$data = $this->GetCellData($fieldAlias,$newRec,$this->GetTargetURL($fieldAlias,$newRec));

		utopia::AjaxUpdateElement($enc_name,$data);
		//$ov = base64_encode($data);
		//AjaxEcho("$('div#$enc_name').html(Base64.decode('$ov'));\n");
	}
}

/**
 * List implimentation of uDataModule.
 * Default module for displaying results in a list format. Good for statistics and record searches.
 */
abstract class uListDataModule extends uDataModule {
	public function MakeSortable($updateField,$selector='table tbody'){
		parent::MakeSortable($updateField,$selector);
	}

	public $injectionFields = array();

	//private $maxRecs = NULL;
	//public function SetMaxRecords($value) {
//		$this->maxRecs = $value;
//	}
	public function GetMaxRows() {
		return NULL;//$this->maxRecs;
	}

	public function ProcessUpdates_add($sendingField,$fieldAlias,$value,&$pkVal = NULL) {
		parent::ProcessUpdates_add($sendingField,$fieldAlias,$value,$pkVal);

		//AjaxEcho("$('TABLE.datalist').trigger('applyWidgets');\n");
	}

	public function ResetField($fieldAlias,$pkVal = NULL) {
		// reset the field.
		//AjaxEcho("// reset field: $fieldAlias\n");
		if ($pkVal && !$this->LookupRecord($pkVal)) {
			$enc_name = $this->GetEncodedFieldName($fieldAlias,$pkVal);
			$this->SendHideRowWithField($enc_name);
			return;
		}
		parent::ResetField($fieldAlias,$pkVal);
	}

	public function UpdateField($fieldAlias,$newValue,&$pkVal = NULL) {
		$isNew = ($pkVal === NULL);

		if ($pkVal === NULL) $this->ResetField($fieldAlias,NULL); // reset the "new record" field

		if ($isNew && !$this->CheckMaxRows(1)) return;

		$ret = parent::UpdateField($fieldAlias,$newValue,$pkVal);
		if ($ret === FALSE) return $ret;

		$enc_name = $this->GetEncodedFieldName($fieldAlias);
		if ($isNew) { // && $ret !== FALSE
			AjaxEcho("//$fieldAlias: pk=$pkVal\n");
			// add the row
			$newRec = $this->LookupRecord($pkVal);
			$ov = base64_encode($this->DrawRow($newRec));
			AjaxEcho("$('#$enc_name').parents('TABLE.datalist:eq(0)').children('TBODY').append(Base64.decode('$ov'));\n");
		}

		return $ret;
	}

	public function ProcessUpdates_del($sendingField,$fieldAlias,$value,&$pkVal = NULL) {
		parent::ProcessUpdates_del($sendingField,$fieldAlias,$value,$pkVal);
		$this->SendHideRowWithField($sendingField);
		$this->CheckMaxRows();
	}

	public function CheckMaxRows($mod = 0) {
		//$ds = $this->GetDataset(true);
		AjaxEcho('// max recs: '.$this->GetMaxRows());
		$rows = $this->GetRowCount();
		if (!$this->GetMaxRows() || $rows + $mod < $this->GetMaxRows()) {
			AjaxEcho('$(".newRow").show();');
			return TRUE;
		}
		AjaxEcho('$(".newRow").hide();');
		if ($rows + $mod == $this->GetMaxRows()) return TRUE;
		return FALSE;
	}

	public function SendHideRowWithField($fieldName) {
		AjaxEcho(<<<SCR_END
ele = $('*[name*=$fieldName]');
// hide row - seems to be within a datalist
ele.parents('TR:eq(0)').remove();
ele.parents("TABLE.datalist").trigger('applyWidgets');
SCR_END
		);
	}

	public function ShowData($dataSource = null) {//$sortColumn=NULL) {
		//	echo "showdata ".get_class($this)."\n";
		//	print_r($this->fields);
		//check pk and ptable are set up
		if ($dataSource) {
			$num_rows = count($dataSource);
		} else {
			if (is_empty($this->GetTabledef()) && !$this->UnionModules) { ErrorLog('Primary table not set up for '.get_class($this)); return; }

			$dataset = $this->GetDataset(TRUE);
			$num_rows = $this->GetRowCount();
			if (mysql_error()) return;
		}

		$children = utopia::GetChildren(get_class($this));
		//print_r($children);
		foreach ($children as $childModule => $links) {
			foreach ($links as $link) {
				//if (!$child) continue;
				//ErrorLog(print_r($child,true));
				$obj = utopia::GetInstance($link['moduleName']);
				if (!flag_is_set($this->GetOptions(),ALLOW_ADD)
						&& flag_is_set($obj->GetOptions(),ALLOW_ADD)
						&& is_subclass_of($link['moduleName'],'uSingleDataModule')) {
					$url = $obj->GetURL(array($obj->GetModuleId().'_new'=>1));
					utopia::LinkList_Add('list_functions:'.get_class($this),null,CreateNavButton('New Item',$url,array('class'=>'greenbg')),1);
				}
			}
		}

		TriggerEvent('OnShowDataList');
		//		LoadChildren(get_class($this));
		// first draw header for list
		//		$fl = (flag_is_set($this->GetOptions(),ALLOW_FILTER)) ? ' filterable' : '';
		if (!isset($GLOBALS['inlineListCount'])) $GLOBALS['inlineListCount'] = 0;
		else $GLOBALS['inlineListCount']++;

		$tabGroupName = utopia::Tab_InitGroup();

		//$layoutID = utopia::tab_ //$tabGroupName.'-'.get_class($this)."_list_".$GLOBALS['inlineListCount'];
		$metadataTitle = ' {tabTitle:\''.$this->GetTitle().'\', tabPosition:\''.$this->GetSortOrder().'\'}';
		//echo "<div id=\"$layoutID\" class=\"draggable$metadataTitle\">";
		ob_start();
		echo "<table class=\"layoutListSection datalist\">";

		/*		echo "<colgroup>";
		 // need first 'empty' column for buttons?
		 if (flag_is_set($this->GetOptions(),ALLOW_DELETE)) { echo "<col></col>"; }
		 foreach ($this->fields as $fieldName => $fieldData) {
			if ($fieldData['visiblename'] === NULL) continue;
			$attr = $this->GetFieldType($fieldName) == ftCURRENCY ? ' align="char" char="."' : '';
			echo "<col$attr></col>";
			}
			echo "</colgroup>\n";   */
		$sectionFieldTitles = array();
		// TODO: pagination for list record display
		if (!flag_is_set($this->GetOptions(),LIST_HIDE_HEADER)) {
			echo "<thead style=\"font-weight: bold\">";

			ob_start();
			echo '<tr>';
			// need first 'empty' column for buttons?
			$colcount = 0;
			// start of SECTION headers
			//$this->firsts = array();
			if (count($this->layoutSections) > 1) {
				if (flag_is_set($this->GetOptions(),ALLOW_DELETE)) { echo "<td class=\"{sorter: false}\">&nbsp;</td>"; $colcount++; }
				$sectionCount = 0;
				$sectionID = NULL;
				$keys = array_keys($this->fields);
				$lastFieldName = end($keys);
				foreach ($this->fields as $fieldName => $fieldData) {
					if ($fieldData['visiblename'] === NULL) continue;
					if ($sectionID === NULL) $sectionID = $fieldData['layoutsection'];

					if ($fieldData['layoutsection'] !== $sectionID) {// || $fieldName == $lastFieldName) {
						// write the section, and reset the count
						$sectionName = $this->layoutSections[$sectionID];
						$secClass = empty($sectionName) ? '' : ' sectionHeader';
						echo "<td colspan=\"$sectionCount\" class=\"$secClass\">".nl2br(htmlentities_skip($sectionName,'<>"'))."</td>";
						$sectionCount = 0;
						$sectionID = $fieldData['layoutsection'];
					}
					$sectionFieldTitles[$sectionID] = array_key_exists($sectionID,$sectionFieldTitles) ? $sectionFieldTitles[$sectionID] : !empty($fieldData['visiblename']);
					//if ($sectionCount == 0 && $sectionID > 0) $this->firsts[$fieldName] = true;
					$sectionCount++;
				}
				$sectionName = $this->layoutSections[$sectionID];
				$secClass = empty($sectionName) ? '' : ' sectionHeader';
				echo "<td colspan=\"$sectionCount\" class=\"$secClass\">".nl2br(htmlentities_skip($sectionName,'<>"'))."</td>";
				echo "</tr>";
			}

			// start of FIELD headers
			echo '<tr>';
			if (flag_is_set($this->GetOptions(),ALLOW_DELETE)) { echo '<td></td>'; $colcount++; }
			foreach ($this->fields as $fieldName => $fieldData) {
				if ($fieldData['visiblename'] === NULL) continue;
				$colcount++;

/*				$classes = array();
				switch ($this->GetFieldType($fieldName)) {
					case 'date':
					case 'time':
					case 'datetime':
					case 'timestamp':
						$classes[] = '{sorter: \'datetime\'}';
				}
				//			if ($fieldData['inputtype'] == itCOMBO && flag_is_set($fieldData['options'],ALLOW_EDIT))
				//				$classes[] = '{textExtraction: \'selectbox\'}';
				//if (array_key_exists($fieldName,$this->firsts)) $classes[] = 'sectionFirst';
				$class = count($classes) > 0 ? ' class="'.join(' ',$classes).'"' : '';
				//			$attr = $this->GetFieldType($fieldName) == ftCURRENCY ? ' style="text-align:\'.\'"' : '';
 */
				echo '<th class="sortable" rel="'.$fieldName.'|'.$this->GetModuleId().'">';
				echo nl2br(htmlentities_skip($fieldData['visiblename'],'<>"')).'<br/>';
				if (flag_is_set($this->GetOptions(),ALLOW_FILTER) && $this->hasEditableFilters === true && $this->hideFilters !== TRUE) {
					foreach ($this->filters as $fType) {
						foreach ($fType as $filterset) { //flag_is_set($fieldData['options'],ALLOW_FILTER)) {
							foreach ($filterset as $filterInfo) {
								if ($fieldName != $filterInfo['fieldName']) continue;
								if ($filterInfo['it'] === itNONE) continue;
								echo $this->GetFilterBox($filterInfo);
								//break 2;
							}
						}
					}
				}
				echo "</th>";
			}
			echo '</tr>'; // close column headers

			$c = ob_get_contents();
			ob_end_clean();

			$pager = $num_rows > 100 ? '<span class="pager" style="float:right;"></span>' : '';
			$records = ($num_rows == 0) ? "There are no records to display." : 'Total Rows: '.$num_rows.' (Max 150 shown)';
			echo '<tr><td colspan="'.$colcount.'">'.$pager.'<b>{list.'.get_class($this).'}'.$records.'</b></td></tr>';

			echo $c;

			echo "</thead>\n";
		}

		//		if ($this->hasEditableFilters === true)
		//			echo "</form>";
		/*
		 // is filterable?
		 if (flag_is_set($this->GetOptions(),ALLOW_FILTER) && $this->hasEditableFilters === true) {
			echo "<thead><form method=get><input type=hidden name='uuid' value='".$this->GetUUID()."'>"; // must have UUID for filters
			echo "<th><input type=button onclick='rf(this)' value='F'></th>";
			//if (flag_is_set($this->GetOptions(),ALLOW_DELETE)) echo "<th></th>";
			$col = 1;
			foreach ($this->fields as $fieldName => $fieldData) {
			if (empty($fieldData['visiblename'])) continue;
			$class = 'filter-HVAL-'.$col;
			echo "<th class='filterheader' nowrap='nowrap'>";// class='$class'>";
			if (flag_is_set($fieldData['options'],ALLOW_FILTER))
			echo $this->GetFilterBox($fieldName,$col);
			echo "</th>";
			$col++;
			}
			echo "</form></thead>\n";
			}
			*/
		// now display data rows
		// process POST filters
		$total = array();
		$totalShown = array();

		timer_start('display rows');

		$gUrl = '';
		//		$gUrl = $this->GetTargetUrl('*');
		//		if (!empty($gUrl))
		//			$gUrl = " l_url='$gUrl'";

		$body = "<tbody$gUrl>";
		if ($num_rows == 0) {
		} else {
			//			if ($result != FALSE && mysql_num_rows($result) > 200)
			//				echo "<tr><td colspan=\"$colcount\">There are more than 200 rows. Please use the filters to narrow your results.</td></tr>";
			$i = 0;
			while (($dataSource && isset($dataSource[$i]) && $row = $dataSource[$i]) || ($row = $this->GetRecord($dataset,$i)) && $i <= 150) {
				$i++;
				// move totals here
				foreach ($this->fields as $fieldName => $fieldData) {
					if ($fieldData['visiblename'] === NULL) continue;
					switch ($this->GetFieldType($fieldName)) {
						case ftNUMBER:
						case ftCURRENCY:
						case ftPERCENT:
							if (!array_key_exists($fieldName,$total)) $total[$fieldName] = 0;
							if (!array_key_exists($fieldName,$totalShown)) $totalShown[$fieldName] = 0;
							//$pkVal = $row[$this->GetPrimaryKey()];
							$preProcessValue = floatval(preg_replace('/[^0-9\.-]/','',$this->PreProcess($fieldName,$row[$fieldName],$row)));
							if ($i <= 150) $totalShown[$fieldName] += $preProcessValue;
							$total[$fieldName] += $preProcessValue;
							break;
						default: break;
					}
				}
				$body .= $this->DrawRow($row);
			}
		}
		$body .= "</tbody>";
		timer_end('display rows');
		$foot = '';
		if (flag_is_set($this->GetOptions(),ALLOW_ADD)) {
			$hideNew = ($this->GetMaxRows() && $num_rows >= $this->GetMaxRows()) ? ' style="display:none"' : '';
			$foot .= '<tr class="newRow"'.$hideNew.'>';
			if (flag_is_set($this->GetOptions(),ALLOW_DELETE)) $foot .= "<td class=\"new-ident\"></td>";
			foreach ($this->fields as $fieldName => $fieldData) {
				if ($fieldData['visiblename'] === NULL) continue;
				$classes=array();
				//if (array_key_exists($fieldName,$this->firsts)) $classes[] = 'sectionFirst';
				$class = count($classes) > 0 ? ' class="'.join(' ',$classes).'"' : '';
				//$this->PreProcess($fieldName,'');
				//$enc_name = $this->GetEncodedFieldName($fieldName);
				if (flag_is_set($fieldData['options'],ALLOW_ADD))
					$foot .= "<td$class>".$this->GetCell($fieldName, NULL).'</td>';
				//if (array_key_exists('inputtype',$fieldData) && $fieldData['inputtype'] !== itNONE && flag_is_set($fieldData['options'],ALLOW_ADD)) {
				//	$foot .= "<td$class>".$this->GetCell($fieldName, NULL).'</td>';
					//$foot .= "<td$class><div id=\"$enc_name\">".$this->DrawSqlInput($fieldName).'</td>';
				//} else
				// TODO: Default value not showing on new records (list)
				//$foot .= "<td$class><div id=\"$enc_name\">".$this->GetLookupValue($fieldName,$this->GetDefaultValue($fieldName)).'</td>';
			}
			$foot .= '</tr>';
		}

		if (!empty($total)) {
			$foot .= '<tr>';
			if (flag_is_set($this->GetOptions(),ALLOW_DELETE)) $foot .= "<td class=\"totals-ident\"></td>";
			foreach ($this->fields as $fieldName => $fieldData) {
				if ($fieldData['visiblename'] === NULL) continue;
				$classes=array();
				//if (array_key_exists($fieldName,$this->firsts)) $classes[] = 'sectionFirst';
				$class = count($classes) > 0 ? ' class="'.join(' ',$classes).'"' : '';
				if (flag_is_set($this->GetOptions(),SHOW_TOTALS) && array_key_exists($fieldName,$total)) {
					$foot .= "<td$class><b>";
					if ($totalShown[$fieldName] != $total[$fieldName])
					$foot .= htmlentities($this->PreProcess($fieldName,$totalShown[$fieldName])).'(shown)<br/>';
					$foot .= htmlentities($this->PreProcess($fieldName,$total[$fieldName]));
					$foot .= '</b></td>';
				} else
				$foot .= "<td$class></td>";
			}
			$foot .= '</tr>';
		}
		if (!empty($foot))
		echo "<tfoot>$foot</tfoot>";

		echo $body;
		// now finish table
		echo "</table>";//"</div>";

		$cont = ob_get_contents();
		ob_end_clean();

		$soMod = get_class($this) == utopia::GetCurrentModule() ? -10 : 0;
		utopia::Tab_Add($this->GetTitle(),$cont,$tabGroupName,false,$this->GetSortOrder()+$soMod);
		utopia::Tab_InitDraw($tabGroupName);
	}

	function DrawRow($row) {
		$pk = $row[$this->GetPrimaryKey()];
		$body = "<tr rel=\"$pk\">";
		if (flag_is_set($this->GetOptions(),ALLOW_DELETE)) {
			//$delbtn = utopia::DrawInput($this->CreateSqlField('delete',$row[$this->GetPrimaryKey()],'del'),itBUTTON,'x',NULL,array('class'=>'btn redbg','onclick'=>'if (!confirm(\'Are you sure you wish to delete this record?\')) return false; uf(this);'));
			$delbtn = $this->GetDeleteButton($row[$this->GetPrimaryKey()]);
			$body .= '<td style="width:1px">'.$delbtn.'</td>';
		}
		foreach ($this->fields as $fieldName => $fieldData) {
			if ($fieldData['visiblename'] === NULL) continue;
			$targetUrl = $this->GetTargetUrl($fieldName,$row);
			$classes=array();
			$class = count($classes) > 0 ? ' class="'.join(' ',$classes).'"' : '';
			$body .= '<td>'.$this->GetCell($fieldName,$row,$targetUrl).'</td>'; //"<td$class$hval$fUrl$fltr id=\"$fldId\">$cellData</td>";
		}
		$body .= "</tr>\n";
		return $body;
	}
}

/**
 * Single form implimentation of uDataModule.
 * Default module for displaying results in a form style. Good for Data Entry.
 */
abstract class uSingleDataModule extends uDataModule {
	/*
	 public function CreateParentNavButtons() {
		foreach ($this->parents as $parentName => $linkArray){
		if ($parentName !== utopia::GetCurrentModule()) continue;
		foreach ($linkArray as $linkInfo) {
		if ($linkInfo['parentField'] !== NULL) continue; // is linked to fields in the list, skip it
		if (flag_is_set($this->GetOptions(),ALLOW_ADD)) { // create an addition button  --  && utopia::GetCurrentModule() == get_class($this)
		$filters = array('newrec'=>1); // set this filter so that the primary key is negative, this will force no policy to be found, and show a new form
		utopia::AppendVar('footer_left',CreateNavButton('New Record',BuildQueryString($this->GetURL(),$filters),NULL,array('class'=>'btn greenbg')));
		}
		}
		}

		//		parent::CreateParentNavButtons();
		}*/
	public function ProcessUpdates_del($sendingField,$fieldAlias,$value,&$pkVal = NULL) {
		parent::ProcessUpdates_del($sendingField,$fieldAlias,$value,$pkVal);
		AjaxEcho('history.go(-1);');
	}

	public function ShowData(){//$customFilter=NULL) {//,$sortColumn=NULL) {
		//	echo "showdata ".get_class($this)."\n";
		//check pk and ptable are set up
		if (is_empty($this->GetTabledef())) { ErrorLog('Primary table not set up for '.get_class($this)); return; }
		//		$fullfilters = $this->GetFullFilters($filter);

		//		echo "looking for ".$this->GetPrimaryKey().'in';
		//		print_r($fullfilters);
		//		$newRec = ($this->HasFilter($this->GetPrimaryKey()) === FALSE);
		//		$result = $this->GetCurrentRecord();
		//		$row == NULL;

		$row = NULL;
		if (!$this->IsNewRecord()) { // records exist, lets get the first.
			$dataset = $this->GetDataset(TRUE);
			$row = $this->GetRecord($dataset,0);
			if (!$row) {
				//echo "The record you requested is not available.";
				return;
			}
			// TODO: pagination for single record display
			//			if (mysql_num_rows($result) > 1) {
			// multiple records exist in this set, sort out pagination
			//			}

		}

		TriggerEvent('OnShowDataDetail');

		if (flag_is_set($this->GetOptions(),ALLOW_DELETE) && !$this->IsNewRecord()) {
			$fltr = $this->FindFilter($this->GetPrimaryKey(),ctEQ,itNONE);
			$delbtn = $this->GetDeleteButton($this->GetFilterValue($fltr['uid']),'Delete Record');
			utopia::AppendVar('footer_left',$delbtn);
		}

		//		if (!$this->IsNewRecord()) { // records exist, lets get the first.
		// pagination?
		//			if (mysql_num_rows($result) > 1) {
		// multiple records exist in this set, sort out pagination
		//			}
		//			$row = $this->GetRecord(0);
		//		}

		$order = $this->GetSortOrder();
		if (get_class($this) == utopia::GetCurrentModule()) $order -= 10;
		$extraCount = 1;
//		if (!flag_is_set($this->GetOptions(), NO_TABS))
		$tabGroupName = utopia::Tab_InitGroup();
		foreach ($this->layoutSections as $sectionID => $sectionName) {
			//$secCount++;
			//			echo "<div class='layoutSection' >";
			// add header?
			if ($sectionName === '') {
				if ($sectionID === 0) $SN = 'General';
				else { $SN = "Extra ($extraCount)"; $extraCount++; }
			} else
			$SN = ucwords($sectionName);
			$metadataTitle = " {tabTitle:'$SN', tabPosition:'".($order+$extraCount)."'}";
			$order = $order + 0.01;

			//            $globTargetUrl = $this->GetTargetUrl('*',$row);
			//            $globTargetFilter = $this->GetTargetFilter('*',$row);
			// start table
			//echo "<div id='$tabGroupName-".get_class($this)."_$sectionID' class=\"draggable$metadataTitle\">";
			$out = "<table class=\"layoutDetailSection\">";
			//if ($SN && count($this->layoutSections) == 1) $out .= "<tr><th colspan=\"2\">$SN</th></tr>"; // add a header to the table


			$fields = $this->GetFields(true,$sectionID);
			$hasFieldHeaders = false;
			foreach ($fields as $fieldName => $fieldData) {
				$hasFieldHeaders = $hasFieldHeaders || !empty($fieldData['visiblename']);
			}

			$fieldCount = count($fields);
			foreach ($fields as $fieldName => $fieldData) {
				//$pkValue	= is_array($row) && array_key_exists($this->GetPrimaryKey(),$row) ? $row[$this->GetPrimaryKey()] : NULL;
				$fieldValue	= $this->PreProcess($fieldName,is_array($row) && array_key_exists($fieldName,$row) ? $row[$fieldName] : '',$row);

				$targetUrl = $this->GetTargetUrl($fieldName,$row);

				$out .= "<tr>";

				if ($hasFieldHeaders && $fieldCount !== 1)
					$out .= "<td class=\"fld\">".$fieldData['visiblename']."</td>";
				$out .= '<td>'.$this->GetCell($fieldName,$row,$targetUrl).'</td>';

				$out .= "</tr>";
			}
			$out .= "</table>";
//			if (!flag_is_set($this->GetOptions(), NO_TABS))
				utopia::Tab_Add($SN,$out,$tabGroupName,false,$order);
//			else
//				echo $out;
		}

//		if (!flag_is_set($this->GetOptions(), NO_TABS))
			utopia::Tab_InitDraw($tabGroupName);
	}
}
