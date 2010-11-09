<?php
//-- debugging
define('SHOW_QUERY'		,false);
define('BASE_MODULE','uCMS_View');

//--  InputType
define('itNONE'		,'');
define('itBUTTON'	,'button');
define('itSUBMIT'	,'submit');
define('itRESET'	,'reset');
define('itCHECKBOX'	,'checkbox');
define('itOPTION'	,'option');
define('itPASSWORD'	,'password');
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
define('IS_ADMIN',flag_gen());
define('LIST_HIDE_HEADER',flag_gen());

define('DEFAULT_OPTIONS',ALLOW_FILTER);

// START CLASSES

/**
 * Basic flexDb module. Enables use of adding parents and module to be installed and run.
 * No field or data access is available, use flexDb_DataModule and its decendants.
 */
abstract class flexDb_BasicModule {
	public static function __callStatic($name, $arguments) {
		// Note: value of $name is case sensitive.
		$instance = FlexDB::GetInstance(get_class($this));
		return call_user_func_array(array($instance,$name),$arguments);
	}

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

		//		if (!array_key_exists('last_module',$_SESSION)) $_SESSION['last_module'] = NULL;
		//		if (array_key_exists('this_module',$_SESSION) && GetCurrentModule() != $_SESSION['this_module'])
		//			$_SESSION['last_module'] = $_SESSION['this_module'];
		//		$_SESSION['this_module'] = GetCurrentModule();


		// setup parents
		//timer_start('PARENTS:'.get_class($this));
		$this->_SetupParents();
		//timer_end('PARENTS:'.get_class($this));
		// setup fields
		//		$this->SetupFields(); // must be done in data subclass
		// init parents?
		//		foreach ($this->parents as $parentModule => $parent)
		//			CallModuleFunc($parentModule,'Initialise');
		return true;
	}

	public $isDisabled = false;
	public function DisableModule() {
		$this->isDisabled = true;
	}

	public function CanParentLoad($parent) {
		// skip parentload for all other than index.php UNLESS the module has PersistentParentLoad == true
		//		if (basename($_SERVER['SCRIPT_FILENAME']) !== 'index.php') {
		//			if (!flag_is_set($this->GetOptions(),PERSISTENT_PARENT)) return FALSE;
		//			if (!isset($this->PersistentParentLoad)) return false;
		//			if ($this->PersistentParentLoad !== TRUE) return false;
		///		}

		if ($this->isDisabled) return false;
		if (!$this->IsActive()) return false;
		//if (flag_is_set($this->GetOptions(),IS_ADMIN) && !internalmodule_AdminLogin::IsLoggedIn()) return false;
		//if (!$this->HasParent($parent)) return false;

		//if (flag_is_set($this->GetOptions(),PERSISTENT_PARENT)) return true;

		// already loaded for this parent?
		//if (array_key_exists($parent,$this->parentLoaded)) return false;

//ErrorLog(get_class($this).': can1 '.(GetModuleVar($parent,'hasRun') ? 1:0).$this->ParentLoadPoint());
	//	flexdb::CancelTemplate();
//echo $parent.' '.get_class($this);
		if ($parent == GetCurrentModule()) return GetModuleVar($parent,'hasRun') == $this->ParentLoadPoint();
//ErrorLog(get_class($this).': can2');

		//echo "$parent ".(GetModuleVar($parent,'hasRun')?'Run':'noRun').' '.get_class($this).'='.$this->ParentLoadPoint()."<br/>";#
		// parent is child itself, if we're not persistant, dont load
		if (!flag_is_set($this->GetOptions(), PERSISTENT_PARENT)) return false;
//ErrorLog(get_class($this).': can3');

		// we are persistent, so check if our loadpoint matches the parents loaded state.
		$activeParent = GetModuleVar($parent, 'activeParent');
		$ploaded = GetModuleVar($parent, 'parentLoaded');
	//	echo $activeParent;
	//	print_r($ploaded);
		//die();
		return (array_key_exists($activeParent,$ploaded) && $ploaded[$activeParent] === 1) == $this->ParentLoadPoint();

//		return true;
	}

	public function ParentLoadPoint() { return 1; }

	public function HasParentLoaded($parent) {
		return array_key_exists($parent,$this->parentLoaded);
	}

	public $activeParent = NULL;
	public $parentLoaded = array();
	// timeframe is either pre(0) or post(1) currentmodule_Load, should be checked against module "ParentLoadPreference", which defaults to Post
	public function _ParentLoad($parent) {
//echo 'attempt '.get_class($this).' for '.$parent.'<br/>';
		if (!$this->CanParentLoad($parent)) return NULL;

		//pre or post
		//		ErrorLog(get_class($this).':'.$this->ParentLoadPoint().'='.$timeframe);
		//		if () return NULL;
		//		ErrorLog("loading ".get_class($this)." with ".($timeframe ? '1':'0'));
		//if ($parent == '/' && !array_key_exists(GetCurrentModule(),$this->parents)) return TRUE;
		//		ErrorLog("parentloading ".get_class($this)." with $parent");

		if (!$this->HasParent($parent)) return NULL;
		//ErrorLog(array_key_exists($parent,$this->parents) ? '1' : 0);
//		if ($parent == get_class($this)) return NULL;
//ErrorLog(get_class($this).': p2');
		if ($this->HasParentLoaded($parent)) return NULL;
//ErrorLog(get_class($this).': p3');
//ErrorLog(GetCurrentModule().':'.$parent.'->'.get_class($this));

		$lm = FlexDB::GetVar('loadedModules',array());
		if (array_search($this,$lm,true) === FALSE) $lm[] = $this;
//echo 'PL '.get_class($this).' for '.$parent.'<br/>';
		$this->activeParent = $parent;
		$this->parentLoaded[$parent] = 0;

//		timer_start('LoadChildren 0 '.get_class($this)."->_ParentLoad($parent)",FlexDB::GetChildren(get_class($this)));
		$lc = $this->LoadChildren();
//		timer_end('LoadChildren 0 '.get_class($this)."->_ParentLoad($parent)");
		//echo get_class($this).' '.$parent.' '.$lc.(is_numeric($lc) ? 'n':'');
		if ($lc !== TRUE && $lc !== NULL) return $lc;

		timer_start('ParentLoad: '.get_class($this).' for '.$parent);
//mail('oridan82@gmail.com','aaa','parentload:'.get_class($this).' for '.$parent);
//echo 'parentload:'.get_class($this).' for '.$parent."\n";
		$result = $this->ParentLoad($parent);
//echo 'end:'.get_class($this).' for '.$parent."\n";
//mail('oridan82@gmail.com','aaa','end:'.get_class($this).' for '.$parent);
		if ($result !== TRUE && $result !== NULL) return $result;
		//$this->CreateParentNavButtons($parent);

		timer_end('ParentLoad: '.get_class($this).' for '.$parent);
		$this->parentLoaded[$parent] = 1;

//		timer_start('LoadChildren 1 '.get_class($this)."->_ParentLoad($parent)",FlexDB::GetChildren(get_class($this)));
		$lc = $this->LoadChildren();
//		timer_end('LoadChildren 1 '.get_class($this)."->_ParentLoad($parent)");
		if ($lc !== TRUE && $lc !== NULL) return $lc;

		$this->activeParent = NULL;

		return $result !== FALSE;
	}

	public function LoadChildren() {
		$children = FlexDB::GetChildren(get_class($this));
		//print_r($children);
		//$keys = array_keys($children);
		//echo 'loading children for '.get_class($this).': '.implode(', ',$keys).'<br/>';

		foreach ($children as $child => $links) {
			//echo 'RUNNING: '.$child.' for '.get_class($this).'<br/>';
			$result = CallModuleFunc($child,'_ParentLoad',get_class($this));
			if ($result === FALSE) return FALSE;
			if (is_numeric($result) && $result > 0) return $result -1;
		}
		return true;
	}

	public $hasRun = false;
	public function _RunModule() {
		if (get_class($this) == GetCurrentModule()) {
			//$qs = $_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '';
			$url = $this->GetURL($_GET);
			$checkurl = $_SERVER['REQUEST_URI'];
			//$checkurl = str_replace('?'.$_SERVER['QUERY_STRING'],'',$_SERVER['REQUEST_URI']);
		//	if (strpos($checkurl,'?') !== FALSE) $checkurl = substr($checkurl,0,strpos($checkurl,'?'));
			if (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') != $this->isSecurePage)
				|| $checkurl !== $url) {//stripos(urldecode($_SERVER['REQUEST_URI']),urldecode($url)) === FALSE) {
					$abs = '';
					if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') != $this->isSecurePage) {
						$layer = 'http';
						if ($this->isSecurePage) $layer .= 's';
						$abs = $layer.'://'.FlexDB::GetDomainName();
					}
					header('Location: '.$abs.$url,true,301); die();
			}
		}
		if (flag_is_set($this->GetOptions(),IS_ADMIN) && FlexDB::UsingTemplate(DEFAULT_TEMPLATE)) FlexDB::$adminTemplate = true;

		if ($this->isDisabled) return;
		//		$this->SetupFields();
		//		die('setup fields');
//		ErrorLog('run 0');
//		timer_start('LoadChildren 0 '.get_class($this).'->_RunModule');
		$lc = $this->LoadChildren();
//		timer_end('LoadChildren 0 '.get_class($this).'->_RunModule');
		if ($lc !== TRUE && $lc !== NULL) return $lc;
		//if ($this->LoadChildren() === FALSE) return FALSE;
//		ErrorLog('run 1');
		timer_start('Run Module');
		$this->RunModule();
		timer_end('Run Module');
		$this->hasRun = true;
//		echo 'run 2';
//		timer_start('LoadChildren 1 '.get_class($this).'->_RunModule');
		$lc = $this->LoadChildren();
//		timer_end('LoadChildren 1 '.get_class($this).'->_RunModule');
		if ($lc !== TRUE && $lc !== NULL) return $lc;
//		echo 'run 3';
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
		$children = FlexDB::GetChildren($parentModule);
		return array_key_exists(get_class($this),$children);
	}
	public function HasChild($childModule) {
		$children = FlexDB::GetChildren(get_class($this));
		return array_key_exists($childModule,$children);
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
		if ($parentModule === '') $parentModule = BASE_MODULE;
		//elseif ($parentModule === '/') $parentModule = GetCurrentModule();

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
				if (is_subclass_of($this,'flexDb_DataModule')) {
					$fltr =& $this->FindFilter($linkInfo['toField'],$linkInfo['ct'],itNONE,FILTER_WHERE);
					if ($fltr === NULL) {
						$uid = $this->AddFilterWhere($linkInfo['toField'],$linkInfo['ct']);
						$fltr =& $this->GetFilterInfo($uid);
					} else $uid = $fltr['uid'];
					$fltr['linkFrom'] = $parentModule.':'.$linkInfo['fromField'];
					$linkInfo['_toField'] = $linkInfo['toField'];
					$linkInfo['toField'] = $uid;
				}
				//				if (is_numeric($key)) {
				//					unset($fieldLinks[$key]);
				//					$fieldLinks[$val] = $val;
			}	}//	}

	/*	if (GetCurrentModule() == get_class($this)) {
			if ($parentModule === '/') $pm = GetCurrentModule();
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
			breadcrumb::AddModule($pm,$filters,0,GetCurrentModule());
		}*/

		//if (!array_key_exists('children',$GLOBALS)) $GLOBALS['children'] = array();
		$children = FlexDB::GetChildren($parentModule);
		// check parent field hasnt already been selected
		if ($parentField !== NULL && $parentField !== '*') {
			//if (array_key_exists($parentModule,$children))
			foreach ($children as $childName => $links) {
				foreach ($links as $link) {
					//if ($child['moduleName'] !== get_class($this)) continue;
					if ($link['parentField'] == $parentField) {
						//trigger_error('Cannot add parent ('.$parentModule.') of '.get_class($this).', parentField ('.$parentField.') has already been defined in '.$child['moduleName'].'.',E_USER_ERROR);
						return;
					}
				}
			}
		}


		if (!is_null($fieldLinks) && !is_array($fieldLinks)) // must be null or array
			trigger_error('Cannot add parent ('.$parentModule.') of '.get_class($this).', fieldLinks parameter is an invalid type.',E_USER_ERROR);

		$info = array('moduleName'=>get_class($this), 'parentField'=>$parentField, 'fieldLinks' => $fieldLinks, 'text' => $text);
		$this->parents[$parentModule][] = $info;
		FlexDB::AddChild($parentModule, get_class($this), $info);

		return $fieldLinks;
	}

	public function AddChild($childModule,$fieldLinks=NULL,$parentField=NULL,$text=NULL) {
		//$childModule = (string)$childModule;
		//echo "addchild $childModule<br/>";
		CallModuleFunc($childModule,'AddParent',get_class($this),$fieldLinks,$parentField,$text);
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
		$requireAdmin = flag_is_set($this->GetOptions(),IS_ADMIN);
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

	public $rewriteMapping=NULL;
	public $rewriteURLReadable=NULL;
	public function HasRewrite() { return $this->rewriteMapping !== NULL; }
	//    array( 'u' , 'fieldName' , 'some-random-text' , 'another_field' )
	public function SetRewrite($mapping,$URLReadable = false) {
		if ($mapping === FALSE || $mapping === NULL) {
			$this->rewriteMapping = NULL; return;
		}
		if (is_string($mapping) && $mapping !== '') $mapping = array($mapping);
		if (!is_array($mapping)) $mapping = array();
		array_unshift($mapping,'{uuid}');

		// defaults?

		$this->rewriteMapping = $mapping;
		$this->rewriteURLReadable = $URLReadable;

		$this->ParseRewrite();
	}

	public function ParseRewrite($caseSensative = false) {
		if ($this->rewriteMapping === NULL) return FALSE;
		if (get_class($this) !== GetCurrentModule()) return FALSE;

		$REQUESTED_URL = array_key_exists('HTTP_X_REWRITE_URL',$_SERVER) ? $_SERVER['HTTP_X_REWRITE_URL'] : $_SERVER['REQUEST_URI'];
		$REQUESTED_URL = preg_replace('/\?.*/i','',$REQUESTED_URL);

		if (strpos($REQUESTED_URL, PATH_REL_ROOT.'u/')===FALSE) return FALSE;
		$path = urldecode(str_replace(PATH_REL_ROOT.'u/','',$REQUESTED_URL));
		$return = array();

		$sections = explode('/',$path);
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
			//echo $this->rewriteMapping[$key].' '.$map.'<br/>';
		}
		//print_r($return);die();
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
					//echo $match;
					$newVal = '';
					if (array_key_exists($fieldName,$filters)) $newVal = $filters[$fieldName];
					elseif (array_key_exists('_f_'.$fieldName,$filters)) $newVal = $filters['_f_'.$fieldName];
			//		else $newVal = '';

					unset($filters[$fieldName]);
					unset($filters['_f_'.$fieldName]);

					//					else {
					// check filter names for this field ($match)
					//$filter = $this->find
					//}

					//if ($newVal !== NULL)
					$mapped[$key] = str_replace('{'.$fieldName.'}',$newVal,$mapped[$key]);
				}
			}
		}

		foreach ($mapped as $key => $val) {
			$URLreadable = is_array($this->rewriteURLReadable) ? $this->rewriteURLReadable[$key] : $this->rewriteURLReadable;
			$mapped[$key] = ($URLreadable) ? urlencode(UrlReadable($val)) : urlencode($val);
		//	print_r($mapped[$key]);
		}

		// DONE: ensure all rewrite segments are accounted for (all '/' are present)
		return PATH_REL_ROOT.'u/'.join('/',$mapped);
	}

	public function GetURL($filters = NULL, $encodeAmp = false) {
		if (!is_array($filters))
			$filters = array();
		//			foreach ($filters as $fieldName => $val) {
		//				$filArr[$fieldName] = $val;
		//			}
		//		}
		$uuidArr = array('uuid'=>$this->GetUUID());
		if (is_array($uuidArr['uuid'])) $uuidArr['uuid'] = $uuidArr['uuid'][0];

		if (get_class($this) !== BASE_MODULE || $this->rewriteMapping !== NULL)
			$filters = array_merge($uuidArr,$filters);

		$url = DEFAULT_FILE;
		if ($this->rewriteMapping !== NULL)
			$url = $this->RewriteURL($filters);
   
		return BuildQueryString($url,$filters,$encodeAmp);
	}
	public function IsActive() {
		if (flag_is_set($this->GetOptions(),ALWAYS_ACTIVE)) return true;
		return FlexDB::ModuleExists(get_class($this),true);
	}
	public function CanBeActive() {
		// check dependancies exist
		if (!$this->CheckDependencies()) return FALSE;

		// check parent is active
		//		if ($this->module_parent == '' || $this->module_parent == '*') return true;
		/*
		 if ((!empty($this->module_parent) && $this->module_parent != '*') && (!class_exists($this->module_parent) || !CallModuleFunc($this->module_parent,'IsActive'))) {
			if ($showErrors) ErrorLog("Class (".get_class($this).") cannot be active due to inactive parent ($this->module_parent)");
			$result = false;
			} */
		return TRUE;
	}
	public function IsInstalled() {
		//check if its installed in the db
		//$result = sql_query("SELECT * FROM internal_modules WHERE (`module_name` = '". get_class($this) ."')");
		return FlexDB::ModuleExists(get_class($this));
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
			$row = FlexDB::UUIDExists($uuid);
			if ($row === FALSE) {
				//echo "not installed:".get_class($this);
				//if (($row = $this->IsInstalled()) == FALSE) {
				//DebugMail('not installed',get_class($this));
				$active = flag_is_set($this->GetOptions(),INSTALL_INACTIVE) ? '0' : '1';
				sql_query("INSERT INTO internal_modules (`uuid`,`module_name`,`module_active`) VALUES ('$uuid','". get_class($this) ."','$active')",false);
			} else {
				//			$qry = "UPDATE db_modules SET `module_parent` = '".$this->module_parent."', `module_name` = '". get_class($this) ."'"; // removed because parent no longer used
				$qry = "UPDATE internal_modules SET `uuid` = '".$uuid."', `module_name` = '". get_class($this) ."', `sort_order` = '".$this->GetSortOrder()."'";
				//$cba = true;// $this->CanBeActive($row['module_active'] == 1);
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

	public function CheckDependencies() {
		$result = true;

		foreach (array_keys($this->parents) as $parentName) {
			if ($parentName == '*' || $parentName == '') continue;
			if (!class_exists($parentName)) { ErrorLog( "Class (".get_class($this).") cannot be active due to missing dependency ($parentName)"); $result = false; continue; }
		}
		return $result;
	}

	public function GetFileFromTable($field,$table,$key,$pkVal,$att = 'inline') {
		return PATH_REL_SELF."?__ajax=getFile&amp;f=$field&amp;t=$table&amp;k=$key&amp;p=$pkVal&amp;a=$att";
	}

	public function GetImageLinkFromTable($field,$table,$key,$pkVal,$width=NULL,$height=NULL) {
		if ($width) $width = "&amp;w=$width";
		if ($height) $height = "&amp;h=$height";
		return PATH_REL_SELF."?__ajax=getImage&amp;f=$field&amp;t=$table&amp;k=$key&amp;p=$pkVal$width$height";
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

	public function DrawSqlImage($fieldAlias,$pkVal,$width=NULL,$height=NULL,$attr=NULL,$link=FALSE,$linkAttr=NULL) {
		if ($pkVal == NULL) return '';
		$field = $this->GetFieldProperty($fieldAlias ,'field');
		$setup = $this->sqlTableSetupFlat[$this->GetFieldProperty($fieldAlias,'tablename')];

		$table = $setup['table'];
		$key = $setup['pk'];
		//$pkVal = $this->GetRealValue($fieldAlias,$pkVal);
		return $this->DrawImageFromTable($field,$table,$key,$pkVal,$width,$height,$attr,$link,NULL,NULL,$linkAttr);
	}

	public function HookEvent($eventName,$funcName) {
		$GLOBALS['events'][$eventName][] = get_class($this).".$funcName";
	}

	public function GetSortOrder() {
		//if (is_object($module)) $module = get_class($module);
		if (get_class($this) == GetCurrentModule()) return 1;
		return NULL;
	}
	//	public function __construct() { $this->_SetupFields(); } //$this->SetupParents(); }
	public abstract function ParentLoad($parent); // called when current_path = parent_path
	public abstract function RunModule();  // called when current_path = parent_path/<module_name>/


	public function CreateParentNavButtons($parentName) {
//		if ($this->navCreated) return;
		//ErrorLog(get_class($this).' making buttons on '.$parentName);
		if ($this->isDisabled) return;
		if (!is_array($this->parents)) return;
		if ($parentName !== GetCurrentModule()) return;
		if (!array_key_exists($parentName,$this->parents)) return;

//		$this->navCreated = true;
		$sortOrder = $this->GetSortOrder();
		$listDestination =  'child_buttons';
		if (flag_is_set($this->GetOptions(),IS_ADMIN)) {
			if (get_class($this) == GetCurrentModule()) FlexDB::LinkList_Add($listDestination,'',NULL,-500);
			$sortOrder = $sortOrder - 1000;
		}

			if ($parentName == '/') return;
	//	$lm = FlexDB::GetVar('loadedModules',array());
	//	foreach ($this->parents as $parentName => $linkArray) {
//			$parentName = $linkArray['moduleName'];
			if (flag_is_set(CallModuleFunc(get_class($this),'GetOptions'),NO_NAV)) return;
	//		if (array_search($this,$lm,true) === FALSE) continue;

			if (($parentName != 'internalmodule_Admin' && flag_is_set(CallModuleFunc(GetCurrentModule(),'GetOptions'),IS_ADMIN)) && $parentName != GetCurrentModule()) return;
			//echo get_class($this).' '.$parentName.'<br/>';

			if (flag_is_set(CallModuleFunc($parentName,'GetOptions'),IS_ADMIN) && !flag_is_set(CallModuleFunc(GetCurrentModule(),'GetOptions'),IS_ADMIN)) return;

//			if (flag_is_set(CallModuleFunc(GetCurrentModule(),'GetOptions'),IS_ADMIN) /*&& flag_is_set($this->GetOptions(),IS_ADMIN)*/ && !flag_is_set(CallModuleFunc($parentName,'GetOptions'),IS_ADMIN)) continue;

			$linkArray = $this->parents[$parentName];
			foreach ($linkArray as $linkInfo) {
				if ($linkInfo['parentField'] !== NULL) continue; // has a parentField?  if so, ignore
				$btnText = !empty($linkInfo['text']) ? $linkInfo['text'] : $this->GetTitle();
				if (!empty($linkInfo['fieldLinks']) && GetCurrentModule()) { // is linked to fields in the list
					$cr = CallModuleFunc(GetCurrentModule(),'GetCurrentRecord');
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
						FlexDB::LinkList_Add($listDestination,$btnText,$this->GetURL($filters),$sortOrder,NULL,array('class'=>'fdb-btn'));
						//echo "<a id=\"fhtest\" href=\"".BuildQueryString($this->GetURL(),$filters)."\" class=\"draggable {tabTitle:'$btnText', tabPosition:'".$GLOBALS['modules'][get_class($this)]['sort_order']."'}\">$btnText</a>";
						//		FlexDB::AppendVar('child_buttons',CreateNavButton($linkInfo['text'],BuildQueryString($this->GetURL(),$filters)));
					}
				} else { // not linked to fields (so no filters)
					FlexDB::LinkList_Add($listDestination,$btnText,$this->GetURL(),$sortOrder,NULL,array('class'=>'fdb-btn'));
					//	FlexDB::AppendVar('child_buttons',CreateNavButton($linkInfo['text'],$this->GetURL()));
				}
			}
		//}
	}
}

/**
 * Abstract class extending the basic module, adding data access and filtering.
 *
 */
abstract class flexDb_DataModule extends flexDb_BasicModule {
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

	public $fieldsSetup = FALSE;
	public function _SetupFields() {
		if ($this->fieldsSetup == TRUE) return;
		$this->fieldsSetup = TRUE;

	//	$this->_SetupParents();

		$this->SetupFields();
		$this->SetupUnionFields();
		if (is_array($this->UnionModules)) foreach ($this->UnionModules as $modulename) {
			CallModuleFunc($modulename,'_SetupFields');
			//CallModuleFunc($modulename,'SetupUnionFields'); // called from _SetupFields
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
					$m = FlexDB::ModuleExists(get_class($this));
					$isNew = is_array($filters) && array_key_exists($m['module_id'].'_new',$filters);
					if (!empty($val) && !($filter['fieldName'] == $this->GetPrimaryKey() && $isNew))
						$filArr['_f_'.$filter['uid']] = $val;
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

	public function CanParentLoad($parent) {
		if ($this->IsActive() && $this->HasParent($parent)) $this->_SetupFields();
		return parent::CanParentLoad($parent);
	}
	public function Initialise() {
		if (!parent::Initialise()) return false;
		$this->_SetupFields();
		return true;
	}
	public function _ParentLoad($parent) {
		$this->_SetupFields();
		$par = parent::_ParentLoad($parent);

		//		ErrorLog('data_PL:'.get_class($this).":$parent/$timeframe=".($par === TRUE ? 'true':'not true'));
		//if ($par !== TRUE) return $par;
		//		ErrorLog('creating nav');
		//		if (!array_key_exists(GetCurrentModule(),$this->parents)) return;

		// now check that all fields linking in exist.  WHERE fields.
		/*	if (is_array($this->parents)) foreach ($this->parents as $parent) {
			if (is_array($parent)) foreach ($parent as $info) {
			if (is_array($info['fieldLinks'])) foreach ($info['fieldLinks'] as $linkInfo) {
			if ((stripos($linkInfo['_toField'],'(select') === FALSE) && !array_key_exists($linkInfo['_toField'],$this->fields)) ErrorLog(get_class($this).' is trying to link to a field that doesnt exist ('.$linkInfo['_toField'].')'); //---- this must be moved to after filtering
			}
			}
			} */
		//errorlog( 'createbuttons '.get_class($this));
		//if (!flag_is_set($this->GetOptions(),NO_NAV) && $parent == GetCurrentModule()) $this->CreateParentNavButtons();
		//if ($par === TRUE) $this->CreateParentNavButtons($parent);

		return $par;
	}

	public $forceNewRec = false;
	public function ForceNewRecord() {
		$this->forceNewRec = true;
	}

	public function IsNewRecord() {
		if ($this->forceNewRec === TRUE) return true;
    $m = FlexDB::ModuleExists(get_class($this));
		if (array_key_exists($m['module_id'].'_new',$_REQUEST)) return true;
		//		$dset = GetModuleVar(GetCurrentModule(),'dataset');
		//		if ($dset == NULL) return true;
		//		if (mysql_num_rows($dset) == 0) return true;
		//		if (get_class($this) !== GetCurrentModule() && CallModuleFunc(GetCurrentModule(),'GetCurrentRecord') === NULL) return true;
		return false;
		if (get_class($this) !== GetCurrentModule()) return false;//CallModuleFunc(CetCurrentModule(),'IsNewRecord');

		return false;
	}

	public function EnforceNewRec() {
		return;
		/*		if (mysql_num_rows($this->GetDataset())>0) return;

		// error occured?
		$err = mysql_error();
		if (!empty($err)) {
		ob_end_clean(); die($err);
		}

		$uid = $this->FindFilter($this->GetPrimaryKey(),ctEQ);
		if ($uid !== NULL && !is_empty($this->GetFilterValue($uid))) { // attempt to filter to a specific record
		header('Location: '.$this->GetURL()."&newrec=1"); die();
		}
		return;

		// fallback to first parent with no fieldlinks.
		foreach ($this->parents as $parentName => $linkArray) {
		foreach ($linkArray as $linkInfo) {
		if ($linkInfo['fieldLinks'] === NULL) {
		header('Location: '.CallModuleFunc($parentName,'GetURL'));
		exit();
		}
		}
		}

		ob_end_clean(); die('Contact the administrator. Incorrect attempt to get a new record.');*/
	}

//	public function _RunModule() {
		//		$this->GetDataset();
//		parent::_RunModule();
//	}

	public function GetEncodedFieldName($field,$pkValue=NULL) {
	  $pk = $pkValue === NULL ? '' : "($pkValue)";
		return cbase64_encode(get_class($this).":$field$pk");
	}

	public function CreateSqlField($field,$pkValue,$prefix=NULL) {
		if ($prefix == NULL) $prefix = 'add';
		return "sql[{$prefix}][".$this->GetEncodedFieldName($field,$pkValue)."]";
	}

  
  public function GetDeleteButton($pk,$btnText = NULL,$title = NULL) {
    $title = $title ? "Delete '$title'" : 'Delete Record';
    if ($btnText)
      return '<a name="'.$this->CreateSqlField('del',$pk,'del').'" class="fdb-btn redbg" onclick="if (confirm(\'Are you sure you wish to delete this record?\')) uf(this); return false;" title="'.$title.'">'.$btnText.'</a>';
    return '<a class="btn btn-del" name="'.$this->CreateSqlField('del',$pk,'del').'" href="#" onclick="if (confirm(\'Are you sure you wish to delete this record?\')) uf(this); return false;" title="'.$title.'"></a>';
  }
//	public function CreateSqlDeleteField($where) {
//		$prefix = 'del';
//		return "sql[{$prefix}][".cbase64_encode(get_class($this).":".$this->GetPrimaryTable()."($where)")."]";
//	}

	public function DrawSqlInput($field,$defaultValue='',$pkValue=NULL,$attributes=NULL) {
		if ($attributes==NULL) $attributes = array();
		$inputType = $this->fields[$field]['inputtype'];
		$length = $this->GetFieldProperty($field,'length') ? $this->GetFieldProperty($field,'length') : $this->GetTableProperty($field,'length');
		$values = $this->GetFieldProperty($field,'values');

		$prefix = NULL;

		switch ($inputType) {
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
		$attributes['class'] .= ' fdb-uf';

		$fieldName = $this->CreateSqlField($field,$pkValue,$prefix);
		if ($inputType == itFILE) $attributes['id'] = $fieldName;
		return FlexDB::DrawInput($fieldName,$inputType,$defaultValue,$values,$attributes);
	}

	public function GetPrimaryKey() {
		if ($this->pk == NULL && $this->GetTabledef() != NULL)
		$this->pk = CallModuleFunc($this->GetTabledef(),'GetPrimaryKey');
		return $this->pk;
	}

	public function GetPrimaryTable() {
		if ($this->pt == NULL)
		$this->pt = GetModuleVar($this->GetTabledef(),'tablename');
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
		SetModuleVar($modulename,'UNION_MODULE',TRUE);
		//		CallModuleFunc($modulename,'_SetupFields');
		//		CallModuleFunc($modulename,'AddField','__module__',"'$modulename'",'');
		//		$sts = GetModuleVar($modulename,'sqlTableSetup');
		//		CallModuleFunc($modulename,'AddField','__module_pk__',CallModuleFunc($modulename,'GetPrimaryKey'),$sts['alias']);
	}

	public function AddUnionParent($parentModule) {
		CallModuleFunc($parentModule,'AddUnionModule',get_class($this));
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
			if (!array_key_exists($valField,$this->fields) && get_class($this) != GetCurrentModule() && GetCurrentModule()) {
				CallModuleFunc(GetCurrentModule(),'AddOnUpdateCallback',$valField,array($this,'RefreshDefaultValue'),$name,$onlyIfNull);
			} else
			$this->AddOnUpdateCallback($valField,array($this,'RefreshDefaultValue'),$name,$onlyIfNull);
			//			ErrorLog('added');
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
			$value = CallModuleFunc($dl['module'],'GetRealValue',$dl['getField'],$lookupVal);

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

		// check if linkFrom - if so, get the value of the linked field
//		if (array_key_exists('linkFrom',$fltr) && !empty($fltr['linkFrom'])) {
//			list($m,$f) = explode(':',$fltr['linkFrom']);
//			$val = CallModuleFunc($m,'GetDefaultValue',$f);
//			return $val;
//		}
		//echo "::: filter found with uid($uid) and value($value)";
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
			//			echo "$alias=$fromField:$toField//".CallModuleFunc($vtable['tModule'],'GetPrimaryKey')."<br/>";
			if ($toField == CallModuleFunc($vtable['tModule'],'GetPrimaryKey')) return $fromField;
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

		$newTable = array();
		$this->sqlTableSetupFlat[$alias] =& $newTable;
		$newTable['alias']	= $alias;
		$newTable['table']	= GetModuleVar($tableModule,'tablename');
		$newTable['pk']		= CallModuleFunc($tableModule,'GetPrimaryKey');
		$newTable['tModule']= $tableModule;
		if ($parent==NULL) {
			if ($this->sqlTableSetup != NULL) {
				ErrorLog('Can only have one base table');
				return;
			}
			$this->sqlTableSetup = $newTable;

			$this->AddField($this->GetPrimaryKey(),$this->GetPrimaryKey(),$alias);
			return;
		} else
			$newTable['parent'] = $parent;

		// $fromField in $this->sqlTableSetupFlat[$parent]['tModule']
		if (is_string($joins)) $joins = array($joins=>$joins);
		if (is_array($joins)) foreach ($joins as $fromField => $toField) {
			if ($fromField[0] !== "'" && $fromField[0] !== '"' && stristr($fromField,'.') === FALSE &&
			CallModuleFunc($this->sqlTableSetupFlat[$parent]['tModule'],'GetFieldProperty',$fromField,'pk') !== true &&
			CallModuleFunc($this->sqlTableSetupFlat[$parent]['tModule'],'GetFieldProperty',$fromField,'unique') !== true &&
			CallModuleFunc($this->sqlTableSetupFlat[$parent]['tModule'],'GetFieldProperty',$fromField,'index') !== true)
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

	public function GetValues($aliasName) {
		//		echo get_class($this);
		$arr = NULL;
		if (!array_key_exists($aliasName,$this->fields)) { // field doesnt exist, check if filter exists
			$fltr = $this->FindFilter($aliasName);
			$arr = $fltr['values'];
	} elseif (array_key_exists('values',$this->fields[$aliasName])) {
			//			ErrorLog("finding vals of $aliasName");
			$arr = $this->fields[$aliasName]['values'];
		}
		return $arr;
	}

	public function SetValues($aliasName,$values=NULL,$stringify=FALSE) {
		//		$aliasName = strtolower($aliasName);
		// if values == array, then set the values to the array.
		//		print_r($values);
		$vals = $this->FindValues($aliasName,$values,$stringify);
		$this->SetFieldProperty($aliasName,'values',$vals);
	}

	public function FindValues($aliasName,$values,$stringify = FALSE) {
		$arr = NULL;
		$sort = true;
		if (is_array($values)) {
			if (!is_assoc($values)) { // assume we want the key = val
				$values = array_flip($values);
				$stringify = true;
				$sort = false;
				//				$newVals = array();
				//				foreach ($values as $key => $val) {
				//					$newVals[$val] = $val;
				//unset($values[$key]);
				//				}
				//				$values = $newVals;
			}
			$arr = $values;
		} elseif (IsSelectStatement($values)) {
			$arr = array();
			$result = sql_query($values);
			while ($result != false && (($row = mysql_fetch_row($result)) !== FALSE)) {
				if (is_string($row[0])) $arr[] = $row[0];
			}
			$arr = array_flip($arr);
			$stringify = true;
		} elseif (is_string($values) && !empty($this->fields[$aliasName]['vtable'])) {
			$tbl = $this->fields[$aliasName]['vtable'];
			$pk = CallModuleFunc($tbl['tModule'],'GetPrimaryKey');
			$table = $tbl['table'];
			$arr = GetPossibleValues($table,$pk,$this->fields[$aliasName]['field'],$values);
		}

		if (is_array($arr) && $stringify)
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

	public function AddField($aliasName,$fieldName,$tableAlias=NULL,$visiblename=NULL,$inputtype=itNONE,$values=NULL) {//,$options=0,$values=NULL) {
		$this->_SetupFields();
		//$tdfields = GetModuleVar($this->GetTabledef(),"fields");
		//$this->fields[$fieldName] = $tdfields[$fieldName];
		//		$aliasName = strtolower($aliasName);
		//		if ($tableAlias !== NULL)
		//			$fieldName = strtolower($fieldName);
		//		$tableAlias = strtolower($tableAlias);
		if ($tableAlias === NULL) $tableAlias = $this->sqlTableSetup['alias'];
		$fieldName = trim($fieldName);
		// field is mix?
		//if (isset($this->fields[$aliasName])) { trigger_error("Field with alias ($aliasName) already exists in this module (".get_class($this).")."); return FALSE;}
		if ((!is_array($this->sqlTableSetupFlat) || !array_key_exists($tableAlias,$this->sqlTableSetupFlat)) && !empty($tableAlias)) { trigger_error("No table ($tableAlias) has been created in this module (".get_class($this)."). Field: $aliasName."); return FALSE;}

		//if (!array_key_exists($aliasName,$this->fields)) // always replace so we can re-order automatic fields (like file data)
			$this->fields[$aliasName] = array();
		//		if (preg_match_all('/{[^}]+}/',$fieldName,$matches) > 0)
		//			$this->SetFieldProperty($aliasName,'pragma',$fieldName);

		//		$tablename = $this->GetPrimaryTable();// GetModuleVar($tabledef,'tablename');
		$this->fields[$aliasName]['alias'] = $aliasName;
		$this->fields[$aliasName]['tablename'] = $tableAlias;
		$this->fields[$aliasName]['visiblename'] = $visiblename;
		$this->fields[$aliasName]['inputtype'] = $inputtype;
		$this->fields[$aliasName]['options'] = ALLOW_ADD | ALLOW_EDIT; // this can be re-set using $this->SetFieldOptions
		//		$this->SetFieldProperty($aliasName,'values',$values);
		$this->fields[$aliasName]['field'] = $fieldName;

		if (!empty($tableAlias)) {
			$this->fields[$aliasName]['vtable'] = $this->sqlTableSetupFlat[$tableAlias];
		}

		if ($this->GetFieldType($aliasName) == ftFILE) {
			$this->AddField($aliasName.'_filename', $fieldName.'_filename', $tableAlias);
			$this->AddField($aliasName.'_filetype', $fieldName.'_filetype', $tableAlias);
		}
		//		if ($inputtype == itDATE) {
		//			$this->SetFieldProperty($aliasName,'dateformat','dd/MMM/yyyy');
		//		}
		// values here
		if ($values === NULL) switch ($inputtype) {
			case itCOMBO:
			case itOPTION:
			case itSUGGEST:
			case itSUGGESTAREA:
				$values = '';
			default:
				break;
		}
		$this->SetValues($aliasName,$values);
		//		} else {
		//			switch ($inputtype) {
		//				case itCOMBO:
		//					$this->SetValues($aliasName); // no point in passing values, will always be null here
		//					break;
		//			}
		//		}
		if ($visiblename !== NULL) {
			if (empty($this->layoutSections)) $this->NewSection();
			$this->fields[$aliasName]['layoutsection'] = count($this->layoutSections)-1;
		}
		return TRUE;
		//		$lookupData = CallModuleFunc($this->GetTabledef(),'GetLookupData',$fieldName);

		//		if (!empty($lookupData)) {
		//			$this->SetFieldProperty($fieldName,'lookup_data',$lookupData);
		//			if ($values === NULL)
		//				$this->SetFieldProperty($fieldName,'values',GetPossibleValues($lookupData['lookupTable'],$lookupData['lookupField'],$lookupData['returnField'],$lookupData['where']));
		//		}
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
		$numargs = func_num_args();
		$arr = array();
		for ($i = 2; $i < $numargs; $i++)
		$arr[] = func_get_arg($i);
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

		switch ($this->GetFieldType($alias)) {
			case ftDATE: $fieldName = "STR_TO_DATE($fieldName,'".FORMAT_DATE."')"; break;
			case ftTIME: $fieldName = "STR_TO_DATE($fieldName,'".FORMAT_TIME."')"; break;
			case ftDATETIME:
			case ftTIMESTAMP: $fieldName = "STR_TO_DATE($fieldName,'".FORMAT_DATETIME."')"; break;
//			default: $fieldName = $fld;
		}

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

	public function AddFilterWhere($fieldName,$compareType,$inputType=itNONE,$value=NULL,$values=NULL,$title=NULL) {
		//	if (!array_key_exists($fieldName,$this->fields) && $inputType !== itNONE) { ErrorLog("Cannot add editable WHERE filter on field '$fieldName' as the field does not exist."); return; }
		if (!isset($this->filters[FILTER_WHERE]) || count(@$this->filters[FILTER_WHERE]) == 0) $this->NewFiltersetWhere();
		return $this->AddFilter_internal($fieldName,$compareType,$inputType,$value,$values,FILTER_WHERE,$title);
	}

	public function AddFilter($fieldName,$compareType,$inputType=itNONE,$value=NULL,$values=NULL,$title=NULL) {
		if (array_key_exists($fieldName,$this->fields) && stripos($this->fields[$fieldName]['field'],' ') === FALSE && !$this->UNION_MODULE)
			return $this->AddFilterWhere($fieldName,$compareType,$inputType,$value,$values,$title);

		//	if (!array_key_exists($fieldName,$this->fields)) { ErrorLog("Cannot add HAVING filter on field '$fieldName' as the field does not exist.");ErrorLog(print_r(useful_backtrace(),true)); return; }
		if (!isset($this->filters[FILTER_HAVING]) || count(@$this->filters[FILTER_HAVING]) == 0) $this->NewFiltersetHaving();
			return $this->AddFilter_internal($fieldName,$compareType,$inputType,$value,$values,FILTER_HAVING,$title);
	}

	private $filterUID = 0;
	public function GetNewUID() {
		//		if (get_class($this) != GetCurrentModule()) return CallModuleFunc(GetCurrentModule(),'GetNewUID');

		$this->filterUID = $this->filterUID +1;
    $m = FlexDB::ModuleExists(get_class($this));
		return $m['module_id'].'_'.($this->filterUID - 1);
	}

	// private - must use addfilter or addfilterwhere.
	private function AddFilter_internal($fieldName,$compareType,$inputType=itNONE,$dvalue=NULL,$values=NULL,$filterType=NULL,$title=NULL) { // enforce forces any new records to be set to this value
		//		if (!isset($value) || empty($value)) return;
		$uid = $this->GetNewUID();
		$value = $dvalue;
		//		$this->filterUID++;

		//		if (GetCurrentModule() !== get_class($this)) {
		//			// search current record for this field.
		//			$currentRecord = GetModuleVar(GetCurrentModule(),'currentRecord');
		//			print_r($currentRecord);
		//			if ($currentRecord !== NULL && array_key_exists($fieldName,$currentRecord))
		//				$value = $currentRecord[$fieldName];
		//						echo get_class($this)." $fieldName found value: $value::\n";
		//		}
		//		if ($value === NULL) $value = $this->GetFilterValue($uid); // enforce a filter  -- possibility of a "default filter"
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
				$values = '';
			default:
				break;
		}


		$vals = $this->FindValues($fieldName,$values,!is_array($values));
		if ($value !== NULL && is_array($vals) && !array_key_exists($value,$vals)) {
			$checkVals = $this->FindValues($fieldName,$values);
			if (!array_key_exists($value,$checkVals)) $checkVals = array_flip($checkVals);
			if (array_key_exists($value,$checkVals)) $value = $checkVals[$value];
		}


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
		$fieldData['values']= $vals;
		$fieldData['default'] = $dvalue;
		$fieldData['value'] = $value;

		if ($inputType != itNONE) $this->hasEditableFilters = true;

		$filterset[count($filterset)-1][] = $fieldData;
		return $fieldData['uid'];
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
		return array_key_exists($fieldName,$this->fields);
	}

	public function SetFieldProperty($fieldName,$propertyName,$propertyValue) {
		if (!array_key_exists($fieldName,$this->fields)) { ErrorLog(get_class($this)."->SetFieldProperty($fieldName,$propertyName). Field does not exist."); return; }
		$this->fields[$fieldName][$propertyName] = $propertyValue;
	}

	public function GetFieldProperty($fieldName,$propertyName) {
		//		if (!$this->AssertField($fieldName)) return NULL;
		if (!array_key_exists($fieldName,$this->fields)) return NULL;
		if (!array_key_exists($propertyName,$this->fields[$fieldName])) return NULL;

		return $this->fields[$fieldName][strtolower($propertyName)];
	}

	public function SetFieldType($alias,$type) {
		$this->_SetupFields();
		$this->SetFieldProperty($alias,'datatype',$type);
	}

	public function GetFieldType($alias) {
		$type = $this->GetFieldProperty($alias,'datatype');
		if (empty($type)) $type = $this->GetTableProperty($alias,'type');

		return $type;
	}

	public $hideFilters = FALSE;
	public function HideFilters() {
		$this->hideFilters = TRUE;
	}

	public function GetFieldLookupString($alias,$fieldData) {
		$fieldName = $fieldData['field'];
		if (empty($fieldName)) $fieldName = "''";
		if ($fieldData['tablename'] === NULL) return;

		// first replace pragmas
		if (preg_match('/{[^}]+}/',$fieldName) > 0) { // has pragma code
			$fieldName = ReplacePragma($fieldName, $fieldData['tablename']);
		}

		if ($this->GetFieldProperty($alias, 'is_function') || substr($fieldName,0,1) == '(' || substr($fieldName,0,1) == "'" || substr($fieldName,0,1) == '"') {
			$toAdd = $fieldName;
		} elseif (preg_match('/{[^}]+}/',$fieldData['field']) > 0) { // has pragma code
			if (substr($fieldData['field'],0,1) === '{') // starts with a pragma, so assume we need to concat
				$toAdd = CreateConcatString($fieldData['field'], $fieldData['tablename']);
			else // doesnt start with a pragma, so it could well be a function, only replace the pragmas with the fields, dont make it concat
				$toAdd = ReplacePragma($fieldData['field'], $fieldData['tablename']);
		} else {
			// is it a date?
			// is it a timestamp?
			// use DATE_FORMAT
			$toAdd = CreateConcatString($fieldName, $fieldData['tablename']);
			//$flds[] = "$concat as $alias";
		}

		switch ($this->GetFieldType($alias)) {
			case 'date': $toAdd = "IF($toAdd = 0,'',DATE_FORMAT($toAdd,'".FORMAT_DATE."'))"; break;
			case 'time': $toAdd = "IF($toAdd = 0,'',TIME_FORMAT($toAdd,'".FORMAT_TIME."'))"; break;
			case 'datetime':
			case 'timestamp': $toAdd = "IF($toAdd = 0,'',DATE_FORMAT($toAdd,'".FORMAT_DATETIME."'))"; break;
		}

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
		$qry = "SELECT $distinct ".join(",\n",$flds);
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
				foreach ($filterset as $arrID => $filterInfo) {
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

		// this block will search the "current module" for the value of the field on the current record.
		/*		if (GetCurrentModule() !== get_class($this) && GetCurrentModule() !== '') {
			// search current record for this field.
			$currentRecord = CallModuleFunc(GetCurrentModule(),'GetCurrentRecord');
			if ($currentRecord !== NULL && array_key_exists('fieldName',$filterData) && array_key_exists($filterData['fieldName'],$currentRecord))
			return $currentRecord[$filterData['fieldName']];
			}
			*/

		// ptime static filter value
		// this line grabs STATIC filters (filters set by code), this enforced if the input type is null
		$defaultValue = (is_array($filterData) && array_key_exists('value',$filterData)) ? $filterData['value'] : NULL;

		if (is_array($filterData) && $filterData['it'] == itNONE) {
			// for union modules, we cannot get a value form currentmodule because it is itself, part of the query
			if (GetCurrentModule() !== get_class($this) && (!isset($this->UNION_MODULE) || $this->UNION_MODULE !== TRUE)) {
				//$row = ($this->activeParent !== NULL) ? GetModuleVar($this->activeParent,'currentRecord') : GetModuleVar(GetCurrentModule(),'currentRecord');
				//			echo '///'.get_class($this).':'.$this->activeParent;
				if (array_key_exists('linkFrom',$filterData)) {
					list($linkParent,$linkFrom) = explode(':',$filterData['linkFrom']);
					// linkparent is loaded?  if not then we dont really want to use it as a filter.....
					if (CallModuleFunc($linkParent,'HasParentLoaded',get_class($this)) || $linkParent == GetCurrentModule()) {
						$row = CallModuleFunc($linkParent,'GetCurrentRecord',$refresh);
						if (!$row && !$refresh) $row = CallModuleFunc($linkParent,'GetCurrentRecord',true);
						//print_r($row);
						//echo "gcr($linkParent:$linkFrom)";
						//$row = ($this->activeParent !== NULL) ? CallModuleFunc($this->activeParent,'GetCurrentRecord') : NULL;//CallModuleFunc(GetCurrentModule(),'GetCurrentRecord');
						//$row = CallModuleFunc(GetCurrentModule(),'GetCurrentRecord')
						//				if (strtolower(substr(trim($filterData['fieldName'],'('),0,6)) == 'select') {
						//					$row = mysql_fetch_row(sql_query($filterData['fieldName']));
						//					return $row[0];
						//				} else

						if (is_array($row) && array_key_exists($linkFrom,$row)) {
							return $row[$linkFrom];
							//					else {
							//						ErrorLog(get_class($this).': Cant find '.$filterData['linkFrom']);
							//						errorLog(print_r(useful_backtrace(1,5),true));
							//					}
						} else {// if the filter value of the parent is null (if we're updating for example), then we want to get the value of the filter
							$fltrLookup = CallModuleFunc($linkParent,'FindFilter',$linkFrom,ctEQ);
							$val = NULL;
							// stop lookup callbacks
							if (is_array($fltrLookup) && array_key_exists('linkFrom',$fltrLookup) && stristr($fltrLookup['linkFrom'],get_class($this)) === FALSE )
								$val = CallModuleFunc($linkParent, 'GetFilterValue',$fltrLookup['uid']);
							//ErrorLog($val);
							if ($val!==NULL) return $val;
							//if ($fltrLookup['value']) return $fltrLookup['value'];

							//$uid = $uid['uid'];
						}
						// else ErrorLog(get_class($this).": Cant get value of parent field ($this->activeParent.{$filterData['linkFrom']})");
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

		/*return;

		//if ($currentModuleOnly && GetCurrentModule() !== get_class($this)) return NULL;
		//if not current module, get the value of this filter from the parent
		if ($filterData['it'] == itNONE && GetCurrentModule() !== get_class($this) && ) {
		//	print_r($filterData);
		//			if ($filterData['it'] == itNONE) {
		$row = CallModuleFunc(GetCurrentModule(),'GetCurrentRecord');
		//		echo $row[$filterData['fieldName']];
		if ($row !== NULL && array_key_exists($filterData['fieldName'],$row))
		return $row[$filterData['fieldName']];
		//			}
		}

		if (!$processRequest) return NULL;

		if (!array_key_exists('filters',$_REQUEST)) return NULL;
		if (!array_key_exists($uid,$_REQUEST['filters'])) return NULL;

		return urldecode($_REQUEST['filters'][$uid]);*/
	}

	public function GetTableProperty($alias,$property) {
		//		if (!$this->AssertField($alias,$alias.'.'.$property)) return NULL;
		if (!array_key_exists($alias,$this->fields)) return NULL;
		if (!array_key_exists('vtable',$this->fields[$alias])) return NULL;

		$tabledef = $this->fields[$alias]['vtable']['tModule'];
		//		$fieldName = $this->GetRootField($alias);
		$fieldName = $this->fields[$alias]['field'];
		//echo "finding prop $property for field $fieldName in $tabledef<br/>";
		return CallModuleFunc($tabledef,"GetFieldProperty",$fieldName,$property);
	}

	// filterSection = [where|having]

	public function FormatFieldName($fieldName, $fieldType = NULL) {
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
		$fieldName= $fieldNameOverride ? $fieldNameOverride : $filterData['fieldName'];
		$compareType=$filterData['ct'];
		$inputType=$filterData['it'];
		if ($compareType == ctCUSTOM) return '('.$filterData['fieldName'].')';

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
		if ($value === NULL) return '';
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
			return "($val $compareType $fieldName)";
			$vals = explode(',',$value);
			$val = "('".join("','",$vals)."')";
			return "($fieldToCompare $compareType $val)";
		}

		return "($fieldToCompare $compareType $val)";
	}

	public function GetWhereStatement($extra = NULL) {
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

				//				if (!array_key_exists($fieldName,$this->fields) && $this->GetPrimaryKey() != $fieldName && strtolower(substr($fieldName,0,8)) !== '(select ') continue;
				//				if (empty($fData['value'])) continue;
				//				if ($fData['value'] == "%%" && $fData['ct'] == ctLIKE) continue;
				// if the field doesnt exist in the primary table. -- should be ANY table used. and if more than one, should be specific.
				//				ErrorLog($this->GetTabledef().' '.$fieldName);
				//				if (!CallModuleFunc($this->GetTabledef(),'FieldExists',$fieldName)) continue;

				if (($filterString = $this->GetFilterString($fData['uid'])) !== '')
				$setParts[] = $filterString;
			}
			if (count($setParts) >0) $where[] = '('.join(' AND ',$setParts).')';
		}
		return join(' AND ',$where);

		$extraWhere = array();
		if (is_array($extra)) {
			foreach ($extra as $field => $value) {
				$value = is_numeric($value) ? $value : "'$value'";
				$extraWhere[] = "($field = $value)";
			}
		}

		if (empty($where) && empty($extraWhere)) return '';

		if (count($where) > 0)
		array_push($extraWhere,join(' OR ',$where));

		$state = join(' AND ',$extraWhere);

		return $state;
	}

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
				$setParts[] = $filterString;
			}

			if (count($setParts) >0) $having[] = '('.join(' AND ',$setParts).')';
		}
		if (empty($having)) return '';

		return join(' OR ',$having);
	}

	public function GetOrderBy() {
		//	if ($this->grouping !== NULL || !empty($this->grouping)) return ' ORDER BY NULL';
		if (!empty($this->ordering)) return join(', ',$this->ordering);

		return 'NULL';
	}

	public function GetGrouping() {
		if ($this->grouping === NULL || empty($this->grouping)) return '';

		return join(', ',$this->grouping);
	}

	/**
	 * Get a dataset based on setup.
	 * @param (bool|null) NULL to return a fresh dataset, TRUE to refresh the internal dataset, FALSE to return the cached dataset.
	 * @returns MySQL Dataset
	 */
	public function &GetDataset($refresh = FALSE) {
		$this->_SetupFields();

		//echo get_class($this).".GetDataset()\n";
		//if ($this->IsNewRecord()) return NULL;
		if ($this->dataset !== NULL && $refresh === FALSE) { return $this->dataset; }

		//echo ($this->dataset === NULL) ? 'ds is null, ' : '';
		//echo ($refresh !== FALSE) ? 'refresh isnt false' : '';
		//echo "\n";
		//print_r(useful_backtrace(0,6));

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
				CallModuleFunc($moduleName,'_SetupFields');
				$select2 = CallModuleFunc($moduleName, 'GetSelectStatement');
				$where2 = CallModuleFunc($moduleName, 'GetWhereStatement'); $where2 = $where2 ? " WHERE $where2" : '';
				$group2 = CallModuleFunc($moduleName, 'GetGrouping'); $group2 = $group2 ? " GROUP BY $group2" : '';
				$having2 = CallModuleFunc($moduleName, 'GetHavingStatement');
				$having2 = $having2 ? $having.' AND ('.$having2.')' : $having;
				//				if (!empty($having2)) $having2 = $having.' AND ('.$having2.')';
				//				else $having2 = $having;
				$order2 = CallModuleFunc($moduleName, 'GetOrderBy'); $order2 = $order2 ? " ORDER BY $order2" : '';
				$query .= "\nUNION\n($select2$where2$group2$having2$order2)";
			}
			$query .= " $order";
		}
		//	ob_end_clean();
		//	print_r(useful_backtrace(0,4));
		//print_r($this->filters);

		if (array_key_exists('__explain',$_REQUEST)) {
			FlexDB::CancelTemplate();
			$this->explain = GetRows(sql_query("EXPLAIN EXTENDED $query"));
			print_r($this->explain);
			die();
		}

		$dataset = sql_query($query);
		if ($refresh === TRUE || $this->dataset === NULL) {
			//			$this->internalRowNum = 0;
			$this->dataset =& $dataset;
			$this->EnforceNewRec();
		}
	//	if (SHOW_QUERY || array_key_exists('showQ',$_REQUEST)) {
	//		echo "\n\nQUERY (".get_class($this).",".mysql_num_rows($dataset)."): <pre>$query</pre>";
	//		print_r($this->filters);
	//		print_r(useful_backtrace(1,6));
	//	}

		return $dataset;
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
		//		ErrorLog(get_class($this).".GetRecord($rowNum)");
		//        if (is_bool($refresh)) {
		//
		//            $dataset = $this->GetDataset($refresh);
		//        } else
		//            $dataset = $refresh;

		if ($dataset === NULL) return NULL;
		if ($dataset === $this->dataset && $this->IsNewRecord()) return NULL;

		//        if ($rowNum === NULL) {
		//			$rowNum = $this->internalRowNum;
		//			$this->internalRowNum++;
		//		}
		if ($rowNum < 0) { // negative rowNum means find record from end of the set (-1 = last record)
			$rowNum = mysql_num_rows($dataset) + $rowNum;
		}

		if ($rowNum > mysql_num_rows($dataset)-1 || $rowNum < 0 || mysql_num_rows($dataset) == 0) { // requested row is greater than total rows or less than 0 or no rows exist
			$row = NULL;
		} else {
			//mysql_data_seek($dataset,$rowNum);
			$row = GetRow($dataset,$rowNum);
		}

		if ($dataset === $this->dataset) {
			$this->currentRecord = $row;//mysql_fetch_assoc($dataset);
			$this->lastRowNum = $rowNum;
			//$_SESSION['datastore'][get_class($this)] = $this->currentRecord;
		}

		return $row;
	}

	public function GetCurrentRecord($refresh = FALSE) {
		//if (GetCurrentModule() !== get_class($this) && (isset($this->UNION_MODULE) && $this->UNION_MODULE === TRUE) && GetCurrentModule())
			//return CallModuleFunc(GetCurrentModule(),'GetCurrentRecord',$refresh);

		//	if (isset($this->currentRecord) && $refresh === FALSE)
		//		if (is_null($this->currentRecord) && !is_null($this->lastInsertId))
		//			return $this->LookupRecord($this->lastInsertId);

		if ($refresh === TRUE) {
			if ($this->currentRecord !== NULL)
			return $this->LookupRecord($this->currentRecord[$this->GetPrimaryKey()]);
			else
			return $this->GetRecord($this->GetDataset(),0);
		}

		return $this->currentRecord;

		//ErrorLog('refreshing current record');
		//		return $this->GetRecord($this->GetDataset($refresh),max($this->internalRowNum-1,0));

		//		if ($refresh == TRUE) return $this->LookupRecord(array($this->GetPrimaryKey()=>$this->currentRecord[$this->GetPrimaryKey()]));

		//		return $this->currentRecord;
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
		return GetRows($dataset);
	}

	public function LookupRecord($filter=NULL,$clearFilters=false) {
		//		ErrorLog(get_class($this)."@lookup ".print_r($arr,true));
		//echo '//'.get_class($this)."@lookup ".print_r($arr,true);
		//echo "LRs={$this->internalRowNum}   ";
		$rows = $this->GetRows($filter,$clearFilters);
		if (is_array($rows)) return reset($rows);
		return NULL;
		//return $instance->GetRecord($dataset,0);
		/*
		 if (!is_array($filter)) return NULL;
		 //        $rn = $this->internalRowNum;
		 $dataset = $this->GetDataset(NULL);
		 //		mysql_data_seek($dataset,0);
		 $return = NULL;
		 while (($row = mysql_fetch_assoc($dataset))) {
		 //			ErrorLog(print_r($row,true));
		 //			print_r($row);
			$match = 0;
			foreach ($filter as $lookupField => $matchValue) {
			if (array_key_exists($lookupField,$row) && $row[$lookupField] == $matchValue)
			$match++;
			}
			if ($match == count($filter)) { $return = $row; break; }
			}
			//        $this->internalRowNum = $rn;
			//        mysql_data_seek($dataset,$rn);
			//echo "LRe={$this->internalRowNum}<br/>";
			return $return;*/
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

	public function CheckDependencies($showErrors = false) {
		$result = true;
		if (!parent::CheckDependencies($showErrors)) $result = false;
		return $result;
		/*
		 // alert dependancies:
		 foreach ($this->fields as $fieldName => $fieldData) {
			if (!array_key_exists('comments',$fieldData)) continue;
			@list(,$dataField,$lookupField,$lookupTable) = ParseFieldComments($fieldData['comments']);
			if (empty($lookupField)) continue;
			//check tabledef class exists
			if (!TableExists($lookupTable)) {
			if ($showErrors) ErrorLog("Missing table dependency ($this->tablename) requires ($lookupTable)"); $result = false; continue; }

			if (GetRow(sql_query("SHOW FIELDS FROM `$lookupTable` LIKE '$lookupField'")) === NULL) {
			if ($showErrors) ErrorLog("Missig table dependency ($this->tablename) requires ($lookupField in $lookupTable)"); $result = false; continue; }
			}

			return $result;*/
	}

	public function PreProcess($fieldName,$value,$pkVal=NULL,$forceType = NULL) {
		$originalValue = $value;
		$suf = ''; $pre = ''; $isNumeric=true;
		if ($forceType === NULL) $forceType = $this->GetFieldType($fieldName);
		switch ($forceType) {
			case ftFILE:
				$rec = $this->LookupRecord($pkVal);
				$filename = '';
				$link = $this->GetFileFromTable($fieldName,GetModuleVar($this->GetTabledef(),'tablename'),$this->GetPrimaryKey(),$pkVal);
				if ($rec && array_key_exists($fieldName.'_filename',$rec) && $rec[$fieldName.'_filename']) $filename = '<b><a href="'.$link.'">'.$rec[$fieldName.'_filename'].'</a></b> - ';
				if (!strlen($value)) $value = '';
				else $value = $filename.round(strlen($value)/1024,2).'Kb<br/>';
				break;
			case ftIMAGE:
				if (!$value) break;
				$size = $this->GetFieldProperty($fieldName,'length');
				$value = $this->DrawSqlImage($fieldName,$pkVal,$size,$size);
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
					//$value = FlexDB::GetRelativePath($value);
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
				array_unshift($args,$originalValue,$pkVal,$value);
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
//		return PATH_REL_ROOT.DEFAULT_FILE."?__ajax=getUpload&amp;f=$fieldAlias&amp;m=$uuid&amp;p=$pkVal";
	}

	// TODO: Requests for XML data (ajax)
	// to be used later with full AJAX implimentation
	public function CreateXML() {
		// call parent createxml
		// parse and inject child links into 'dataset' xml object
	}

	public function GetFilterBox($filterInfo,$attributes=NULL,$spanAttributes=NULL) {
		// already filtered?

		if ($filterInfo['it'] === itNONE) return '';
		$fieldName = $filterInfo['fieldName'];

		$default = $this->GetFilterValue($filterInfo['uid']);

		$pre = '';
		if (!empty($filterInfo['title'])) {
//			$pre = $filterInfo['title'].' ';
			$emptyVal = $filterInfo['title'];//.' '.$filterInfo['ct'];
		} else
			$emptyVal = $this->fields[$fieldName]['visiblename'];//.' '.$filterInfo['ct'];

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

		return '<span '.$spanAttr.'>'.$pre.FlexDB::DrawInput('_f_'.$filterInfo['uid'],$filterInfo['it'],$default,$vals,$attributes,false).'</span>';
	}

	//	public function __construct() { $this->SetupFields(); }

	/*	public function ProcessUpdates($function,$decodedStr) {
			if ($function == 'del') {
			InterpretSqlDeleteString($decodedStr,$module,$table,$where);
			return CallModuleFunc($module,'ProcessUpdates_'.$function,$table,$where);
			} else {
			InterpretSqlString($decodedStr,$module,$field,$pkVal);
			return CallModuleFunc($module,'ProcessUpdates_'.$function,$field,$value,$pkVal);
			}
		}   */

	public function ProcessUpdates($function,$sendingField,$fieldAlias,$value,&$pkVal = NULL) {
		$this->_SetupFields();
/*		$lm = FlexDB::GetVar('loadedModules');
		$mainClass = get_class($this);// GetCurrentModule();

		$arrBefore = array(); $arrAfter = array();

		$arrBefore[$mainClass] = $this->LookupRecord($pkVal);
		foreach ($lm as $module) {
			if (!is_callable(array($module,'GetCurrentRecord'))) continue;
			$arrBefore[$childInfo['moduleName']] = $module->GetCurrentRecord();
		}
*/
		$func = 'ProcessUpdates_'.$function;
		$this->$func($sendingField,$fieldAlias,$value,$pkVal);
		return;
		//CallModuleFunc($mainClass,'ProcessUpdates_'.$function,$sendingField,$fieldAlias,$value,$pkVal);

		$arrAfter[$mainClass] = $this->LookupRecord($pkVal);
		foreach ($lm as $module) {
			if (!is_callable(array($module,'GetCurrentRecord'))) continue;
			$arrAfter[$childInfo['moduleName']] = $module->GetCurrentRecord();
		}

		$this->ResetField($fieldAlias,$pkVal);

		$updatesOccurred = false;
		$thisModule = get_class($this);
		foreach ($arrAfter as $moduleName => $newRec) {
			if ($newRec === NULL) continue; // new record doesnt exist, we need to do something
			$oldRec = $arrBefore[$moduleName];
			$modPkVal = $newRec[CallModuleFunc($moduleName,'GetPrimaryKey')];
			foreach ($newRec as $fieldName => $value) {
				// already reset this field
				if ($fieldName == $fieldAlias && $moduleName == $thisModule) continue;
				// skip if field hasn't changed
				if ($value === $oldRec[$fieldName]) continue;

				//$fieldData = GetModuleVar($moduleName,'fields'); $fieldData = $fieldData[$fieldName];

				// reset field
				CallModuleFunc($moduleName,'ResetField',$fieldName,$modPkVal);
			}
		}
	}

	public function ProcessUpdates_add($sendingField,$fieldAlias,$value,&$pkVal = NULL) {
		$this->UpdateField($fieldAlias,$value,$pkVal);
	}

	function ProcessUpdates_md5($sendingField,$fieldAlias,$value,&$pkVal = NULL) {
		if (empty($value)) return FALSE;
		return $this->UpdateField($fieldAlias,md5($value),$pkVal);
	}

	public function ProcessUpdates_del($sendingField,$fieldAlias,$value,&$pkVal = NULL) {
		AjaxEcho('//'.get_class($this)."@ProcessUpdates_del($fieldAlias,$value,$pkVal)");
		//if (!$pkVal) return;

		$table = GetModuleVar($this->GetTabledef(),'tablename');
		$where = $this->GetPrimaryKey()." = '$pkVal'";

		sql_query("DELETE FROM $table WHERE $where");

		return false;
		return true;
		echo "$table, $where<br/>";
		//		list($field,$pk) = split('=',$where);
		//		die("$table, $field, $pk");
		// delete process, look thru all tables and find a cell which references this record
		//loop classes
		$inuse = array();
		$classes = get_declared_classes();
		foreach ($classes as $classname) {
			// change to is_subclass_of datamodule
			if (substr($classname,0,9) != 'module_') continue;

			CallModuleFunc($classname,'_SetupFields');
			$fields = GetModuleVar($classname,'fields');

			// check each field for a lookup on this table
			foreach ($fields as $fieldName => $fieldData) {
				//				if (!isset($fieldData['lookup_data'])) continue;
				//				if ($fieldData['lookup_data']['lookupTable'] != $table) continue;
				if (!isset($fieldData['vtable'])) continue;
				if ($fieldData['vtable']['table'] != $table) continue;
				echo "$classname>$fieldName : ";
				// ok, found a field using this table, lets check that field for any uses
				// first replace the field in WHERE with this field name
				$newWhere = str_replace($fieldData['vtable']['toField'],$fieldName,$where);
				//				$tabledef = CallModuleFunc($classname,'GetTabledef');
				$srchTable = $fieldData['vtable']['table'];//GetModuleVar($tabledef,'tablename');
				//echo "SELECT * FROM $srchTable WHERE $newWhere";
				$pk = CallModuleFunc($classname,'GetPrimaryKey');
				echo "SELECT $pk FROM $srchTable WHERE $newWhere<br/>";
				$result = sql_query("SELECT $pk FROM $srchTable WHERE $newWhere");
				while (($row = GetRow($result)))
				$inuse[] = "$srchTable: where $pk = {$row[$pk]}";
			}
		}
		// loop fields
		//@list(,$dataField,$lookupField,$lookupTable) = ParseFieldComments($row['Comment']);
		//if ($lookupTable == $table) { // same table - search for entries which match
		//	$qry = "SELECT * FROM $currentTable WHERE $currentField = $val";
		//}
		if (!empty($inuse)) {
			ob_end_clean(); die(nl2br("Cannot delete. This record is in use by:\n".join("\n",$inuse)));
			return FALSE;
		}
		//	echo "DELETE FROM $table WHERE $where";
		//	return sql_query("DELETE FROM $table WHERE $where");
		ob_end_clean(); die();
		return false;
	}

	public function ProcessUpdates_file($sendingField,$fieldName,$value,&$pkVal = NULL) {
		$this->UploadFile($fieldName,$value,$pkVal);
		//$this->ResetField($fieldName,$pkVal);
	}

	public function UploadFile($fieldAlias,$fileInfo,&$pkVal = NULL) {
		//$allowedTypes = $this->GetFieldProperty($fieldAlias, 'allowed');
		if (!file_exists($fileInfo['tmp_name'])) { AjaxEcho('alert("File too large. Maximum File Size: '.FlexDB::ReadableBytes(FlexDB::GetMaxUpload()).'");'); return; }
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

		$tbl		= $this->fields[$fieldAlias]['vtable'];
		$values		= $this->GetValues($fieldAlias);

		if ($newValue !== NULL && $newValue !== '' && is_numeric($newValue) && $this->fields[$fieldAlias]['inputtype'] == itSUGGEST || $this->fields[$fieldAlias]['inputtype'] == itSUGGESTAREA) {
			$valSearch = (is_assoc($values)) ? array_flip($values) : $values;
			$srch = array_search($newValue, $valSearch);
			if ($srch !== FALSE) $newValue = $srch;
		}
		$originalValue = $newValue;

		//$isNumericKeys = TRUE;
		//if (is_array($values)) foreach (array_values($values) as $val) {
//			if (!is_numeric($val)) { $isNumericKeys = FALSE; break; }
		//}
		// if its a pragma, get root field.  else get normal field and pk
		$field = $this->fields[$fieldAlias]['field'];
		$table		= $tbl['table'];
		$tablePk	= $tbl['pk'];

		//if ((preg_match_all('/{[^}]+}/',$field,$matches) > 0) || (is_array($values) && !is_assoc($values))) {
			// parent table
			//$tbl = $this->sqlTableSetupFlat[$tableAlias];

			if (array_key_exists('parent',$tbl)) {
				foreach ($tbl['joins'] as $fromField=>$toField)
				if ($toField == $tbl['pk']) {
					$field = $fromField;
					break;
				}
				$tbl = $this->sqlTableSetupFlat[$tbl['parent']];
				$table		= $tbl['table'];
				$tablePk	= $tbl['pk'];
			}
			//			print_r($tbl);
			//			if ($field == NULL) ErrorLog('somethings wrong');
			// loop thru joins, find PK as the toJoin fromJoin is then field
		//} else {
			//		print_r($vtable);
			//		echo "// $fieldAlias direct table \n";
			//$field		= $this->fields[$fieldAlias]['field'];
		//}

/*		if (is_array($values)) {
			foreach ($values as $key => $val) {
				if ($val == $originalValue) {$originalValue = $key; break;}
			}
		}
*/
		// it may be necessary to go right back to the tier 1 parent for nested tables > 2 tiers
		//		if (array_key_exists('joins',$vtable)) foreach ($vtable['joins'] as $fromField => $toField) {
		// loop through each join, if the toField is the PK of vtable[tModule] then field = fromField
		//			if ($toField == CallModuleFunc($vtable['tModule'],'GetPrimaryKey')) { $field = $fromField; break; }
		//		}
		//		$field		= array_key_exists('fromField',$vtable) && !empty($vtable['fromField']) ? $vtable['fromField'] : $this->fields[$fieldAlias]['field'];
		//		$field		= $vtable['fromField'] ? $vtable['fromField'] : $this->fields[$fieldAlias]['field'];


		// check old value
		/*-- dont bother - no need to send 2 queries when we only need to send one.
		 if ($pkVal !== NULL) {
		 //		echo "SELECT $field FROM $table WHERE `$tablePk` = '$pkVal'";
			$row = GetRow(sql_query("SELECT $field FROM $table WHERE `$tablePk` = '$pkVal'"));
			//		echo "{$row[$field]} == $newValue";
			if ($row === FALSE || $row[$field] == $newValue)
			return FALSE; // statement has failed or field doesnt need updating
			} */

		// preformat the value
		$newValue = trim($newValue);
		$pfVal = $newValue;
		if ($this->GetFieldType($fieldAlias) != ftRAW) $newValue = mysql_real_escape_string($newValue);
		if ($newValue === '' || $newValue === NULL) $newValue = 'NULL';
		else switch ($this->GetFieldType($fieldAlias)) {      //"STR_TO_DATE('$newValue','".FORMAT_DATE."')"; break;
			case ftRAW: break;
			case ftDATE:		$newValue = $newValue == '' ? 'NULL' : "(STR_TO_DATE('".fixdateformat($newValue)."','".FORMAT_DATE."'))"; break;
			case ftTIME:		$newValue = $newValue == '' ? 'NULL' : "(STR_TO_DATE('$newValue','".FORMAT_TIME."'))"; break;
			case ftDATETIME:	// datetime
			case ftTIMESTAMP:	$newValue = $newValue == '' ? 'NULL' : "(STR_TO_DATE('$newValue','".FORMAT_DATETIME."'))"; break;
			case ftCURRENCY:	// currency
			case ftPERCENT:		// percent
			case ftFLOAT:		// float
			case ftDECIMAL:		$newValue = "'".floatval(preg_replace('/[^0-9\.-]/','',$newValue))."'"; break;
			case ftBOOL:		// bool
			case ftNUMBER:		$newValue = "'".($newValue==='' ? '' : intval(preg_replace('/[^0-9\.-]/','',$newValue)))."'"; break;
			default:
				$newValue = "'$newValue'";
		}

		// lets update the field
		$ret = true;
		if ($pkVal === NULL) { // create new record
			$insertQry = "INSERT INTO $table SET `$field` = $newValue";
			//echo "//$insertQry\n";

			sql_query($insertQry);

			if (mysql_error()) { return FALSE; }


			if (!$pkVal) {
				if ($fieldAlias == $this->GetPrimaryKey())
					$pkVal = $pfVal;
				else
					$pkVal = mysql_insert_id();
				
				if ($this->IsNewRecord()) $ret = $this->GetURL($pkVal);
			}

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
			//echo("alert('$pkVal');");die();
			//			$this->currentRecord = $this->LookupRecord(array($this->GetPrimaryKey()=>$pkVal));//GetRow(sql_query("SELECT * FROM $table WHERE `$tablePk` = '{$pkVal}'"));

			// new record has been created.  pass the info on to child modules, incase they need to act on it.
			$this->OnNewRecord($pkVal);
			$children = FlexDB::GetChildren(get_class($this));
			//if (array_key_exists('children',$GLOBALS) && array_key_exists(get_class($this),$GLOBALS['children']))
			foreach ($children as $child => $links) CallModuleFunc($child,'OnParentNewRecord',$pkVal);

			//$ret = TRUE;
		//	$fltr = $this->FindFilter($this->GetPrimaryKey(),ctEQ,itNONE);
		//	if (get_class($this) == GetCurrentModule() && $fltr['uid'] !== NULL) {
		//		$ret = $this->GetUrl($pkVal);
		//	}
		} else { // update existing record
			$updateQry = "UPDATE $table SET `$field` = $newValue WHERE `$tablePk` = '$pkVal'";
			//echo "//$updateQry";
			sql_query($updateQry);
			if (mysql_error()) { return FALSE; }
			if ($fieldAlias == $this->GetPrimaryKey()) {
				$ret = $this->GetURL($pfVal);
				$pkVal = $pfVal;
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

		return $ret;
	}

	public function GetCell($fieldName, $row, $url = '') {
		if (is_array($row) && array_key_exists('__module__',$row) && $row['__module__'] != get_class($this)) {
			return CallModuleFunc($row['__module__'],'GetCell',$fieldName,$row,$url);
		}
		if ($this->UNION_MODULE)
			$pkField = '__module_pk__';
		else
			$pkField = $this->GetPrimaryKey();
		$fldId = $this->GetEncodedFieldName($fieldName,$row[$pkField]);
		$celldata = $this->GetCellData($fieldName,$row,$url);
		return "<!-- NoProcess --><div style=\"display:inline\" id=\"$fldId\">$celldata</div><!-- /NoProcess -->";
	}

	public function GetCellData($fieldName, $row, $url = '') {
		if (is_array($row) && array_key_exists('__module__',$row) && $row['__module__'] != get_class($this)) {
			return CallModuleFunc($row['__module__'],'GetCellData',$fieldName,$row,$url);
		}
		$pkVal = NULL;
		if (is_array($row)) $pkVal = array_key_exists('__module_pk__',$row) ? $row['__module_pk__'] : $row[$this->GetPrimaryKey()];

		//		echo "// start PP for $fieldName ".(is_array($row) && array_key_exists($fieldName,$row) ? $row[$fieldName] : '')."\n";
		$value = $this->PreProcess($fieldName,(is_array($row) && array_key_exists($fieldName,$row)) ? $row[$fieldName] : '',$pkVal);

		$fieldData = $this->fields[$fieldName];
		//$url = htmlentities($url);
		// htmlentities moved here from the to do.
		$inputType = array_key_exists('inputtype',$fieldData) ? $fieldData['inputtype'] : itNONE;
		if ((array_key_exists('htmlencode',$fieldData) && $fieldData['htmlencode']) || $inputType == itTEXTAREA) $value = htmlentities($value,ENT_COMPAT,CHARSET_ENCODING);
		if ($inputType !== itNONE && (($row !== NULL && flag_is_set($this->GetOptions(),ALLOW_EDIT)) || ($row === NULL  && flag_is_set($this->GetOptions(),ALLOW_ADD)))) {
			$attr = !empty($url) ? array('ondblclick'=>'javascript:nav(\''.$url.'\')') : NULL;
			$ret = $this->DrawSqlInput($fieldName,$value,$pkVal,$attr);
		} else {
			//possible problems where value contains html? (html will be displayed in full)
			$ret = (!empty($url) && ($value != '' && $value[0] != '<')) ? "<a href=\"$url\">$value</a>" : $value;
		}
		return $ret;
	}

	public function GetTargetURL($field,$row,$includeFilter = true) {
		$fURL = $this->GetFieldProperty($field,'url');
		if ($fURL) return $fURL;

		// check union module
		$searchModule = is_array($row) && array_key_exists('__module__',$row) ? $row['__module__'] : get_class($this);
		//		print_r($GLOBALS['children']);
		//echo "$searchModule<br/>";
		$children = FlexDB::GetChildren($searchModule);

		$info = NULL;
		// get specific field
		foreach ($children as $childName => $links) {
			foreach ($links as $link) {
				if ($link['parentField'] == $field) { $info = $link; break; }
			}
		}
		// if not found, check for fallback
		if (!$info) {
			if ($field !== '*') return $this->GetTargetURL('*',$row,$includeFilter);
			return NULL;
		}

		//$targetUrl = CallModuleFunc($info['moduleName'],'GetURL');

		if ($includeFilter) {
			$targetFilter = $this->GetTargetFilters($field,$row);
			//if (!$targetFilter) $targetFilter = $this->GetTargetFilter('*',$row);
		} else
		$targetFilter = NULL;
		//print_r($targetFilter);
		//print_r($this->filters);
		return CallModuleFunc($info['moduleName'],'GetURL',$targetFilter);

		//return (!$targetFilter) ? $targetUrl : "$targetUrl&amp;$targetFilter";
	}

	public function GetTargetFilters($field,$row) {
		//        ErrorLog("GTF($field,$row)");
		if ($row == NULL) return NULL;
		$searchModule = is_array($row) && array_key_exists('__module__',$row) ? $row['__module__'] : get_class($this);


		$children = FlexDB::GetChildren($searchModule);

		$info = NULL;
		// get specific field
		foreach ($children as $child => $links) {
			foreach ($links as $link) {
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
		CallModuleFunc($targetModule,'_SetupFields');
		$targetModuleFields = GetModuleVar($targetModule,'fields');
		// check $targetModule filters have a target field
		$targetFilters = GetModuleVar($targetModule,'filters');
		if (is_array($targetFilters)) foreach ($targetFilters as $setType) {
			foreach ($setType as $set) {
				foreach ($set as $fltr) {
					if (strpos($fltr['fieldName'],' ') === FALSE && !array_key_exists($fltr['fieldName'],$targetModuleFields)) {
						echo 'Field defined in filter ('.$fltr['fieldName'].') is not present in target module ('.$targetModule.') dataset.<br/>';
					}
				}
			}
		}

		$newFilter = array();
		$additional = array();
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
				if (CallModuleFunc($row['__module__'],'AssertField',$linkInfo['fromField'],$targetModule)) {
					$unionFields = GetModuleVar($row['__module__'],'fields');
					$uFieldCount = 0;
					foreach ($unionFields as $uFieldAlias => $uFieldInfo) {
						if ($uFieldAlias == $linkInfo['fromField']) break;
						$uFieldCount++;
					}

					reset($this->fields);
					for ($i = 0; $i < $uFieldCount; $i++) {
						next($this->fields);
					}
					$value = $row[key($this->fields)];
					//			} else {
					//				ErrorLog("Cannot find field ({$linkInfo['fromField']}) linking from ($targetModule)");
					//				continue;
					//			} elseif ($this->AssertField($linkInfo['fromField'])) {
					//				$value = $row[$linkInfo['fromField']];
				}

				/*				if ($linkInfo['fromField'] === CallModuleFunc($row['__module__'],'GetPrimaryKey'))
					$value = $row['__module_pk__'];
					else {
					$value = $row[$linkInfo['fromField']];
					} */
			} else {
				//$value = $row[$linkInfo['fromField']]; // use actual value, getting the real value on every field causes a lot of lookups, the requested field must be the field that stores the actual value
				/**/
				$tableModule = $this->fields[$linkInfo['fromField']]['vtable']['tModule'];
				if ($this->GetRootField($linkInfo['fromField']) == $this->fields[$linkInfo['fromField']]['field']) {
					//if ($tableModule == $this->GetTabledef()) {
					$value = $row[$linkInfo['fromField']];
				} else {
					$value = $this->GetRealValue($linkInfo['fromField'],$row[$this->GetPrimaryKey()]);
				} /**/
				//ErrorLog(print_r($linkInfo,true));
			}
			//echo $value."<br/>";
			if (!empty($value))
			$newFilter['_f_'.$linkInfo['toField']] = $value;
			//elseif (flag_is_set(CallModuleFunc($targetModule,'GetOptions'),ALLOW_ADD))
			//$additional['newrec'] = 1;
		}
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
		$newRec = ($pkVal) ? $this->LookupRecord($pkVal) : NULL;

		$data = $this->GetCellData($fieldAlias,$newRec,$this->GetTargetURL($fieldAlias,$newRec));

		FlexDB::AjaxUpdateElement($enc_name,$data);
		//$ov = base64_encode($data);
		//AjaxEcho("$('div#$enc_name').html(Base64.decode('$ov'));\n");
	}
}

/**
 * List implimentation of flexDb_DataModule.
 * Default module for displaying results in a list format. Good for statistics and record searches.
 */
abstract class flexDb_ListDataModule extends flexDb_DataModule {
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

		$this->ResetField($fieldAlias,NULL); // reset the "new record" field

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
		$ds = $this->GetDataset(true);
		AjaxEcho('// max recs: '.$this->GetMaxRows());
		if (!$this->GetMaxRows() || mysql_num_rows($ds) + $mod < $this->GetMaxRows()) {
			AjaxEcho('$(".newRow").show();');
			return TRUE;
		}
		AjaxEcho('$(".newRow").hide();');
		if (mysql_num_rows($ds) + $mod == $this->GetMaxRows()) return TRUE;
		return FALSE;
	}

	public function SendHideRowWithField($fieldName) {
		AjaxEcho(<<<SCR_END
ele = $('*[name*=$fieldName]');
// hide row - seems to be within a datalist
ele.parents('TR:eq(0)').hide();
ele.parents("TABLE.datalist").trigger('applyWidgets');
SCR_END
		);
	}

	public function ShowData() {//$sortColumn=NULL) {
		//	echo "showdata ".get_class($this)."\n";
		//	print_r($this->fields);
		//check pk and ptable are set up
		if (is_empty($this->GetTabledef()) && !$this->UnionModules) { ErrorLog('Primary table not set up for '.get_class($this)); return; }

		$dataset = $this->GetDataset(TRUE);
		if (mysql_error()) return;

		$children = FlexDB::GetChildren(get_class($this));
		//print_r($children);
		foreach ($children as $childModule => $links) {
			foreach ($links as $link) {
				//if (!$child) continue;
				//ErrorLog(print_r($child,true));
				if (!flag_is_set($this->GetOptions(),ALLOW_ADD)
						&& flag_is_set(CallModuleFunc($link['moduleName'],'GetOptions'),ALLOW_ADD)
						&& is_subclass_of($link['moduleName'],'flexDb_SingleDataModule')
						&& empty($link['fieldLinks'])) {
					$m = FlexDB::ModuleExists($link['moduleName']);
					$url = CallModuleFunc($link['moduleName'],'GetURL',array($m['module_id'].'_new'=>1));
					FlexDB::LinkList_Add('list_functions:'.get_class($this),null,CreateNavButton('New Item',$url,array('class'=>'greenbg')),1);
				}
			}
		}

		TriggerEvent('OnShowDataList');
		//		LoadChildren(get_class($this));
		// first draw header for list
		//		$fl = (flag_is_set($this->GetOptions(),ALLOW_FILTER)) ? ' filterable' : '';
		if (!isset($GLOBALS['inlineListCount'])) $GLOBALS['inlineListCount'] = 0;
		else $GLOBALS['inlineListCount']++;

		$tabGroupName = FlexDB::Tab_InitGroup();

		//$layoutID = FlexDB::tab_ //$tabGroupName.'-'.get_class($this)."_list_".$GLOBALS['inlineListCount'];
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
						echo "<td colspan=\"$sectionCount\" class=\"{sorter: false}$secClass\">".nl2br(htmlentities_skip($sectionName,'<>"'))."</td>";
						$sectionCount = 0;
						$sectionID = $fieldData['layoutsection'];
					}
					$sectionFieldTitles[$sectionID] = array_key_exists($sectionID,$sectionFieldTitles) ? $sectionFieldTitles[$sectionID] : !empty($fieldData['visiblename']);
					//if ($sectionCount == 0 && $sectionID > 0) $this->firsts[$fieldName] = true;
					$sectionCount++;
				}
				$sectionName = $this->layoutSections[$sectionID];
				$secClass = empty($sectionName) ? '' : ' sectionHeader';
				echo "<td colspan=\"$sectionCount\" class=\"{sorter: false}$secClass\">".nl2br(htmlentities_skip($sectionName,'<>"'))."</td>";
				echo "</tr>";
			}

			// start of FIELD headers
			echo '<tr>';
			if (flag_is_set($this->GetOptions(),ALLOW_DELETE)) { echo "<th class=\"{sorter: false}\">&nbsp;</th>"; $colcount++; }
			foreach ($this->fields as $fieldName => $fieldData) {
				if ($fieldData['visiblename'] === NULL) continue;
				$colcount++;

				$classes = array();
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
				echo "<th$class>";
				echo nl2br(htmlentities_skip($fieldData['visiblename'],'<>"'));
				if (flag_is_set($this->GetOptions(),ALLOW_FILTER) && $this->hasEditableFilters === true && $this->hideFilters !== TRUE) {
					foreach ($this->filters as $fType) {
						foreach ($fType as $filterset) { //flag_is_set($fieldData['options'],ALLOW_FILTER)) {
							foreach ($filterset as $filterInfo) {
								if ($fieldName != $filterInfo['fieldName']) continue;
								if ($filterInfo['it'] === itNONE) continue;
								echo "<br/>".$this->GetFilterBox($filterInfo);
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

			$pager = mysql_num_rows($dataset) > 100 ? '<span class="pager" style="float:right;"></span>' : '';
			$records = ($dataset == FALSE || mysql_num_rows($dataset) == 0) ? "There are no records to display." : 'Total Rows: '.mysql_num_rows($dataset).' (Max 150 shown)';
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
		if ($dataset == FALSE || mysql_num_rows($dataset) == 0) {
		} else {
			//			if ($result != FALSE && mysql_num_rows($result) > 200)
			//				echo "<tr><td colspan=\"$colcount\">There are more than 200 rows. Please use the filters to narrow your results.</td></tr>";
			$i = 0;
			while (($row = $this->GetRecord($dataset,$i)) && $i <= 150) {
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
							$pkVal = $row[$this->GetPrimaryKey()];
							$preProcessValue = floatval(preg_replace('/[^0-9\.-]/','',$this->PreProcess($fieldName,$row[$fieldName],$pkVal)));
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
			$hideNew = ($this->GetMaxRows() && mysql_num_rows($dataset) >= $this->GetMaxRows()) ? ' style="display:none"' : '';
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

		FlexDB::Tab_Add($this->GetTitle(),$cont,$tabGroupName,false,$this->GetSortOrder());
		FlexDB::Tab_InitDraw($tabGroupName);
	}

	function DrawRow($row) {
		$body = "<tr>";
		if (flag_is_set($this->GetOptions(),ALLOW_DELETE)) {
			//$delbtn = FlexDB::DrawInput($this->CreateSqlField('delete',$row[$this->GetPrimaryKey()],'del'),itBUTTON,'x',NULL,array('class'=>'fdb-btn redbg','onclick'=>'if (!confirm(\'Are you sure you wish to delete this record?\')) return false; uf(this);'));
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
 * Single form implimentation of flexDb_DataModule.
 * Default module for displaying results in a form style. Good for Data Entry.
 */
abstract class flexDb_SingleDataModule extends flexDb_DataModule {
	/*
	 public function CreateParentNavButtons() {
		foreach ($this->parents as $parentName => $linkArray){
		if ($parentName !== GetCurrentModule()) continue;
		foreach ($linkArray as $linkInfo) {
		if ($linkInfo['parentField'] !== NULL) continue; // is linked to fields in the list, skip it
		if (flag_is_set($this->GetOptions(),ALLOW_ADD)) { // create an addition button  --  && GetCurrentModule() == get_class($this)
		$filters = array('newrec'=>1); // set this filter so that the primary key is negative, this will force no policy to be found, and show a new form
		FlexDB::AppendVar('footer_left',CreateNavButton('New Record',BuildQueryString($this->GetURL(),$filters),NULL,array('class'=>'fdb-btn-new')));
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
		$dataset = $this->GetDataset(TRUE);

		$row = NULL;
		if (!$this->IsNewRecord()) { // records exist, lets get the first.
			$row = $this->GetRecord($dataset,0);
			if (!$row) {
				echo "The record you requested is not available.";
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
			//$delbtn = FlexDB::DrawInput($this->CreateSqlDeleteField($this->GetPrimaryKey().'='.$this->GetFilterValue($fltr['uid'])),itBUTTON,'Delete Record',NULL,array('class'=>'fdb-btn-del','onclick'=>'if (!confirm(\'Are you sure you wish to delete this record?\')) return false; uf(this);'));
			$delbtn = $this->GetDeleteButton($this->GetFilterValue($fltr['uid']),'Delete Record');
			//$delbtn = '<a name="'.$this->CreateSqlField('del',$this->GetFilterValue($fltr['uid']),'del').'" class="fdb-btn redbg" onclick="if (!confirm(\'Are you sure you wish to delete this record?\')) return false; uf(this);">Delete Record</a>';
			FlexDB::AppendVar('footer_left',$delbtn);
		}

		//		if (!$this->IsNewRecord()) { // records exist, lets get the first.
		// pagination?
		//			if (mysql_num_rows($result) > 1) {
		// multiple records exist in this set, sort out pagination
		//			}
		//			$row = $this->GetRecord(0);
		//		}

		$order = $this->GetSortOrder();
		$extraCount = 1;
		if (!flag_is_set($this->GetOptions(), NO_TABS))
			$tabGroupName = FlexDB::Tab_InitGroup();
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
				$pkValue	= is_array($row) && array_key_exists($this->GetPrimaryKey(),$row) ? $row[$this->GetPrimaryKey()] : NULL;
				$fieldValue	= $this->PreProcess($fieldName,is_array($row) && array_key_exists($fieldName,$row) ? $row[$fieldName] : '',$pkValue);

				$targetUrl = $this->GetTargetUrl($fieldName,$row);

				$out .= "<tr>";

				if ($hasFieldHeaders && $fieldCount !== 1)
					$out .= "<td class=\"fld\">".$fieldData['visiblename']."</td>";
				$out .= '<td>'.$this->GetCell($fieldName,$row,$targetUrl).'</td>';

				$out .= "</tr>";
			}
			$out .= "</table>";
			if (!flag_is_set($this->GetOptions(), NO_TABS))
				FlexDB::Tab_Add($SN,$out,$tabGroupName,false,$order);
			else
				echo $out;
		}

		if (!flag_is_set($this->GetOptions(), NO_TABS))
			FlexDB::Tab_InitDraw($tabGroupName);
	}
}

?>