if (!utopia) utopia = {};
utopia.ShowNotice = function (msg) {
	if (!$('.uNotices').length) $('body').append('<div class="uNotices-wrap"><div class="uNotices"/></div>');
	$msg = $(msg).hide();
	$('.uNotices').append($msg);

	var words = $msg.text().match(/\w+/g).length;
	var ms = (words / 180) * 60 * 1000; // 180 words per minute
	
	$msg.slideDown(500).delay(1000+ms).slideUp(500,function(){console.log(this);$(this).remove()});
}