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
$(function(){utopia.Initialise.run();});
$(document).ajaxComplete(function() {utopia.Initialise.run();});