<?php
define('TEMPLATE_BLANK','__blank__');
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
utopia::AddTemplateParser('home_url_abs','utopia::GetSiteURL','');
utopia::AddTemplateParser('inline','inline=0','');

utopia::SetVar('tp',PATH_REL_CORE.'images/tp.gif');

utopia::AddTemplateParser('setrequest','utopia::setRequest');

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
					$obj =& utopia::GetInstance($class['module_name']);
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

	static function GetRewriteURL() {
		$REQUESTED_URL = array_key_exists('HTTP_X_REWRITE_URL',$_SERVER) ? $_SERVER['HTTP_X_REWRITE_URL'] : $_SERVER['REQUEST_URI'];
		$REQUESTED_URL = preg_replace('/\?.*/i','',$REQUESTED_URL);

		$REQUESTED_URL = preg_replace('/^'.preg_quote(PATH_REL_ROOT,'/').'/','',$REQUESTED_URL);
		$REQUESTED_URL = preg_replace('/^u\//','',$REQUESTED_URL);

		$path = urldecode($REQUESTED_URL);
		
		return $path;
	}

	static function SetCurrentModule($module) {
		if (!self::ModuleExists($module)) return;
		
		$cm = utopia::GetCurrentModule();
		$o =& utopia::GetInstance($cm);
		if (flag_is_set($o->GetOptions(),PERSISTENT)) return;
		
		utopia::SetVar('current_module',$module);
	}
	private static $cmCache = array();
	static function GetCurrentModule() {
		// cm variable
		if (utopia::VarExists('current_module')) return utopia::GetVar('current_module');

		// GET uuid
		if (isset($_GET['uuid'])) {
			$m = utopia::UUIDExists($_GET['uuid']);
			if ($m) return $m['module_name'];
		}

		// rewritten url?   /u/MOD/
		$u = self::GetRewriteURL();
		if (!isset(self::$cmCache[$u])) foreach (self::GetModules() as $m) {
			if (preg_match('/^'.preg_quote($m['uuid'].'/','/').'/i',$u.'/')) {
				self::$cmCache[$u] = $m['module_name'];
				return $m['module_name'];
			}
			self::$cmCache[$u] = false;
		}
		if (self::$cmCache[$u]) return self::$cmCache[$u];

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
		$obj =& utopia::GetInstance($module);
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
		
		if (isset($attributes['class'])) $attributes['class'] .= ' inputtype inputtype-'.$inputType;
		else $attributes['class'] = 'inputtype inputtype-'.$inputType;

		$defaultValue = utopia::jsonTryDecode($defaultValue);
		
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
				$attributes['class'] = str_replace('inputtype ','',$attributes['class']);
				$attr = BuildAttrString($attributes);
				$out .= '<a '.$attr.' href="javascript:void(0)">'.$defaultValue.'</a>';
				break;
			case itCHECKBOX:
				if (is_array($possibleValues)) {
					$at = array();
					if (isset($attributes['styles'])) $at['styles'] = $attributes['styles'];
					$at = BuildAttrString($at);
		
					if (is_array($possibleValues) && !preg_match('/^usql\-/',$fieldName)) {
						$attributes['name'] = $attributes['name'].'[]';
						$attr = BuildAttrString($attributes);
					}
					$out .= '<span'.$at.' class="inputtype inputtype-checkboxlist">';
					foreach ($possibleValues as $key => $val) {
						$checked = ((string)$key === $defaultValue || (is_array($defaultValue) && in_array($key,$defaultValue))) ? ' checked="checked"' : '';
						$val = htmlentities($val,ENT_COMPAT,CHARSET_ENCODING);
						$out .= "<label><input$attr type=\"checkbox\"$checked value=\"$key\"/>$val</label>";
					}
					$out .= '</span>';
				} else {
					$checked = ($defaultValue == 1) ? ' checked="checked"': '';
					$out .= "<input$attr type=\"checkbox\"$checked value=\"1\"/>";
				}
				break;
			case itOPTION:
				if (!is_array($possibleValues)) { ErrorLog('Option field specified but no possible values found'); return ''; }
				$count = 0;
				$defaultExists = false;
				foreach ($possibleValues as $key => $val) {
					$count++; $attributes['id'] = "$fieldName-$count"; $attr = BuildAttrString($attributes);
					$checked = ($key == $defaultValue || (is_array($defaultValue) && in_array($key,$defaultValue))) ? ' checked="checked"' : '';
					if ($checked != '') $defaultExists = true;
					$out .= "<input type=\"radio\" $attr value=\"$key\"$checked/>$val<br/>";
				}
				if (!$defaultExists && ($defaultValue != ''))
				$out .= "<input type=\"radio\" $attr value=\"$val\" checked=\"true\">$defaultValue";
				break;
			case itPASSWORD:
			case itPLAINPASSWORD:
				$out .= "<input type=\"password\" $attr value=\"\"/>";
				break;
			case itTEXT:
				$defaultValue = str_replace('"','&quot;',$defaultValue);
				$out .= "<input type=\"text\" $attr value=\"$defaultValue\"/>";
				break;
			case itTEXTAREA:
				//sanitise value.
				if (!utopia::SanitiseValue($defaultValue,'string') && !utopia::SanitiseValue($defaultValue,'NULL')) $defaultValue = 'Value has been sanitised: '.var_export($defaultValue,true);
				if (is_string($defaultValue)) $defaultValue = mb_convert_encoding($defaultValue, CHARSET_ENCODING, 'HTML-ENTITIES');
				$defaultValue = htmlentities($defaultValue,ENT_COMPAT,CHARSET_ENCODING);
				$out .= "<textarea $attr>$defaultValue</textarea>";
				break;
			case itCOMBO:
				if (empty($possibleValues)) $possibleValues = array();
				$defaultExists = false;
				$blankVal = isset($possibleValues['']) ? $possibleValues[''] : FALSE;
				if ($blankVal === FALSE) {
					if (isset($attributes['placeholder']) && $attributes['placeholder']) $blankVal = $attributes['placeholder'];
					else $blankVal = '&nbsp;';
				}
				$out .= "<select $attr><option value=\"\">$blankVal</option>";
				if (is_array($possibleValues)) foreach ($possibleValues as $key => $val) {
					if ((string)$key === '') continue;
					$selected = '';
					if ($defaultValue !== '' && ((is_array($defaultValue) && in_array($key,$defaultValue)) || ((string)$key === (string)$defaultValue))) {
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
	static function Tab_Add($tabTitle,$tabContent,$tabID,$tabGroup=NULL,$isURL=false,$order=NULL) {
		if ($order === NULL) $order = count(self::$tabGroups[$tabGroup]);
		if (!$tabGroup) $tabGroup = self::Tab_InitGroup();
		$tabID .= '-'.UrlReadable($tabTitle);
		if (isset(self::$tabGroups[$tabGroup]['tab'.$tabID])) { ErrorLog("TabID ($tabID) already exists in Group ($tabGroup)"); return; }
		self::$tabGroups[$tabGroup][$tabID] = array('id'=>$tabID,'title'=>$tabTitle,'content'=>$tabContent,'isURL'=>$isURL,'order'=>$order);
	}
	static function Tab_GetCount($tabGroup=NULL) {
		if (!$tabGroup) $tabGroup = self::Tab_InitGroup();
		return count(self::$tabGroups[$tabGroup]);
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
		if (count($tabGroupArray) <= 1) { $tabInfo = reset($tabGroupArray); echo $tabInfo['content']; return; }

		array_sort_subkey($tabGroupArray,'order');

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
		if ($includeDefault) $nTemplates[''] = 'Default Template';

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

		if ($template != TEMPLATE_BLANK && !file_exists(PATH_ABS_ROOT.$template.'/template.php')) {
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
				return false;
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
		
		$namedcss = $template.'/styles.css';
		$templatefile = $template.'/template.php';
		
		if (file_exists($namedcss)) $cssfiles[] = $namedcss;
		
		// parse template file for additional styles
		if (class_exists('DOMDocument')) {
			$doc = new DOMDocument();
			try {
				$doc->loadHTML(file_get_contents($templatefile));
			} catch (Exception $e) { }
			foreach ($doc->getElementsByTagName('link') as $link) {
				if ($link->getAttribute('rel') == 'stylesheet') {
					$v = $link->getAttribute('href');
					self::MergeVars($v);
					$v = preg_replace('/^'.preg_quote(PATH_REL_ROOT,'/').'/',PATH_ABS_ROOT,$v);
					$cssfiles[] = $v;
				}
			}
		}

		return $cssfiles;
	}
	private static $doneCSS = false;
	public static function OutputTemplate() {
		uEvents::TriggerEvent('BeforeOutputTemplate');
		if (!self::UsingTemplate()) {
			ob_end_clean();
			echo utopia::GetVar('content');
			return;
		}
		if (isset($_GET['inline']) && !is_numeric($_GET['inline'])) $_GET['inline'] = 0;
		if (self::UsingTemplate(TEMPLATE_BLANK) || (isset($_GET['inline']) && $_GET['inline'] == 0)) {
			$template = utopia::GetVar('content');
		} else {
			$tCount = -1; // do all by default
			if (isset($_GET['inline'])) $tCount = $_GET['inline']-1;
			$template = '';
			$css = self::GetTemplateCSS();
			foreach ($css as $cssfile) uCSS::LinkFile($cssfile);
				
			// first get list of parents
			$templates = array();
			$templateDir = utopia::GetTemplateDir(true);
			if (!file_exists($templateDir)) $templateDir = utopia::GetAbsolutePath($templateDir);
			if (file_exists($templateDir)) $templates[] = $templateDir;
			while ($tCount-- && file_exists($templateDir.'/template.ini')) {
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
				$templatePath = $templateDir.'/template.php';
				$template = get_include_contents($templatePath);
				// mergevars
				while (self::MergeVars($template));
				// setvar
				self::SetVar('content',$template);
			}
			if (!$template) $template = '{utopia.content}';
		}
		ob_end_clean();

		while (self::MergeVars($template));

		$template = str_replace('<head>', '<head>' . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>', $template);
		
		// Make all resources secure
		if (self::IsRequestSecure()) {
			$template = str_replace('http://'.self::GetDomainName(),'https://'.self::GetDomainName(),$template);
		}
		do if (self::UsingTemplate() && class_exists('DOMDocument')) {
			libxml_use_internal_errors(true);
			$doc = new DOMDocument();
			$doc->formatOutput = true;
			$doc->preserveWhiteSpace = false;
			$doc->validateOnParse = true;

			if (!$doc->loadHTML('<?xml encoding="UTF-8">'.utf8_decode($template))) break;
			$isSnip = (stripos($template,'<html') === false);
			$doc->encoding = 'UTF-8';
			
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
				$node = $doc->createElement('meta'); $node->setAttribute('name','keywords'); $node->setAttribute('content',utopia::GetKeywords(true));
				$head->appendChild($node);
			}
			
			$node = $doc->createElement('meta'); $node->setAttribute('name','generator'); $node->setAttribute('content','uCore PHP Framework');
			$head->appendChild($node);
			
			// template is all done, now lets run a post process event
			try {
				uEvents::TriggerEvent('ProcessDomDocument',null,array(&$doc));
			} catch (Exception $e) { uErrorHandler::EchoException($e); }
			
			// move all LINK end of HEAD
			$links = $head->getElementsByTagName('link');
			for ($i = 0; $i < $links->length; $i++) { $head->appendChild($links->item(0)); }
			// move all SCRIPT end of HEAD (after LINK)
			$scripts = $head->getElementsByTagName('script');
			for ($i = 0; $i < $scripts->length; $i++) { $head->appendChild($scripts->item(0)); }
			// move all STYLE end of HEAD
			$styles = $doc->getElementsByTagName('style');
			for ($i = 0; $i < $styles->length; $i++) { $head->appendChild($styles->item(0)); }
			
			$ctNode = null;
			foreach ($head->getElementsByTagName('meta') as $meta) {
				if ($meta->hasAttribute('http-equiv') && strtolower($meta->getAttribute('http-equiv')) == 'content-type') { $ctNode = $meta; break; }
			}
			if (!$ctNode) {
				$ctNode = $doc->createElement('meta');
				$head->appendChild($ctNode);
			}
			$ctNode->setAttribute('http-equiv','content-type'); $ctNode->setAttribute('content','text/html;charset='.CHARSET_ENCODING);
			if ($ctNode !== $head->firstChild) $head->insertBefore($ctNode,$head->firstChild);
			
			$doc->normalizeDocument();
			if (strpos(strtolower($doc->doctype->publicId),' xhtml ')) {
				$template = $doc->saveXML();
			} else {
				$template = $doc->saveHTML();
			}
			$template = preg_replace('/<\?xml encoding="UTF-8"\??>\n?/i','',$template);
			if ($isSnip) {
				$template = preg_replace('/.*<body[^>]*>\s*/ims', '',$template); // remove everything up to and including the body open tag
				$template = preg_replace('/\s*<\/body>.*/ims', '',$template); // remove everything after and including the body close tag
			}
		} while (false);
		
		while (self::MergeVars($template));
		
		if (isset($_GET['callback'])) {
			$output = json_encode(array(
				'title'	=> self::GetTitle(true),
				'content'	=> $template,
			));
			header('Content-Type: application/javascript');
			echo $_GET['callback'].'('.$output.')';
			return;
		}
		
		echo $template;
	}

	static function IsInsideNoProcess($fullString,$position) {
		$str = strrev(substr($fullString,0,$position));
		$str = strrev(substr($str,0,strpos($str,strrev('<!-- NoProcess -->'))));
		return strpos($str,'<!-- /NoProcess -->') !== FALSE;
	}

	static function MergeVars(&$string) {
		$start = $string;
		
		$pr = rtrim(PATH_REL_ROOT,'/');
		$string = preg_replace('/'.preg_quote($pr.$pr,'/').'/',$pr,$string);
		
		if (preg_match_all('/(%7B|{)(.+)(}|%7D)/Ui',$string,$matches,PREG_PATTERN_ORDER)) { // loop through all parser tags {.+}
			$searchArr = $matches[0];
			foreach ($searchArr as $search) {
				// if contains another pragma then skip it, pick up post-merged on next pass.
				while (preg_match('/(%7B|{)(.+)(}|%7D)/Ui',$search,$res,0,1)) $search = $res[0];

				foreach (self::$templateParsers as $ident => $arr) {
					if (!preg_match('/(%7B|{)'.$ident.'(}|%7D)/Ui',$search,$match)) continue; // doesnt match this templateparser
					$data = isset($match[2]) ? $match[2] : false;
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
							$replace = self::RunTemplateParser($ident,$data?$data:null);
						} catch (Exception $e) { $replace = uErrorHandler::EchoException($e); }
					
						if ($replace === NULL || $replace === FALSE) {
							$offset = $pos + $searchLen;
							continue;
						}
						$replaceLen = strlen($replace);
						// $test either (doesnt contain a noprocess) OR (also contains the end tag)
						$string = substr_replace($string, $replace, $pos, $searchLen); // str_replace($search,$replace,$contents);
						$offset = $pos + $replaceLen;
						return ($string !== $start);
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
		else $ident .= '()';
		if (isset(self::$templateParsers[$ident])) { throw new Exception("$ident is already defined as a template parser."); }
		self::$templateParsers[$ident] = array($function,$catchOutput);
	}
	static function RunTemplateParser($ident,$data=null) {
		if (!isset(self::$templateParsers[$ident])) return;
		$parser = self::$templateParsers[$ident];

		if (!is_callable($parser[0]) && is_string($parser[0])) {
			return $parser[0];
		}

		$data = html_entity_decode($data);
		$args = array();
		if ($data !== '') {
			$pairs = explode('&',$data);
			foreach ($pairs as $pair) {
				if (strpos($pair,'=') === FALSE) {
					$args[] = $pair; continue;
				}
				list($key,$val) = explode('=',$pair);
				$args[$key] = $val;
			}
		}
		if (is_assoc($args)) $args = array($args);
		
		if ($parser[1]) ob_start();
		$replace = call_user_func_array($parser[0],$args);

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

	static function SetRequest($query) {
		foreach ($query as $k=>$v) $_REQUEST[$k] = $v;
		return '';
	}
	
	static function PageNotFound($title = '404 Not Found',$content = 'The page you requested could not be found. Return to the <a href="{home_url}">homepage</a>.') {
		header("HTTP/1.0 404 Not Found",true,404);
		utopia::SetTitle($title);
		if ($title) echo "<h1>$title</h1>";
		if ($content) echo "<p>$content</p>";
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
	static function EvalString($string) {
		if (!$string) return $string;
		
		$string = preg_replace('/\<\?\s/i','<?php ',$string);

		ob_start();
			eval('?>'.$string.'<?php ');
			$string = ob_get_contents();
		ob_end_clean();
		
		return $string;
	}
	
	static function output_buffer($text) {
		utopia::AppendVar('content',$text);
		return '';
	}

	static function GetDomainName() {
		return $_SERVER['HTTP_HOST'];
	}
	static $siteurl = null;
	static function GetSiteURL() {
		if (!self::$siteurl) self::$siteurl = rtrim(modOpts::GetOption('site_url'),'/');
		return self::$siteurl;
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

	private static $ajaxFunctions = array();
	static function RegisterAjax($ajaxIdent, $callback) {
		if (array_key_exists($ajaxIdent,self::$ajaxFunctions)) {
			//ErrorLog(get_class($this)." cannot register ajax identifier '$ajaxIdent' because it is already registered.");
			return FALSE;
		}

		self::$ajaxFunctions[$ajaxIdent]['callback'] = $callback;
		return true;
	}
	static function RunAjax($ajaxIdent) {
		if (!array_key_exists($ajaxIdent,self::$ajaxFunctions)) die("Cannot perform ajax request, '$ajaxIdent' has not been registered.");

		$callback	= self::$ajaxFunctions[$ajaxIdent]['callback'];

		// validate
		if (!is_callable($callback)) die("Callback function for ajax method '$ajaxIdent' does not exist.");

		call_user_func($callback);

		utopia::Finish(); // commented why ?
		die();
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
		$hasTime = ($t % 86400) !== 0;
		if ($hasTime) return strftime(FORMAT_DATETIME,$t);
		return strftime(FORMAT_DATE,$t);
	}
	static function convCurrency($originalValue,$pkVal,$processedVal,$rec,$fieldName) {
		$locale = DEFAULT_LOCALE;
		if ($rec && $rec[$fieldName.'_locale']) $locale=$rec[$fieldName.'_locale'];
		return self::money_format($originalValue,$locale);
	}
	static function money_format($originalValue,$locale=DEFAULT_LOCALE) {
		if (!is_numeric($originalValue)) return $originalValue;
		if (!$locale) $locale = DEFAULT_LOCALE;
		$locales = uLocale::ListLocale(NULL,'%C',true);
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
		$value = mb_convert_encoding($value, 'HTML-ENTITIES', CHARSET_ENCODING);
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
		if (function_exists('finfo_open') && defined('FILEINFO_MIME_TYPE')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$cType = finfo_file($finfo,$path);
			finfo_close($finfo);
		} elseif (function_exists('mime_content_type')) {
			$cType = mime_content_type($path);
		} else {
			$cType = exec("file -bi '$path'");
		}
		return $cType;
	}
	
	static function OutputPagination($pages,$pageKey = 'page',$spread = 2) {
		//$pages $pageKey
		if ($pages <= 1) return 1;
		$parsed = parse_url($_SERVER['REQUEST_URI']);
		$args = isset($parsed['query']) ? $parsed['query'] : '';
		if (is_string($args)) parse_str($args,$args);

		$page = isset($args[$pageKey]) ? $args[$pageKey] : 0;
		echo '<ul class="pagination">';
		if ($page > 0) { // previous
			$args[$pageKey] = $page -1;
			$rel = array('prev');
			if (!$args[$pageKey]) unset($args[$pageKey]);
			if ($page-1 == 0) $rel[] = 'first';
			echo '<li class="previous"><a rel="'.implode(' ',$rel).'" class="btn uPaginationLink" href="'.$parsed['path'].($args ? '?'.http_build_query($args) :'').'">Previous</a></li>';
		}
		
		$prespace = false; $postspace=false;
		for ($i = 0; $i<$pages; $i++) {
			$args[$pageKey] = $i;
			$rel = array();
			if (!$args[$pageKey]) unset($args[$pageKey]);
			if ($i < $page-$spread && $i != 0) { if (!$prespace) echo '<li>...</li>'; $prespace = true; continue; }
			if ($i > $page+$spread && $i != ($pages-1)) { if (!$postspace) echo '<li>...</li>'; $postspace = true; continue; }
			if ($i == $page-1) $rel[] = 'prev';
			if ($i == $page+1) $rel[] = 'next';
			if ($i == 0) $rel[] = 'first';
			if ($i == $pages-1) $rel[] = 'last';
			echo '<li><a rel="'.implode(' ',$rel).'" class="btn uPaginationLink" href="'.$parsed['path'].($args ? '?'.http_build_query($args) :'').'">'.($i+1).'</a></li>';
		}
	
		if ($page < $pages-1) { // next
			$args[$pageKey] = $page +1;
			$rel = array('next');
			if ($page+1 == $pages-1) $rel[] = 'last';
			echo '<li class="next"><a rel="'.implode(' ',$rel).'" class="btn uPaginationLink" href="'.$parsed['path'].($args ? '?'.http_build_query($args) :'').'">Next</a></li>';
		}
		echo '</ul>';
		return $page+1;
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
	
	static function IsAjaxRequest() {
		if (array_key_exists('__ajax',$_REQUEST)) return true;
		foreach (headers_list() as $h) {
			if (preg_match('/^X-Requested-With:\s*XMLHttpRequest/i',$h)) return true;
		}
		return false;
	}

	static function GetGlobalSearch($val,&$args) {
		$all = array(array());
		$cAll = count($all);

		// match phrases
		preg_match_all('/(".+")|([\w\+\']+)/',$val,$matches);
		foreach ($matches[0] as $v) {
			$v = trim($v,'"');
			switch (strtolower($v)) {
				case 'or':	$all[] = array(); $cAll = count($all);
				case 'and':	continue 2;
			}
			$args[] = $v;
			$all[$cAll-1][] = '{__global__} LIKE CONCAT(\'%\',?,\'%\')';
		}
		
		$a = array();
		foreach ($all as $or) {
			$a[] = implode(' AND ',$or);
		}
		
		return implode(' OR ',$a);
	}
}
