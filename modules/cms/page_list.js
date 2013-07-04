
function RefreshIcons() {
	$('.ui-treesort-item:not(.ui-treesort-folder) > .cmsParentToggle').remove();
	$('.ui-treesort-folder').each(function () {
		var icon = $('.cmsParentToggle',this);
		if (!icon.length) icon = $('<span class="cmsParentToggle ui-widget ui-icon" style="width:16px; float:left"/>').prependTo(this);
		if ($('ul:visible',this).length)
			icon.removeClass('ui-icon-triangle-1-e').addClass('ui-icon-triangle-1-s');
		else
			icon.removeClass('ui-icon-triangle-1-s').addClass('ui-icon-triangle-1-e');
	});
}
function dropped() {
	RefreshIcons();
	data = serialiseTree();
	$.post('?__ajax=reorderCMS',{data:JSON.stringify(data)});
}
function serialiseTree() {
	var data = {};
	$('#tree li').each(function () {
		var parent = $(this).parents('.ui-treesort-item:first').attr('id');
		if (!parent) parent = '';
		data[$(this).attr('id')] = parent+':'+$(this).parents('ul:first').children('li').index(this);
	});
	return data;
}
function InitialiseTree() {
	$('#tree ul').not($('#tree ul:first')).hide();
	$('#tree').treeSort({init:RefreshIcons,change:dropped});
}
$(document).on('click','.cmsParentToggle',function (e) {
	$(this).parent('li').children('ul').toggle();
	RefreshIcons();
	e.stopPropagation();
});

$(function() {
	InitialiseTree();
});