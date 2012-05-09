// JavaScript Document

var isFiltering = false;
function FilterOnEnter(sender) {
    isFiltering = true;
	if (sender.tagName == 'SELECT') return;
	$(sender).removeClass('utopia-filter-default');
	if (sender.value == $(sender).attr('title'))
        sender.value = '';
}

function FilterOnLeave(sender) {
    isFiltering = false;
    if ($(sender).attr('name').indexOf('_f_') > -1) StartFilterTimer();
	if (sender.tagName == 'SELECT') return;

	if (sender.value == '') $(sender).val($(sender).attr('title'));
	if (sender.value == $(sender).attr('title')) $(sender).addClass('utopia-filter-default');
}

$(document).ready(function(){
	//$('.btn').button();
	// preload hourglass image
	$("<img>").attr("src", PATH_REL_CORE+'images/hourglass.png');
	//$("<img>").attr("src", PATH_REL_CORE+'images/utopia-systems-hover.png');
	
	$('form').append('<input type="submit" style="width:0;height:0;border:0;padding:0;margin:0;" />');

	$('li:first','ul').addClass('first-item');
	$('li:last','ul').addClass('last-item');

	$(".tabGroup").tabs();
	$(".tabGroup").bind("tabsshow", function(event, ui) { 
		var scrollPos = $(window).scrollTop();
		window.location.hash = ui.tab.hash;
		$(window).scrollTop(scrollPos);
	});
//	$('#btnOptions').bind('click',function () { showOptions() });
  $('th.sortable').live('click',function (e) {
    var fieldname = $(this).attr('rel');
    if (!fieldname) return;

    var arr = fieldname.split('|');

    var fieldname = arr[0];
    var mID = arr[1];
    var fullCurrent = gup('_s_' + mID);
    var currentSort = '';
    
    // if shift held, use the existing sort.
    if (e.shiftKey) currentSort = fullCurrent;
    // else if field is already in the filter, retain it.
    else if (fullCurrent.match(new RegExp(fieldname, 'i')) !== null) { currentSort = fullCurrent.match(new RegExp(fieldname+'( (ASC|DESC))?','i'))[0];}
    
    var order = {};    
    // if blank, just add order
    if (!currentSort) {
      order['_s_'+mID] = fieldname;
    
    } else if (currentSort.match(new RegExp(fieldname+' ASC', 'i')) !== null) {
      // replace to DESC
      order['_s_'+mID] = currentSort.replace(new RegExp(fieldname+' ASC', 'i'),fieldname+' DESC');
      
    } else if (currentSort.match(new RegExp(fieldname+' DESC', 'i')) !== null) {
      // Replace to ASC
      order['_s_'+mID] = currentSort.replace(new RegExp(fieldname+' DESC', 'i'),fieldname);
      
    } else if (currentSort.match(new RegExp(fieldname, 'i')) !== null) {
      // append DESC
      order['_s_'+mID] = currentSort.replace(new RegExp(fieldname, 'i'),fieldname+' DESC');
      
    } else {
      // else append to end.
      order['_s_'+mID] = currentSort + ', '+fieldname;
    }
    ReloadWithItems(order);
  });

	$(window).bind("beforeunload", function(){ uf=null; });
//	$(window).unload(function () { alert('unloading'); uf=null; });
  $(".uFilter").each(function() {
		FilterOnLeave(this);
		$(this).bind('click', function (event) {if (!$.browser.msie) this.focus(); event.stopPropagation(); return false;});
		$(this).bind('focus', function (event) {FilterOnEnter(this);});
		$(this).bind('blur' , function (event) {FilterOnLeave(this);});
		//$(this).bind('change', function (event) {ReloadFilters();});
		
		$(this).bind('keydown', function (event) { if ($(this).attr('name').indexOf('_f_') == -1) return; if ((event.charCode == '13' || event.keyCode == '13') && (!$(this).is('TEXTAREA') && !$(this).is('SELECT'))) this.blur(); });

		 // allow text to be selected in the table headers without it breaking the antiselect code of tablesorter2
		$(this).bind('selectstart', function (event) {event.stopPropagation(); return true;});
	});
	
	window.onscroll = function() {
    // Thanks to Johan SundstrÃ¶m (http://ecmanaut.blogspot.com/) and David Lantner (http://lantner.net/david) 
    // for their help getting Safari working as documented at http://www.derekallard.com/blog/post/conditionally-sticky-sidebar
    if( window.XMLHttpRequest ) { // IE 6 doesn't implement position fixed nicely...
//      if (document.documentElement.scrollTop > 328 || self.pageYOffset > 328) {
        $('#uPanel').css({'position':'fixed','top':0,'left':0});
//        $('#uPanel').css('top', '0');
//      } else if (document.documentElement.scrollTop < 328 || self.pageYOffset < 328) {
//        $('#content_sub').css('position', 'absolute');
//        $('#content_sub').css('top', '328px');
//      }
    }
  }
	/*
	$.tablesorter.addParser({
		// set a unique id 
		id: 'datetime',   
		is: function(s) {
 detected      
			return false;   
		},        
		format: function(s) {
			// format your data for normalization          
			var date = Date.fromSqlFormat(FORMAT_DATETIME,s);
			if (date != null) return date.getTime();
			var date = Date.fromSqlFormat(FORMAT_DATE,s);
			if (date != null) return date.getTime();
			var date = Date.fromSqlFormat(FORMAT_TIME,s);
			if (date != null) return date.getTime();
			return null;
/*			var datetimeRegex	= new RegExp("([0-9]{2})/([0-9]{2})/([0-9]{4}) ([0-9]{2}):([0-9]{2}):([0-9]{2})(\.[0-9]+)?");
			var dateRegex		= new RegExp("([0-9]{2})/([0-9]{2})/([0-9]{4})");
			var timeRegex		= new RegExp("([0-9]{2}):([0-9]{2}):([0-9]{2})(\.[0-9]+)?"); * /
			
			arr = datetimeRegex.exec(s);
			if (!empty(arr)) return Date.UTC(arr[3],arr[2],arr[1],arr[4],arr[5],arr[6],arr[7]);
			
			arr = dateRegex.exec(s);
			if (!empty(arr)) return Date.UTC(arr[3],arr[2],arr[1],0,0,0,0);
			
			arr = timeRegex.exec(s);
			if (!empty(arr)) return Date.UTC(0,0,0,arr[1],arr[2],arr[3],arr[4]);
			return 0;
		},     
		// set type, either numeric or text     
		type: 'numeric'
	});
	$('.datalist').each(function () {
		$(this).tablesorter({cancelSelection:true, widgets: ['zebra'],debug:false});
/*		
		// does a pager span exist?
		var pagerSpan = $("SPAN.pager",this).eq(0);
		if (pagerSpan.length > 0) {
			var pagerID = 'pager_'+$(this).attr('id');
			pagerSpan.attr('id',pagerID);
			pagerSpan.html('<form><img src="/internal/js/ui.tablesorter/addons/pager/icons/first.png" class="first"/><img src="/internal/js/ui.tablesorter/addons/pager/icons/prev.png" class="prev"/><input type="text" class="pagedisplay"/><img src="/internal/js/ui.tablesorter/addons/pager/icons/next.png" class="next"/><img src="/internal/js/ui.tablesorter/addons/pager/icons/last.png" class="last"/><select class="pagesize"><option selected="selected"  value="10">10</option><option value="25">25</option><option value="50">50</option><option value="999999999">All</option></select></form>');
			RefreshTableSorters();
		}* /
	});*/
	
	$('img.calendar_trigger').bind('click', function (event) {event.stopPropagation(); return true;});

	$("[name^=sql]").bind('keydown', function (event) {if ((event.charCode == '13' || event.keyCode == '13') && (!$(this).is('TEXTAREA'))) this.blur(); });
});

function RefreshTableSorters() {
	$('.datalist:has(SPAN.pager)').each(function () {
		var pagerSpan = $('SPAN.pager',this);
		$(this).tablesorterPager({positionFixed:false,container: $("#"+pagerSpan.attr('id'))}); 
	});
}

function RefreshTables() {
	$('.datalist TBODY TR:even').removeClass('shadeRow');
	$('.datalist TBODY TR:odd').addClass('shadeRow');
}

function UpdateSelectedLinks() {
	var uuid = getParameterByName('uuid');
	var queryArgs = window.location.search.substring(1).split('&');
	$('a').each(function() {
		if (!$(this).attr('href')) return;
		var _href = $(this).attr('href');
		var _hrefArr = _href.split('?');
		var _hrefPath = _hrefArr[0];
		var _hrefArgs = _hrefArr[1] ? _hrefArr[1].split('&') : [];
		var linkUUID = getParameterByName('uuid',_href);

		var classname = '';
		if (uuid || linkUUID) {
			classname = (uuid == linkUUID) ? 'active-link' : '';
		} else {
			if (_href == PATH_REL_ROOT) return;
			if (window.location.pathname.indexOf(_hrefPath) != 0) return;
			var exact = true;
			if (_hrefPath == window.location.pathname) {
				for (var i=0;i<queryArgs.length;i++) {
					if (!queryArgs[i]) continue;
					var item = queryArgs[i].split('=');
					var v = getParameterByName(item[0],_href);
					if (v != item[1]) exact = false;
				}
				for (var i=0;i<_hrefArgs.length;i++) {
					if (!_hrefArgs[i]) continue;
					var item = _hrefArgs[i].split('=');
					var v = getParameterByName(item[0]);
					if (v != item[1]) exact = false;
				}
			}
			classname = ((_hrefPath == window.location.pathname) && exact) ? 'active-link' : 'active-link-parent';
		}
		if (!classname) return;
		
		if ($(this).parent('li').length) {
			$(this).parent('li').addClass(classname);
			$(this).parent('li').parents('li').addClass('active-link-parent');
		} else {
			$(this).addClass(classname);
		}
	});
}

function UIButtons() {
	$('.btn').not('.ui-button').button();
}

var InitJavascript = {
	_functs: [],
	add: function (f) {
		if ($.inArray(f,this._functs) > -1) return;
		this._functs.push(f);
		f();
	},
	run: function () {
		for (f in this._functs) {
			this._functs[f]();
		}
	}
}
$(function () {
	InitJavascript.add(InitDatePickers);
	InitJavascript.add(InitAutocomplete);
	InitJavascript.add(RefreshTables);
	InitJavascript.add(UpdateSelectedLinks);
	InitJavascript.add(UIButtons);
});

function InitDatePickers() {
	$('.dPicker').each(function () {
		if ($(this).attr('hasDatepicker') != undefined) return;
		//var newFormatDate = FORMAT_DATE.replace('%d','dd').replace('%b','M').replace('%Y','yy');
		$(this).datepicker({changeMonth: true, changeYear: true, yearRange:'-90:+20',attachTo:'body',dateFormat:FORMAT_DATE,speed: ''});//, autoPopUp: 'button', buttonImage: '/images/datepicker.gif', buttonImageOnly: true });
	});
    $.datepicker.formatDate = DatePickerFormatDate;
    $.datepicker.parseDate = DatePickerParseDate;
}
function DatePickerParseDate (format, value, shortYearCutoff, dayNamesShort, dayNames, monthNamesShort, monthNames) {
    return Date.fromSqlFormat(format, value);
}
function DatePickerFormatDate (format, date, dayNamesShort, dayNames, monthNamesShort, monthNames) {
    return date.sqlFormat(format);
}

function InitAutocomplete() {
	var cache = {};
	$('.autocomplete').autocomplete({
		source: function(request, response) {
			if ( request.term in cache ) {
				response( cache[ request.term ] );
				return;
			}

			request.gv = $(this.element).metadata().gv;
			$.ajax({
				url: PATH_REL_CORE+'index.php?__ajax=Suggest',
				dataType: "json",
				data: request,
				success: function( data ) {
					cache[ request.term ] = data;
					response( data );
				}
			});
		},
		select: function (event,ui) {
			// is filter?
			$(this).trigger('change');
		},
		minLength:0,delay:200
	}).each(function () {
		$(this).data( "autocomplete" )._renderItem = function( ul, item ) {
			var desc = item.desc ? '<br><span style="font-size:0.7em">' + item.desc + '</span>' : '';
			return $( "<li></li>" )
				.data( "item.autocomplete", item )
				.append( "<a>" + item.label + desc + "</a>" )
				.appendTo( ul );
		};
	});
}
function gup( name ){  name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");  var regexS = "[\\?&/]"+name+"=([^&#]*)";  var regex = new RegExp( regexS );  var results = regex.exec( window.location.href );  if( results == null )    return "";  else  return decodeURIComponent(results[1].replace(/\+/g,' ')); }

function empty(subject) {
	return (typeof(subject)=="undefined" || subject == null || subject == '');
}
/**
*
*  Base64 encode / decode
*  http://www.webtoolkit.info/
*
**/

var Base64 = {

    // private property
    _keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

    // public method for encoding
    encode : function (input) {
        var output = "";
        var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
        var i = 0;

        input = Base64._utf8_encode(input);

        while (i < input.length) {

            chr1 = input.charCodeAt(i++);
            chr2 = input.charCodeAt(i++);
            chr3 = input.charCodeAt(i++);

            enc1 = chr1 >> 2;
            enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
            enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
            enc4 = chr3 & 63;

            if (isNaN(chr2)) {
                enc3 = enc4 = 64;
            } else if (isNaN(chr3)) {
                enc4 = 64;
            }

            output = output +
            this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
            this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);

        }

        return output;
    },

    // public method for decoding
    decode : function (input) {
        var output = "";
        var chr1, chr2, chr3;
        var enc1, enc2, enc3, enc4;
        var i = 0;

        input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

        while (i < input.length) {

            enc1 = this._keyStr.indexOf(input.charAt(i++));
            enc2 = this._keyStr.indexOf(input.charAt(i++));
            enc3 = this._keyStr.indexOf(input.charAt(i++));
            enc4 = this._keyStr.indexOf(input.charAt(i++));

            chr1 = (enc1 << 2) | (enc2 >> 4);
            chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
            chr3 = ((enc3 & 3) << 6) | enc4;

            output = output + String.fromCharCode(chr1);

            if (enc3 != 64) {
                output = output + String.fromCharCode(chr2);
            }
            if (enc4 != 64) {
                output = output + String.fromCharCode(chr3);
            }

        }

        output = Base64._utf8_decode(output);

        return output;

    },

    // private method for UTF-8 encoding
    _utf8_encode : function (string) {
        string = string.replace(/\r\n/g,"\n");
        var utftext = "";

        for (var n = 0; n < string.length; n++) {

            var c = string.charCodeAt(n);

            if (c < 128) {
                utftext += String.fromCharCode(c);
            }
            else if((c > 127) && (c < 2048)) {
                utftext += String.fromCharCode((c >> 6) | 192);
                utftext += String.fromCharCode((c & 63) | 128);
            }
            else {
                utftext += String.fromCharCode((c >> 12) | 224);
                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                utftext += String.fromCharCode((c & 63) | 128);
            }

        }

        return utftext;
    },

    // private method for UTF-8 decoding
    _utf8_decode : function (utftext) {
        var string = "";
        var i = 0;
        var c = c1 = c2 = 0;

        while ( i < utftext.length ) {

            c = utftext.charCodeAt(i);

            if (c < 128) {
                string += String.fromCharCode(c);
                i++;
            }
            else if((c > 191) && (c < 224)) {
                c2 = utftext.charCodeAt(i+1);
                string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                i += 2;
            }
            else {
                c2 = utftext.charCodeAt(i+1);
                c3 = utftext.charCodeAt(i+2);
                string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                i += 3;
            }

        }

        return string;
    }

}

var reloadTimer = null;
function StartFilterTimer() {
    clearTimeout(reloadTimer);
    reloadTimer = setTimeout("ReloadFilters()",2000);
}
function ReloadFilters() {
  if (isFiltering == true) return;
  var newFilters = {};
  //console.log(newFilters);
	//var oldVal;
	var updated = false;
	//var processed = new Array();
	
	$(".uFilter").each(function () {
		var name = $(this).attr('name');
		if (empty(name)) return;
		//processed.push(name);
		//alert(gup(escape(name)));
		//oldVal = decodeURIComponent(gup(escape(name))).replace(/\+/g, ' ');
		//if (oldVal == '')
		//	oldVal = decodeURIComponent(gup(name)).replace(/\+/g, ' ');
		var oldVal = gup(name);
		var newVal;
	
		if (($(this).val() == $(this).attr('title')) || ($(this).attr('type') == 'checkbox' && !$(this).attr('checked')) || ($(this).attr('type') == 'radio' && !$(this).attr('checked')))
			newVal = '';
		else
			newVal = String($(this).val());

		valHasChanged = (oldVal !== newVal);
		//alert(name + ": " +  oldVal + " -> " + newVal);
		//console.log(name,'o:'+oldVal,'n:'+newVal);
		if (valHasChanged) { updated = true;
		//if (!empty(newVal))
		newFilters[name] = newVal;
		}
	});
//console.log(updated,newFilters);
	if (!updated) return;
	// need to track existing filters, if they dont have a filter box then we must re-add them

	ReloadWithItems(newFilters);
}

function ReloadWithItems(items, ignoreCurrent) {
  if (!ignoreCurrent) {
    arr = window.location.search.substr(1).split('&');
    $(arr).each(function () {
      var arr = this.split('=');
      if (empty(arr[1])) return;
      var name = arr[0];
      var val = decodeURIComponent(arr[1].replace(/\+/g,' '));
      if (empty(name)) return;
      if (typeof(items[name]) !== 'undefined') return;
        items[name] = val;
    });
  }
  var newQS = '';
  for (i in items) {
    if (!items[i]) continue;
    if (i.toLowerCase() == 'uuid') // always put uuid first
      newQS = i + '=' + items[i] + '&' + newQS;
    else
      newQS = newQS + i + '=' + items[i] + '&';
  }
  if (newQS != '') newQS = '?' + newQS;
  window.location.assign(window.location.pathname+newQS+window.location.hash);
}

/*
function showOptions() {
	$('#optionsMenu').toggle('fast');
	return;
	if ($('#optionsMenu:visible').length > 0)
		$('#optionsMenu').css('display','none');
	else
		$('#optionsMenu').css('display',$('#btnOptions').css('display'));
}
*/

$(function() { // call on docready to allow cancelling events to bind first.
	$(document).on('change','.uf',_fieldChange);
	$(document).on('click','input[type=button].uf, .btn.uf',_fieldChange);
	$(document).on('click','.btn',function(event) {event.stopPropagation();});
	$(document).on('click','.btn-submit',function(event) {var frm = $(this).closest('form'); if (frm.length) return frm[0].submit();});
	$(document).on('click','.btn-reset',function(event) {var frm = $(this).closest('form'); if (frm.length) return frm[0].reset();});
});
var isUpdating = [];
function StoppedUpdating(ele) {
	for (e in isUpdating) {
		if (isUpdating[e].element == ele) delete isUpdating[e];
	}
}
function _fieldChange(event) { uf(this); return false; }
function uf(ele, forcedValue, hourglassEle) {
	for (e in isUpdating) {
		if (isUpdating[e].element == ele) return;
	}
	isUpdating.push({element:ele,val:forcedValue});
	
	if (!hourglassEle) hourglassEle = ele;
	if (typeof(hourglassEle) == 'string') hourglassEle = document.getElementById(hourglassEle);
	if (hourglassEle) {
		var hourglass = $('<img align="texttop" src="'+PATH_REL_CORE+'images/hourglass.png">');
		var offset = $(hourglassEle).offset();
		hourglass.css({
			position:'absolute',
			'z-index':5000,
			top: offset.top,
			left: offset.left + hourglassEle.offsetWidth
		});
		$('body').append(hourglass);
		$('body').addClass('progressCursor');
		if (!$(hourglassEle).is(':file')) $(hourglassEle).attr('disabled','disabled');
	}

	// timeout for autocomplete
	setTimeout(function(){_uf(ele, hourglass);},200);
}
function _uf(ele,hourglass) {
	var forcedValue = '';
	for (e in isUpdating) {
		if (isUpdating[e].element == ele) { 
			forcedValue = isUpdating[e].val;
			break;
		}
	}

	var eleName = ele;
	if (typeof(ele) != 'string') {
		eleName = $(ele).attr('name');
	}
	
	var eleVal = '';

	if (forcedValue != undefined || forcedValue != null) {
		eleVal = forcedValue;
	} else {
		var eleVal = [];
		$(ele).siblings('[name*="'+eleName+'"]').andSelf().each(function () {
			if (this.tagName == 'INPUT' && $(this).is(':checkbox')) {
				if ($(this).is(':checked')) eleVal.push($(this).val());// else eleVal.push('');
			} else {
				eleVal.push($(this).val());
			}
		});
		switch (eleVal.length) {
			case 0: eleVal = ''; break;
			case 1: eleVal = eleVal[0]; break;
		}
	}

	var eleData = {'__ajax':'updateField'}

	targetPage = window.location.toString().replace(window.location.hash,'');
	
	if ($(ele).attr('type') == 'file') {
		$(ele).ajaxFileUpload({
			type:'POST',
			async: true,
			cache: false,
			url:targetPage,
			data:eleData,
			dataType: "script",
			success: function (msg) {
				eval(msg);
				InitJavascript.run();
				$(hourglass).remove();

				StoppedUpdating(ele);
				$('.auto-complete-list').hide();
//				$('.uf').change(_fieldChange);
			}
		});
		return;
	}

	eleData[eleName] = eleVal;

	$.ajax({
		type: "POST",
		async: true,
		cache: false,
		url: targetPage,
		data: eleData,
		dataType: "script"
	}).done(function(msg){   
		$(hourglass).remove();
		InitJavascript.run();
	}).fail(function(obj,type,e){      
		$(hourglass).remove();
		if (empty(e)) return;
		$(ele).css('background','red');
		ErrorLog(type+': '+e.message);
	}).always(function(){
		$(hourglass).remove();
		StoppedUpdating(ele);
		$('.auto-complete-list').hide();
	});
}
