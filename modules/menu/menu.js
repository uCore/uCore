$(function () {
	$('.dropdown .u-menu li').hover(
		function() { $('ul:first',this).show(); },
		function() { $('ul:first',this).hide(); }
	);
});