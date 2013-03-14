var chooseWidgetDialog = null;
function ChooseWidget() {
	if (!chooseWidgetDialog) {
		chooseWidgetDialog = $('<div><p>Create a <a style="font-weight:bold" class="newWidgetLink" href="">new widget</a> or select an existing widget from the list below.</p><div class="widgets"></div><div id="widgetPreview" style="padding:10px"></div></div>');
		var $ew = $('.widgets',chooseWidgetDialog);
		// select box exists? - onchange, show preview
		if (!$('#widgetSelector',$ew).length) $ew.prepend('<select id="widgetSelector"><option></option></select>');
		var $wSel = $('#widgetSelector',$ew).change(function() {
			$.get('?__ajax=getParserContent&ident=widget&data='+$(this).val(),function (data) {
				$('#widgetPreview').html('<p style="font-weight:bold">Preview</p>'+data);
			});
		});

		$.getJSON('?__ajax=getWidgets',function (data) {
			$('.newWidgetLink',chooseWidgetDialog).attr('href',data[0]);
			data = data[1];
			var cType = null;

			$(data).each(function (i) {
				if (!data[i]['block_type']) return;
				if (!cType || cType != data[i]['block_type']) { cType = data[i]['block_type']; $wSel.append('<optgroup label="'+cType+'" />'); }
				var $grp = $('optgroup[label="'+cType+'"]',$wSel);
				$grp.append('<option>'+data[i]['block_id']+'</option');
			});
		});
		chooseWidgetDialog.dialog({autoOpen:false, modal:true,buttons: {
			"Insert Widget": function() {
				InsertWidget($wSel.val());
				$( this ).dialog( "close" );
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		}});
	}

	// reset view
	$('#widgetPreview').html('');
	$('#widgetSelector').val('');

	// open widget selection
	chooseWidgetDialog.dialog('open');
}

function InsertWidget(id) {
	$.get('?__ajax=getWidgetPlaceholder&id='+id,function (data) {
		CKEDITOR.currentInstance.insertText(data);
	});
}

$(document).on('click','.widget-field',function () {
	CKEDITOR.currentInstance.insertText('{'+($(this).data('fieldname'))+'}');
});