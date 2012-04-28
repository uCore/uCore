$(function () {
	$(document).delegate('.admin-bar .admin-menu .toggle','click',function() {
		$body = $('.admin-bar .admin-body .'+$(this).attr('rel'));
		if (!$body) return;
		$body.animate({height:'toggle',width:'toggle'});
	});
	var top = parseInt($('html').css('margin-top'));
	if (!top) top = 0;
	top += $('.admin-menu').height();
	$('html').css('margin-top',top);
});
