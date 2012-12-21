if (!utopia) utopia = {};
utopia.ShowNotice = function (msg) {
	if (!$('.uNotices').length) $('body').append('<div class="uNotices-wrap"><div class="uNotices"/></div>');
	$msg = $(msg).hide();
	$('.uNotices').append($msg);
	$msg.slideDown(500).delay(2500).slideUp(500,function(){console.log(this);$(this).remove()});
}