var chooseWidgetDialog = null;
function ChooseWidget() {
	if (!chooseWidgetDialog) {
		chooseWidgetDialog = $('<div><p>Create a <a style="font-weight:bold" class="newWidgetLink" href="">new widget</a> or select an existing widget from the list below.</p><div class="widgets"></div></div>');
		var ew = $('.widgets',chooseWidgetDialog);
		$.getJSON('?__ajax=getWidgets',function (data) {
			$('.newWidgetLink',chooseWidgetDialog).attr('href',data[0]);
			data = data[1];
			var cType = null;
			$(data).each(function (i) {
				if (!data[i]['block_type']) return;
				if (!cType || cType != data[i]['block_type']) { cType = data[i]['block_type']; ew.append('<h2>'+cType+'</h2>'); }
				var btn = $('<span>'+data[i]['block_id']+'</span>').button();
				btn.click(function () { InsertWidget(data[i]['block_id']); chooseWidgetDialog.dialog('close'); });
				ew.append(btn);
			});
		});
		chooseWidgetDialog.dialog({autoOpen:false, modal:true});
	}

	// open widget selection
	chooseWidgetDialog.dialog('open');
}

function InsertWidget(id) {
	$.get('?__ajax=getWidgetPlaceholder&id='+id,function (data) {
		tinyMCE.execCommand('mceInsertContent',false,data);
	});
}
