function moveMceToolbars(event,ed) {
	ed.onPostRender.add(function(ed, cm) { // move the toolbars post render
		$(".mceExternalToolbar").appendTo(".mceToolbarContainer");
		$(".mceExternalToolbar").click(function() {
			$(this).show();
		});
	});
	ed.onInit.add(function(ed, evt) { // hide the toolbars when leaving an editor
		tinymce.dom.Event.add(ed.getWin(), 'blur', function(e) {
			$('.mceExternalToolbar').hide();
		});
	});
};
$(document).on('tinyMceSetup',moveMceToolbars);

$(document).on('click','.page-publish',function(event) {
	if (!confirm('Any changes you have made will become visible to the public.  Do you wish to continue?')) {
		event.stopImmediatePropagation();
		event.preventDefault();
		return false;
	}
});
$(document).on('click','.page-revert',function(event) {
	if (!confirm('Reverting this page will reset all of your changes to the last published version.  Do you wish to continue?')) {
		event.stopImmediatePropagation();
		event.preventDefault();
		return false;
	}
});