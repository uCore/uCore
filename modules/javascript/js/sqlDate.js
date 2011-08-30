/*
	SQL date formatting options for javascript Date object
	Written by Tom Kay (www.utopiasystems.co.uk)
	This is a "thrown together" extension.
	Neither Utopia Systems or Tom Kay are responsible for any possible damages caused by this script.

	Please keep this comment with the code.
	
	Usage:
		Date.fromSqlFormat(<format>, <date string>)
		  returns a Date object set to the specified date.
		  
		Date.sqlFormat(format)
		  returns a String representation of the date object, formatted as specified.
*/
Date.fromSqlFormat = function (format,datestr) {
	var sql_dateformat_lookup = Array();
	//day
	sql_dateformat_lookup['%d'] = '[0-9]{2}';
	sql_dateformat_lookup['%e'] = '[0-9]{1,2}';
	//month
	sql_dateformat_lookup['%m'] = '[0-9]{2}';
	sql_dateformat_lookup['%c'] = '[0-9]{1,2}';
	sql_dateformat_lookup['%b'] = '[a-zA-Z]*';
	sql_dateformat_lookup['%M'] = '[a-zA-Z]*';
	//year
	sql_dateformat_lookup['%Y'] = '[0-9]{4}';
	sql_dateformat_lookup['%y'] = '[0-9]{2}';
	// time
	sql_dateformat_lookup['%H'] = '[0-9]{2}';
	sql_dateformat_lookup['%k'] = '[0-9]{1,2}';
	sql_dateformat_lookup['%i'] = '[0-9]{2}';
	sql_dateformat_lookup['%s'] = '[0-9]{2}';
	sql_dateformat_lookup['%f'] = '[0-9]{1-6}';
	var monthLookup = Array();
	monthLookup['January']		= monthLookup['Jan']	= 1;
	monthLookup['February']		= monthLookup['Feb']	= 2;
	monthLookup['March']		= monthLookup['Mar']	= 3;
	monthLookup['April']		= monthLookup['Apr']	= 4;
	monthLookup['May']			= monthLookup['May']	= 5;
	monthLookup['June']			= monthLookup['Jun']	= 6;
	monthLookup['July']			= monthLookup['Jul']	= 7;
	monthLookup['August']		= monthLookup['Aug']	= 8;
	monthLookup['September']	= monthLookup['Sep']	= 9;
	monthLookup['October']		= monthLookup['Oct']	= 10;
	monthLookup['November']		= monthLookup['Nov']	= 11;
	monthLookup['December']		= monthLookup['Dec']	= 12;

	var retpos = Array();
	
	var parser = new RegExp("(%[a-zA-Z]{1})","g");
	var newregex = format;
	var count = 0;
	while ((parsedArray = parser.exec(format)) != null) {
		for (var i = 1; i<parsedArray.length; i++) { // build regex for this format
			count++;
			seg = parsedArray[i];
			newregex = newregex.replace(seg,'('+sql_dateformat_lookup[seg]+')');
			retpos[seg] = count;
		}
	}
	
	regex = new RegExp(newregex);
	var d = null;
	if ((dReg = regex.exec(datestr)) != null) {
		d = new Date();
		// find day
		var day = dReg[retpos['%d']] |  dReg[retpos['%e']];
		if (day > 0) d.setDate(day);
		// find month
		var month = dReg[retpos['%m']] | monthLookup[dReg[retpos['%b']]] | monthLookup[dReg[retpos['%M']]];
		if (month > 0) d.setMonth(month-1);
		// find year
		var year = dReg[retpos['%y']] | dReg[retpos['%Y']];
		if (year > 0) d.setFullYear(year);
		// find hours
		var hours = dReg[retpos['%H']];
		if (hours > 0) d.setHours(hours); else d.setHours(0);
		// find mins
		var mins = dReg[retpos['%i']];
		if (mins > 0) d.setMinutes(mins); else d.setMinutes(0);
		// find seconds
		var seconds = dReg[retpos['%s']];
		if (seconds > 0) d.setSeconds(seconds); else d.setSeconds(0);
		// find microseconds
		var milliseconds = dReg[retpos['%f']];
		if (milliseconds > 0) d.setMilliseconds(milliseconds); else d.setMilliseconds(0);
	}
	
	return d;
}

Date.prototype.sqlFormat = function (format) {
	function pad(str,desiredSize,padChar) {
		if (typeof(padChar) == 'undefined') padChar = '0';
		str = String(str);
		while (str.length < desiredSize) str = padChar + str;
		return str;
	}
	var shortMonthLookup = Array(); var longMonthLookup = Array();
	shortMonthLookup[1]		= 'Jan';	longMonthLookup[1]	= 'January';
	shortMonthLookup[2]		= 'Feb';	longMonthLookup[2]	= 'February';
	shortMonthLookup[3]		= 'Mar';	longMonthLookup[3]	= 'March';
	shortMonthLookup[4]		= 'Apr';	longMonthLookup[4]	= 'April';
	shortMonthLookup[5]		= 'May';	longMonthLookup[5]	= 'May';
	shortMonthLookup[6]		= 'Jun';	longMonthLookup[6]	= 'June';
	shortMonthLookup[7]		= 'Jul';	longMonthLookup[7]	= 'July';
	shortMonthLookup[8]		= 'Aug';	longMonthLookup[8]	= 'August';
	shortMonthLookup[9]		= 'Sep';	longMonthLookup[9]	= 'September';
	shortMonthLookup[10]	= 'Oct';	longMonthLookup[10]	= 'October';
	shortMonthLookup[11]	= 'Nov';	longMonthLookup[11]	= 'November';
	shortMonthLookup[12]	= 'Dec';	longMonthLookup[12]	= 'December';
	
	var sql_dateformat_lookup = Array();
	//day
	sql_dateformat_lookup['%d'] = pad(this.getDate(),2);//'[0-9]{2}';
	sql_dateformat_lookup['%e'] = this.getDate();//'[0-9]{1,2}';
	//month
	sql_dateformat_lookup['%m'] = pad(this.getMonth()+1,2);//'[0-9]{2}';
	sql_dateformat_lookup['%c'] = this.getMonth()+1;//'[0-9]{1,2}';
	sql_dateformat_lookup['%b'] = shortMonthLookup[this.getMonth()+1];//'[a-zA-Z]*';
	sql_dateformat_lookup['%M'] = longMonthLookup[this.getMonth()+1];//'[a-zA-Z]*';
	//year
	sql_dateformat_lookup['%Y'] = this.getFullYear();//'[0-9]{4}';
	sql_dateformat_lookup['%y'] = String(this.getFullYear()).substr(2);//'[0-9]{2}';
	// time
	sql_dateformat_lookup['%H'] = pad(this.getHours(),2);//'[0-9]{2}';
	sql_dateformat_lookup['%k'] = this.getHours();//'[0-9]{1,2}';
	sql_dateformat_lookup['%i'] = pad(this.getMinutes(),2);//'[0-9]{2}';
	sql_dateformat_lookup['%s'] = pad(this.getSeconds(),2);//'[0-9]{2}';
	sql_dateformat_lookup['%f'] = this.getMilliseconds();
	
	str = format;
	for (seg in sql_dateformat_lookup)
		str = str.replace(seg,sql_dateformat_lookup[seg]);
	
	return str;
}