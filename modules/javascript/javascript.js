var utopia = utopia ? utopia : {};
utopia.Initialise = {
	_functs: [],
	add: function (f) {
		if ($.inArray(f,this._functs) > -1) return;
		this._functs.push(f);
	},
	run: function () {
		for (f in this._functs) {
			this._functs[f]();
		}
	}
}

utopia.RedrawField = function(selector,encVal) {
	$sel = $(selector);
	var curval = $sel.html(),
		newval = Base64.decode(encVal);
	if (curval == newval) return;
	$sel.html(newval);
}

$(function(){utopia.Initialise.run();});
$(document).ajaxComplete(function() {utopia.Initialise.run();});


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

function getParameterByName(name,querystring) {
	if (!querystring) querystring = window.location.search;
	var match = RegExp('[?&]' + name + '=([^&#]*)')
		.exec(querystring);
	return match && decodeURIComponent(match[1].replace(/\+/g, ' '));
}

function ErrorLog(text) {
	alert(text);
}
