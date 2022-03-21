<?php
define('TEMPLATE_BLANK', '__blank__');
define('TEMPLATE_DEFAULT', '__default__');

utopia::AddTemplateParser('utopia', 'utopia::parseVars');
utopia::AddTemplateParser('list', 'utopia::DrawList');
utopia::AddTemplateParser('get', 'utopia::parseGet');
utopia::AddTemplateParser('post', 'utopia::parsePost');
utopia::AddTemplateParser('request', 'utopia::parseRequest');
utopia::AddTemplateParser('session', 'utopia::parseSession');
utopia::AddTemplateParser('const', 'utopia::parseConst');
utopia::AddTemplateParser('domain', 'utopia::GetDomainName', '');

utopia::AddTemplateParser('home_url', PATH_REL_ROOT, '');
utopia::AddTemplateParser('home_url_abs', 'utopia::GetSiteURL', '');
utopia::AddTemplateParser('inline', 'inline=0', '');

utopia::SetVar('tp', PATH_REL_CORE . 'images/tp.gif');

utopia::AddTemplateParser('setrequest', 'utopia::setRequest');
utopia::AddTemplateParser('setget', 'utopia::setGet');

final class utopia
{
  private static $children = [];
  private static $parents = [];

  static function AddChild($parent, $child, $info)
  {
    $info['parent'] = $parent;
    $info['child'] = $child;

    self::$children[$parent][$child][] = $info;
    self::$parents[$child][$parent][] = $info;
  }

  static function GetChildren($parent)
  {
    $specific = (isset(self::$children[$parent])) ? self::$children[$parent] : [];
    $currentModule = ($parent == utopia::GetCurrentModule() && isset(self::$children['/'])) ? self::$children['/'] : [];
    $catchAll = (isset(self::$children['*'])) ? self::$children['*'] : [];
    $baseModule = [];

    switch($parent)
    {
      case 'uCMS_View':
        $currentPage = uCMS_View::findPage();
        if($currentPage && $currentPage['is_home'] && isset(self::$children['']))
        {
          $baseModule = self::$children[''];
        }
        break;
      case 'uDashboard':
        if(isset(self::$children['']))
        {
          $baseModule = self::$children[''];
        }
    }

    $arr = array_merge($catchAll, $baseModule, $currentModule, $specific);

    return $arr;
  }

  static function GetParents($child)
  {
    return (isset(self::$parents[$child])) ? self::$parents[$child] : [];
  }

  static function SetTitle($text)
  {
    self::SetVar('title', $text);
  }

  static function GetTitle($textOnly = false, $fallback = false)
  {
    if(!self::VarExists('title'))
    {
      $content = '&lt;&lt; No Title &gt;&gt;';
    }
    else
    {
      $content = self::GetVar('title');
    }

    if(empty($content) || $textOnly)
    {
      return $content;
    }
    return '<title>' . $content . '</title>' . "\n";
  }

  static function SetDescription($text)
  {
    self::SetVar('meta_description', $text);
  }

  static function GetDescription($textOnly = false)
  {
    if(!self::VarExists('meta_description'))
    {
      $content = '';
    }
    else
    {
      $content = self::GetVar('meta_description');
    }

    if(empty($content) || $textOnly)
    {
      return $content;
    }
    return '<meta name="description" content="' . $content . '" />' . "\n";
  }

  static function SetKeywords($text)
  {
    self::SetVar('meta_keywords', $text);
  }

  static function GetKeywords($textOnly = false)
  {
    if(!self::VarExists('meta_keywords'))
    {
      $content = '';
    }
    else
    {
      $content = self::GetVar('meta_keywords');
    }

    if(empty($content) || $textOnly)
    {
      return $content;
    }
    return '<meta name="keywords" content="' . $content . '" />' . "\n";
  }

  static function AddMetaTag($name, $content)
  {
    $nifunc = function ($obj, $event, $doc) use ($name, $content) {
      $head = $doc->getElementsByTagName("head")->item(0);
      $node = $doc->createElement("meta");
      $node->setAttribute("name", "'.$name.'");
      $node->setAttribute("content", "'.$content.'");
      $head->appendChild($node);
    };
    uEvents::AddCallback('ProcessDomDocument', $nifunc);
  }

  /**
   * Link a css file to the document
   *
   * @deprecated
   */
  static function AddCSSFile($path, $start = false)
  {
    uCSS::LinkFile($path, $start ? -1 : null);
  }

  /**
   * Link a javascript file to the document
   *
   * @deprecated
   */
  static function AddJSFile($path, $start = false)
  {
    uJavascript::LinkFile($path, $start ? -1 : null);
  }

  private static $allmodules = null;

  /**
   * GetModules:  Returns an array of all registered class names which are derived from iUtopiaModule
   */
  static function GetModules($refresh = false)
  {
    if(self::$allmodules === null || $refresh)
    {
      $rows = [];
      $classes = get_declared_classes();
      foreach($classes as $id => $class)
      {
        $ref = new ReflectionClass($class);
        if($ref->isAbstract())
        {
          continue;
        }

        if(!$ref->implementsInterface('iUtopiaModule'))
        {
          continue;
        }

        $parents = array_values(class_parents($class));
        $interfaces = $ref->getInterfaceNames();

        $class = ['module_name' => $class];
        $class['module_id'] = $id;
        $class['types'] = array_merge($parents, $interfaces);
        $class['uuid'] = null;

        if($ref->isSubclassOf('uBasicModule'))
        {
          $class['uuid'] = $class['module_name']::GetUUID();
          if(is_array($class['uuid']))
          {
            $class['uuid'] = $class['uuid'][0];
          }
        }
        $rows[$class['module_name']] = $class;
      }

      self::$allmodules = $rows;
    }

    return self::$allmodules;
  }

  static function GetModuleId($module)
  {
    if(!is_string($module))
    {
      $module = get_class($module);
    }
    $m = utopia::ModuleExists($module);
    if($m)
    {
      return $m['module_id'];
    }
    return false;
  }

  static function ModuleExists($module)
  {
    $modules = self::GetModules();
    if(isset($modules[$module]))
    {
      return $modules[$module];
    }
    return false;
  }

  static function UUIDExists($uuid)
  {
    $modules = self::GetModules();
    foreach($modules as $m)
    {
      if($uuid == $m['uuid'])
      {
        return $m;
      }
      if(is_array($m['uuid']) && array_search($uuid, $m['uuid']) !== false)
      {
        return $m;
      }
    }
    return false;
  }

  static function GetModulesOf($type)
  {
    $inputs = self::GetModules();
    foreach($inputs as $k => $m)
    {
      if(array_search($type, $m['types']) === false)
      {
        unset($inputs[$k]);
      }
    }
    return $inputs;
  }

  /**
   * GetRewriteURL: Returns the current URL with PATH_REL_ROOT trimmed from the start
   */
  static function GetRewriteURL()
  {
    $REQUESTED_URL = array_key_exists(
      'HTTP_X_REWRITE_URL',
      $_SERVER
    ) ? $_SERVER['HTTP_X_REWRITE_URL'] : $_SERVER['REQUEST_URI'];
    $REQUESTED_URL = preg_replace('/\?.*/i', '', $REQUESTED_URL);

    $REQUESTED_URL = preg_replace('/^' . preg_quote(PATH_REL_ROOT, '/') . '/', '', $REQUESTED_URL);
    $REQUESTED_URL = preg_replace('/^u\//', '', $REQUESTED_URL);

    $path = urldecode($REQUESTED_URL);

    return $path;
  }

  static function SetCurrentModule($module)
  {
    if(!self::ModuleExists($module))
    {
      return;
    }

    $cm = utopia::GetCurrentModule();
    $o = utopia::GetInstance($cm);
    if(flag_is_set($o->GetOptions(), PERSISTENT))
    {
      return;
    }

    utopia::SetVar('current_module', $module);
  }

  private static $cmCache = [];

  /**
   * GetCurrentModule: Returns class name of module to run (syn: current module)
   */
  static function GetCurrentModule()
  {
    // cm variable
    if(utopia::VarExists('current_module'))
    {
      return utopia::GetVar('current_module');
    }

    // GET uuid
    if(isset($_GET['uuid']))
    {
      $m = utopia::UUIDExists($_GET['uuid']);
      if($m)
      {
        return $m['module_name'];
      }
    }

    // rewritten url?   /u/MOD/
    $u = self::GetRewriteURL();
    if(!isset(self::$cmCache[$u]))
    {
      foreach(self::GetModules() as $m)
      {
        if($m['uuid'] && preg_match('/^' . preg_quote($m['uuid'] . '/', '/') . '/i', $u . '/'))
        {
          self::$cmCache[$u] = $m['module_name'];
          return $m['module_name'];
        }
        self::$cmCache[$u] = false;
      }
    }
    if(self::$cmCache[$u])
    {
      return self::$cmCache[$u];
    }

    // admin root?
    if(strpos($_SERVER['REQUEST_URI'], PATH_REL_CORE) === 0)
    {
      return 'uDashboard';
    }

    // CMS
    return 'uCMS_View';
  }

  private static $launchers = [];

  static function QueueLauncher($module)
  {
    self::$launchers[] = $module;
  }

  static function Launcher($module = null)
  {
    $path = PATH_ABS_ROOT . ltrim($_SERVER['REQUEST_URI'], '/');
    $path = parse_url($path, PHP_URL_PATH);
    $ext = substr($path, strrpos($path, '.'));
    if(is_file($path) && ($ext !== '.php'))
    {
      switch($ext)
      {
        case '.css';
          $contentType = 'text/css';
          break;
        case '.js';
          $contentType = 'application/javascript';
          break;
        default:
          $contentType = utopia::GetMimeType($path);
          break;
      }

      utopia::CancelTemplate();

      $etag = utopia::checksum([$_SERVER['REQUEST_URI'], filemtime($path), filesize($path)]);
      utopia::Cache_Check($etag, $contentType, basename($path));
      utopia::Cache_Output(file_get_contents($path), $etag, $contentType, basename($path));
      return;
    }

    if($module == null)
    {
      $module = self::GetCurrentModule();
    }

    if(!utopia::ModuleExists($module))
    {
      utopia::PageNotFound();
    }

    utopia::SetVar('current_module', $module);
    self::QueueLauncher($module);

    $currentModule = reset(self::$launchers);
    do
    {
      $obj = utopia::GetInstance($currentModule);
      utopia::SetVar('title', $obj->GetTitle());

      // run module
      timer_start('Run Module: ' . $currentModule);
      $obj->_RunModule();
      timer_end('Run Module: ' . $currentModule);
    }
    while(($currentModule = next(self::$launchers)));
  }

  static $instances = [];

  static function &GetInstance($class, $defaultInstance = true)
  {
    if(!$defaultInstance)
    {
      $instance = new $class;
      return $instance;
    }

    if(!isset(self::$instances[$class]))
    {
      self::$instances[$class] = new $class;
    }

    return self::$instances[$class];
  }

  static $finished = false;

  static function Finish()
  {
    if(self::$finished)
    {
      return;
    }
    self::$finished = true;
    while(ob_get_level() > 3)
    {
      ob_end_flush();
    }

    timer_start('Output Template');
    utopia::OutputTemplate();
    timer_end('Output Template');

    if(isset($GLOBALS['timers']) && utopia::DebugMode())
    {
      echo '<pre class="uDebug"><table>';
      foreach($GLOBALS['timers'] as $name => $info)
      {
        if(!is_array($info))
        {
          continue;
        }
        $time = !array_key_exists('time_taken', $info) ? timer_end($name) : $info['time_taken'];
        $time = number_format($time, 2);
        echo '<tr><td style="vertical-align:top;border-top:1px solid black">' . $time . '</td><td style="vertical-align:top;border-top:1px solid black">' . $name . PHP_EOL . $info['info'] . '</td></tr>';
      }
      echo '</table></pre>';
    }

    header('X-Runtime: ' . number_format((microtime(true) - UCORE_START_TIME) * 1000) . 'ms');
    die;
  }

  private static $customInputs = [];

  static function AddInputType($inputType, $callback)
  {
    if(isset(self::$customInputs[$inputType]))
    {
      return;
    }
    define($inputType, $inputType);
    self::$customInputs[$inputType] = $callback;
  }

  static function DrawInput(
    $fieldName, $inputType, $defaultValue = '', $possibleValues = null, $attributes = null, $noSubmit = false
  )
  {
    $out = '';
    if($attributes === null)
    {
      $attributes = [];
    }
    //		$defaultValue = str_replace(DEFAULT_CURRENCY_HTML,DEFAULT_CURRENCY,$defaultValue);
    //		$defaultValue = str_replace(DEFAULT_CURRENCY,DEFAULT_CURRENCY_HTML,$defaultValue);

    //		if (!isset($attributes['id'])) $attributes['id'] = $fieldName;
    //		if(!isset($attributes['name']) && !$noSubmit) $attributes['name'] = $fieldName;
    $attributes['name'] = $fieldName;

    if(isset($attributes['class']))
    {
      $attributes['class'] .= ' inputtype inputtype-' . $inputType;
    }
    else
    {
      $attributes['class'] = 'inputtype inputtype-' . $inputType;
    }

    $defaultValue = utopia::jsonTryDecode($defaultValue);

    $attr = BuildAttrString($attributes);

    if(isset(self::$customInputs[$inputType]))
    {
      return call_user_func_array(
        self::$customInputs[$inputType],
        [$fieldName, $inputType, $defaultValue, $possibleValues, $attributes, $noSubmit]
      );
    }

    switch($inputType)
    {
      case itNONE:
        $out .= $defaultValue;
        break;
      case itBUTTON:
        if(isset($attributes['class']))
        {
          $attributes['class'] .= ' btn';
        }
        else
        {
          $attributes['class'] = 'btn';
        }
        $attributes['class'] .= ' btn-' . $inputType;
        $attributes['class'] = str_replace('inputtype ', '', $attributes['class']);
        $attr = BuildAttrString($attributes);
        $out .= '<button' . $attr . '>' . $defaultValue . '</button>';
        break;
      case itSUBMIT:
        if(isset($attributes['class']))
        {
          $attributes['class'] .= ' btn';
        }
        else
        {
          $attributes['class'] = 'btn';
        }
        $attributes['class'] .= ' btn-' . $inputType;
        $attributes['class'] = str_replace('inputtype ', '', $attributes['class']);
        $attr = BuildAttrString($attributes);
        $out .= '<input' . $attr . ' type="submit" value="' . $defaultValue . '"/>';
        break;
      case itRESET:
        if(isset($attributes['class']))
        {
          $attributes['class'] .= ' btn';
        }
        else
        {
          $attributes['class'] = 'btn';
        }
        $attributes['class'] .= ' btn-' . $inputType;
        $attributes['class'] = str_replace('inputtype ', '', $attributes['class']);
        $attr = BuildAttrString($attributes);
        $out .= '<input' . $attr . ' type="reset" value="' . $defaultValue . '"/>';
        break;
      case itCHECKBOX:
        if(is_array($possibleValues))
        {
          $at = [];
          if(isset($attributes['styles']))
          {
            $at['styles'] = $attributes['styles'];
          }
          $at = BuildAttrString($at);

          if(!preg_match('/^usql\-/', $fieldName))
          {
            $attributes['name'] = $attributes['name'] . '[]';
            $attr = BuildAttrString($attributes);
          }
          $out .= '<span' . $at . ' class="inputtype inputtype-checkboxlist">';
          foreach($possibleValues as $key => $val)
          {
            $checked = ((string)$key === $defaultValue || (is_array($defaultValue) && in_array(
                  $key,
                  $defaultValue
                ))) ? ' checked="checked"' : '';
            $val = htmlentities($val, ENT_COMPAT, CHARSET_ENCODING);
            $out .= "<label><input$attr type=\"checkbox\"$checked value=\"$key\"/>$val</label>";
          }
          $out .= '</span>';
        }
        else
        {
          $checked = ($defaultValue == 1) ? ' checked="checked"' : '';
          $out .= "<input$attr type=\"checkbox\"$checked value=\"1\"/>";
        }
        break;
      case itOPTION:
        if(!is_array($possibleValues))
        {
          ErrorLog('Option field specified but no possible values found');
          return '';
        }
        $count = 0;
        $defaultExists = false;
        foreach($possibleValues as $key => $val)
        {
          $count++;
          $attributes['id'] = "$fieldName-$count";
          $attr = BuildAttrString($attributes);
          $checked = ($key == $defaultValue || (is_array($defaultValue) && in_array(
                $key,
                $defaultValue
              ))) ? ' checked="checked"' : '';
          if($checked != '')
          {
            $defaultExists = true;
          }
          $out .= "<input type=\"radio\" $attr value=\"$key\"$checked/>$val<br/>";
        }
        if(!$defaultExists && ($defaultValue != ''))
        {
          $out .= "<input type=\"radio\" $attr value=\"$val\" checked=\"true\">$defaultValue";
        }
        break;
      case itPASSWORD:
      case itPLAINPASSWORD:
        $out .= "<input type=\"password\" $attr value=\"\"/>";
        break;
      case itTEXT:
        $defaultValue = str_replace('"', '&quot;', $defaultValue);
        $out .= "<input type=\"text\" $attr value=\"$defaultValue\"/>";
        break;
      case itTEXTAREA:
        //sanitise value.
        if(!utopia::SanitiseValue($defaultValue, 'string') && !utopia::SanitiseValue($defaultValue, 'NULL'))
        {
          $defaultValue = 'Value has been sanitised: ' . var_export($defaultValue, true);
        }
        $defaultValue = htmlentities($defaultValue, ENT_COMPAT, CHARSET_ENCODING);
        $out .= "<textarea $attr>$defaultValue</textarea>";
        break;
      case itCOMBO:
        if(empty($possibleValues))
        {
          $possibleValues = [];
        }
        $out .= "<select $attr>";
        if(!isset($possibleValues['']))
        { // blank value does not exist.
          if(isset($attributes['placeholder']) && $attributes['placeholder'] && !isset($possibleValues[$attributes['placeholder']]))
          {
            $blankVal = $attributes['placeholder'];
          }
          else
          {
            $blankVal = '&nbsp;';
          }
          $possibleValues = ['' => $blankVal] + $possibleValues;
        }
        else
        { // blank value exists, ensure it is at the top.
          $v = $possibleValues[''];
          unset($possibleValues['']);
          $possibleValues = ['' => $v] + $possibleValues;
        }
        $defaultExists = false;
        if(is_array($possibleValues))
        {
          foreach($possibleValues as $key => $val)
          {
            if($val === false)
            {
              continue;
            }
            $selected = '';
            if($defaultValue !== '' && ((is_array($defaultValue) && in_array(
                    $key,
                    $defaultValue
                  )) || ((string)$key === (string)$defaultValue)))
            {
              $defaultExists = true;
              $selected = ' selected="selected"';
            }
            $valOutput = $key !== $val ? " value=\"$key\"" : '';
            $out .= "<option$valOutput$selected>$val</option>";
          }
        }
        if(!$defaultExists && ($defaultValue))
        {
          $out .= "<optgroup label=\"No longer available\"><option selected=\"selected\">$defaultValue</option></optgroup>";
        }
        $out .= "</select>";
        break;
      case itLISTBOX:
        if(!is_array($possibleValues))
        {
          ErrorLog('Listbox field specified but no possible values found');
          return '';
        }
        $out .= "<select size=5 $attr><option value=\"\"></option>";
        foreach($possibleValues as $name => $val)
        {
          if(empty($val))
          {
            continue;
          }
          $selected = ($val == $defaultValue || (is_array($defaultValue) && in_array(
                $val,
                $defaultValue
              ))) ? ' selected="selected"' : '';
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
        $out .= "<input type=\"text\" $attr value=\"$formattedVal\"/>";
        break;
      default:
        $defaultValue = str_replace('"', '&quot;', $defaultValue);
        $out .= "<input type=\"$inputType\" $attr value=\"$defaultValue\"/>";
        break;
    }

    return $out;
  }

  /* VAR */
  private static $globalVariables = [];//'powered'=>'<div style="text-align:center; background-color:black; padding:0.2em"><span style="font-size:10px; background-color:#ffffee; padding:0.5em;">Powered by <a target="_blank" href="http://www.utopiasystems.co.uk">Utopia Systems</a></span></div>');

  static function VarExists($varname)
  {
    return isset(self::$globalVariables[$varname]);
  }

  static function SetVar($varname, $value)
  {
    self::$globalVariables[$varname] = $value;
  }

  static function &GetVar($varname, $initialise = null, $raw = false)
  {
    if(!self::VarExists($varname))
    {
      self::$globalVariables[$varname] = $initialise;
    }

    if(!$raw && is_array(self::$globalVariables[$varname]) && is_callable(self::$globalVariables[$varname]))
    {
      $base = self::$globalVariables[$varname];
      $args = array_splice($base, 2);
      $result = call_user_func_array($base, $args);
      return $result;
    }

    return self::$globalVariables[$varname];
  }

  static function AppendVar($var, $text)
  {
    if(self::VarExists($var))
    {
      self::$globalVariables[$var] .= $text;
    }
    else
    {
      self::$globalVariables[$var] = $text;
    }
  }

  static function PrependVar($var, $text)
  {
    if(self::VarExists($var))
    {
      self::$globalVariables[$var] = $text . self::$globalVariables[$var];
    }
    else
    {
      self::$globalVariables[$var] = $text;
    }
  }

  /*  LINKLIST  */
  static $lists = [];

  static function DrawList($id)
  {
    $replacement = utopia::LinkList_Get($id) . utopia::LinkList_Get('list_functions:' . $id);
    return $replacement;
  }

  static function LinkList_Add($listName, $text, $url, $order = 100, $listAttrs = null, $linkAttrs = null)
  {
    if(!isset(self::$lists["linklist_$listName"]))
    {
      self::$lists["linklist_$listName"] = [];
    }
    $list =& self::$lists["linklist_$listName"];
    //if ($list == NULL) $list = array();
    //$bt = useful_backtrace(0,4);
    $list[] = [
      'text'     => $text,
      'url'      => $url,
      'order'    => $order,
      'attrList' => $listAttrs,
      'attrLink' => $linkAttrs,
    ];//,$bt);
  }

  //static function LinkList_Sort($a,$b) {$c1 = $a["order"]; $c2 = $b["order"]; if ($c1 == $c2) return 0;  return $c1 < $c2 ? -1 : 1;}
  static function LinkList_Get($listName, $id = null, $listAttrs = null, $linkAttrs = null)
  {
    if(!$id)
    {
      $id = "ulist_$listName";
    }
    $id = " id=\"$id\"";
    $list =& self::$lists["linklist_$listName"];
    if(!is_array($list))
    {
      return;
    }

    array_sort_subkey($list, 'order');

    $return = "";
    foreach($list as $order => $info)
    {
      $attrsList = "";
      $attrsLink = "";
      if(isset($info['attrList']) && is_array($info['attrList']) && isset($info['attrList']['class']))
      {
        $info['attrList']['class'] .= " linklist-link";
      }
      else
      {
        $info['attrList']['class'] = "linklist-link";
      }
      if(isset($info['attrList']) && is_array($info['attrList']) || is_array($listAttrs))
      {
        if(is_array($listAttrs))
        {
          foreach($listAttrs as $attr => $val)
          {
            $info['attrList'][$attr] = $val;
          }
        }
        foreach($info['attrList'] as $k => $v)
        {
          $attrsList .= " $k=\"$v\"";
        }
      }
      if(isset($info['attrLink']) && is_array($info['attrLink']) || is_array($linkAttrs))
      {
        if(is_array($linkAttrs))
        {
          foreach($linkAttrs as $attr => $val)
          {
            $info['attrLink'][$attr] = $val;
          }
        }
        foreach($info['attrLink'] as $k => $v)
        {
          $attrsLink .= " $k=\"$v\"";
        }
      }

      if(empty($info['text']) && !empty($info['url']))
      {
        $return .= "<li$attrsList>" . $info['url'] . "</li>";
      }
      else if(empty($info['url']))
      {
        if(empty($info['text']))
          // style=\"line-height:5px;height:5px;width:5px;\"
        {
          $return .= "<li class=\"linklist-sep\">&nbsp;</li>";
        }//"<div$attrsLink style=\"width:5px; height:5px;\"></div>";
        else
        {
          $return .= "<li$attrsList><a$attrsLink>" . htmlspecialchars($info['text']) . "</a></li>";
        }//<div$attrsLink>{$info['text']}</div>";
      }
      else
      {
        $href = empty($info['url']) ? '' : " href=\"" . htmlspecialchars($info['url']) . "\"";
        $return .= "<li$attrsList><a$attrsLink$href>" . htmlspecialchars($info['text']) . "</a></li>";
      }
    }
    $return = trim($return, "\n");
    return "\n<ul$id class=\"linklist\">$return</ul>";
  }

  /* TEMPLATE */
  public static function GetTemplates($includeDefault = false, $includeCore = true)
  {
    $userTemplates = array_merge(
      array_filter((array)glob(PATH_ABS_TEMPLATES . '*')),
      array_filter((array)glob(PATH_ABS_THEMES . '*'))
    ); // find all user templates

    $adminTemplates = glob(PATH_ABS_CORE . 'themes/*'); // find all admin templates
    $nTemplates = [];
    if($includeDefault)
    {
      $nTemplates[''] = 'Default Template';
    }

    if(is_array($adminTemplates))
    {
      foreach($adminTemplates as $k => $v)
      {
        if($v == '.' || $v == '..' || !is_dir($v))
        {
          continue;
        }
        $v = str_replace(PATH_ABS_ROOT, '/', $v);
        $v = fix_path($v, '/');
        $nTemplates[$v] = $v;
      }
    }
    if(is_array($userTemplates))
    {
      foreach($userTemplates as $k => $v)
      {
        if($v == '.' || $v == '..' || !is_dir($v))
        {
          continue;
        }
        $v = str_replace(PATH_ABS_ROOT, '/', $v);
        $v = fix_path($v, '/');
        $nTemplates[$v] = $v;
      }
    }

    foreach($nTemplates as $template => $v)
    {
      if(file_exists(PATH_ABS_ROOT . $template . '/template.ini'))
      {
        $inifile = parse_ini_file(PATH_ABS_ROOT . $template . '/template.ini');
        if(isset($inifile['hidden']))
        {
          unset($nTemplates[$template]);
        }
      }
    }

    return $nTemplates;
  }

  public static $adminTemplate = false;
  private static $usedTemplate = null;

  public static function CancelTemplate($justClean = false)
  {
    if(!self::UsingTemplate())
    {
      return;
    }
    ob_clean();
    if(!$justClean)
    {
      self::$usedTemplate = null;
    }
  }

  public static function UseTemplate($template = TEMPLATE_DEFAULT)
  {
    if($template === TEMPLATE_DEFAULT && self::GetCurrentModule() && self::GetInstance(
        self::GetCurrentModule()
      ) instanceof iAdminModule)
    {
      $template = TEMPLATE_ADMIN;
    }
    switch($template)
    {
      case TEMPLATE_BLANK:
        break;
      case TEMPLATE_ADMIN:
        break;
      case TEMPLATE_DEFAULT:
        break;
      default:
        if(!file_exists(PATH_ABS_ROOT . $template . '/template.php'))
        {
          return false;
        }
    }
    self::$usedTemplate = $template;
    return true;
  }

  public static function UsingTemplate($compare = null)
  {
    if($compare === null)
    {
      return (self::$usedTemplate !== null);
    }
    else
    {
      return (self::$usedTemplate === $compare);
    }
  }

  public static function GetTemplateDir($relative = false)
  {
    $templateDir = PATH_ABS_CORE . 'themes/default/';
    switch(self::$usedTemplate)
    {
      case null:
      case TEMPLATE_BLANK:
        return false;
      case TEMPLATE_ADMIN:
        $templateDir = rtrim(PATH_ABS_ROOT, '/') . self::$usedTemplate . '/';
        if(!file_exists($templateDir))
        {
          $templateDir = PATH_ABS_CORE . 'themes/admin/';
        }
        break;
      case TEMPLATE_DEFAULT:
        $templateDir = rtrim(PATH_ABS_ROOT, '/') . modOpts::GetOption('default_template') . '/';
        if(!file_exists($templateDir))
        {
          $templateDir = PATH_ABS_CORE . 'themes/default/';
        }
        break;
      default:
        $templateDir = rtrim(PATH_ABS_ROOT, '/') . self::$usedTemplate . '/';
        if(!file_exists($templateDir))
        {
          $templateDir = PATH_ABS_CORE . 'themes/default/';
        }
        break;
    }
    $path = realpath($templateDir);
    if($relative)
    {
      $path = self::GetRelativePath($path);
    }
    return $path . '/';
  }

  public static function GetTemplateCSS($template = null)
  {
    if(!$template)
    {
      $template = utopia::GetTemplateDir(true);
    }
    $cssfiles = [];

    if(!file_exists($template))
    {
      $template = utopia::GetAbsolutePath($template);
    }
    if(!file_exists($template))
    {
      return $cssfiles;
    }

    // if we have a parent, get its css
    $inifile = $template . '/template.ini';
    if(file_exists($inifile))
    {
      $inifilearr = parse_ini_file($inifile);
      if(isset($inifilearr['parent']))
      {
        $cssfiles = self::GetTemplateCSS(dirname(dirname($inifile)) . '/' . $inifilearr['parent']);
      }
    }

    $namedcss = $template . '/styles.css';

    if(file_exists($namedcss))
    {
      $cssfiles[] = $namedcss;
    }

    return $cssfiles;
  }

  private static $doneCSS = false;
  public static $noSnip = false;

  public static function OutputTemplate()
  {
    uEvents::TriggerEvent('BeforeOutputTemplate');
    if(!self::UsingTemplate())
    {
      try{ob_end_clean();}catch(Exception $e){}
      echo utopia::GetVar('content');
      return;
    }
    if(isset($_GET['inline']) && !is_numeric($_GET['inline']))
    {
      $_GET['inline'] = 0;
    }
    if(self::UsingTemplate(TEMPLATE_BLANK) || (isset($_GET['inline']) && $_GET['inline'] == 0))
    {
      $template = utopia::GetVar('content');
    }
    else
    {
      $tCount = -1; // do all by default
      if(isset($_GET['inline']))
      {
        $tCount = $_GET['inline'] - 1;
      }
      $template = '';
      $css = self::GetTemplateCSS();
      foreach($css as $cssfile)
      {
        uCSS::LinkFile($cssfile);
      }

      // first get list of parents
      $templates = [];
      $templateDir = utopia::GetTemplateDir(true);
      if(!file_exists($templateDir))
      {
        $templateDir = utopia::GetAbsolutePath($templateDir);
      }
      if(file_exists($templateDir))
      {
        $templates[] = $templateDir;
      }
      while($tCount-- && file_exists($templateDir . '/template.ini'))
      {
        $inifile = parse_ini_file($templateDir . '/template.ini');
        if(!isset($inifile['parent']))
        {
          break;
        }
        if(file_exists(PATH_ABS_ROOT . $inifile['parent']))
        {
          $templateDir = PATH_ABS_ROOT . $inifile['parent'];
        }
        else
        {
          $templateDir = dirname($templateDir) . '/' . $inifile['parent'];
        }
        $templates[] = $templateDir;
      }

      foreach($templates as $templateDir)
      {
        // set templatedir
        self::SetVar('templatedir', self::GetRelativePath($templateDir));
        $templatePath = $templateDir . '/template.php';
        $template = get_include_contents($templatePath);
        // mergevars
        while(self::MergeVars($template))
        {
        }
        // setvar
        self::SetVar('content', $template);
      }
      if(!$template)
      {
        $template = '{utopia.content}';
      }
    }
    ob_end_clean();

    while(self::MergeVars($template))
    {
    }

    $template = str_replace(
      '<head>',
      '<head>' . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>',
      $template
    );

    // Make all resources secure
    if(self::IsRequestSecure())
    {
      $template = str_replace('http://' . self::GetDomainName(), 'https://' . self::GetDomainName(), $template);
    }
    do
    {
      if(self::UsingTemplate() && class_exists('DOMDocument'))
      {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->formatOutput = false;
        $doc->preserveWhiteSpace = true;
        $doc->validateOnParse = true;

        if(!$doc->loadHTML('<?xml encoding="UTF-8">' . utf8_decode($template)))
        {
          break;
        }
        $isSnip = (stripos($template, '<html') === false);
        $doc->encoding = 'UTF-8';

        // no html tag?  break out.
        if(!$doc->getElementsByTagName('html')->length)
        {
          break;
        }

        // remove multiple xmlns attributes
        $doc->documentElement->removeAttributeNS(null, 'xmlns');

        // assert BODY tag
        if(!$doc->getElementsByTagName('body')->length)
        {
          $node = $doc->createElement("body");
          $doc->getElementsByTagName('html')->item(0)->appendChild($node);
        }

        // assert HEAD tag
        if(!$doc->getElementsByTagName('head')->length)
        {
          // create head node
          $node = $doc->createElement("head");
          $body = $doc->getElementsByTagName('body')->item(0);
          $newnode = $body->parentNode->insertBefore($node, $body);
        }

        // add HEAD children
        $head = $doc->getElementsByTagName('head')->item(0);

        // set title
        if(!$head->getElementsByTagName('title')->length)
        {
          $node = $doc->createElement('title');
          $node->appendChild($doc->createTextNode(utopia::GetTitle(true)));
          $head->appendChild($node);
        }
        if(utopia::GetDescription(true))
        {
          $node = $doc->createElement('meta');
          $node->setAttribute('name', 'description');
          $node->setAttribute('content', utopia::GetDescription(true));
          $head->appendChild($node);
        }
        if(utopia::GetKeywords(true))
        {
          $node = $doc->createElement('meta');
          $node->setAttribute('name', 'keywords');
          $node->setAttribute('content', utopia::GetKeywords(true));
          $head->appendChild($node);
        }

        $node = $doc->createElement('meta');
        $node->setAttribute('name', 'generator');
        $node->setAttribute('content', 'uCore PHP Framework');
        $head->appendChild($node);

        // template is all done, now lets run a post process event
        try
        {
          uEvents::TriggerEvent('ProcessDomDocument', null, [&$doc]);
        }
        catch(Exception $e)
        {
          uErrorHandler::EchoException($e);
        }

        $ctNode = null;
        foreach($head->getElementsByTagName('meta') as $meta)
        {
          if($meta->hasAttribute('http-equiv') && strtolower($meta->getAttribute('http-equiv')) == 'content-type')
          {
            $ctNode = $meta;
            break;
          }
        }
        if(!$ctNode)
        {
          $ctNode = $doc->createElement('meta');
          $head->appendChild($ctNode);
        }
        $ctNode->setAttribute('http-equiv', 'content-type');
        $ctNode->setAttribute('content', 'text/html;charset=' . CHARSET_ENCODING);
        if($ctNode !== $head->firstChild)
        {
          $head->insertBefore($ctNode, $head->firstChild);
        }

        $doc->normalizeDocument();
        if(strpos(strtolower($doc->doctype->publicId), ' xhtml '))
        {
          $template = $doc->saveXML();
        }
        else
        {
          $template = $doc->saveHTML();
        }
        $template = preg_replace('/<\?xml encoding="UTF-8"\??>\n?/i', '', $template);
        if($isSnip && !self::$noSnip)
        {
          $template = preg_replace(
            '/.*<body[^>]*>\s*/ims',
            '',
            $template
          ); // remove everything up to and including the body open tag
          $template = preg_replace(
            '/\s*<\/body>.*/ims',
            '',
            $template
          ); // remove everything after and including the body close tag
        }
      }
    }
    while(false);

    while(self::MergeVars($template))
    {
    }

    if(isset($_GET['callback']))
    {
      $output = json_encode(
        [
          'title'   => self::GetTitle(true),
          'content' => $template,
        ]
      );
      header('Content-Type: application/javascript');
      echo $_GET['callback'] . '(' . $output . ')';
      return;
    }

    echo $template;
  }

  static function IsInsideNoProcess($fullString, $position)
  {
    $str = strrev(substr($fullString, 0, $position));
    $str = strrev(substr($str, 0, strpos($str, strrev('<!-- NoProcess -->'))));
    return strpos($str, '<!-- /NoProcess -->') !== false;
  }

  static function MergeVars(&$string)
  {
    $start = $string;

    $pr = rtrim(PATH_REL_ROOT, '/');
    $string = preg_replace('/' . preg_quote($pr . $pr, '/') . '/', $pr, $string);

    if(preg_match_all('/{(.+)}/Ui', $string, $matches, PREG_PATTERN_ORDER))
    { // loop through all parser tags {.+}
      $searchArr = $matches[0];
      foreach($searchArr as $search)
      {
        // if contains another pragma then skip it, pick up post-merged on next pass.
        while(preg_match('/{(.+)}/Ui', $search, $res, 0, 1))
        {
          $search = $res[0];
        }

        foreach(self::$templateParsers as $ident => $arr)
        {
          if(!preg_match('/{' . $ident . '}/Ui', $search, $match))
          {
            continue;
          } // doesnt match this templateparser
          $data = isset($match[1]) ? $match[1] : false;
          $searchLen = strlen($search);
          $offset = 0;
          while(($pos = strpos($string, $search, $offset)) !== false)
          {
            $test = substr($string, 0, $pos); // only test on string up to match.
            // TODO:(done) Dont match inside fields!
            $noprocessPos = strrpos($test, '<!-- NoProcess -->');
            if($noprocessPos !== false)
            { // noprocess is found.
              // end noprocess found?
              $test = substr($test, $noprocessPos);
              if(strpos($test, '<!-- /NoProcess -->') === false)
              {
                // end noprocess not found- so skip this replacement
                $offset = $pos + $searchLen;
                continue;
              }
            }
            //if (self::IsInsideNoProcess($string,$pos)) { $offset = $pos + $searchLen; continue; }

            try
            {
              $replace = self::RunTemplateParser($ident, $data ? $data : null);
            }
            catch(Exception $e)
            {
              $replace = uErrorHandler::EchoException($e);
            }

            if($replace === null || $replace === false)
            {
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

  static function MergeFields(&$string, $row)
  {
    if(preg_match_all('/{([a-z]+)\.([^{]+)}/Ui', $string, $matches, PREG_PATTERN_ORDER))
    {
      $searchArr = $matches[0];
      $typeArr = isset($matches[1]) ? $matches[1] : false;
      $varsArr = isset($matches[2]) ? $matches[2] : false;
      foreach($searchArr as $k => $search)
      {
        $field = $varsArr[$k];
        switch($typeArr[$k])
        {
          case 'urlencode':
            $replace = $row[$field];
            $replace = rawurlencode($replace);
            $string = str_replace($search, $replace, $string);
            break;
          case 'field':
            $replace = $row[$field];
            $string = str_replace($search, $replace, $string);
            break;
          default:
        }
      }
    }
    while(utopia::MergeVars($string))
    {
    }
  }

  static $templateParsers = [];

  static function AddTemplateParser($ident, $function, $match = '.+', $catchOutput = false)
  {
    if(is_string($function) && strpos($function, '::') !== false)
    {
      $function = explode('::', $function);
    }
    if($match === '.*')
    {
      self::AddTemplateParser($ident, $function, '.+', $catchOutput);
      self::AddTemplateParser($ident, $function, '', $catchOutput);
      return;
    }
    $ident = preg_quote($ident);

    if($match)
    {
      $ident .= '\.(' . $match . ')';
    }
    else
    {
      $ident .= '()';
    }
    if(isset(self::$templateParsers[$ident]))
    {
      throw new Exception("$ident is already defined as a template parser.");
    }
    self::$templateParsers[$ident] = [$function, $catchOutput];
  }

  static function RunTemplateParser($ident, $data = null)
  {
    if(!isset(self::$templateParsers[$ident]))
    {
      return;
    }
    $parser = self::$templateParsers[$ident];

    if(!is_callable($parser[0]) && is_string($parser[0]))
    {
      return $parser[0];
    }

    $data = html_entity_decode($data);
    $args = [];
    if($data !== '')
    {
      $pairs = explode('&', $data);
      foreach($pairs as $pair)
      {
        if(strpos($pair, '=') === false)
        {
          $args[] = $pair;
          continue;
        }
        list($key, $val) = explode('=', $pair);
        $args[$key] = $val;
      }
    }
    if(is_assoc($args))
    {
      $args = [$args];
    }

    if($parser[1])
    {
      ob_start();
    }
    $replace = call_user_func_array($parser[0], $args);

    if($parser[1])
    {
      $replace = ob_get_clean();
    }
    return $replace;
  }

  static function parseVars($id)
  {
    $replacement = self::GetVar($id . ':before') . self::GetVar($id) . self::GetVar($id . ':after');
    return $replacement;
  }

  static function parseGet($id) { return isset($_GET[$id]) ? $_GET[$id] : ''; }

  static function parsePost($id) { return isset($_POST[$id]) ? $_POST[$id] : ''; }

  static function parseRequest($id) { return isset($_REQUEST[$id]) ? $_REQUEST[$id] : ''; }

  static function parseSession($id) { return isset($_SESSION[$id]) ? $_SESSION[$id] : ''; }

  static function parseConst($id) { return defined($id) ? constant($id) : ''; }

  static function SetRequest($query)
  {
    foreach($query as $k => $v)
    {
      $_REQUEST[$k] = $v;
    }
    return '';
  }

  static function SetGet($query)
  {
    foreach($query as $k => $v)
    {
      $_GET[$k] = $v;
    }
    return '';
  }

  static function PageNotFound(
    $title = '404 Not Found',
    $content = 'The page you requested could not be found. Return to the <a href="{home_url}">homepage</a>.'
  )
  {
    header("HTTP/1.0 404 Not Found", true, 404);
    utopia::SetTitle($title);
    if($title)
    {
      echo "<h1>$title</h1>";
    }
    if($content)
    {
      echo "<p>$content</p>";
    }
    self::AddMetaTag('robots', 'noindex');
    die();
  }

  static function SecureRedirect()
  {
    if(self::IsRequestSecure())
    {
      return;
    }

    $uri = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    if(parse_url($_SERVER['REQUEST_URI'], PHP_URL_SCHEME) != 'https')
    {
      header('Location: ' . $uri, true, 301);
      die();
    }
  }

  static function IsRequestSecure()
  {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off');
  }

  /*  MISC  */
  static function DebugMode($set = null)
  {
    if($set !== null)
    {
      $_SESSION['admin_debug_mode'] = (bool)$set;
    }
    return (isset($_SESSION['admin_debug_mode']) && $_SESSION['admin_debug_mode']);
  }

  static function EvalString($string)
  {
    if(!$string)
    {
      return $string;
    }

    $string = preg_replace('/\<\?\s/i', '<?php ', $string);

    ob_start();
    eval('?>' . $string . '<?php ');
    $string = ob_get_clean();

    return $string;
  }

  static function output_buffer($text)
  {
    utopia::AppendVar('content', $text);
    return '';
  }

  static function GetDomainName()
  {
    return $_SERVER['HTTP_HOST'];
  }

  static $siteurl = null;

  static function GetSiteURL()
  {
    if(!self::$siteurl)
    {
      self::$siteurl = rtrim(modOpts::GetOption('site_url'), '/');
    }
    return self::$siteurl;
  }

  static function GetRelativePath($path)
  {
    $path = realpath($path);
    $path = str_replace(PATH_ABS_ROOT, PATH_REL_ROOT, $path);
    $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
    return $path;
  }

  static function GetAbsolutePath($path)
  {
    if(strpos($path, PATH_REL_ROOT) === 0)
    {
      $path = substr_replace($path, PATH_ABS_ROOT, 0, strlen(PATH_REL_ROOT));
    }
    $path = realpath($path);
    if(!file_exists($path))
    {
      return false;
    }
    return $path;
  }

  private static $ajaxFunctions = [];

  static function RegisterAjax($ajaxIdent, $callback)
  {
    if(array_key_exists($ajaxIdent, self::$ajaxFunctions))
    {
      //ErrorLog(get_class($this)." cannot register ajax identifier '$ajaxIdent' because it is already registered.");
      return false;
    }

    self::$ajaxFunctions[$ajaxIdent]['callback'] = $callback;
    return true;
  }

  static function RunAjax($ajaxIdent)
  {
    if(!array_key_exists($ajaxIdent, self::$ajaxFunctions))
    {
      die("Cannot perform ajax request, '$ajaxIdent' has not been registered.");
    }

    $callback = self::$ajaxFunctions[$ajaxIdent]['callback'];

    // validate
    if(!is_callable($callback))
    {
      die("Callback function for ajax method '$ajaxIdent' does not exist.");
    }

    call_user_func($callback);

    utopia::Finish(); // commented why ?
    die();
  }

  static function AjaxUpdateElement($eleName, $html)
  {
    if(is_object($html))
    {
      return;
    }
    $enc = base64_encode($html);
    AjaxEcho('utopia.RedrawField("' . $eleName . '","' . $enc . '")');
  }

  static function GetMaxUpload($post = true, $upload_max = true)
  {
    if(!function_exists('let_to_num'))
    {
      function let_to_num($v)
      { //This function transforms the php.ini notation for numbers (like '2M') to an integer (2*1024*1024 in this case)
        $l = substr($v, -1);
        $ret = substr($v, 0, -1);
        switch(strtoupper($l))
        {
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
    if($post)
    {
      $post = let_to_num(ini_get('post_max_size'));
    }
    else
    {
      $post = null;
    }
    if($upload_max)
    {
      $upload_max = let_to_num(ini_get('upload_max_filesize'));
    }
    else
    {
      $upload_max = null;
    }

    return min($post, $upload_max);
  }

  static function ReadableBytes($size)
  {
    $arr = ['kb', 'mb', 'gb', 'tb', 'pb'];
    $i = 0;
    while($i < count($arr) && $size > 1000)
    {
      $size = $size / 1024;
      $i++;
    }
    return $size . $arr[$i - 1];
  }

  static function Cache_Check(
    $etag, $contentType, $filename = '', $modified = 0, $age = 2592000, $disposition = 'inline'
  )
  {
    header('Content-Type: ' . $contentType);
    $etag .= GZIP_ENABLED ? '-gzip' : '';
    $etag = '"' . $etag . '"';
    header("ETag: $etag");
    header("Expires: " . gmdate("D, d M Y H:i:s", time() + $age) . " GMT", true);
    header("Cache-Control: public, max-age=$age", false);

    $fn = empty($filename) ? '' : "; filename=\"$filename\"";
    header("Content-Disposition: " . $disposition . $fn);

    if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag)
    {
      header('HTTP/1.1 304 Not Modified', true, 304);
      header('Status: 304 Not Modified', true, 304);
      exit;
    }

    if($modified)
    {
      $lm = gmdate('r', $modified);
      header("Last-Modified: " . $lm, true);
      if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $lm)
      {
        header('HTTP/1.1 304 Not Modified', true, 304);
        header('Status: 304 Not Modified', true, 304);
        exit;
      }
    }
  }

  static function Cache_Output(
    $data, $etag, $contentType, $filename = '', $modified = 0, $age = 2592000, $disposition = 'inline'
  )
  {
    self::Cache_Check($etag, $contentType, $filename, $modified, $age, $disposition);
    echo $data;
    utopia::Finish();
  }

  static function Breakout($text)
  {
    self::CancelTemplate();
    die(print_r($text, true));
  }

  static function constrainImage($src, $maxW = null, $maxH = null, $enlarge = false)
  {
    if($maxW === null && $maxH === null)
    {
      return $src;
    }

    if(imageistruecolor($src))
    {
      imageAlphaBlending($src, true);
      imageSaveAlpha($src, true);
    }
    $srcW = imagesx($src);
    $srcH = imagesy($src);

    $width = $maxW && ($enlarge || $maxW <= $srcW) ? $maxW : $srcW;
    $height = $maxH && ($enlarge || $maxH <= $srcW) ? $maxH : $srcH;

    $ratio_orig = $srcW / $srcH;
    if($width / $height > $ratio_orig)
    {
      $width = $height * $ratio_orig;
    }
    else
    {
      $height = $width / $ratio_orig;
    }
    $maxW = $maxW ? $maxW : $width;
    $maxH = $maxH ? $maxH : $height;

    $img = imagecreatetruecolor($maxW, $maxH);
    $trans_colour = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $trans_colour);

    $offsetX = ($maxW - $width) / 2;
    $offsetY = ($maxH - $height) / 2;

    imagecopyresampled($img, $src, $offsetX, $offsetY, 0, 0, $width, $height, $srcW, $srcH);
    imagealphablending($img, true);
    imagesavealpha($img, true);

    return $img;
  }

  // converters
  static function strtotime($string)
  {
    $parsed = strptime($string, FORMAT_TIME);
    if($parsed === false)
    {
      $parsed = strptime($string, FORMAT_DATE);
    }
    if($parsed === false)
    {
      $parsed = strptime($string, FORMAT_DATETIME);
    }
    if($parsed !== false)
    {
      $parsed = mktime(
        $parsed['tm_hour'],
        $parsed['tm_min'],
        $parsed['tm_sec'],
        1,
        $parsed['tm_yday'] + 1,
        $parsed['tm_year'] + 1900
      );
    }
    else
    {
      $old = date_default_timezone_get();
      date_default_timezone_set('UTC');
      $parsed = strtotime($string);
      date_default_timezone_set($old);
    }
    return $parsed;
  }

  static function convDate($originalValue, $pkVal, $processedVal)
  {
    if(!$originalValue)
    {
      return '';
    }
    $t = self::strtotime($originalValue);
    return strftime(FORMAT_DATE, $t);
  }

  static function convTime($originalValue, $pkVal, $processedVal)
  {
    if(!$originalValue)
    {
      return '';
    }
    $t = self::strtotime($originalValue);
    return strftime(FORMAT_TIME, $t);
  }

  static function convDateTime($originalValue, $pkVal, $processedVal)
  {
    if(!$originalValue)
    {
      return '';
    }
    $t = self::strtotime($originalValue);
    $hasTime = ($t % 86400);
    if($hasTime)
    {
      return strftime(FORMAT_DATETIME, $t);
    }
    return strftime(FORMAT_DATE, $t);
  }

  static function convCurrency($originalValue, $pkVal, $processedVal, $rec, $fieldName)
  {
    $locale = DEFAULT_LOCALE;
    if($rec && isset($rec[$fieldName . '_locale']) && $rec[$fieldName . '_locale'])
    {
      $locale = $rec[$fieldName . '_locale'];
    }
    return self::money_format($originalValue, $locale);
  }

  static function money_format($originalValue, $locale = DEFAULT_LOCALE)
  {
    if(!is_numeric($originalValue))
    {
      return $originalValue;
    }
    if(!$locale)
    {
      $locale = DEFAULT_LOCALE;
    }
    $locales = uLocale::ListLocale(null, '%C', true);
    $c = null;
    if(isset($locales[$locale]))
    {
      $c = $locales[$locale];
    }
    else
    {
      foreach($locales as $l)
      {
        foreach($l as $v)
        {
          if($v === $locale)
          {
            $c = $l;
            break 2;
          }
        }
      }
    }
    if(!$c)
    {
      return $originalValue;
    }
    $dp = $originalValue - floor($originalValue) > 0 || $originalValue < 100 ? 2 : 0;
    $value = number_format($originalValue, $dp, $c['mon_decimal_point'], $c['mon_thousands_sep']);

    if($originalValue >= 0)
    {
      if($c['p_cs_precedes'])
      {
        if($c['p_sep_by_space'])
        {
          $value = ' ' . $value;
        }
        $value = $c['currency_symbol'] . $value;
      }
      else
      {
        if($c['p_sep_by_space'])
        {
          $value .= ' ';
        }
        $value .= $c['currency_symbol'];
      }
    }
    else
    {
      if($c['n_cs_precedes'])
      {
        if($c['n_sep_by_space'])
        {
          $value = ' ' . $value;
        }
        $value = $c['currency_symbol'] . $value;
      }
      else
      {
        if($c['n_sep_by_space'])
        {
          $value .= ' ';
        }
        $value .= $c['currency_symbol'];
      }
    }
    $value = mb_convert_encoding($value, 'HTML-ENTITIES', CHARSET_ENCODING);
    return $value;
  }

  static function stringify($array)
  {
    return array_combine($array, $array);
  }

  static function stripslashes_deep($value)
  {
    $value = is_array($value) ?
      array_map('utopia::stripslashes_deep', $value) :
      stripslashes($value);

    return $value;
  }

  static function compareVersions($ver1, $ver2)
  {
    if($ver1 == $ver2)
    {
      return 0;
    }

    //major.minor.maintenance.build
    preg_match_all('/([0-9]+)\.?([0-9]+)?\.?([0-9]+)?\.?([0-9]+)?/', $ver1, $matches1, PREG_SET_ORDER);
    $matches1 = $matches1[0];
    array_shift($matches1);
    preg_match_all('/([0-9]+)\.?([0-9]+)?\.?([0-9]+)?\.?([0-9]+)?/', $ver2, $matches2, PREG_SET_ORDER);
    $matches2 = $matches2[0];
    array_shift($matches2);

    if($matches1 == $matches2)
    {
      return 0;
    }
    while(count($matches1) < 4)
    {
      $matches1[] = 0;
    }
    while(count($matches2) < 4)
    {
      $matches2[] = 0;
    }
    foreach($matches1 as $k => $v)
    {
      if($v == $matches2[$k])
      {
        continue;
      }
      if($v < $matches2[$k])
      {
        return -1;
      }
      if($v > $matches2[$k])
      {
        return 1;
      }
    }
    return 0;
  }

  static function jsonTryDecode($value, $assoc = true)
  {
    if(!is_string($value) || !strlen($value))
    {
      return $value;
    }
    if(!preg_match('/(^\[.*\]$)|(^\{.*\}$)/', $value))
    {
      return $value;
    }
    $originalValue = $value;
    $value = json_decode($value, $assoc);
    if($value === null)
    {
      $value = $originalValue;
    }
    return $value;
  }

  static function checksum($val)
  {
    if(!is_string($val))
    {
      $val = json_encode($val);
    }
    return md5($val) . sha1($val);
  }

  static function GetMimeType($path)
  {
    $cType = null;
    if(function_exists('finfo_open') && defined('FILEINFO_MIME_TYPE'))
    {
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $cType = finfo_file($finfo, $path);
      finfo_close($finfo);
    }
    else if(function_exists('mime_content_type'))
    {
      $cType = mime_content_type($path);
    }
    else
    {
      $cType = exec("file -bi '$path'");
    }
    return $cType;
  }

  static function OutputPagination($pages, $pageKey = 'page', $spread = 2)
  {
    //$pages $pageKey
    if($pages <= 1)
    {
      return 1;
    }
    $parsed = parse_url($_SERVER['REQUEST_URI']);
    $args = isset($parsed['query']) ? $parsed['query'] : '';
    if(is_string($args))
    {
      parse_str($args, $args);
    }
    $args = utopia::stripslashes_deep($args);

    $page = isset($args[$pageKey]) ? $args[$pageKey] : 0;
    echo '<ul class="pagination">';
    if($page > 0)
    { // previous
      $args[$pageKey] = $page - 1;
      $rel = ['prev'];
      if(!$args[$pageKey])
      {
        unset($args[$pageKey]);
      }
      if($page - 1 == 0)
      {
        $rel[] = 'first';
      }
      echo '<li class="previous"><a rel="' . implode(
          ' ',
          $rel
        ) . '" class="btn uPaginationLink" href="' . $parsed['path'] . ($args ? '?' . http_build_query(
            $args
          ) : '') . '">Previous</a></li>';
    }

    $prespace = false;
    $postspace = false;
    for($i = 0; $i < $pages; $i++)
    {
      $args[$pageKey] = $i;
      $rel = [];
      if(!$args[$pageKey])
      {
        unset($args[$pageKey]);
      }
      if($i < $page - $spread && $i != 0)
      {
        if(!$prespace)
        {
          echo '<li>...</li>';
        }
        $prespace = true;
        continue;
      }
      if($i > $page + $spread && $i != ($pages - 1))
      {
        if(!$postspace)
        {
          echo '<li>...</li>';
        }
        $postspace = true;
        continue;
      }
      if($i == $page - 1)
      {
        $rel[] = 'prev';
      }
      if($i == $page + 1)
      {
        $rel[] = 'next';
      }
      if($i == 0)
      {
        $rel[] = 'first';
      }
      if($i == $pages - 1)
      {
        $rel[] = 'last';
      }
      echo '<li><a rel="' . implode(
          ' ',
          $rel
        ) . '" class="btn uPaginationLink" href="' . $parsed['path'] . ($args ? '?' . http_build_query(
            $args
          ) : '') . '">' . ($i + 1) . '</a></li>';
    }

    if($page < $pages - 1)
    { // next
      $args[$pageKey] = $page + 1;
      $rel = ['next'];
      if($page + 1 == $pages - 1)
      {
        $rel[] = 'last';
      }
      echo '<li class="next"><a rel="' . implode(
          ' ',
          $rel
        ) . '" class="btn uPaginationLink" href="' . $parsed['path'] . ($args ? '?' . http_build_query(
            $args
          ) : '') . '">Next</a></li>';
    }
    echo '</ul>';
    return $page + 1;
  }

  static function SanitiseValue(&$value, $type, $default = null, $isRegex = false)
  {
    $type = strtolower($type);
    if($type === 'null')
    {
      $type = 'NULL';
    }
    if($type === 'float')
    {
      $type = 'double';
    }
    if(($isRegex && preg_match($type, $value) > 0) || (gettype($value) !== $type))
    {
      if($default !== null)
      {
        $value = $default;
      }
      return false;
    }
    return true;
  }

  static function IsAjaxRequest()
  {
    if(array_key_exists('__ajax', $_REQUEST))
    {
      return true;
    }
    if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
    {
      return true;
    }

    return false;
  }
}
