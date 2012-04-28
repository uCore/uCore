function moveMceToolbars(event,ed) {
	ed.onPostRender.add(function(ed, cm) {
		$(".mceExternalToolbar").appendTo(".mceToolbarContainer");
		$(".mceExternalToolbar").click(function() {
			$(this).show();
		});
	});
};
//InitJavascript.add(moveMceToolbars);
$(document).on('tinyMceSetup',moveMceToolbars);