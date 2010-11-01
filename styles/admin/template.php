<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
{UTOPIA.head}
</head>

<body>

<div id='titleWrap'><div id="title"><h1 style="margin:0;text-align:right;">Utopia Core</h1><h2 style="margin:0;text-align:right;">{UTOPIA.title}</h2></div></div>

<div id="maincontent"><a id="top"></a>
<?php
if (internalmodule_AdminLogin::IsLoggedIn(ADMIN_USER)) {
  //echo "<a class=\"btn\" href=\"".PATH_REL_CORE."index.php\">Admin Home</a>";
  $arr = array();
  $children = FlexDB::GetChildren('internalmodule_Admin');
  foreach ($children as $links) {
    foreach ($links as $child) { 
      if ($child['fieldLinks']) continue;
      $opts = CallModuleFunc($child['moduleName'],'GetOptions');
      if (!flag_is_set($opts,IS_ADMIN) || flag_is_set($opts,NO_NAV)) continue;
      $url = CallModuleFunc($child['moduleName'],'GetURL');
      $title = CallModuleFunc($child['moduleName'],'GetTitle');
      if (!$url || !$title) continue;
      $arr[] = "<a class=\"btn\" href=\"$url\">$title</a>";
    }
  }
  
  echo '<ul id="adminButtons"><li>'.implode('</li><li>',$arr).'</li></ul>';
}
?>
{UTOPIA.breadcrumb}
{UTOPIA.content}
</div>

<div id="framecontentTop">
<div id="headerGrad"><img alt="" src="<?php echo FlexDB::GetTemplateDir(true); ?>images/longgrad.jpg" width="100%" height="100%" /></div>
<div id="logo"></div>
</div>

<div id="framecontentBottom"><div id='footer'>
<div id="goTop">Valid <a target="_blank" href="http://validator.w3.org/check?uri=referer">XHTML</a>/<a target="_blank" href="http://jigsaw.w3.org/css-validator/check/referer">CSS</a> | <a href="#top">[^] Top</a></div>
</div></div>

</body>
</html>
