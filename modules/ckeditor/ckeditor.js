CKEDITOR.disableAutoInline = true;
CKEDITOR.config.toolbar_Basic = [
	[ 'Format', 'Styles', 'Bold', 'Italic', 'Underline', 'Strikethrough', '-', 'NumberedList', 'BulletedList', '-', 'RemoveFormat' ]
];
CKEDITOR.config.toolbar_Advanced = [
	[ 'Format', 'Styles', 'Bold', 'Italic', 'Underline', 'Strikethrough', '-', 'NumberedList', 'BulletedList', '-', 'RemoveFormat' ],
	[ 'Image', 'Flash', 'Table', 'Source' ]
];
CKEDITOR.config.toolbar = 'Advanced';
CKEDITOR.config.filebrowserBrowseUrl = FILE_BROWSE_URL;
CKEDITOR.config.filebrowserWindowWidth = '800';
CKEDITOR.config.filebrowserWindowHeight = '600';

function _ckOnUpdate(info) {
	var newData = info.editor.getData();
	if (newData != info.listenerData) {
		info.editor.updateElement();
		if ($(info.editor.element.$).hasClass('uf')) {
			uf(info.editor.element.$,newData);
		}
	}
	info.editor.removeListener('blur',_ckOnUpdate);
	info.editor.on('blur',_ckOnUpdate,null,newData);
}
CKEDITOR.on('instanceReady',function(info) {
	var oldData = info.editor.getData();
	info.editor.on('blur',_ckOnUpdate,null,oldData);
});

	
function initCKE() {
	function initEditor(ele,config) {
		if (!$(ele).hasClass('ckEditReplace')) return false;
		if ($(ele).data('toolbar')) config.toolbar = $(ele).data('toolbar');
	}
	try {
		CKEDITOR.replaceAll(initEditor);
		CKEDITOR.inlineAll();
	} catch (e) {}
}
utopia.Initialise.add(initCKE);