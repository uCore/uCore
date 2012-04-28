// handle the toolbar show/hide with events
$(document).on('click','*',function(event) {
	if ($(this).closest('.mceExternalToolbar').length) return false;
	$('.mceExternalToolbar').hide();
});

function moveMceToolbars(event,ed) {
	// move the toolbars post render
	ed.onPostRender.add(function(ed, cm) {
		$(".mceExternalToolbar").appendTo(".mceToolbarContainer");
	});
};
$(document).on('tinyMceSetup',moveMceToolbars);

// create 'cancel action' events for publish and revert - so the user has chance to abort
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