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