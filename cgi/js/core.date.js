
Date.month_by_index = function(month_index)
{
	var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
	return months[month_index];
}

Date.str_to_js = function(d)
{
	var matches;
	
	matches = d.match(/^(\d\d\d\d)\-(\d\d)\-(\d\d)$/);
	if (matches && d != '0000-00-00') return new Date(matches[1], matches[2]-1, matches[3]);
	return false;
}

Date.js_to_str = function(d)
{
	return d.getFullYear()+"-"+String(d.getMonth()+1).pad(2, "0", "LEFT")+"-"+String(d.getDate()).pad(2, "0", "LEFT");
}

Date.add_month = function(time, delta_m)
{
	var y, m, temp_date;
	
	temp_date = new Date(Number(time));
	y = temp_date.getFullYear();
	m = temp_date.getMonth();
	
	m += delta_m;
	
	if (m < 0)
	{
		y--;
		m = 11;
	}
	else if (m > 11)
	{
		y++;
		m = 0;
	}
	
	return new Date(y, m, 15, 12, 0, 0);
}

// start_date is string (YYYY-MM-DD)
// optional target_day argument
Date.delta_month = function(start_date, delta)
{
	var tmp, target_day, start_year, start_month, start_day, end_year, end_month, end_date, test_date;
	
	if (delta == 0 || isNaN(delta) || String(delta).indexOf('.') != -1)
	{
		return start_date;
	}
	tmp = start_date.split('-');
	start_year = Number(tmp[0]);
	start_month = Number(tmp[1]);
	start_day = Number(tmp[2]);
	target_day = (arguments.length > 2) ? arguments[2] : start_day;
	
	// set end month
	if (delta > 0)
	{
		end_month = ((start_month + delta - 1) % 12) + 1;
	}
	else
	{
		end_month = ((start_month + delta) % 12);
		if (end_month < 1)
		{
			end_month += 12;
		}
	}
	end_month = String(end_month).pad(2, '0', 'LEFT');
	end_year = start_year + Math.floor((start_month + delta - 1) / 12);
	end_date = end_year+'-'+end_month+'-'+String(target_day).pad(2, '0', 'LEFT');
	// we could end up at a nonexistent date, eg feb 31st.. this checks for that
	test_date = Date.js_to_str(Date.str_to_js(end_date));
	if (end_date != test_date)
	{
		test_date = test_date.substr(0, 7)+'-01';
		end_date = Date.js_to_str(new Date(Date.str_to_js(test_date).getTime() - 86400000));
	}
	return end_date;
}