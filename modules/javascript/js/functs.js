function addClass(e,c) {
		if(new RegExp("(^|\\s)" + c + "(\\s|$)").test(e.className)) return;
		e.className += ( e.className ? " " : "" ) + c;
}

function removeClass(e,c) {
		e.className = !c ? "" : e.className.replace(new RegExp("(^|\\s*\\b[^-])"+c+"($|\\b(?=[^-]))", "g"), "");
}

/* anti-close mecha */
var navi = false;
function onWindowUnload(e) {
	e = e || window.event;
	if (isUpdating > 0)
		e.cancelBubble = true;
		return false;
	//	alert('Some fields have not yet been saved. Press ok to wait.');
	// still updating ?
	
	if (isUpdating > 0)
		e.returnValue = 'Closing or refreshing this window will not save changes to '+isUpdating+' fields. These fields will by marked by a light blue background.';
}
function onClick(e) {
	e = e || window.event;
	navi = true; setTimeout('navi=false',500);
}
function onKeyDown(e) {
	e = e || window.event;
	if (e.keyCode!=13) return true;
	
	if (e.srcElement) {
		if (e.srcElement.tagName == 'TEXTAREA') return true;
		if (e.srcElement.onchange) e.srcElement.onchange();
		e.keyCode=9;
		return true;
	}
	if (e.target) {
		if (e.target.tagName == 'TEXTAREA') return;
		if (e.target.onchange) e.target.onchange(e);
		// cancel first
		e.preventDefault();
		e.stopPropagation();
		return;
	}
}

function roundNumber(num, dec) {
    var result = Math.round(num*Math.pow(10,dec))/Math.pow(10,dec);
    return result;
}
function HVAL(a, b) {
//	if (a[fdTableSort.pos] == chr(5)) { return 0; exit; };
	return fdTableSort.sortText(a, b);
}
function HVALPrepareData(tdNode, innerText) {
	try {
	return tdNode.getAttribute('hval');
	} catch (e) {}
}

function addEvent(obj, type, fn) {
	if( obj.attachEvent ) {
		obj["e"+type+fn] = fn;
		obj[type+fn] = function(){obj["e"+type+fn]( window.event );};
		obj.attachEvent( "on"+type, obj[type+fn] );
	} else
		obj.addEventListener( type, fn, false );
}

function qsUpdate(url,newVals,unset) {
	newPairsArray = new Object();

// strip hash from url and re-add it later
	hashPos = url.indexOf('#');
	hash = '';
	if (hashPos > -1)
		hash = url.substr(url.indexOf('#'));
	url = url.replace(hash,'');
	
	urlParts = url.split("?");
	baseUrl = urlParts[0];
	if (unset == null) unset = Array();
	
	qs = urlParts[1];
	if (qs){
		pairsArray = qs.split('&');
		for (i = 0; i < pairsArray.length; i++) {
			keyval = pairsArray[i].split('=');
			dontSet = false;
			for (j = 0; j < unset.length; j++) {
				if (unset[j] == keyval[0]) dontSet = true;
			}
			if (dontSet == true) continue;
			key = keyval[0];
			val = keyval[1];
			newPairsArray[key] = val;
		}
	}
	
	for (i in newVals) {
		newPairsArray[i] = newVals[i];
	}
	
	newParts = [];
	for (key in newPairsArray) {
		if (newPairsArray[key] == null) continue;
		if (newPairsArray[key].length == 0) continue;
		newParts.push(key + '=' + newPairsArray[key]);
	}
	
	newQs = newParts.join('&');
	if (newQs.length >0) newQs = '?' + newQs;
	if (hash == '#') hash = '';
	newUrl = baseUrl + newQs + hash;
	return newUrl;
}

function ErrorLog(text) {
	alert(text);
	//$('#errors').append('<div>'+text+'</div>');
	//$('#errbutton').show();
}

function URLEncode (clearString) {
  var output = '';
  var x = 0;
  clearString = clearString.toString();
  var regex = /(^[a-zA-Z0-9_.]*)/;
  while (x < clearString.length) {
    var match = regex.exec(clearString.substr(x));
    if (match != null && match.length > 1 && match[1] != '') {
    	output += match[1];
      x += match[1].length;
    } else {
      if (clearString[x] == ' ')
        output += '+';
      else {
        var charCode = clearString.charCodeAt(x);
        var hexVal = charCode.toString(16);
		if (hexVal.length < 2) hexVal = '0'+hexVal;
        output += '%' + hexVal.toUpperCase();
      }
      x++;
    }
  }
  return output;
}

function rf(sender) {
	sender.form.submit();
	return;
	frm = sender.form;
	$(':input',sender.form).each(function (i){
		if ($(this).val() == '' || $(this).attr('emptyVal') == $(this).val()) $(this).hide();// $(this).removeAttr('name'); // 
	})
	frm.submit();
}

function nav(url,newWindow)
{
	if (newWindow == true) {
		window.open(url);
		return;
	}
	window.location = url;
	return;
	if (url.substr(0,1) == '#') { window.location = url; return; }
	
	
	var f = document.createElement('form');
	document.body.appendChild(f);

// create url	
	if (url.substr(0,1) == '?') {
		qpos = window.location.href.search(/\?/);
		uri = window.location.href.substr(0,qpos);
		qs = url.substr(1).split('&');
	} else {
		qpos = url.search(/\?/);
		uri = url.substr(0,qpos);
		qs = url.substr(qpos+1).split('&');
	}
	
	// process query string
	for (i=0;i<qs.length;i++) {
		q = qs[i].split('=');
		
		var input = document.createElement('input');
		input.name = q[0];
		input.value= q[1];
		f.appendChild(input);
	}
	
	f.action = uri;
	
	f.submit();
	return;
	var f = document.createElement('form');
	document.appendChild(f);
	f.action = '/moo';
	f.method = 'get';
	
	var i = document.createElement('input');
	f.appendChild(i);
	i.name='uuid';
	i.value=uuid;
	
	f.submit();
}

function pausecomp(millis) 
{
var date = new Date();
var curDate = null;

do { curDate = new Date(); } 
while(curDate-date < millis);
}

function pause(ms) {
	var st = new Date();
	st.add('s',ms/1000);
	
	do {} while (pauseUntil(st) == false);
}

function pauseUntil(stopTime) {
	var date = new Date();
	if (date >= stopTime) return true;
	return false;
}

//$(document).ready(function(){
//	if ($('#errors').html() != '') $('#errbutton').show();


	
//	$("table.datalist TBODY TD").each(DrawLink);//.hover(DataCellHover,DataCellHoverOut);
	 
//    $("img").ifixpng('/pixel.gif');
//	$("div#statusOverlay").hide();
//});


function formatRes(data) {
	alert(data);
//	alert (data[0]);
//alert(position);
//return true;
	// update field with position
//	return data[1];
}

// simulate link?
function DataCellHover() {
	if (typeof(GetCellLink) == 'undefined') return;
	cLink = GetCellLink(this);
	if (empty(cLink)) return;
	
//	$(this).wrapInner('<a href="'+cLink+'"></a>'); return;
	
	$(this).addClass('link');
	$(this).bind('click',function () {nav(cLink)});
}

function DataCellHoverOut() {
	if (typeof($) == 'undefined') return;
	$(this).removeClass('link');
}

function GetCellLink(cell) {
	if (empty(cell) || empty($(cell).text())) return;
	var url = $(cell).attr('l_url');
	var filter = $(cell).attr('l_fltr');
	var parent = cell;
	while ((empty(url) || empty(filter)) && !$(parent).is('tbody') ) {
		parent = $(parent).parent();
		url = url ? url : $(parent).attr('l_url');// ? $(this).attr('l_url') : url;
		filter = filter ? filter : $(parent).attr('l_fltr');// ? $(this).attr('l_fltr') : filter;
	}
	if (empty(url) || empty(filter)) return; // cannot create url
	
	return url + '&' + filter;
}

function DrawLink() {
	if (empty(this) || empty($(this).text())) return;
	
	cellLink = GetCellLink(this);
	if (empty(cellLink)) return; // cannot create url

	if ($(this).children().not("br").length > 0) {
		$(this).children().not("br").dblclick(function () { nav(GetCellLink(this)); });
		return;
	}
	
	newTag = '<a href="'+cellLink+'"></a>';//.addClass('tableCellHoverLink').css({'width':'100%','display':'block'});
	//alert(newTag); 
//	$(this).wrapInner(newTag);
	$(this).html('<a href="'+cellLink+'">'+$(this).html()+'</a>');

	return; /// below is old
	
	$("table.datalist TBODY").each(function () {
		var url = $(this).attr('l_url');
		var filter = $(this).attr('l_fltr');
		$('TR',this).each(function () {
			url = $(this).attr('l_url') ? $(this).attr('l_url') : url;
			filter = $(this).attr('l_fltr') ? $(this).attr('l_fltr') : filter;
			$('TD',this).each(function () {
				var fUrl = $(this).attr('l_url') ? $(this).attr('l_url') : url;
				var fFilter = $(this).attr('l_fltr') ? $(this).attr('l_fltr') : filter;

				if (fUrl == undefined || fFilter == undefined) return true; // cannot create url
				
				$(this).html('<a href="'+fUrl+'&'+fFilter+'">'+$(this).html()+'</a>');
				//$(this).wrapInner('<a href="'+fUrl+'&'+fFilter+'"></a>');
			});
		});
	});
}


function pause(endTime, callback) {
//  if (time >= endTime) callback();
}

/* This script and many more are available free online at
The JavaScript Source!! http://javascript.internet.com
Created by: Justas | http://www.webtoolkit.info/ */
var Url = {
     // public method for URL encoding
     encode : function (string) {
          return escape(this._utf8_encode(string));
     },

     // public method for URL decoding
     decode : function (string) {
          return this._utf8_decode(unescape(string));
     },

     // private method for UTF-8 encoding
     _utf8_encode : function (string) {
          string = string.replace(/\r\n/g,"\n");
          var utftext = "";

          for (var n = 0; n < string.length; n++) {
               var c = string.charCodeAt(n);
               if (c < 128) {
                    utftext += String.fromCharCode(c);
             } else if((c > 127) && (c < 2048)) {
                  utftext += String.fromCharCode((c >> 6) | 192);
                  utftext += String.fromCharCode((c & 63) | 128);
             } else {
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
               } else if((c > 191) && (c < 224)) {
                    c2 = utftext.charCodeAt(i+1);
                    string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                    i += 2;
               } else {
                    c2 = utftext.charCodeAt(i+1);
                    c3 = utftext.charCodeAt(i+2);
                    string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                    i += 3;
               }
          }
        return string;
     }
}


function zeroPad(num,count)
{
	var numZeropad = num + '';
	while(numZeropad.length < count) {
		numZeropad = "0" + numZeropad;
	}
	return numZeropad;
}