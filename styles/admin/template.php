<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="robots" content="noindex"/>
<?php utopia::AddCSSFile('/uCore/styles/admin/jquery/jquery-ui-1.8.12.ucore.css');?>
</head>
<body>
<div id="wrap">
	<div id="header"><div id="innerheader">
	</div></div>
	<div id="contentWrap">
		<div id="nav">{UTOPIA.modlinks}</div>
		<div id="content">
			{utopia.content}
		</div>
	</div>
</div>
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-8710319-10']);
  _gaq.push(['_trackPageview']);
  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>
</body>
</html>
