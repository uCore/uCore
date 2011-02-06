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
	$("<img>").attr("src", PATH_REL_CORE+'images/utopia-systems-hover.png');
	
	if (typeof(DD_belatedPNG) != 'undefined') DD_belatedPNG.fix('*');

	$(".tabGroup").tabs();
	$(".tabGroup").bind("tabsshow", function(event, ui) { 
	    window.location.hash = ui.tab.hash;
	})
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
    ReloadWithForm(order);
  });

	$(window).bind("beforeunload", function(){ uf=null; });
//	$(window).unload(function () { alert('unloading'); uf=null; });
	//$('<form style="display:none" id="internal_FuF" method="get" action="">'+(gup('uuid')!='' ? '<input type="hidden" name="uuid" value="'+gup('uuid')+'" />' : '')+'</form>').appendTo('body');
  $('<form style="display:none" id="internal_FuF" method="get" action="'+window.location.pathname+'"></form>').appendTo('body');
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
	
	InitJavascript.run();
});
$(window).load(function () { InitJavascript.run(); });

function RefreshTableSorters() {
	$('.datalist:has(SPAN.pager)').each(function () {
		var pagerSpan = $('SPAN.pager',this);
		$(this).tablesorterPager({positionFixed:false,container: $("#"+pagerSpan.attr('id'))}); 
	});
}

function RefreshTables() {
	$('.datalist TBODY TR:even').addClass('shadeRow');
	$('.datalist TBODY TR:odd').removeClass('shadeRow');
}

function UpdateSelectedLinks() {
  $('a').each(function() {
    if (!$(this).attr('href')) return;
    var _href = $(this).attr('href');
    if ((_href != window.location.pathname) && (_href == '/' || window.location.pathname.indexOf(_href.slice(0,-4)) != 0)) return;
	var classname = (_href == window.location.pathname) ? 'active-link' : 'active-link-parent';
    if ($(this).parent('li').length) {
      $(this).parent('li').addClass(classname);
    } else {
      $(this).addClass(classname);
    }
  });
}

function UIButtons() {
	$('.btn').not('.ui-button').button();
}

var InitJavascript = {
	_functs: [InitDatePickers, InitAutocomplete, RefreshTables, UpdateSelectedLinks, UIButtons],
	add: function (f) {
		this._functs.push(f);
	},
	run: function () {
		for (f in this._functs) {
			this._functs[f]();
		}
	}
}

$('.fdb-uf').live('change',_fieldChange);
$('input[type=button].fdb-uf').live('click',_fieldChange);

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
		//this.onclick = null;
		//isFilterField = $(this).attr('name').substr(0,3).toLowerCase() == '_f_';

//		console.log( $(this).metadata());
		//$(this).autoComplete({ajax:url,minChars:0,requestType:'get',postVar:'q',autoFill:0,mustMatch:false/*(!isFilterField)*/,selectFirst:false,max:50,useCache:true,matchSubset:true,postData:{gv:$(this).metadata().gv}})
		//$(this)
			source: function(request, response) {
				if ( request.term in cache ) {
					response( cache[ request.term ] );
					return;
				}
	/*			if ($(this).attr('autocomplete') != undefined) {
					response({});
					return;
				}*/

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
			minLength:0,delay:200
		}).each(function () {
			$(this).data( "autocomplete" )._renderItem = function( ul, item ) {
				var desc = item.desc ? '<br><span style="font-size:0.7em">' + item.desc + '</span>' : '';
				return $( "<li></li>" )
					.data( "item.autocomplete", item )
					.append( "<a>" + item.label + desc + "</a>" )
					.appendTo( ul );
			};
		});//.change(function(event, ui){
			// ui.item
			//data, $li
		//	uf(this,data.value);
	//	});
//		.result(function(event, data, formatted) {
//			if (isFilterField)
//				ReloadFilters();
//			else
//				uf(this,data[1]);
//		});
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
//	if (!empty(gup('uuid')))
//		$('#internal_FuF').append('<input type="hidden" name="uuid" value="'+gup('uuid')+'">');
	
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
		newFilters[name] = newVal;// $('#internal_FuF').append('<input type="hidden" name="'+name+'" value="'+newVal+'">');
		}
	});
//console.log(updated,newFilters);
	if (!updated) return;
	// need to track existing filters, if they dont have a filter box then we must re-add them

	ReloadWithForm(newFilters);
}

function ReloadWithForm(items, ignoreCurrent) {
  $("#internal_FuF").empty();
  //console.log('before',items);
  if (!ignoreCurrent) {
    arr = window.location.search.substr(1).split('&');
    $(arr).each(function () {
      var arr = this.split('=');
      var name = arr[0];
      var val = decodeURIComponent(arr[1].replace(/\+/g,' '));
      if (empty(name)) return;
      if (typeof(items[name]) !== 'undefined') return;
  //    for (i in processed) if (processed[i] == name) return;
  //    if (name.toLowerCase() == 'uuid')
  //      newFilters[name] = val;//$('#internal_FuF').prepend('<input type="hidden" name="'+name+'" value="'+val+'">');
  //    else
        items[name] = val;//$('#internal_FuF').append('<input type="hidden" name="'+name+'" value="'+val+'">');
    });
  }
  //console.log('after',items);
  for (i in items) {
    if (!items[i]) continue;
    if (i.toLowerCase() == 'uuid') // always put uuid first
      $('#internal_FuF').prepend('<input type="hidden" name="'+i+'" value="'+items[i]+'">');
    else
      $('#internal_FuF').append('<input type="hidden" name="'+i+'" value="'+items[i]+'">');
  }
  
	if ($("#internal_FuF").children().length == 0) {
		window.location.assign(window.location.pathname+window.location.hash);
	} else {
		//$("#internal_FuF").attr('action',($("#internal_FuF").attr('action') ? $("#internal_FuF").attr('action') :'') +window.location.hash);
		$("#internal_FuF").submit();
	}
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

var isUpdating = [];
function StoppedUpdating(ele) {
	for (e in isUpdating) {
		if (isUpdating[e].element == ele) delete isUpdating[e];
	}
}
function _fieldChange(e) { uf(this); }
function uf(ele, forcedValue, hourglassEle) {
	for (e in isUpdating) {
		if (isUpdating[e].element == ele) return;
	}
	isUpdating.push({element:ele,val:forcedValue});
	
	if (!hourglassEle) hourglassEle = ele;
	if (typeof(hourglassEle) == 'string') hourglassEle = document.getElementById(hourglassEle);
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
    //if (isUpdating == true) { /*alert('Please wait a few moments for the previous operation to complete.');*/ $(ele).val(ele.defaultValue); return; }
    //isUpdating = true;
    
/*    if (typeof(ele) == 'string') {
    	//alert();
    	ele = $('#'+ele);// document.getElementById(ele);
	}*/
	//oldVal = $(ele).val();
	var eleData = '';
	
	var eleName = ele;
	if (typeof(ele) != 'string') {
		eleName = $(ele).attr('name');
	}
	
	var eleVal = '';

	if (forcedValue != undefined || forcedValue != null) {
		eleVal = forcedValue;
	} else {
		if (ele.tagName == 'INPUT' && ele.getAttribute('type').toLowerCase() == 'checkbox') {// if checkbox, ensure checked value = 1 and unchecked = 0
			if ($(ele).attr('checked')) eleVal = '1'; else eleVal = '0';
		//	eleVal = val;
		} //else if ($(ele).attr('type') == 'button') { // manual serialise
		//	eleVal = escape($(ele).val());
		//}// else 
		//	eleVal = $(ele).serialize();
	    if (empty(eleVal))
	    	eleVal = $(ele).val();
	}
	
	eleVal = encodeURIComponent(Base64.encode(eleVal));

	eleData = "__ajax=updateField&"+escape(eleName)+"="+eleVal;
	
	targetPage = window.location.toString().replace(window.location.hash,'');
	
//	$('#statusOverlay').show();
//	alert(targetPage);
	
	if ($(ele).attr('type') == 'file') {
		$(ele).ajaxFileUpload({
			type:'POST',
			async: false,
			cache: false,
			url:targetPage,
			data:{'__ajax':'updateField'},
			dataType: "script",
			success: function (msg) {
				eval(msg);
				$(hourglass).remove();
				//$(ele).after(' DONE');
                //alert('test');
			},
            complete: function(){
				StoppedUpdating(ele);
				$('.auto-complete-list').hide();
				$(hourglass).remove();
				$('.fdb-uf').change(_fieldChange);
 //               $('#statusOverlay').hide();
            }
		});
		return;
	}

	$.ajax({
		type: "POST",
		async: false,
		cache: false,
		url: targetPage,
		data: eleData,
		dataType: "script",
		success: function(msg){   
			//$(ele).after(' DONE');
//			alert(msg);
	//		eval(msg);
/*			if (msg.substr(0,8) == '<script>') {
//				eval(msg);
				return true;
			}

			if (msg.substr(0,6) == 'reload') {
				pk = msg.split(':');
				if (pk[1] !== undefined){
					z = pk[1].split("=");
					val = new Object();
					val[z[0]] = z[1];
					url = qsUpdate(window.location.href,val,Array('newrec'));
				} else
					url = window.location;
				if (window.location.href == url)
					window.location.reload(true);
				else
					window.location.replace(url);
			} else if (msg == 'success') {
				$(ele).css('background',null);
			} else if (msg != '') {
				ErrorLog(msg);
				$(ele).css('background','red');
			}*/
		},
		error: function(obj,type,e){      
			if (empty(e)) return;
			$(ele).css('background','red');
			ErrorLog(type+': '+e.message);
		},
		complete: function(){
			//$(ele).after(' DONE');
			StoppedUpdating(ele);
			$('.auto-complete-list').hide();
			$(hourglass).remove();
			$('.fdb-uf').change(_fieldChange);
//			$('#statusOverlay').hide();
		}
	});
}
