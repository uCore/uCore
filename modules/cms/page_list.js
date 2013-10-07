
function RefreshIcons() {
	$('.ui-treesort-item:not(.ui-treesort-folder) > .cmsParentToggle').remove();
	$('.ui-treesort-folder').each(function () {
		var icon = $('.cmsParentToggle',this);
		if (!icon.length) icon = $('<span class="cmsParentToggle"/>').prependTo(this);
		if ($('ul:visible',this).length)
			icon.addClass('open');
		else
			icon.removeClass('open');
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
	$('#tree ul ul').hide();
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