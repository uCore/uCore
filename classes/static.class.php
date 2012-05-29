<?php
define('TEMPLATE_BLANK','__blank__');
//define('TEMPLATE_ADMIN','__admin__');
define('TEMPLATE_DEFAULT','__default__');

utopia::AddTemplateParser('utopia','utopia::parseVars');
utopia::AddTemplateParser('list','utopia::DrawList');
utopia::AddTemplateParser('tab','utopia::Tab_GetOutput');
utopia::AddTemplateParser('get','utopia::parseGet');
utopia::AddTemplateParser('post','utopia::parsePost');
utopia::AddTemplateParser('request','utopia::parseRequest');
utopia::AddTemplateParser('session','utopia::parseSession');
utopia::AddTemplateParser('const','utopia::parseConst');
utopia::AddTemplateParser('domain','utopia::GetDomainName','');

utopia::AddTemplateParser('home_url',PATH_REL_ROOT,'');
utopia::AddTemplateParser('inline','inline=true','');

utopia::SetVar('tp',PATH_REL_CORE.'images/tp.gif');

class utopia {
	private static $children = array();
	static function AddChild($parent, $child, $info) {
		$info['parent'] = $parent;
		if (isset(self::$children[$parent]) && isset(self::$children[$parent][$child])) {
			foreach (self::$children[$parent][$child] as $compare) {
				if ($info == $compare) return;
			}
		}
		self::$children[$parent][$child][] = $info;
	}

	static function GetChildren($parent) {
		$specific      = (isset(self::$children[$parent]))     ? self::$children[$parent] : array();
		$currentModule = ($parent == utopia::GetCurrentModule() && isset(self::$children['/'])) ? self::$children['/'] : array();
		$catchAll      = (isset(self::$children['*'])) ? self::$children['*'] : array();
		$baseModule = array();

		switch ($parent) {
			case 'uCMS_View':
				$currentPage   = uCMS_View::findPage();
				if ($currentPage['is_home'] && isset(self::$children[''])) $baseModule = self::$children[''];
				break;
			case 'uDashboard':
				if (isset(self::$children[''])) $baseModule = self::$children[''];
		}

		$arr = array_merge($catchAll,$baseModule,$currentModule,$specific);

		return $arr;
	}

	static function SetTitle($text) {
		self::SetVar('title',$text);
	}
	static function GetTitle($textOnly = false, $fallback = false) {
		if (!self::VarExists('title'))
			$content = '&lt;&lt; No Title &gt;&gt;';
		else
			$content = self::GetVar('title');

		if (empty($content) || $textOnly) return $content;
		return '<title>'.$content.'</title>'."\n";
	}
	static function SetDescription($text) {
		self::SetVar('meta_description',$text);
	}
	static function GetDescription($textOnly = false) {
		if (!self::VarExists('meta_description'))
		$content = '';
		else
		$content = self::GetVar('meta_description');

		if (empty($content) || $textOnly) return $content;
		return '<meta name="description" content="'.$content.'" />'."\n";
	}
	static function SetKeywords($text) {
		self::SetVar('meta_keywords',$text);
	}
	static function GetKeywords($textOnly = false) {
		if (!self::VarExists('meta_keywords'))
		$content = '';
		else
		$content = self::GetVar('meta_keywords');

		if (empty($content) || $textOnly) return $content;
		return '<meta name="keywords" content="'.$content.'" />'."\n";
	}
	static function AddMetaTag($name,$content) {
		$nifunc = create_function('$obj,$event,$doc','
			$head = $doc->getElementsByTagName("head")->item(0);
			$node = $doc->createElement("meta");
			$node->setAttribute("name","'.$name.'"); $node->setAttribute("content","'.$content.'");
			$head->appendChild($node);
		');
		uEvents::AddCallback('ProcessDomDocument',$nifunc);
	}
	
	/**
	 * Link a css file to the document
	 *
	 * @deprecated
	 */
	static function AddCSSFile($path,$start = false) {
		uCSS::LinkFile($path,$start?-1:null);
	}
	/*
	 * Link a javascript file to the document
	 *
	 * @deprecated
	 */
	static function AddJSFile($path,$start = false) {
		uJavascript::LinkFile($path,$start?-1:null);
	}

	private static $allmodules = NULL;
	static function GetModules($refresh=false) {
		if (self::$allmodules === NULL || $refresh) {
			$rows = array();
			$classes = get_declared_classes();
			foreach ($classes as $id => $class) {
				$ref = new ReflectionClass($class);
				if ($ref->isAbstract()) continue;

				if (!$ref->implementsInterface('iUtopiaModule')) continue;

				$parents = array_values(class_parents($class));
                                $interfaces = $ref->getInterfaceNames();
				
				$class = array('module_name'=>$class);
				$class['module_id'] = $id;
				$class['types'] = array_merge($parents,$interfaces);
				$class['uuid'] = $class['module_name'];

				if ($ref->isSubclassOf('uBasicModule')) {
					$obj = utopia::GetInstance($class['module_name']);
        	                        $class['uuid'] = $obj->GetUUID();
				}
				$rows[$class['module_name']] = $class;
			}

			self::$allmodules = $rows;
		}

		return self::$allmodules;
	}

	static function ModuleExists($module) {
		$modules = self::GetModules();
		if (isset($modules[$module])) return $modules[$module];
	}
	static function UUIDExists($uuid) {
		$modules = self::GetModules();
		foreach ($modules as $m) {
			if ($uuid == $m['uuid']) return $m;
			if (is_array($m['uuid']) && array_search($uuid,$m['uuid']) !== FALSE) return $m;
		}
		return false;
	}

	static function GetModulesOf($type) {
		$inputs = self::GetModules();
		foreach ($inputs as $k => $m) {
			if (array_search($type,$m['types']) === FALSE) unset($inputs[$k]);
		}
		return $inputs;
	}

	static function GetRewriteSections() {
		$REQUESTED_URL = array_key_exists('HTTP_X_REWRITE_URL',$_SERVER) ? $_SERVER['HTTP_X_REWRITE_URL'] : $_SERVER['REQUEST_URI'];
		$REQUESTED_URL = preg_replace('/\?.*/i','',$REQUESTED_URL);

		$REQUESTED_URL = preg_replace('/^'.addcslashes(PATH_REL_ROOT,'/').'/','',$REQUESTED_URL);
		$REQUESTED_URL = preg_replace('/^u\//','',$REQUESTED_URL);

		$path = urldecode($REQUESTED_URL);

		return explode('/',$path);
	}

	static function SetCurrentModule($module) {
		if (!self::ModuleExists($module)) return;
		
		$cm = utopia::GetCurrentModule();
		$o = utopia::GetInstance($cm);
		if (flag_is_set($o->GetOptions(),PERSISTENT)) return;
		
		utopia::SetVar('current_module',$module);
	}
	static function GetCurrentModule() {
		// cm variable
		if (utopia::VarExists('current_module')) return utopia::GetVar('current_module');

		// GET uuid
		if (isset($_GET['uuid'])) {
			$m = utopia::UUIDExists($_GET['uuid']);
			if ($m) return $m['module_name'];
		}

		// rewritten url?   /u/MOD/
		$sections = self::GetRewriteSections();
		if ($sections && isset($sections[0])) {
			$m = utopia::UUIDExists($sections[0]);
			if ($m) return $m['module_name'];
		}

		// admin root?
		if (strpos($_SERVER['REQUEST_URI'],PATH_REL_CORE) === 0) return 'uDashboard';

		// CMS
		return 'uCMS_View';
	}

	static function Launcher($module = NULL) {
		// requesting a real path?
		$path = parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
		if (is_file(PATH_ABS_ROOT.$path) && $path !== PATH_REL_CORE.'index.php') {
			self::CancelTemplate();
			include(PATH_ABS_ROOT.$path);
			self::Finish();
		}

		if ($module == NULL) $module = self::GetCurrentModule();

		if (!utopia::ModuleExists($module)) {
			utopia::PageNotFound();
		}

		utopia::SetVar('current_module',$module);
		$obj = utopia::GetInstance($module);
		utopia::SetVar('title',$obj->GetTitle());
		// run module
		$obj->_RunModule();
	}

	static $instances = array();
	static function &GetInstance($class,$defaultInstance = true) {
		if (!$defaultInstance) {
			$instance = new $class;
			return $instance;
		}

		if (!isset(self::$instances[$class]))
			self::$instances[$class] = new $class;

		return self::$instances[$class];
	}

	static $finished = false;
	static function Finish() {
		if (self::$finished) return;
		self::$finished = true;
		while (ob_get_level() > 3) ob_end_flush();
		include(PATH_ABS_CORE.'finalise.php');
	}

	private static $customInputs = array();
	static function AddInputType($inputType,$callback) {
		if (isset(self::$customInputs[$inputType])) return;
		define($inputType,$inputType);
		self::$customInputs[$inputType] = $callback;
	}

	static function DrawInput($fieldName,$inputType,$defaultValue='',$possibleValues=NULL,$attributes = NULL,$noSubmit = FALSE) {
		$out = '';
		if ($attributes === NULL)
		$attributes = array();
		//		$defaultValue = str_replace(DEFAULT_CURRENCY_HTML,DEFAULT_CURRENCY,$defaultValue);
		//		$defaultValue = str_replace(DEFAULT_CURRENCY,DEFAULT_CURRENCY_HTML,$defaultValue);

		//		if (!isset($attributes['id'])) $attributes['id'] = $fieldName;
		//		if(!isset($attributes['name']) && !$noSubmit) $attributes['name'] = $fieldName;
		$attributes['name'] = $fieldName;
		
		if (isset($attributes['class'])) $attributes['class'] .= ' inputtype-'.$inputType;
		else $attributes['class'] = 'inputtype-'.$inputType;

		$defaultValue = utopia::jsonTryDecode($defaultValue);
		
		//print_r($attributes);
		$attr = BuildAttrString($attributes);

		if (isset(self::$customInputs[$inputType]))
			return call_user_func_array(self::$customInputs[$inputType],array($fieldName,$inputType,$defaultValue,$possibleValues,$attributes,$noSubmit));

		switch ($inputType) {
			case itNONE: $out .= $defaultValue; break;
			case itBUTTON:
			case itSUBMIT:
			case itRESET:
				if (isset($attributes['class']))
					$attributes['class'] .= ' btn';
				else
					$attributes['class'] = 'btn';
				$attributes['class'] .= ' btn-'.$inputType;
				$attr = BuildAttrString($attributes);
				$out .= '<a '.$attr.' href="javascript:void(0)">'.$defaultValue.'</a>';
				break;
			case itCHECKBOX:
				if (is_array($possibleValues)) foreach ($possibleValues as $name => $val) {
					$checked = ($val === $defaultValue || (is_array($defaultValue) && in_array($val,$defaultValue))) ? ' checked="checked"' : '';
					$out .= "<div class=\"left mr10\"><input$attr type=\"checkbox\"$checked value=\"$val\"/>$name</div>";
				} else {
					$checked = ($defaultValue == 1) ? ' checked="checked"': '';
					$out .= "<input$attr type=\"checkbox\"$checked value=\"1\"/>";
				}
				break;
			case itOPTION:
				if (!is_array($possibleValues)) { ErrorLog('Option field specified but no possible values found'); return ''; }
				$count = 0;
				$defaultExists = false;
				foreach ($possibleValues as $name => $val) {
					$count++; $attributes['id'] = "$fieldName-$count"; $attr = BuildAttrString($attributes);
					$checked = ($val == $defaultValue || (is_array($defaultValue) && in_array($val,$defaultValue))) ? ' checked="checked"' : '';
					if ($checked != '') $defaultExists = true;
					$out .= "<input type=\"radio\" $attr value=\"$val\"$checked/>$name<br/>";
				}
				if (!$defaultExists && ($defaultValue != ''))
				$out .= "<input type=\"radio\" $attr value=\"$val\" checked=\"true\">$defaultValue";
				break;
			case itMD5:
			case itPASSWORD:
			case itPLAINPASSWORD:
				$out .= "<input type=\"password\" $attr value=\"\"/>";
				break;
			case itTEXT:
				$val = $defaultValue;
				$out .= "<input type=\"text\" $attr value=\"$val\"/>";
				break;
			case itTEXTAREA:
				//sanitise value.
				if (!utopia::SanitiseValue($defaultValue,'string') && !utopia::SanitiseValue($defaultValue,'NULL')) $defaultValue = 'Value has been sanitised: '.var_export($defaultValue,true);
				$defaultValue = htmlentities($defaultValue,ENT_QUOTES,CHARSET_ENCODING);
				$out .= "<textarea $attr>$defaultValue</textarea>";
				break;
			case itCOMBO:
				if (empty($possibleValues)) $possibleValues = array();
				$defaultExists = false;
				$blankVal = isset($possibleValues['']) ? $possibleValues[''] : FALSE;
				if ($blankVal === FALSE) $blankVal = '&nbsp;';
				$out .= "<select $attr><option value=\"\">$blankVal</option>";
				if (is_array($possibleValues)) foreach ($possibleValues as $key => $val) {
					if ($key === '') continue;
					$selected = '';
					if ($defaultValue !== '' && ($key == $defaultValue || $val == $defaultValue || (is_array($defaultValue) && in_array($key,$defaultValue)))) {
						$defaultExists = true;
						$selected = ' selected="selected"';
					}
					$valOutput = $key !== $val ? " value=\"$key\"" : '';
					$out .= "<option$valOutput$selected>$val</option>";
				}
				if (!$defaultExists && ($defaultValue != '')) {
					$out .= "<optgroup label=\"No longer available\"><option selected=\"selected\">$defaultValue</option></optgroup>";
				}
				$out .= "</select>";
				//				} else if (is_string($possibleValues)) { // autocomplete info
				//					$out .= "<input type=\"text\" $attr class=\"autocomplete\" gv=\"$possibleValues\" value=\"$defaultValue\">\n";
				//				}
				break;
			case itSUGGEST:
				//				if (is_array($possibleValues)) { // array of combos
				//					$out .= "<select $attr><option value=\"\"></option>";
				//					foreach ($possibleValues as $name => $val) {
				//						if (empty($val)) continue;
				//						$selected = ($val == $defaultValue) ? ' selected="true"' : '';
				//						$out .= "<option value=\"$val\"$selected>$name</option>";
				//					}
				//					$out .= "</select>";
				//				} else if (is_string($possibleValues)) { // autocomplete info
				//
				if (!isset($attributes['class'])) $attributes['class'] = '';
				$attributes['class'] .= " autocomplete {gv:'$possibleValues'}";
				$attr = BuildAttrString($attributes);
				$out .= "<input type=\"text\" $attr value=\"$defaultValue\"/>\n";
				//				}
				break;
			case itSUGGESTAREA:
				//AA $defaultValue = htmlentities($defaultValue,ENT_QUOTES,CHARSET_ENCODING);
				//	settype($possibleValues,'integer');
				//	$ml = (is_numeric($possibleValues) && $possibleValues > 0) ? " cols=\"$possibleValues\" rows=\"".floor($possibleValues*0.08)."\"" : "";
				//					$out .= "<textarea $attr $ml>$defaultValue</textarea>";
				if (!isset($attributes['class'])) $attributes['class'] = '';
				$attributes['class'] .= " autocomplete {gv:'$possibleValues'}";
				$attr = BuildAttrString($attributes);
				$out .= "<textarea $attr>$defaultValue</textarea>\n";
				break;
			case itLISTBOX:
				if (!is_array($possibleValues)) { ErrorLog('Listbox field specified but no possible values found'); return ''; }
				$out .= "<select size=5 $attr><option value=\"\"></option>";
				foreach ($possibleValues as $name => $val) {
					if (empty($val)) continue;
					$selected = ($val == $defaultValue || (is_array($defaultValue) && in_array($val,$defaultValue))) ? ' selected="selected"' : '';
					$out .= "<option value=\"$val\"$selected>$name</option>";
				}
				$out .= "</select>";
				break;
			case itFILE:
				//$defaultValue = htmlentities($defaultValue,ENT_QUOTES,CHARSET_ENCODING);
				//$defaultValue = htmlentities($defaultValue);
				$out .= "<input type=\"file\" $attr/>";
				break;
			case itDATE:
				//$formattedVal = ($defaultValue === SQL_FORMAT_EMPTY_TIMESTAMP) || ($defaultValue === SQL_FORMAT_EMPTY_DATE) || ($defaultValue === NULL) || ($defaultValue === '') ? '' : $defaultValue;//date('d/m/Y',strptime($defaultValue,'d/m/Y'));
				$formattedVal = $defaultValue;
				if (!isset($attributes['class'])) $attributes['class'] = '';
				$attributes['class'] .= " dPicker";
				$attr = BuildAttrString($attributes);
				$out .= "<input type=\"text\" $attr value=\"$formattedVal\"/>";
				break;
			case itSCAN:
				$out .= '<applet code="com.asprise.util.jtwain.web.UploadApplet.class"
	codebase="http://asprise.com/product/jtwain/files/"
	archive="JTwain.jar"
	width="600" height="470">
	<param name="DOWNLOAD_URL" value="http://asprise.com/product/jtwain/files/AspriseJTwain.dll">
	<param name="DLL_NAME" value="AspriseJTwain.dll">
	<param name="UPLOAD_URL" value="http://'.$_SERVER['HTTP_HOST'].'/internal/ajax/update.php?uuid='.$_REQUEST['uuid'].'">
	<param name="UPLOAD_PARAM_NAME" value="'.$fieldName.'">
	<param name="UPLOAD_EXTRA_PARAMS" value="">
	<param name="UPLOAD_OPEN_URL" value="http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'">
	<param name="UPLOAD_OPEN_TARGET" value="_self">
	Oops, Your browser does not support Java applet!
</applet>';
				//$defaultValue = htmlentities($defaultValue,ENT_QUOTES,CHARSET_ENCODING);
				//$defaultValue = htmlentities($defaultValue);
				//$out .= "<input type=\"file\" $attr value=\"$defaultValue\">";
				break;
			case itCUSTOM:
				//	ErrorLog(get_class($this).': Unhandled input type itCUSTOM');
				break;
			default:
				ErrorLog("Unrecognized input type ($inputType) on field ($fieldName)");
				break;
		}

		return $out;
	}

	/* VAR */
	private static $globalVariables = array();//'powered'=>'<div style="text-align:center; background-color:black; padding:0.2em"><span style="font-size:10px; background-color:#ffffee; padding:0.5em;">Powered by <a target="_blank" href="http://www.utopiasystems.co.uk">Utopia Systems</a></span></div>');
	static function VarExists($varname) {
		return isset(self::$globalVariables[$varname]);
	}

	static function SetVar($varname,$value) {
		self::$globalVariables[$varname] = $value;
	}
	static function &GetVar($varname,$initialise=NULL, $raw = false) {
		if (!self::VarExists($varname))
			self::$globalVariables[$varname] = $initialise;

		if (!$raw && is_array(self::$globalVariables[$varname]) && is_callable(self::$globalVariables[$varname])) {
			$base = self::$globalVariables[$varname];
			$args = array_splice($base,2);
			$result = call_user_func_array($base,$args);
			return $result;
		}

		return self::$globalVariables[$varname];
	}

	static function AppendVar($var,$text) {
		if (self::VarExists($var))
			self::$globalVariables[$var] .= $text;
		else
			self::$globalVariables[$var] = $text;
	}
	static function PrependVar($var,$text) {
		if (self::VarExists($var))
			self::$globalVariables[$var] = $text.self::$globalVariables[$var];
		else
			self::$globalVariables[$var] = $text;
	}

	/*  TABS  */
	private static $tabGroups = array();
	private static $tabOrderCount = 1;
	static function Tab_InitGroup($tabGroup=NULL) {
		if (!$tabGroup) $tabGroup = utopia::GetCurrentModule().'-tabs';
		if (isset(self::$tabGroups[$tabGroup])) return $tabGroup;
		//			echo '<div class="tabGroup" id="'.$tabGroup.'"><ul></ul></div>';
		self::$tabGroups[$tabGroup] = array();
		return $tabGroup;
	}
	static function Tab_Add($tabTitle,$tabContent,$tabGroup=NULL,$isURL=false,$order=0) {
		//if ($order === NULL) $order = self::$tabOrderCount;
		//self::$tabOrderCount++;
		//echo "Added Tab: $tabTitle - $order<br>";
		if (!$tabGroup) $tabGroup = self::Tab_InitGroup();
		$tabID = self::Tab_GetCount($tabGroup)+1;
		//echo $tabTitle
		if (isset(self::$tabGroups[$tabGroup]['tab'.$tabID])) { ErrorLog("TabID ($tabID) already exists in Group ($tabGroup)"); return; }
		self::$tabGroups[$tabGroup]['tab'.$tabID] = array('id'=>$tabGroup.'-'.$tabID,'title'=>$tabTitle,'content'=>$tabContent,'isURL'=>$isURL,'order'=>$order);
	}
	static function Tab_GetCount($tabGroup=NULL) {
		if (!$tabGroup) $tabGroup = self::Tab_InitGroup();
		return count(self::$tabGroups[$tabGroup]);
	}
	static function Tab_Append($tabID,$content,$tabGroup=NULL) {
		if (!$tabGroup) $tabGroup = self::Tab_InitGroup();
//		print_r(self::$tabGroups[$tabGroup]);
		if (!isset(self::$tabGroups[$tabGroup]['tab'.$tabID])) { ErrorLog("TabID ($tabID) doesnt exist in Group ($tabGroup) for append."); return; }
		self::$tabGroups[$tabGroup]['tab'.$tabID]['content'] .= $content;
	}
	static function Tab_GetOutput($group) {
		ob_start();
		self::Tab_DrawGroup($group);
		$c = ob_get_contents();
		ob_end_clean();
		return $c;
	}
	static function Tab_DrawGroup($tabGroup=NULL,$extraClasses="") {
		if (!$tabGroup) $tabGroup = self::Tab_InitGroup();
		$tabGroupArray = self::$tabGroups[$tabGroup];
		//print_r($tabGroupArray);
		if (count($tabGroupArray) <= 1) { $tabInfo = reset($tabGroupArray); echo $tabInfo['content']; return; }


		$takenOrders = array();
		foreach ($tabGroupArray as $tabID => $tabInfo) {
			$takenOrders[] = $tabInfo['order'];
		}
		foreach ($tabGroupArray as $tabID => $tabInfo) {
			if ($tabInfo['order'] === NULL) {
				$i = 2;
				while (array_search($i,$takenOrders)!==FALSE) $i++;
				$tabGroupArray[$tabID]['order'] = $i;
				$takenOrders[] = $i;
			}
		}
		array_sort_subkey($tabGroupArray,'order');
		//print_r($tabGroupOrdered);

		echo '<div class="tabGroup '.$extraClasses.'" id="'.$tabGroup.'">';
		echo '<ul>';
		foreach ($tabGroupArray as $tabID =>$tabInfo) {
			if ($tabInfo['isURL'])
			echo '<li><a href="'.$tabInfo['content'].'"><span>'.$tabInfo['title'].'</span></a></li>';
			else
			echo '<li><a href="#'.$tabID.'"><span>'.$tabInfo['title'].'</span></a></li>';
		}
		echo '</ul>';
		foreach ($tabGroupArray as $tabID => $tabInfo) {
			if ($tabInfo['isURL']) continue;
			echo '<div id="'.$tabID.'" class="{tabTitle:\''.$tabInfo['title'].'\'}">'.$tabInfo['content']."</div>\n";
		}
		echo '</div>'; // cose container
	}
	private static $tabs_drawing = array();
	static function Tab_InitDraw($tabGroup) {
		if (isset($_REQUEST['__ajax'])) return;
		if (array_search($tabGroup,self::$tabs_drawing) !== FALSE) return;
		self::$tabs_drawing[] = $tabGroup;
		echo '{tab.'.$tabGroup.'}';
		//ob_start();
	}
/*	static function Tab_FinaliseDrawing() {
		if (count(self::$tabs_drawing)<=0) return;
		ob_flush();
		$body = utopia::GetVar('content');
		foreach (self::$tabs_drawing as $group) {
			ob_start();
			self::Tab_DrawGroup($group);
			$tabBlock = ob_get_contents();
			ob_end_clean();
			$
			utopia::SetVar('content',str_replace('{tab.'.$group.'}', $tabBlock, $body));
		}
		return;
		$contents = "";
		foreach (self::$tabs_drawing as $group) {
			$contents = $contents.ob_get_contents();
			ob_end_clean();
			//$tabGroupArray = self::$tabGroups[$group]; foreach ($tabGroupArray as $tabID => $tabInfo) echo "$tabID:{$tabInfo['order']}<br>";
			self::Tab_DrawGroup($group);
		}
		echo $contents;
	}*/

	/*  LINKLIST  */
	static function DrawList($id) {
		$replacement = utopia::LinkList_Get($id).utopia::LinkList_Get('list_functions:'.$id);
		return $replacement;
	}
	static function LinkList_Add($listName,$text,$url,$order = 100,$listAttrs = NULL,$linkAttrs = NULL) {
		$list =& utopia::GetVar("linklist_$listName");
		if ($list == NULL) $list = array();
//$bt = useful_backtrace(0,4);
		$list[] = array('text'=>$text,'url'=>$url,'order'=>$order,'attrList'=>$listAttrs,'attrLink'=>$linkAttrs);//,$bt);
	}
	//static function LinkList_Sort($a,$b) {$c1 = $a["order"]; $c2 = $b["order"]; if ($c1 == $c2) return 0;  return $c1 < $c2 ? -1 : 1;}
	static function LinkList_Get($listName,$id=NULL,$listAttrs = NULL,$linkAttrs = NULL) {
		if (!$id) $id = "ulist_$listName";
		$id = " id=\"$id\"";
		$list = utopia::GetVar("linklist_$listName");
		if (!is_array($list)) return;

		array_sort_subkey($list,'order');

		$return = "";
		foreach ($list as $order => $info) {
			$attrsList = "";
			$attrsLink = "";
			if (isset($info['attrList']) && is_array($info['attrList']) && isset($info['attrList']['class']))
			$info['attrList']['class'] .= " linklist-link";
			else
			$info['attrList']['class'] = "linklist-link";
			if (isset($info['attrList']) && is_array($info['attrList']) || is_array($listAttrs)) {
				if (is_array($listAttrs)) foreach ($listAttrs as $attr=>$val)
				$info['attrList'][$attr] = $val;
				foreach ($info['attrList'] as $k => $v) {
					$attrsList .= " $k=\"$v\"";
				}
			}
			if (isset($info['attrLink']) && is_array($info['attrLink']) || is_array($linkAttrs)) {
				if (is_array($linkAttrs)) foreach ($linkAttrs as $attr=>$val)
				$info['attrLink'][$attr] = $val;
				foreach ($info['attrLink'] as $k => $v) {
					$attrsLink .= " $k=\"$v\"";
				}
			}

			if (empty($info['text']) && !empty($info['url'])) {
				$return .= "<li$attrsList>".$info['url']."</li>";
			} elseif (empty($info['url'])) {
				if (empty($info['text']))
				// style=\"line-height:5px;height:5px;width:5px;\"
				$return .= "<li class=\"linklist-sep\">&nbsp;</li>";//"<div$attrsLink style=\"width:5px; height:5px;\"></div>";
				else
				$return .= "<li$attrsList><a$attrsLink>".htmlspecialchars($info['text'])."</a></li>";//<div$attrsLink>{$info['text']}</div>";
			} else {
				$href = empty($info['url']) ? '' : " href=\"".htmlspecialchars($info['url'])."\"";
				$return .= "<li$attrsList><a$attrsLink$href>".htmlspecialchars($info['text'])."</a></li>";
			}
		}
		$return = trim($return,"\n");
		return "\n<ul$id class=\"linklist\">$return</ul>";
	}

	/* TEMPLATE */
	public static function GetTemplates($includeDefault=false,$includeCore=true) {
		$userTemplates = glob(PATH_ABS_TEMPLATES.'*'); // find all user templates
		$adminTemplates = glob(PATH_ABS_CORE.'styles/*'); // find all admin templates
		$nTemplates = array();
		if ($includeDefault) $nTemplates['Default Template'] = TEMPLATE_DEFAULT;

		if (is_array($adminTemplates)) foreach ($adminTemplates as $k => $v) {
			if ($v == '.' || $v == '..' || !is_dir($v)) continue;
			$v = str_replace(PATH_ABS_ROOT,'/',$v);
			$v = fix_path($v,'/');
			$nTemplates[$v] = $v;
		}
		if (is_array($userTemplates)) foreach ($userTemplates as $k => $v) {
			if ($v == '.' || $v == '..' || !is_dir($v)) continue;
			$v = str_replace(PATH_ABS_ROOT,'/',$v);
			$v = fix_path($v,'/');
			$nTemplates[$v] = $v;
		}
		
		foreach ($nTemplates as $template => $v) {
			if (file_exists(PATH_ABS_ROOT.$template.'/template.ini')) {
				$inifile = parse_ini_file(PATH_ABS_ROOT.$template.'/template.ini');
				if (isset($inifile['hidden'])) unset($nTemplates[$template]);
			}
		}
		
		return $nTemplates;
	}
	public static $adminTemplate = false;
	private static $usedTemplate = NULL;
	public static function CancelTemplate($justClean=false) { if (!self::UsingTemplate()) return; ob_clean(); if (!$justClean) self::$usedTemplate = NULL; }
	public static function UseTemplate($template = TEMPLATE_DEFAULT) {
		if ($template == TEMPLATE_DEFAULT) {
			if (self::GetCurrentModule() && self::GetInstance(self::GetCurrentModule()) instanceof iAdminModule) $template = TEMPLATE_ADMIN;
			else $template = modOpts::GetOption('default_template');
		}
		if (!$template) $template = TEMPLATE_BLANK;
		$ret = true;

		if ($template != TEMPLATE_BLANK && !file_exists(PATH_ABS_ROOT.$template)) {
			$template = TEMPLATE_BLANK;
			$ret = false;
		}
		self::$usedTemplate = $template;
		return $ret;
	}
	public static function UsingTemplate($compare = NULL) { if ($compare === NULL) return (self::$usedTemplate !== NULL); else return (self::$usedTemplate === $compare); }
	public static function GetTemplateDir($relative=false) {
		$templateDir = PATH_ABS_CORE.'styles/default/';
		switch (self::$usedTemplate) {
			case NULL:
			case TEMPLATE_BLANK:
				$templateDir = PATH_ABS_CORE.'styles/default/';
				break;
			default:
				$templateDir = PATH_ABS_ROOT.self::$usedTemplate.'/';
				break;
		}
		$path = realpath($templateDir);
		if ($relative) $path = self::GetRelativePath($path);
		return $path.'/';
	}
	public static function GetTemplateCSS($template = NULL) {
		if (!$template) $template = utopia::GetTemplateDir(true);
		$cssfiles = array();

		if (!file_exists($template)) $template = utopia::GetAbsolutePath($template);
		if (!file_exists($template)) return $cssfiles;
		
		// if we have a parent, get its css
		$inifile = $template.'/template.ini';
		if (file_exists($inifile)) {
			$inifile = parse_ini_file($inifile);
			if (isset($inifile['parent'])) $cssfiles = self::GetTemplateCSS(PATH_ABS_TEMPLATES.$inifile['parent']);
		}
		
		// add my css
		if (utopia::IsMobile() && file_exists($template.'/mobile.css'))
			$cssfiles[] = $template.'/mobile.css';
		elseif (file_exists($template.'/styles.css'))
			$cssfiles[] = $template.'/styles.css';

		return $cssfiles;
	}
	private static $doneCSS = false;
	public static function OutputTemplate() {
		uEvents::TriggerEvent('BeforeOutputTemplate');
		$template = '';
		if (self::UsingTemplate()) {
			$css = self::GetTemplateCSS();
			foreach ($css as $cssfile) uCSS::LinkFile($cssfile);
				
			// first get list of parents
			$templates = array();
			$templateDir = utopia::GetTemplateDir(true);
			if (!file_exists($templateDir)) $templateDir = utopia::GetAbsolutePath($templateDir);
			$templates[] = $templateDir;
			while (file_exists($templateDir.'/template.ini')) {
				$inifile = parse_ini_file($templateDir.'/template.ini');
				if (!isset($inifile['parent'])) break;
				if (file_exists(PATH_ABS_ROOT.$inifile['parent'])) {
					$templateDir = PATH_ABS_ROOT.$inifile['parent'];
				} else {
					$templateDir = dirname($templateDir).'/'.$inifile['parent'];
				}
				$templates[] = $templateDir;
			}
			
			foreach ($templates as $templateDir) {
				// set templatedir
				self::SetVar('templatedir',self::GetRelativePath($templateDir));
				// read template (mobile?)
				if (utopia::IsMobile() && file_exists($templateDir.'/mobile.php')) {
					$templatePath = $templateDir.'/mobile.php';
				} else {
					$templatePath = $templateDir.'/template.php';
				}
				$template = get_include_contents($templatePath);
				// mergevars
				while (self::MergeVars($template));
				// setvar
				self::SetVar('content',$template);
			}
		}
		if (!$template) $template = '{utopia.content}';
		ob_end_clean();

		while (self::MergeVars($template));

		$contentType = 'text/html';
		foreach (headers_list() as $header) if (stripos($header,'Content-Type') !== FALSE) $contentType = $header;
		if ($contentType === null || stripos($contentType,'html') !== FALSE) {
			$dom = str_get_html($template,true,true,DEFAULT_TARGET_CHARSET,false);
			if ($dom) {
				foreach (self::$globalVariables as $key => $val) {
					if (!preg_match('/^\<(\/?)([a-z]+)\>$/i',$key,$matches)) continue;
					$tag = $matches[2];
					$append = $matches[1] != '';
					foreach ($dom->find($tag) as $ele) {
						if ($append) {
							$ele->innertext = $ele->innertext.$val;
						} else {
							$ele->innertext = $val.$ele->innertext;
						}
					}
				}
				$template = $dom;
			}
			while (self::MergeVars($template));
		}

		// Make all resources secure
		if (self::IsRequestSecure()) {
			$template = str_replace('http://'.self::GetDomainName(),'https://'.self::GetDomainName(),$template);
		}

		do if (self::UsingTemplate() && class_exists('DOMDocument')) {
			$doc = new DOMDocument();
			$doc->formatOutput = true;
			$doc->preserveWhiteSpace = false;
			$doc->validateOnParse = true;

			try {
				if (!$doc->loadHTML('<?xml encoding="UTF-8">'.$template)) break;
			} catch (Exception $e) { }
			
			// no html tag?  break out.
			if (!$doc->getElementsByTagName('html')->length) break;

			// remove multiple xmlns attributes
			$doc->documentElement->removeAttributeNS(NULL,'xmlns');
			
			// assert BODY tag
			if (!$doc->getElementsByTagName('body')->length) {
				$node = $doc->createElement("body");
				$doc->getElementsByTagName('html')->item(0)->appendChild($node);
			}
			
			// assert HEAD tag
			if (!$doc->getElementsByTagName('head')->length) {
				// create head node
				$node = $doc->createElement("head");
				$body = $doc->getElementsByTagName('body')->item(0);
				$newnode = $body->parentNode->insertBefore($node,$body);
			}
			
			// add HEAD children
			$head = $doc->getElementsByTagName('head')->item(0);
			
			$node = $doc->createElement('title');
			$node->appendChild($doc->createTextNode(utopia::GetTitle(true)));
			$head->appendChild($node);
			if (utopia::GetDescription(true)) {
				$node = $doc->createElement('meta'); $node->setAttribute('name','description'); $node->setAttribute('content',utopia::GetDescription(true));
				$head->appendChild($node);
			}
			if (utopia::GetKeywords(true)) {
				$node = $doc->createElement('meta'); $node->setAttribute('name','ketwords'); $node->setAttribute('content',utopia::GetKeywords(true));
				$head->appendChild($node);
			}
			
			$node = $doc->createElement('meta'); $node->setAttribute('name','generator'); $node->setAttribute('content','uCore PHP Framework');
			$head->appendChild($node);
			
			// template is all done, now lets run a post process event
			uEvents::TriggerEvent('ProcessDomDocument',null,array(&$doc));
			
			// move all LINK end of HEAD
			$links = $head->getElementsByTagName('link');
			for ($i = 0; $i < $links->length; $i++) { $head->appendChild($links->item(0)); }
			// move all SCRIPT end of HEAD (after LINK)
			$scripts = $head->getElementsByTagName('script');
			for ($i = 0; $i < $scripts->length; $i++) { $head->appendChild($scripts->item(0)); }
			
			$doc->normalizeDocument();
			if (strpos(strtolower($doc->doctype->publicId),' xhtml '))
				$template = $doc->saveXML();
			else {
				$ctNode = null;
				foreach ($head->getElementsByTagName('meta') as $meta) {
					if ($meta->hasAttribute('http-equiv') && $meta->getAttribute('http-equiv') == 'content-type') { $ctNode = $meta; break; }
				}
				if (!$ctNode) {
					$ctNode = $doc->createElement('meta');
					$ctNode->setAttribute('http-equiv','Content-type'); $ctNode->setAttribute('content','text/html;charset='.CHARSET_ENCODING);
					$head->appendChild($ctNode);
				}
				$head->insertBefore($ctNode,$head->firstChild);
				$template = $doc->saveHTML();
			}
		} while (false);
		
		while (self::MergeVars($template));
		
		echo $template;
	}

	static function IsInsideNoProcess($fullString,$position) {
		$str = strrev(substr($fullString,0,$position));
		$str = strrev(substr($str,0,strpos($str,strrev('<!-- NoProcess -->'))));
		return strpos($str,'<!-- /NoProcess -->') !== FALSE;
	}

	static function MergeVars(&$string) {
		$start = $string;
		foreach (self::$templateParsers as $ident => $arr) {
			if (preg_match_all('/{'.$ident.'}/Ui',$string,$matches,PREG_PATTERN_ORDER)) {
				$searchArr = $matches[0];
				$varsArr = isset($matches[1]) ? $matches[1] : false;
				foreach ($searchArr as $k => $search) {
					if (strpos($search,'{',1) !== FALSE) continue; // if contains another pragma then skip it, pick up post-merged on next pass.
					$searchLen = strlen($search);
					$offset = 0;
					while (($pos = strpos($string, $search, $offset)) !== FALSE) {
						$test = substr($string, 0, $pos); // only test on string up to match.
						// TODO:(done) Dont match inside fields!
						$noprocessPos = strrpos($test, '<!-- NoProcess -->');
						if ($noprocessPos !== FALSE) { // noprocess is found.
							// end noprocess found?
							$test = substr($test,$noprocessPos);
							if (strpos($test,'<!-- /NoProcess -->') === FALSE) {
								// end noprocess not found- so skip this replacement
								$offset = $pos + $searchLen;
								continue;
							}
						}
						//if (self::IsInsideNoProcess($string,$pos)) { $offset = $pos + $searchLen; continue; }

						try {
							$replace = self::RunTemplateParser($ident,$varsArr?$varsArr[$k]:null);
						} catch (Exception $e) { $replace = uErrorHandler::EchoException($e); }
					
						if ($replace === NULL || $replace === FALSE) {
							$offset = $pos + $searchLen;
							continue;
						}
						$replaceLen = strlen($replace);

						// $test either (doesnt contain a noprocess) OR (also contains the end tag)
						$string = substr_replace($string, $replace, $pos, $searchLen); // str_replace($search,$replace,$contents);
						$offset = $pos + $replaceLen;
					}
				}
			}
		}
		return ($string !== $start);
	}

	static $templateParsers = array();
	static function AddTemplateParser($ident,$function,$match='.+',$catchOutput = false) {
		if (is_string($function) && strpos($function,'::') !== FALSE) {
			$function = explode('::',$function);
		}
		$ident = preg_quote($ident);
		if ($match === '.*') $ident .= '(?:\.('.$match.'))?';
		elseif ($match) $ident .= '\.('.$match.')';
		if (isset(self::$templateParsers[$ident])) { throw new Exception("$ident is already defined as a template parser."); }
		self::$templateParsers[$ident] = array($function,$catchOutput);
	}
	static function RunTemplateParser($ident,$data=null) {
		if (!isset(self::$templateParsers[$ident])) return;
		$parser = self::$templateParsers[$ident];

		if (!is_callable($parser[0]) && is_string($parser[0])) {
			return $parser[0];
		}

		if ($parser[1]) ob_start();
		if ($data)
			$replace = call_user_func($parser[0],$data);
		else
			$replace = call_user_func($parser[0]);

		if ($parser[1]) {
			$replace = ob_get_contents();
			ob_end_clean();
		}
		return $replace;
	}
	static function parseVars($id) {
		$replacement = self::GetVar($id.':before').self::GetVar($id).self::GetVar($id.':after');
		return $replacement;
	}
  static function parseGet($id) { return isset($_GET[$id]) ? $_GET[$id] : ''; }
  static function parsePost($id) { return isset($_POST[$id]) ? $_POST[$id] : ''; }
  static function parseRequest($id) { return isset($_REQUEST[$id]) ? $_REQUEST[$id] : ''; }
  static function parseSession($id) { return isset($_SESSION[$id]) ? $_SESSION[$id] : ''; }
  static function parseConst($id) { return defined($id) ? constant($id) : ''; }

	static function PageNotFound() {
		header("HTTP/1.0 404 Not Found",true,404);
		utopia::SetTitle('404 Not Found');
		echo '<h1>404 Not Found</h1>';
		echo '<p>The page you requested could not be found. Return to the <a href="{home_url}">homepage</a>.</p>';
		self::AddMetaTag('robots','noindex');
		die();
	}

	static function SecureRedirect() {
		if (self::IsRequestSecure()) return;

		$uri = 'https://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		if (parse_url($_SERVER['REQUEST_URI'],PHP_URL_SCHEME) != 'https') {
			header('Location: '.$uri,true,301);
			die();
		}
	}
	static function IsRequestSecure() {
		return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off');
	}

	/*  MISC  */
	static function output_buffer($text) {
		utopia::AppendVar('content',$text);
		return '';
	}

	static function GetDomainName() {
		return $_SERVER['HTTP_HOST'];
	}

	static function GetRelativePath($path) {
		$path = realpath($path);
		$path = str_replace(PATH_ABS_ROOT,PATH_REL_ROOT,$path);
		$path = str_replace(DIRECTORY_SEPARATOR,'/',$path);
		return $path;
	}
	static function GetAbsolutePath($path) {
		if (strpos($path,PATH_REL_ROOT) === 0) $path = substr_replace($path,PATH_ABS_ROOT,0,strlen(PATH_REL_ROOT));
		$path = realpath($path);
		if (!file_exists($path)) return FALSE;
		return $path;
	}

	static function AjaxUpdateElement($eleName,$html) {
		if (is_object($html)) return;
    	$enc = base64_encode($html);
    	AjaxEcho('$(".'.$eleName.'").html(Base64.decode("'.$enc.'"))');
	}

	static function GetMaxUpload($post=true,$upload_max=true) {
		if (!function_exists('let_to_num')){
			function let_to_num($v){ //This function transforms the php.ini notation for numbers (like '2M') to an integer (2*1024*1024 in this case)
				$l = substr($v, -1);
				$ret = substr($v, 0, -1);
				switch(strtoupper($l)){
				case 'P':
					$ret *= 1024;
				case 'T':
					$ret *= 1024;
				case 'G':
					$ret *= 1024;
				case 'M':
					$ret *= 1024;
				case 'K':
					$ret *= 1024;
					break;
				}
				return $ret;
			}
		}
		if ($post) $post = let_to_num(ini_get('post_max_size')); else $post = NULL;
		if ($upload_max) $upload_max = let_to_num(ini_get('upload_max_filesize')); else $upload_max = NULL;

		return min($post, $upload_max);
	}

	static function ReadableBytes($size) {
		$arr = array('kb','mb','gb','tb','pb');
		$i=0;
		while ($i < count($arr) && $size > 1000) {
			$size = $size/1024;
			$i++;
		}
		return $size.$arr[$i-1];
	}

	static function Cache_Check($etag, $contentType,$filename='',$modified=0,$age=2592000,$disposition='inline') {
		header('Content-Type: '.$contentType,true);
		$etag .= GZIP_ENABLED ? '-gzip' : '';
		$etag = '"'.$etag.'"';
		header("ETag: $etag",true);
		header("Expires: ".gmdate("D, d M Y H:i:s",time()+$age) . " GMT",true);
		header("Cache-Control: public, max-age=$age",true);		$fn = empty($filename) ? '' : "; filename=\"$filename\"";
		header("Content-Disposition: ".$disposition.$fn,true);

		if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
			header('HTTP/1.1 304 Not Modified', true, 304);
			exit;
		}

		if ($modified) {
			$lm = gmdate('r',$modified);
			header("Last-Modified: ".$lm,true);
			if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $lm) {
				header('HTTP/1.1 304 Not Modified', true, 304);
				exit;
			}
		}
	}

	static function Cache_Output($data,$etag,$contentType,$filename='',$modified=0,$age=2592000,$disposition='inline') {
		self::Cache_Check($etag,$contentType,$filename,$modified,$age,$disposition);
		echo $data;
	}

	static function Breakout($text) {
		self::CancelTemplate();
		die(print_r($text,true));
	}

	// helpers
	static function IsMobile() {
		if (!isset($_SERVER['HTTP_USER_AGENT'])) return false;
		$useragent = $_SERVER['HTTP_USER_AGENT'];
		return (preg_match('/android.+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i',substr($useragent,0,4)));
	}

	static function constrainImage($src,$maxW=NULL,$maxH=NULL,$enlarge=false) {
		if ($maxW === NULL && $maxH === NULL) return $src;

		if (imageistruecolor($src)) {
			imageAlphaBlending($src, true);
			imageSaveAlpha($src, true);
		}
		$srcW = imagesx($src);
		$srcH = imagesy($src);

                $width	= $maxW && ($enlarge || $maxW <= $srcW) ? $maxW : $srcW;
                $height	= $maxH && ($enlarge || $maxH <= $srcW) ? $maxH : $srcH;

		$ratio_orig = $srcW/$srcH;
		if ($width/$height > $ratio_orig) {
			$width = $height*$ratio_orig;
		} else {
			$height = $width/$ratio_orig;
		}
		$maxW = $maxW ? $maxW : $width;
		$maxH = $maxH ? $maxH : $height;

		$img = imagecreatetruecolor($maxW,$maxH);
		$trans_colour = imagecolorallocatealpha($img, 0, 0, 0, 127);
		imagefill($img, 0, 0, $trans_colour);

		$offsetX = ($maxW - $width) /2;
		$offsetY = ($maxH - $height) /2;

		//fastimagecopyresampled($img,$src,$offsetX,$offsetY,0,0,$width,$height,$srcW,$srcH,1);
		imagecopyresampled($img,$src,$offsetX,$offsetY,0,0,$width,$height,$srcW,$srcH);
		imagealphablending($img, true);
		imagesavealpha($img, true);

		return $img;
	}
	
	// converters
	static function convDate($originalValue,$pkVal,$processedVal) {
		if (!$originalValue) return '';
		$t = strtotime( $originalValue );
		return strftime(FORMAT_DATE,$t);
	}
	static function convTime($originalValue,$pkVal,$processedVal) {
		if (!$originalValue) return '';
		$t = strtotime( $originalValue );
		return strftime(FORMAT_TIME,$t);
	}
	static function convDateTime($originalValue,$pkVal,$processedVal) {
		if (!$originalValue) return '';
		$t = strtotime( $originalValue );
		return strftime(FORMAT_DATETIME,$t);
	}
	static function convCurrency($originalValue,$pkVal,$processedVal,$rec,$fieldName) {
		$locale = DEFAULT_LOCALE;
		if ($rec && $rec[$fieldName.'_locale']) $locale=$rec[$fieldName.'_locale'];
		return self::money_format($originalValue,$locale);
	}
	static function money_format($originalValue,$locale=DEFAULT_LOCALE) {
		if (!$locale) $locale = DEFAULT_LOCALE;
		$locales = uLocale::ListLocale(NULL);
		$c = null;
		if (isset($locales[$locale])) $c = $locales[$locale];
		else {
			foreach ($locales as $l) {
				foreach ($l as $v) if ($v === $locale) {
					$c = $l; break 2;
				}
			}
		}
		if (!$c) return $originalValue;
		$dp = $originalValue - floor($originalValue) > 0 ? 2 : 0;
		$value = number_format($originalValue,$dp,$c['mon_decimal_point'],$c['mon_thousands_sep']);
		
		if ($originalValue >= 0) {
			if ($c['p_cs_precedes']) {
				if ($c['p_sep_by_space']) $value = ' '.$value;
				$value = $c['currency_symbol'].$value;
			} else {
				if ($c['p_sep_by_space']) $value .= ' ';
				$value .= $c['currency_symbol'];
			}
		} else {
			if ($c['n_cs_precedes']) {
				if ($c['n_sep_by_space']) $value = ' '.$value;
				$value = $c['currency_symbol'].$value;
			} else {
				if ($c['n_sep_by_space']) $value .= ' ';
				$value .= $c['currency_symbol'];
			}
		}
		return $value;
	}

	static function stripslashes_deep($value) {
		$value = is_array($value) ?
			array_map('utopia::stripslashes_deep', $value) :
			stripslashes($value);

		return $value;
	}
	static function compareVersions($ver1,$ver2) {
		if ($ver1 == $ver2) return 0;

		//major.minor.maintenance.build
		preg_match_all('/([0-9]+)\.?([0-9]+)?\.?([0-9]+)?\.?([0-9]+)?/',$ver1,$matches1,PREG_SET_ORDER); $matches1 = $matches1[0]; array_shift($matches1);
		preg_match_all('/([0-9]+)\.?([0-9]+)?\.?([0-9]+)?\.?([0-9]+)?/',$ver2,$matches2,PREG_SET_ORDER); $matches2 = $matches2[0]; array_shift($matches2);

		if ($matches1 == $matches2) return 0;
		while (count($matches1) < 4) $matches1[] = 0;
		while (count($matches2) < 4) $matches2[] = 0;
		foreach ($matches1 as $k => $v) {
			if ($v == $matches2[$k]) continue;
			if ($v < $matches2[$k]) return -1;
			if ($v > $matches2[$k]) return 1;
		}
		return 0;
	}
	static function jsonTryDecode($value, $assoc = true) {
		if (!is_string($value) || !strlen($value)) return $value;
		if (!preg_match('/(^\[.*\]$)|(^\{.*\}$)/',$value)) return $value;
		$originalValue = $value;
		$value = json_decode($value,$assoc);
		if ($value === NULL) $value = $originalValue;
		return $value;
	}
	static function GetMimeType($path) {
		$cType = NULL;
		if (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$cType = finfo_file($finfo,$path);
		} elseif (function_exists('mime_content_type')) {
			$cType = mime_content_type($path);
		} else {
			ob_start();system("file -bi '$path'",$cType);ob_end_clean();
		}
		return $cType;
	}
	
	static function OutputPagination($pages,$pageKey = 'page') {
		//$pages $pageKey
		if ($pages <= 1) return;
		$parsed = parse_url($_SERVER['REQUEST_URI']);
		$args = isset($parsed['query']) ? $parsed['query'] : '';
		if (is_string($args)) parse_str($args,$args);

		$page = isset($args[$pageKey]) ? $args[$pageKey] : 0;
		echo '<ul class="pagination">';
		if ($page > 0) {
			$args[$pageKey] = $page -1;
			$rel = array('prev');
			if (!$args[$pageKey]) unset($args[$pageKey]);
			if ($page-1 == 0) $rel[] = 'first';
			echo '<li><a rel="'.implode(' ',$rel).'" class="btn" href="'.$parsed['path'].($args ? '?'.http_build_query($args) :'').'">&lt; Previous</a></li>';
		}
		for ($i = 0; $i<$pages; $i++) {
			$args[$pageKey] = $i;
			$rel = array();
			if (!$args[$pageKey]) unset($args[$pageKey]);
			if ($i == $page-1) $rel[] = 'prev';
			if ($i == $page+1) $rel[] = 'next';
			if ($i == 0) $rel[] = 'first';
			if ($i == $pages-1) $rel[] = 'last';
			echo '<li><a rel="'.implode(' ',$rel).'" class="btn" href="'.$parsed['path'].($args ? '?'.http_build_query($args) :'').'">'.($i+1).'</a></li>';
		}
		if ($page < $pages-1) {
			$args[$pageKey] = $page +1;
			$rel = array('next');
			if ($page+1 == $pages-1) $rel[] = 'last';
			echo '<li><a rel="'.implode(' ',$rel).'" class="btn" href="'.$parsed['path'].($args ? '?'.http_build_query($args) :'').'">Next &gt;</a></li>';
		}
		echo '</ul>';
	}
	
	static function SanitiseValue(&$value,$type,$default=null,$isRegex=false) {
		$type = strtolower($type);
		if ($type === 'null') $type = 'NULL';
		if ($type === 'float') $type = 'double';
		if (($isRegex && preg_match($type,$value) > 0) || (gettype($value) !== $type)) {
			if ($default !== null) $value = $default;
			return false;
		}
		return true;
	}
}
