<?php
define('TEMPLATE_BLANK','__blank__');
define('TEMPLATE_ADMIN','__admin__');
define('TEMPLATE_DEFAULT','__default__');

FlexDB::AddTemplateParser('utopia','FlexDB::parseVars');
FlexDB::AddTemplateParser('list','FlexDB::DrawList');
FlexDB::AddTemplateParser('tab','FlexDB::Tab_GetOutput');

FlexDB::SetVar('tp',PATH_REL_CORE.'images/tp.gif');

class FlexDB {
	private static $children = array();
	static function AddChild($parent, $child, $info) {
		if (array_key_exists($parent,self::$children) && array_key_exists($child,self::$children[$parent])) {
			foreach (self::$children[$parent][$child] as $compare) {
				if ($info == $compare) return;
			}
		}
		self::$children[$parent][$child][] = $info;
	}
	static function GetChildren($parent) {
		$specific = array();
		$currentModule = array();
		$catchAll = array();
		if (array_key_exists($parent,self::$children)) $specific = self::$children[$parent];
		if ($parent == GetCurrentModule() && array_key_exists('/',self::$children)) $currentModule = self::$children['/'];
		if (array_key_exists('*',self::$children)) $catchAll = self::$children['*'];

		$arr = array_merge_recursive($specific,$currentModule,$catchAll);
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

//		if (!$content && $fallback)
//			$content = CallModuleFunc(GetCurrentModule(),'GetTitle');
//		else
//			$content = '&lt;&lt; No Title &gt;&gt;';


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
		FlexDB::AppendVar('<head>','<meta name="'.$name.'" content="'.$content.'" />');
	}

	static $initCSShead = false;
	static function AddCSSFile($path) {
		if (!self::$initCSShead) FlexDB::PrependVar('</head>',"{UTOPIA.cssHead}\n");
		self::$initCSShead = true;

		FlexDB::AppendVar('cssHead',"<link type=\"text/css\" rel=\"stylesheet\" href=\"$path\" />\n");
	}
	static $initJShead = false;
	static function AddJSFile($path,$start=false) {
		if (!self::$initJShead) FlexDB::AppendVar('</head>',"{UTOPIA.jsHead}\n");
		self::$initJShead = true;

		if ($start)
			FlexDB::PrependVar('jsHead',"<script type=\"text/javascript\" src=\"$path\"></script>\n");
		else
			FlexDB::AppendVar('jsHead',"<script type=\"text/javascript\" src=\"$path\"></script>\n");
	}

	private static $allmodules = NULL;
	private static $activemodules = NULL;
	static function GetModules($activeonly=false,$refresh=false) {
		if (self::$allmodules === NULL || $refresh) {

			$result = sql_query('SELECT * FROM internal_modules');
			$rows = GetRows($result);

			array_sort_subkey($rows,'sort_order');

			self::$allmodules = $rows;
		}

		if ($activeonly && self::$allmodules) {
			if (self::$activemodules === NULL || $refresh) {
				$rows = self::$allmodules;

				foreach ($rows as $key => $row) {
					if (flag_is_set(CallModuleFunc($row['module_name'],'GetOptions'),ALWAYS_ACTIVE)) continue;

					if (!$row['module_active']) unset($rows[$key]);
				}
				self::$activemodules = $rows;
			}
			return self::$activemodules;
		}

		return self::$allmodules;
	}

	static function ModuleExists($module, $activeonly = false) {
		$modules = self::GetModules($activeonly);
		foreach ($modules as $m) {
			if ($module == $m['module_name']) return $m;
		}
		return false;
	}
	static function UUIDExists($uuid, $activeonly = false) {
		$modules = self::GetModules($activeonly);
		foreach ($modules as $m) {
			if ($uuid == $m['uuid']) return $m;
		}
		return false;
	}

	static $instances = array();
	static function &GetInstance($class,$defaultInstance = true) {
		$null = null;
		if (!class_exists($class)) { ErrorLog("Class ($class) doesnt exist"); return $null; }

		if (!array_key_exists($class,self::$instances))
			self::$instances[$class] = new $class;

		return self::$instances[$class];
	}

	static function Finish() {
//		self::Tab_FinaliseDrawing();
		include(PATH_ABS_CORE.'finalise.php');

//		$c = ob_get_contents();
	}

	private static $customInputs = array();
	static function AddInputType($inputType,$callback) {
		if (array_key_exists($inputType,self::$customInputs)) return;
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

		if (array_key_exists('style',$attributes) && is_array($attributes['style'])) {
			$style = array();
			foreach ($attributes['style'] as $key=>$val) $style[] = "$key:$val";
			$attributes['style'] = join(';',$style);
		}

		//print_r($attributes);
		$attr = BuildAttrString($attributes);

		if (array_key_exists($inputType,self::$customInputs))
		return call_user_func_array(self::$customInputs[$inputType],array($fieldName,$inputType,$defaultValue,$possibleValues,$attributes,$noSubmit));

		switch ($inputType) {
			case itNONE: $out .= $defaultValue; break;
			case itBUTTON:
			case itSUBMIT:
			case itRESET:
				if (array_key_exists('class',$attributes))
				$attributes['class'] .= ' fdb-btn';
				else
				$attributes['class'] = 'fdb-btn';
				$attr = BuildAttrString($attributes);
				$out .= "<input$attr type=\"$inputType\" value=\"$defaultValue\"/>";
				break;
			case itCHECKBOX:
				$checked = ($defaultValue == 1) ? ' checked="checked"': '';
				$out .= "<input$attr type=\"checkbox\"$checked value=\"1\"/>";
				break;
			case itOPTION:
				if (!is_array($possibleValues)) { ErrorLog('Option field specified but no possible values found'); return ''; }
				$count = 0;
				$defaultExists = false;
				foreach ($possibleValues as $name => $val) {
					$count++; $attributes['id'] = "$fieldName-$count"; $attr = BuildAttrString($attributes);
					$checked = ($val == $defaultValue) ? ' checked="true"' : '';
					if ($checked != '') $defaultExists = true;
					$out .= "<input type=\"radio\" $attr value=\"$val\"$checked/>$name<br/>";
				}
				if (!$defaultExists && ($defaultValue != ''))
				$out .= "<input type=\"radio\" $attr value=\"$val\" checked=\"true\">$defaultValue";
				break;
			case itPASSWORD:
				//				settype($possibleValues,'integer');
				//				$ml = (is_numeric($possibleValues) && $possibleValues > 0) ? " maxlength=\"$possibleValues\" size=\"".floor($possibleValues*0.75)."\"" : "";
				//				$ml = (is_numeric($possibleValues) && $possibleValues > 0) ? " size=\"".floor($possibleValues*0.75)."\"" : "";
				$out .= "<input type=\"password\" $attr value=\"\"/>";
				break;
			case itTEXT:
				//			echo "MOO:$defaultValue";
				//				$defaultValue = htmlentities($defaultValue,ENT_QUOTES,CHARSET_ENCODING);
				//			echo "FARK:$defaultValue";
				//				settype($possibleValues,'integer');
				//				$ml = (is_numeric($possibleValues) && $possibleValues > 0) ? " maxlength=\"$possibleValues\" size=\"".floor($possibleValues*0.75)."\"" : "";
				//				$ml = (is_numeric($possibleValues) && $possibleValues > 0) ? " size=\"".floor($possibleValues*0.75)."\"" : "";
				$val = $defaultValue;
				//AA $val = htmlentities($val,ENT_QUOTES,CHARSET_ENCODING);
				$out .= "<input type=\"text\" $attr value=\"$val\"/>";
				break;
			case itTEXTAREA:
				//$defaultValue = $htmlentities($defaultValue,ENT_QUOTES,CHARSET_ENCODING);
				//				settype($possibleValues,'integer');
				//				$ml = (is_numeric($possibleValues) && $possibleValues > 0) ? " cols=\"$possibleValues\" rows=\"".floor($possibleValues*0.08)."\"" : "";
				$out .= "<textarea $attr>$defaultValue</textarea>";
				break;
			case itCOMBO:
				//				if (empty($possibleValues)) { ErrorLog('Combo field specified but no possible values found'); return; }
				//				if (is_array($possibleValues)) { // array of combos
				$defaultExists = false;
				$blankKey = array_search('',$possibleValues);
				if ($blankKey === FALSE) $blankKey = '&nbsp;';
				$out .= "<select $attr><option value=\"\">$blankKey</option>";
				if (is_array($possibleValues)) foreach ($possibleValues as $name => $val) {
					if ($val === '') continue;
					$selected = '';
					//if (is_numeric($val) && !is_numeric($defaultValue)) $defaultValue = (int)$defaultValue;
					if ($val === $defaultValue || $name === $defaultValue) {
						$defaultExists = true;
						$selected = ' selected="selected"';
					}
					$valOutput = $val !== $name ? " value=\"$val\"" : '';
					//AA $name = htmlentities($name,ENT_QUOTES,CHARSET_ENCODING);
					$out .= "<option$valOutput$selected>$name</option>";
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
				if (!array_key_exists('class',$attributes)) $attributes['class'] = '';
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
				if (!array_key_exists('class',$attributes)) $attributes['class'] = '';
				$attributes['class'] .= " autocomplete {gv:'$possibleValues'}";
				$attr = BuildAttrString($attributes);
				$out .= "<textarea $attr>$defaultValue</textarea>\n";
				break;
			case itLISTBOX:
				if (!is_array($possibleValues)) { ErrorLog('Listbox field specified but no possible values found'); return ''; }
				$out .= "<select size=5 $attr><option value=\"\"></option>";
				foreach ($possibleValues as $name => $val) {
					if (empty($val)) continue;
					$selected = ($val == $defaultValue) ? ' selected="selected"' : '';
					$out .= "<option value=\"$val\"$selected>$name</option>";
				}
				$out .= "</select>";
				break;
			case itFILE:
				//$defaultValue = htmlentities($defaultValue,ENT_QUOTES,CHARSET_ENCODING);
				//$defaultValue = htmlentities($defaultValue);
				$out .= "$defaultValue<input type=\"file\" $attr/>";
				break;
			case itDATE:
				//$formattedVal = ($defaultValue === SQL_FORMAT_EMPTY_TIMESTAMP) || ($defaultValue === SQL_FORMAT_EMPTY_DATE) || ($defaultValue === NULL) || ($defaultValue === '') ? '' : $defaultValue;//date('d/m/Y',strptime($defaultValue,'d/m/Y'));
				$formattedVal = $defaultValue;
				if (!array_key_exists('class',$attributes)) $attributes['class'] = '';
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
		return array_key_exists($varname,self::$globalVariables);
	}

	static function SetVar($varname,$value) {
		self::$globalVariables[$varname] = $value;
	}
	static function &GetVar($varname,$initialise=NULL, $raw = false) {
		if (!self::VarExists($varname))
			self::$globalVariables[$varname] = $initialise;

		if (!$raw && is_callable(self::$globalVariables[$varname])) {
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
		if (!$tabGroup) $tabGroup = GetCurrentModule().'-tabs';
		if (array_key_exists($tabGroup,self::$tabGroups)) return $tabGroup;
		//			echo '<div class="tabGroup" id="'.$tabGroup.'"><ul></ul></div>';
		self::$tabGroups[$tabGroup] = array();
		return $tabGroup;
	}
	static function Tab_Add($tabTitle,$tabContent,$tabGroup=NULL,$isURL=false,$order=NULL) {
		//if ($order === NULL) $order = self::$tabOrderCount;
		//self::$tabOrderCount++;
		//echo "Added Tab: $tabTitle - $order<br>";
		if (!$tabGroup) $tabGroup = self::Tab_InitGroup();
		$tabID = self::Tab_GetCount($tabGroup)+1;
		//echo $tabTitle
		if (array_key_exists('tab'.$tabID,self::$tabGroups[$tabGroup])) { ErrorLog("TabID ($tabID) already exists in Group ($tabGroup)"); return; }
		self::$tabGroups[$tabGroup]['tab'.$tabID] = array('id'=>$tabGroup.'-'.$tabID,'title'=>$tabTitle,'content'=>$tabContent,'isURL'=>$isURL,'order'=>$order);

	}
	static function Tab_GetCount($tabGroup=NULL) {
		if (!$tabGroup) $tabGroup = self::Tab_InitGroup();
		return count(self::$tabGroups[$tabGroup]);
	}
	static function Tab_Append($tabID,$content,$tabGroup=NULL) {
		if (!$tabGroup) $tabGroup = self::Tab_InitGroup();
//		print_r(self::$tabGroups[$tabGroup]);
		if (!array_key_exists('tab'.$tabID,self::$tabGroups[$tabGroup])) { ErrorLog("TabID ($tabID) doesnt exist in Group ($tabGroup) for append."); return; }
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
		echo '<ul style="">';
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
		if (array_search($tabGroup,self::$tabs_drawing) !== FALSE) return;
		self::$tabs_drawing[] = $tabGroup;
		echo '{tab.'.$tabGroup.'}';
		//ob_start();
	}
/*	static function Tab_FinaliseDrawing() {
		if (count(self::$tabs_drawing)<=0) return;
		ob_flush();
		$body = FlexDB::GetVar('content');
		foreach (self::$tabs_drawing as $group) {
			ob_start();
			self::Tab_DrawGroup($group);
			$tabBlock = ob_get_contents();
			ob_end_clean();
			$
			FlexDB::SetVar('content',str_replace('{tab.'.$group.'}', $tabBlock, $body));
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
		$replacement = FlexDB::LinkList_Get($id).FlexDB::LinkList_Get('list_functions:'.$id);
		return $replacement;
	}
	static function LinkList_Add($listName,$text,$url,$order = 100,$listAttrs = NULL,$linkAttrs = NULL) {
		$list =& FlexDB::GetVar("linklist_$listName");
		if ($list == NULL) $list = array();
//$bt = useful_backtrace(0,4);
		$list[] = array('text'=>$text,'url'=>$url,'order'=>$order,'attrList'=>$listAttrs,'attrLink'=>$linkAttrs);//,$bt);
	}
	//static function LinkList_Sort($a,$b) {$c1 = $a["order"]; $c2 = $b["order"]; if ($c1 == $c2) return 0;  return $c1 < $c2 ? -1 : 1;}
	static function LinkList_Get($listName,$id=NULL,$listAttrs = NULL,$linkAttrs = NULL) {
		if (!$id) $id = "ulist_$listName";
		$id = " id=\"$id\"";
		$list = FlexDB::GetVar("linklist_$listName");
		if (!is_array($list)) return;

		array_sort_subkey($list,'order');
		//uasort($list,array('FlexDB','LinkList_Sort'));
//print_r($list);
		$return = "";
		foreach ($list as $order => $info) {
			$attrsList = "";
			$attrsLink = "";
			if (array_key_exists('attrList',$info) && is_array($info['attrList']) && array_key_exists('class',$info['attrList']))
			$info['attrList']['class'] .= " linklist-link";
			else
			$info['attrList']['class'] = "linklist-link";
			if (array_key_exists('attrList',$info) && is_array($info['attrList']) || is_array($listAttrs)) {
				if (is_array($listAttrs)) foreach ($listAttrs as $attr=>$val)
				$info['attrList'][$attr] = $val;
				foreach ($info['attrList'] as $k => $v) {
					$attrsList .= " $k=\"$v\"";
				}
			}
			if (array_key_exists('attrLink',$info) && is_array($info['attrLink']) || is_array($linkAttrs)) {
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
	public static $adminTemplate = false;
	private static $usedTemplate = NULL;
	public static function CancelTemplate($justClean=false) { if (!self::UsingTemplate()) return; ob_end_clean(); if (!$justClean) self::$usedTemplate = NULL; }
	public static function UseTemplate($template = DEFAULT_TEMPLATE) {
		//if ($template == TEMPLATE_DEFAULT) $template = STYLE_PATH;
		$ret = true;
		if ($template != TEMPLATE_BLANK && $template != TEMPLATE_ADMIN && !file_exists(PATH_ABS_TEMPLATES.$template.'/template.php')) {
			echo 'Template not found: '.PATH_ABS_TEMPLATES.$template.'/template.php';
			$template = TEMPLATE_BLANK;
			$ret = false;
		}
		if (!FlexDB::UsingTemplate()) {
			ob_start('FlexDB::output_buffer');
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
				break;
			case TEMPLATE_ADMIN:
				$templateDir = PATH_ABS_CORE.'styles/admin/';
				break;
			default:
				$templateDir = PATH_ABS_TEMPLATES.self::$usedTemplate.'/';
				break;
		}
		$path = realpath($templateDir);
		if ($relative) $path = self::GetRelativePath($path);
		return $path.'/';
	}
	public static function OutputTemplate() {
		self::SetVar('templatedir',FlexDB::GetTemplateDir(true));
		if (self::$adminTemplate) self::UseTemplate(TEMPLATE_ADMIN);
		if (!self::UsingTemplate()) return;

		$adminPanel = CallModuleFunc('internalmodule_AdminLogin', 'GetAdminPanel');

		$templateDir = self::GetTemplateDir();
		$templatePath = $templateDir.'template.php';
		self::CancelTemplate(true);

		$template = get_include_contents($templatePath);
/*		switch ($templateDir) {
			case TEMPLATE_ADMIN:
				$template = get_include_contents(PATH_ABS_CORE.'styles/admin/template.php');
				break;
			case NULL:
			case TEMPLATE_BLANK:
				$template = get_include_contents(PATH_ABS_CORE.'styles/default/template.php');
				//$template = '<html><head>{UTOPIA.head}</head><body>{UTOPIA.content}</body></html>';
				break;
			default:
				$template = get_include_contents(PATH_ABS_ROOT.$temp);
				break;
		}*/
		//FlexDB::CancelTemplate();

		self::AddCSSFile(PATH_REL_CORE.'default.css');
		if (file_exists($templateDir.'styles.css'))
			self::AddCSSFile(self::GetRelativePath($templateDir.'styles.css'));

		self::PrependVar('<head>',FlexDB::GetTitle().FlexDB::GetDescription().FlexDB::GetKeywords());
		self::AppendVar('</head>','<script type="text/javascript">'.FlexDB::GetVar('script_include').'</script>'."\n");
//		self::AppendVar('</head>','<base href="http://'.self::GetDomainName().self::GetRelativePath($templateDir).'/" />'."\n");

		self::AppendVar('</body>','{UTOPIA.powered}{UTOPIA.admin_panel}');

		$time_taken = timer_findtime('full process');
		self::SetVar('admin_panel',
				'<div id="uPanel"><div id="utopiaImg" onclick="$(\'#adminSlave\').toggle();"></div>'.
				//'<div style="">'.
				'<div id="adminSlave">'.
				'<div id="adminClose" onclick="$(\'#adminSlave\').toggle()"></div>'.
				'<div style="font-size:16px; margin-right:14px">Powered by <a target="_blank" href="http://www.utopiasystems.co.uk">Utopia Systems Ltd</a>.</div>'.
				'<div>[ '.$time_taken.'ms ] [ '.$GLOBALS['sql_query_count'].' queries ]</div>'.
				$adminPanel.
				'</div></div>');

		while (self::MergeVars($template));

		foreach (self::$globalVariables as $key => $val) {
			if (substr($key,0,2) == '</') {
				$template = str_replace($key,$val.$key,$template);
			} elseif ($key[0] == '<') {
				$template = str_replace($key,$key.$val,$template);
			}
		}

		while (self::MergeVars($template));

		// Make all resources secure
		if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
			$template = str_replace('http://'.self::GetDomainName(),'https://'.self::GetDomainName(),$template);
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
		// TODO: Dont match inside fields!
		foreach (self::$templateParsers as $ident => $arr) {
			if (preg_match_all('/{'.$ident.'}/Ui',$string,$matches,PREG_PATTERN_ORDER)) {
				$searchArr = $matches[0];
				$varsArr = array_key_exists(1,$matches) ? $matches[1] : false;
	//	if (preg_match_all('/{UTOPIA\.([^}]+)}/i',$string,$matches,PREG_SET_ORDER)) {
				foreach ($searchArr as $k => $search) {
          if (strpos($search,'{',1) !== FALSE) continue; // if contains another pragma then skip it, pick up post-merged on next pass.
					$searchLen = strlen($search);
					$offset = 0;
					while (($pos = strpos($string, $search, $offset)) !== FALSE) {
						$test = substr($string, 0, $pos); // only test on string up to match.
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


						if ($arr[1]) ob_start();
						if ($varsArr) { // must process, get replacement
							$id = $varsArr[$k];
							$replace = call_user_func($arr[0],$id);
						} else
							$replace = call_user_func($arr[0]);
						if ($arr[1]) {
							$replace = ob_get_contents();
							ob_end_clean();
						}

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
			//$function = array($class,$fn);
		}

		if ($match) $ident .= '\.('.$match.')';
		if (array_key_exists($ident,self::$templateParsers)) { error_log("$ident is already defined as a template parser."); return; }
		//self::$templateParsers[$ident] = $function;
		//if (array_key_exists($ident,self::$templateParsers)) { error_log("$ident is already defined as a template parser."); return; }
		//if (!is_callable($function)) { error_log("Function for template parser ($ident) is not callable."); return; }
		self::$templateParsers[$ident] = array($function,$catchOutput);
	}
	static function parseVars($id) {
		$replacement = self::GetVar($id.':before').self::GetVar($id).self::GetVar($id.':after');
		return $replacement;
	}

	static function PageNotFound() {
		header("HTTP/1.0 404 Not Found",true,404);
		FlexDB::SetTitle('404 Not Found');
		echo '<h1>404 Not Found</h1>';
		echo '<p>The page you requested could not be found. Return to the <a href="/">homepage</a>.</p>';
		FlexDB::Finish(); die();
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
		if (!FlexDB::UsingTemplate()) return FALSE;
		FlexDB::AppendVar('content',$text);
		return $text;
	}

	static function GetDomainName() {
		return $_SERVER['HTTP_HOST'];
	}

	static function GetRelativePath($fullpath) {
		$pos = strpos($fullpath,PATH_ABS_ROOT);
		return PATH_REL_ROOT.trim(substr($fullpath,$pos+strlen(PATH_ABS_ROOT)),DIRECTORY_SEPARATOR);
	}

	static function AjaxUpdateElement($eleName,$html) {
    	$enc = base64_encode($html);
    	AjaxEcho('$("#'.$eleName.'").html(Base64.decode("'.$enc.'"))');
	}

	static function GetMaxUpload($post=true,$upload_max=true) {
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

	static function Cache_Check($etag, $contentType,$filename='',$modified=NULL,$age=2592000,$disposition='inline') {
		header('Content-Type: '.$contentType,true);
		header('Pragma: public',true);
		header("Etag: $etag",true);
		header("Expires: ".gmdate("D, d M Y H:i:s",time()+$age) . " GMT",true);
		header("Cache-Control: public, max-age=$age",true);		$fn = empty($filename) ? '' : "; filename=$filename";
		header("Content-Disposition: ".$disposition.$fn,true);

		if (array_key_exists('HTTP_IF_NONE_MATCH',$_SERVER) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
			header('HTTP/1.1 304 Not Modified', true, 304); die();
		}

		if (!$modified) $modified = 0;
		$lm = gmdate('r',$modified);
		header("Last-Modified: ".$lm,true);
		if (array_key_exists('HTTP_IF_MODIFIED_SINCE',$_SERVER) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $lm) {
			header('HTTP/1.1 304 Not Modified', true, 304); die();
		}
	}

	static function Cache_Output($data,$etag,$contentType,$filename='',$modified=NULL,$age=2592000,$disposition='inline') {
		self::Cache_Check($etag,$contentType,$filename,$modified,$age,$disposition);
		header('Content-Length: ' . strlen($data),true);
	//	while (ob_get_level()) ob_end_clean();
		die($data);
	}

	static function Breakout($text) {
		self::CancelTemplate();
		die(print_r($text,true));
	}
}

?>