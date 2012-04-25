$(function () {
	$(document).delegate('.admin-bar .admin-menu .toggle','click',function() {
		$body = $('.admin-bar .admin-body .'+$(this).attr('rel'));
		if (!$body) return;
		$body.animate({height:'toggle',width:'toggle'});
	});
	$('html').css('margin-top',parseInt($('html').css('margin-top'))+$('.admin-menu').height());
});
