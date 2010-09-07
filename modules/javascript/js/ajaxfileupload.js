jQuery.fn.ajaxFileUpload = function(settings) {
	afuFinished = function() {
		data = jQuery(this).contents().find("body").html();
		if (settings.success)
			settings.success(data);
		
		$(iFrame).remove();
		$(form).remove();
	}
	
	var frameID = 'submitForm_'+(new Date()).getTime();
	var iFrame = jQuery('<iframe style="display:none" id="'+frameID+'" name="'+frameID+'"></iframe>').appendTo('body');
	iFrame.load(afuFinished);
	
	extras = '';
	for (key in settings.data) extras += '<input type="hidden" name="'+key+'" value="'+settings.data[key]+'">';
	
	var form = jQuery('<form style="display:none" action="'+settings.url+'" target="'+frameID+'" method="post" enctype="multipart/form-data">'+extras+'</form>').appendTo('body');

	this.each(function () {
		jQuery(this).appendTo(form);
	});
	form.submit();
};