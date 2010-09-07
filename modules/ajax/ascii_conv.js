/*
 * JavaScript ASCII Converter
 *
 * do whatever you want with this.
 *
 * useage: have a form with text inputs named "chars", "dec", "hex", "bin" and
 * "delimiter". on suitable events call from_char(), from_dec(), from_hex() or
 * from_bin() with the form as the only argument.
 *
 * TPO 2001/2004
 */

var charArray = new Array(
	' ', '!', '"', '#', '$', '%', '&', "'", '(', ')', '*', '+', ',', '-',
	'.', '/', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', ':', ';',
	'<', '=', '>', '?', '@', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I',
	'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W',
	'X', 'Y', 'Z', '[', '\\', ']', '^', '_', '`', 'a', 'b', 'c', 'd', 'e',
	'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's',
	't', 'u', 'v', 'w', 'x', 'y', 'z', '{', '|', '}', '~', '', 'Ç', 'ü',
	'é', 'â', 'ä', 'à', 'å', 'ç', 'ê', 'ë', 'è', 'ï', 'î', 'ì', 'Ä', 'Å',
	'É', 'æ', 'Æ', 'ô', 'ö', 'ò', 'û', 'ù', 'ÿ', 'Ö', 'Ü', 'ø', '£', 'Ø',
	'×', 'ƒ', 'á', 'í', 'ó', 'ú', 'ñ', 'Ñ', 'ª', 'º', '¿', '®', '¬', '½',
	'¼', '¡', '«', '»', '_', '_', '_', '¦', '¦', 'Á', 'Â', 'À', '©', '¦',
	'¦', '+', '+', '¢', '¥', '+', '+', '-', '-', '+', '-', '+', 'ã', 'Ã',
	'+', '+', '-', '-', '¦', '-', '+', '¤', 'ð', 'Ð', 'Ê', 'Ë', 'È', 'i',
	'Í', 'Î', 'Ï', '+', '+', '_', '_', '¦', 'Ì', '_', 'Ó', 'ß', 'Ô', 'Ò',
	'õ', 'Õ', 'µ', 'þ', 'Þ', 'Ú', 'Û', 'Ù', 'ý', 'Ý', '¯', '´', '­', '±',
	'_', '¾', '¶', '§', '÷', '¸', '°', '¨', '·', '¹', '³', '²', '_', ' ');

var hex_digits = new Array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
		'A', 'B', 'C', 'D', 'E', 'F');


function charToByte(c)
{
	var i;
	for(i=0; i < charArray.length; i++) {
		if(c == charArray[i]) return i+32;
	}
	return 0;
}

function byteToChar(n)
{
	if(n < 32 || n > 255) return " ";
	return charArray[n-32];
}

function byteToHex(n)
{
	return hex_digits[(n >> 4) & 0xf] + hex_digits[n & 0xf];
}

function byteToBin(n)
{
	var ret_str = "";
	var i;
	for(i=7; i>=0; i--) {
		ret_str += (n >> i) & 1;
	}
	return ret_str;
}



function clean_numstr(raw_str, base)
{
	var ret_str = "";
	var c = "";
	var i;
	for(i=0; i < raw_str.length; i++) {
		c = raw_str.charAt(i);
		if(c == "0" || parseInt(c, base) > 0) {
			ret_str += c;
		}
	}
	return ret_str;
}

function isNothing(str, theForm, form_bit)
{
	if(str == "") {
		alert("This field evaluates to nothing!");
		return false;
	}
	eval(form_bit);
	return true;
}



function hex_from_dec(theForm)
{
	var dec_str = clean_numstr(theForm.dec.value, 10);
	var delimiter = theForm.delimiter.value;
	var hex_str = "";
	var num_str = "";
	var i = 0, n;

	while(i < dec_str.length) {
		n = 0;
		if(i > 0) hex_str += delimiter;
		for(; i < dec_str.length && (n < 25 || (n == 25 && dec_str.charAt(i) < 6)); i++) {
			n *= 10;
			n += parseInt(dec_str.charAt(i));
		}
		hex_str += byteToHex(n);
	}
	return isNothing(hex_str, theForm, "theForm.hex.value = str");
}

function chars_from_hex(theForm)
{
	var hex_str = clean_numstr(theForm.hex.value, 16);
	var char_str = "";
	var num_str = "";
	var i;
	for(i=0; i < hex_str.length; i+=2)
		char_str += byteToChar(parseInt(hex_str.substring(i, i+2), 16));
	return isNothing(char_str, theForm, "theForm.chars.value = str");
}

function hex_from_chars(theForm)
{
	var char_str = theForm.chars.value;
	var delimiter = theForm.delimiter.value;
	var hex_str = "";
	var i, n;
	for(i=0; i < char_str.length; i++) {
		n = charToByte(char_str.charAt(i));
		if(n != 0) {
			if(i > 0) hex_str += delimiter;
			hex_str += byteToHex(n);
		}
	}
	return isNothing(hex_str, theForm, "theForm.hex.value = str");
}

function dec_from_hex(theForm)
{
	var hex_str = clean_numstr(theForm.hex.value, 16);
	var delimiter = theForm.delimiter.value;
	var dec_str = "";
	var dec_byte = "";
	var i;
	for(i=0; i < hex_str.length-1; i+=2) {
		if(i > 0) dec_str += delimiter;
		dec_str += parseInt(hex_str.substring(i, i+2), 16);
	}
	return isNothing(dec_str, theForm, "theForm.dec.value = str");
}

function bin_from_hex(theForm)
{
	var hex_str = clean_numstr(theForm.hex.value, 16);
	var delimiter = theForm.delimiter.value;
	var bin_str = "";
	var bin_byte = "";
	var i;
	for(i=0; i < hex_str.length-1; i+=2) {
		if(i > 0) bin_str += delimiter;
		bin_str += byteToBin(parseInt(hex_str.substring(i, i+2), 16));
	}
	return isNothing(bin_str, theForm, "theForm.bin.value = str");
}

function hex_from_bin(theForm)
{
	var delimiter = theForm.delimiter.value;
	var bin_str = clean_numstr(theForm.bin.value, 2);
	var hex_str = "";
	var i;

	for (i=0; i < bin_str.length-7; i+=8) {
		if(i > 0) hex_str += delimiter;
		hex_str += byteToHex(parseInt(bin_str.substring(i, i+8), 2));
	}
	return isNothing(hex_str, theForm, "theForm.hex.value = str");
}

function from_dec(theForm)
{
	if (hex_from_dec(theForm)) {
		bin_from_hex(theForm);
		chars_from_hex(theForm);
	}
}

function from_hex(theForm)
{
	if(bin_from_hex(theForm)){
		dec_from_hex(theForm);
		chars_from_hex(theForm);
	}
}

function from_char(theForm)
{
	if(hex_from_chars(theForm)) {
		bin_from_hex(theForm);
		dec_from_hex(theForm);
	}
}

function from_bin(theForm)
{
	if(hex_from_bin(theForm)) {
		dec_from_hex(theForm);
		chars_from_hex(theForm);
	}
}

