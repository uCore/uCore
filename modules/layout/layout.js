// JavaScript Document
var ajaxWaitCount = 0;

function dropDiv() {
	if (parseInt($(this).css('top')) < 0)
		$(this).css('top','0px');
	if (parseInt($(this).css('left')) < 0)
		$(this).css('left','0px');
		
//	alert('action=set&id='+this.id+'&top='+$(this).css('top')+'&left='+$(this).css('left'));
	$.ajax({
		type: "POST",
		cache: false,
		url: window.location.toString(),
		data: '__ajax=posQuery&action=set&id='+this.id+'&top='+$(this).css('top')+'&left='+$(this).css('left'),
		success: function(msg){
			if (msg != '')
				ErrorLog('Error Setting Position: '+msg);
		},
		error: function(obj,type,e){
			if (empty(e)) return;
			ErrorLog(type+': '+e.message);
		},
		complete: function(){
			//alert("$('*#"+ele.id+"').css('background','');");
			//setTimeout("$('*#"+ele.id+"').css('background','');",3000);
		}
	});
}

function findDivPos(ele) {
	$("div#statusOverlay").show();
	$.ajax({
		type: "POST",
		cache: false,
		url: window.location.toString(),
		data: '__ajax=posQuery&action=get&id='+ele.id,
		success: function(msg) {
			a=msg.split('&');
			for (i = 0; i<a.length; i++) {
				arr = a[i].split('=');
				if (arr[0] == 'top' || arr[0] == 'left') {
					if (arr[1].replace('px','') < 0)
						arr[1] = '0px';
					$(ele).css(arr[0],arr[1]);
				}
			}
		},
		error: function(obj,type,e){
			if (empty(e)) return;
			ErrorLog(type+': '+e.message);
		},
		complete: function(){
			ajaxWaitCount--;
			if (ajaxWaitCount == 0)
				$("div#statusOverlay").hide();
			//alert("$('*#"+ele.id+"').css('background','');");
			//setTimeout("$('*#"+ele.id+"').css('background','');",3000);
		}
	});
}

var draggable = false;
function ToggleDraggable() {
	if (!draggable) {
		$(".draggable").draggable({
								   preventionDistance: 25,
								   grid:[25,25],
								   containment:[0,0,99999,99999],
								   stop:dropDiv
								   });
		draggable = true;
		$(".draggable").draggableEnable();
		alert('Frames are now draggable');
	} else {
		$(".draggable").draggableDisable();
		draggable = false;
		alert('Frames can no longer be dragged');
	}
}

function CreateTabbedContent() {
	if (!USE_TABS) return false;
	
	$('.tabGroup').each(function () {
		var tabs = $(this).tabs(); 
		if ($(this).hasClass('verticalTab')) {
			$(tabs).addClass('ui-tabs-vertical ui-helper-clearfix');
			$("li",$(this)).removeClass('ui-corner-top').addClass('ui-corner-left');
		}
		tabs.bind("tabsshow", function(event, ui) { 
			window.location.hash = ui.tab.hash;
		});
		
		
		return;
		var containerID = $(this).attr('id')
		var tabContainer = $(this);
		var tabs = new Array();
		var tabCount = 0;
		$('[id^='+containerID+']').each(function () {
			id = $(this).attr('id');
			if (id == containerID) return;
			
			if (empty(id)) return false;
			var tabPos = $(this).metadata().tabPosition;
	//alert(id + ' ' + tabPos);
			if (!empty(tabPos)) {
				if (!empty(tabs[tabPos]))
					tabs.splice(tabPos+1,0,id);
				else
					tabs[tabPos] = id;
			} else {
				tabs[tabCount] = id;
				tabCount++;
			}
		});
		
		for (i=tabs.length;i>=0;i--)
			if (empty(tabs[i])) tabs.splice(i,1);
			
		if (tabs.length > 1) { // only 1 tab, no need for tabs
			//$('.tabGroup').eq(0).before('<div id="ContentTabs"><ul></ul></div>');
			//var tabContainer = ('#ContentTabs');
			var tabUL = $('UL',tabContainer);
			$(tabs).each(function () {
				if (empty(this)) return false;
				var tabContent = $('#'+this);
				tabTitle = tabContent.metadata().tabTitle;
				if (empty(tabTitle)) return false;
				
	            if (tabContent.is('a')) {
	                $(tabUL).append('<li><a target="_self" href="'+tabContent.attr('href')+'"><span>'+tabTitle+'</span></a></li>');
	            } else {
				    $(tabUL).append('<li><a href="#'+this+'"><span>'+tabTitle+'</span></a></li>'); //$("TH",this).text()
	            }
	//			tabContent.removeAttr('id').wrap('<div id="'+this+'"></div>');
				tabContent.appendTo(tabContainer);
	//			tabContent.css('position','static');
	//			if ($.browser.msie)
	//				$('#'+this).css('margin-top','20px');
			});
			
			tabContainer.tabs();//.bind('select.ui-tabs',function(event, ui) { window.location = ui.tab; return true; });
	//		return true;
		}
	});
	
	return true;
}

$(document).ready(function(){
	// read positions
	if (!CreateTabbedContent()) {
		$(".draggable").each(function () {
			$(this).css('position','absolute');
			ajaxWaitCount++;
			findDivPos(this);
		});
	}	
});