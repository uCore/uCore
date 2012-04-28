$(function () {
	$(document).delegate('.admin-bar .admin-menu .toggle','click',function() {
		$body = $('.admin-bar .admin-body .'+$(this).attr('rel'));
		if (!$body) return;
		$body.animate({height:'toggle',width:'toggle'});
	});
	var top = parseInt($('html').css('margin-top'));
	if (!top) top = 0;
	$emp = $('.admin-menu li:empty:visible').hide(); // hide any empty menu items (assuming dynamic content) - outerHeight wont include them.
	top += $('.admin-menu li:first').outerHeight();
	$emp.show(); // show empty menu items again
	$('html').css('margin-top',top);
});
