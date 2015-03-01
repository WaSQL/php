function Calendar(target_id,month, year, s) {
	if(undefined==target_id){alert('No Calendar target ID:'+target_id);return false;}
	var tobj=getObject(target_id);
	if(undefined==tobj){alert('No Calendar target named'+target_id+' found');return false;}

	this.current_date = new Date();
	this.current_dateYear=this.current_date.getFullYear();
	this.current_dateMonth=this.current_date.getMonth();
	this.current_dateDate=this.current_date.getDate();
	this.target_id=target_id;
	this.id=this.target_id+'_cal';
	this.dateid=this.id+'_date';
	this.timeid=this.id+'_time';
	var caldiv=getObject(this.id);
  	if(undefined == caldiv){
    	caldiv = document.createElement("div");
    	caldiv.id=this.id;
    	caldiv.className='w_calendar_div';
    	tobj.insertAdjacentElement('afterEnd',caldiv);
	}
	else if(undefined==s && caldiv.style.display=='block'){
    	caldiv.style.display='none';
    	return;
	}
	var controlType=tobj.getAttribute('data-type');
	if(undefined != controlType){
		switch(controlType.toLowerCase()){
			case 'datetime':
				//datetime control - show both date and time
				this.showtime=true;
				this.showdate=true;
				caldiv.style.width='220px';
			break;
			case 'time':
				//time control
				this.showtime=true;
				this.showdate=false;
				caldiv.style.width='115px';
			break;
			default:
				//default to a date control
				this.showdate=true;
				this.showtime=false;
				caldiv.style.width='175px';
			break;
		}
	}
	else{
		//default to a date control
		this.showdate=true;
		this.showtime=false;
		caldiv.style.width='175px';
	}
	//get current value
	this.hasvalue=false;
	var cval=getText(tobj);
	if(cval.length){
		this.hasvalue=true;
		// Split timestamp into [ Y, M, D, h, m, s ]
		var t = cval.split(/[- :]/);
		if(t.length==6){
			//var d = new Date(year, month, day, hours, minutes, seconds, milliseconds);
			this.cval = new Date(parseInt(t[0]), parseInt(t[1])-1, parseInt(t[2]), parseInt(t[3]), parseInt(t[4]), parseInt(t[5]),0);
			this.cvalYear=this.cval.getFullYear();
			this.cvalMonth=this.cval.getMonth();
			this.cvalDate=this.cval.getDate();
			this.cvalHr=this.cval.getHours()
			this.cvalMin=this.cval.getMinutes();
		}
		else if(t.length==3 && t[0].length==4){
			this.cval = new Date(parseInt(t[0]), parseInt(t[1])-1, parseInt(t[2]), 0, 0, 0,0);
			this.cvalYear=this.cval.getFullYear();
			this.cvalMonth=this.cval.getMonth();
			this.cvalDate=this.cval.getDate();
		}
		else if(t.length==3){
        	this.cval = new Date(this.current_dateYear, this.current_dateMonth, this.current_dateDate, parseInt(t[0]), parseInt(t[1]), parseInt(t[2]),0);

			this.cvalHr=this.cval.getHours()
			this.cvalMin=this.cval.getMinutes();
		}

	}
	var html='';
	if(this.showdate){
		//current date
		if(undefined==month && undefined==year){
			if(cval.length){
				month=this.cval.getMonth();
				year=this.cval.getFullYear();
				this.current_day=this.cval.getDay();
			}
		}

		this.month = (isNaN(month) || month == null) ? this.current_date.getMonth() : month;
		this.year  = (isNaN(year) || year == null) ? this.current_date.getFullYear() : year;
	  	// these are labels for the days of the week
		this.days_labels = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
		// these are human-readable month name labels, in order
		this.months_labels = ['Jan', 'Feb', 'Mar', 'Apr','May', 'Jun', 'Jul', 'Aug', 'Sep','Oct', 'Nov', 'Dec'];
		// these are the days of the week for each month, in order
		this.days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
		// get first day of month
		var firstDay = new Date(this.year, this.month, 1);
		var startingDay = firstDay.getDay();
		this.month=firstDay.getMonth();
		this.year=firstDay.getFullYear();
		this.nextyear=parseInt(this.year)+1;
		this.prevyear=parseInt(this.year)-1;
		switch(parseInt(this.month)){
	    	case 0:
				this.prevmonth=11;
				this.nextmonth=this.month+1;
				this.prevmonthyear=parseInt(this.year)-1;
				this.nextmonthyear=this.year;
			break;
	    	case 11:
	    		this.prevmonth=this.month-1;
				this.nextmonth=0;
				this.prevmonthyear=this.year;
				this.nextmonthyear=parseInt(this.year)+1;
			break;
	    	default:
				this.prevmonth=this.month-1;
				this.nextmonth=this.month+1;
				this.prevmonthyear=this.year;
				this.nextmonthyear=this.year;
			break;
		}
		// find number of days in month
		var monthLength = this.days_in_month[this.month];
		// compensate for leap year
		if (this.month == 1) { // February only!
	    	if((this.year % 4 == 0 && this.year % 100 != 0) || this.year % 400 == 0){
	      		monthLength = 29;
	    	}
	  	}
		// do the header
		var monthName = this.months_labels[this.month];
		html += 	'<table class="w_calendar_table" border="0" style="border:1px solid #CCC;"><tr valign="top"><td style="padding:0px !important;border-right:px solid #CCC !important;">';
		html += 	'<table class="w_calendar_table">';
		//month and year
		html += 	'	<tr class="w_calendar_month">'+"\n";
		html +=		'		<th title="Prev Year" class="w_pointer" onclick="Calendar(\''+this.target_id+'\',\''+this.month+'\',\''+this.prevyear+'\',1);"><span class="icon-dir-left w_link w_block"></span></th>'+"\n";
		html +=		'		<th title="Prev Month" class="w_pointer" onclick="Calendar(\''+this.target_id+'\',\''+this.prevmonth+'\',\''+this.prevmonthyear+'\',1);"><span class="icon-arrow-left w_link w_block"></span></th>'+"\n";
		html +=		'		<th colspan="3" class="w_calendar_title">' + monthName + "&nbsp;" + this.year+'</th>'+"\n";
		html +=		'		<th title="Next Month" class="w_pointer" onclick="Calendar(\''+this.target_id+'\',\''+this.nextmonth+'\',\''+this.nextmonthyear+'\',1);"><span class="icon-arrow-right w_link w_block"></span></th>'+"\n";
		html +=		'		<th title="Next Year" class="w_pointer" onclick="Calendar(\''+this.target_id+'\',\''+this.month+'\',\''+this.nextyear+'\',1);"><span class="icon-dir-right w_link w_block"></span></th>'+"\n";
		html +=		'	</tr>'+"\n";
		//days of the week
	  	html += 	'	<tr class="w_calendar_head">'+"\n";
	  	for(var i = 0; i <= 6; i++ ){
	    	html += '		<th style="padding:0px !important;">'+this.days_labels[i]+'</th>'+"\n";
	  	}
	  	html += 	'	</tr>'+"\n";
		html +=		'	<tr class="w_calendar_body">'+"\n";
	  	// fill in the days
	  	var day = 1;
	  	// this loop is for is weeks (rows)
	  	var rows=1;
	  	for (var i = 0; i < 9; i++) {
	    	// this loop is for weekdays (cells)
	    	for (var j = 0; j <= 6; j++) {
				//today?
				var today=false;
				var cdate=new Date(this.year,this.month,day,0,0,0,0);
				if(this.current_dateYear==cdate.getFullYear() && this.current_dateMonth==cdate.getMonth() && this.current_dateDate==cdate.getDate()){
	                today=true;
				}
				//active?
				var active=false;
				if(this.hasvalue){
					if(this.cvalYear==cdate.getFullYear() && this.cvalMonth==cdate.getMonth() && this.cvalDate==cdate.getDate()){
		                active=true;
					}
				}
				//weekends are th cells
		      	if(j==0 || j==6){html += '		<th';}
		      	else{html += '		<td';}
		      	if(active){html+=' class="active"';}
		      	else if(today){html+=' class="today"';}
		      	html+=' style="padding:0px !important;">';
		      	if (day <= monthLength && (i > 0 || j >= startingDay)) {
					var d=day < 10?'0'+day:day;
					var m=CalendarTwoDigits(this.month+1);
					var onclick=this.year+'-'+m+'-'+d;
		        	html += '<div onclick="CalendarSetDate(\''+this.target_id+'\',\''+onclick+'\');">'+day+'</div>';
		        	day++;
		      	}
		      	//weekends are th cells
		      	if(j==0 || j==6){html += '</th>'+"\n";}
		      	else{html += '</td>'+"\n";}
	
	    	}
	    	// stop making rows if we've run out of days
	    	if (day > monthLength) {
	      		break;
	    	} 
			else {
	      		html += '	</tr>'+"\n";
				html +=	'	<tr class="w_calendar_body">'+"\n";
				rows++;
	    	}
	  	}
		html += '</tr>'+"\n";
		html += '</table></td>'+"\n";
		if(this.showtime){
			this.timesheight=parseInt(rows*21)+15;
			html += 	'<td style="padding:0px !important;border-left:1px solid #CCC;" nowrap>'+"\n";
			html +=		'<div class="w_bold w_smaller" align="center">Time</div>'+"\n";
			this.showtimes=this.id+'_showtimes';
			html +=		'<div class="w_calendar_times" id="'+this.showtimes+'" style="height:'+this.timesheight+'px !important;">'+"\n";
			var hrs=new Array(24,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23);
			var ms=new Array('00','30');
			for(var h=0;h<hrs.length;h++){
				var a='am';
				var hours;
	        	if(hrs[h] > 12){
	            	if(h !=0){a='pm';}
	            	hours=hrs[h]-12;
				}
				else{
					hours=hrs[h];
				}
				var hh=CalendarTwoDigits(hrs[h]);
				for(var m=0;m<ms.length;m++){
					var time=hours+':'+ms[m]+' '+a;
					var onclick=hh+':'+ms[m]+':00';
					var cdate=new Date(this.current_dateYear,this.current_dateMonth,this.current_dateDate,parseInt(hrs[h]),parseInt(ms[m]),0,0);
					var active=false;
					if(this.hasvalue && this.cvalHr==cdate.getHours() && this.cvalMin==cdate.getMinutes()){active=true;}
					html += '<div onclick="CalendarSetTime(\''+this.target_id+'\',\''+onclick+'\');" class="w_calendar_time';
					if(active){html += ' active';}
					html += '">'+time+'</div>'+"\n";
				}
			}
			html +=		'</div>'+"\n";
			html += 	'</td>'+"\n";
		}
		html += '	</tr>'+"\n";
		html += '	<tr><td colspan="7" style="display:relative;padding:0px !important;">'+"\n";
		html += '		<table class="w_calendar_table"><tr>'+"\n";
		if(this.showtime){
		  	html +=	'		<td style="width:50%;"><div id="'+this.dateid+'" style="height:15px;text-align:right;padding-right:4px;">';
			if(this.hasvalue && undefined != this.cvalYear){html += this.cvalYear+'-'+CalendarTwoDigits(this.cvalMonth+1)+'-'+CalendarTwoDigits(this.cvalDate);}
			html+=	'</div></td>'+"\n";
		  	html +=	'		<td style="width:50%"><div id="'+this.timeid+'" style="height:15px;text-align:left;padding-left:4px;">';
			if(this.hasvalue && undefined != this.cvalHr){html += CalendarTwoDigits(this.cvalHr)+':'+CalendarTwoDigits(this.cvalMin)+':'+'00';}
			html +=	'</div></td>'+"\n";
		}
		else{
			html +=	'		<td width="100%"><div id="'+this.dateid+'" style="height:15px;text-align:center;">';
			if(this.hasvalue && undefined != this.cvalYear){html += this.cvalYear+'-'+CalendarTwoDigits(this.cvalMonth+1)+'-'+CalendarTwoDigits(this.cvalDate);}
			html +=	'</div></td>'+"\n";
		}
		html += '	<td class="w_calendar_close" title="Click to close" onclick="hideId(\''+this.id+'\');"><span class="icon-cancel-circled w_link w_block"></span></td>'+"\n";
		html += '</tr></table></td></tr></table>'+"\n";
	}
	else if(this.showtime){
		this.timesheight=200;
		html += 	'<div style="width:100px;">'+"\n";
		html +=		'<div class="w_bold w_small" align="center">Time</div>'+"\n";
		this.showtimes=this.id+'_showtimes';
		html +=		'<div class="w_calendar_times" id="'+this.showtimes+'" style="height:'+this.timesheight+'px;">'+"\n";
		var hrs=new Array(24,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23);
		var ms=new Array('00','30');
		for(var h=0;h<hrs.length;h++){
			var a='am';
			var hours;
        	if(hrs[h] > 12){
            	if(h !=0){a='pm';}
            	hours=hrs[h]-12;
			}
			else{
				hours=hrs[h];
			}
			var hh=CalendarTwoDigits(hrs[h]);
			for(var m=0;m<ms.length;m++){
				var time=hours+':'+ms[m]+' '+a;
				var onclick=hh+':'+ms[m]+':00';
				var cdate=new Date(this.current_dateYear,this.current_dateMonth,this.current_dateDate,parseInt(hrs[h]),parseInt(ms[m]),0,0);
				var active=false;
				if(this.hasvalue && this.cvalHr==cdate.getHours() && this.cvalMin==cdate.getMinutes()){active=true;}
				html += '<div onclick="CalendarSetTime(\''+this.target_id+'\',\''+onclick+'\');" class="w_calendar_time';
				if(active){html += ' active';}
				html +='">'+time+'</div>'+"\n";
			}
		}
		html +=		'</div>'+"\n";
		html +=		'<div id="'+this.timeid+'" style="height:15px;text-align:center;">';
		if(this.hasvalue && undefined != this.cvalHr){html += CalendarTwoDigits(this.cvalHr)+':'+CalendarTwoDigits(this.cvalMin)+':'+'00';}
		html +=		'</div>'+"\n";
		html += 	'</div>'+"\n";
	}

  	//populate the div
	caldiv.innerHTML=html;
	caldiv.style.display='block';
	var h=getHeight(caldiv);
	h=parseInt(h)+3;
	if(h < 150){h=150;}
	caldiv.style.bottom='-'+h+'px';
	caldiv.style.left='0px';
	caldiv.style.zIndex=this.current_date.getTime();



	if(this.showtime){
    	var sobj=getObject(this.showtimes);
    	sobj.scrollTop=320;
	}
	return false;
}
function CalendarSetDate(target_id,date){
	if(undefined==target_id){alert('No Calendar target ID:'+target_id);return false;}
	var tobj=getObject(target_id);
	if(undefined==tobj){alert('No Calendar target named'+target_id+' found');return false;}
	var controlType=tobj.getAttribute('data-type');
	if(undefined != controlType){
		switch(controlType.toLowerCase()){
			case 'datetime':
				this.showtime=true;
				this.showdate=true;
			break;
			case 'time':
				this.showtime=true;
				this.showdate=false;
			break;
			default:
				this.showdate=true;
				this.showtime=false;
			break;
		}
	}
	else{
		this.showdate=true;
		this.showtime=false;
	}
	this.id=target_id+'_cal';
	this.dateid=this.id+'_date';
	this.timeid=this.id+'_time';
	setText(this.dateid,date);
	if(!this.showtime){
    	//no time field
    	setText(target_id,date);
    	hideId(this.id);
    	return false;
	}
	var time=getText(this.timeid);
	if(time.length){
    	setText(target_id,date+' '+time);
    	hideId(this.id);
	}
	return false;
}
function CalendarSetTime(target_id,time){
	if(undefined==target_id){alert('No Calendar target ID:'+target_id);return false;}
	var tobj=getObject(target_id);
	if(undefined==tobj){alert('No Calendar target named'+target_id+' found');return false;}
	var controlType=tobj.getAttribute('data-type');
	if(undefined != controlType){
		switch(controlType.toLowerCase()){
			case 'datetime':
				this.showtime=true;
				this.showdate=true;
			break;
			case 'time':
				this.showtime=true;
				this.showdate=false;
			break;
			default:
				this.showdate=true;
				this.showtime=false;
			break;
		}
	}
	else{
		this.showdate=true;
		this.showtime=false;
	}
	this.id=target_id+'_cal';
	this.dateid=this.id+'_date';
	this.timeid=this.id+'_time';
	setText(this.timeid,time);
	if(!this.showdate){
    	setText(target_id,time);
    	hideId(this.id);
    	return false;
	}
	var date=getText(this.dateid);
	if(date.length){
		setText(target_id,date+' '+time);
    	hideId(this.id);
	}
	return false;
}
function CalendarTwoDigits(d) {
    if(0 <= d && d < 10) return "0" + d.toString();
    if(-10 < d && d < 0) return "-0" + (-1*d).toString();
    return d.toString();
}

