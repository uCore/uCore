<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta name="robots" content="noindex"/>
<?php uCSS::LinkFile(dirname(__FILE__).'/jquery-ui/jquery-ui-1.9.1.custom.min.css',0);?>
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
