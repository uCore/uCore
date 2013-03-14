$(function () {
	$(document).on('click','.admin-bar .admin-menu .toggle',function() {
		$body = $('.admin-bar .admin-body .'+$(this).attr('rel'));
		if (!$body) return;
		$body.animate({height:'toggle',width:'toggle'});
	});
	$(document).on('click','.admin-bar .admin-toggle',function() { $('.admin-container').animate({width:'toggle'}); $(this).toggleClass('rotate'); });

	/*setInterval(function() {
		var top = parseInt($('html').css('padding-top'));
		if (!top) top = 0;
		top += $('.admin-bar').outerHeight();
		$('html').css('margin-top',top);
	},5);*/
});
