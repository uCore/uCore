<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0">
<meta name="robots" content="noindex"/>
<link href="{const.PATH_REL_CORE}themes/admin/fontello/css/ucore-symbols.css" rel="stylesheet" type="text/css">
<link href="http://fonts.googleapis.com/css?family=Lato:400,700,400italic,700italic" rel="stylesheet" type="text/css">
<script src="{const.PATH_REL_CORE}themes/admin/html5shiv.js"></script>
<script>
$(function(){
	if ($('nav ul li').length) {
		$(document).on('click touchstart','nav .icon-menu',function() { $('body').toggleClass('open'); return false; });
	}
});
</script>
</head>
<body class="u-admin">

<header><?php
	$o = utopia::GetInstance('UserProfileDetail'); $r = $o->LookupRecord();
	if ($r) echo '<i class="icon-user"></i> Hi, '.$r['visible_name'].' | ';
	echo '<a href="{home_url}">Website</a>';
	if ($r) echo ' | {logout}';
?></header>
<div id="contentWrap">
	<nav>
		<span class="icon-menu mobile"></span>
		{UTOPIA.modlinks}
	</nav>
	<div id="content">
		{utopia.content}
	</div>
</div>

</body>
</html>
