<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="robots" content="noindex"/>
<?php utopia::AddCSSFile(dirname(__FILE__).'/jquery/jquery-ui-1.8.12.ucore.css');?>
</head>
<body>
<div id="wrap">
	<div id="header"><div id="innerheader">
	</div></div>
	<div id="contentWrap">
		<div id="nav">{UTOPIA.modlinks}</div>
		<div id="content">
			{notices}
			{utopia.content}
		</div>
	</div>
</div>
</body>
</html>
