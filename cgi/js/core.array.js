
Array.map = function(a, callback)
{
	switch (typeof(callback))
	{
		case ('string'): Array.map_string(a, callback); break;
		case ('function'): Array.map_function(a, callback); break;
	}
};

Array.map_string = function(a, callback)
{
	var i, elem;
	
	for (i = 0; i < a.length; ++i)
	{
		elem = a[i];
		a[i] = elem[callback]();
	}
};

Array.map_function = function(a, callback)
{
	var i, elem;
	
	for (i = 0; i < a.length; ++i)
	{
		elem = a[i];
		a[i] = callback(elem);
	}
};

Array.clone = function(a)
{
	var tmp = {k:a};
	tmp = $.extend(true, {}, tmp);
	return tmp.k;
};

Array.range = function(low, high)
{
	var i,
		a = [],
		step = (arguments.length > 2) ? arguments[2] : 1;
	
	for (i = low; i <= high; i += step) {
		a.push(i);
	}
	return a;
};

Array.sort2d = function(a, index)
{
	var
		// ASC or DESC
		dir = (arguments.length > 2) ? arguments[2] : 'ASC',
		// NUM or STR
		type = (arguments.length > 3) ? arguments[3] : (isNaN(a[0][index]) ? 'STR' : 'NUM')
	;
	if (type === 'STR') {
		if (dir === 'DESC') {
			a.sort(function(a, b){ return (a[index].toLowerCase() < b[index].toLowerCase() ? 1 : -1); });
		}
		else {
			a.sort(function(a, b){ return (b[index].toLowerCase() < a[index].toLowerCase()) ? 1 : -1; });	
		}
	}
	else {
		if (dir === 'DESC') {
			a.sort(function(a, b){ return (a[index] - b[index]); });
		}
		else {
			a.sort(function(a, b){ return (b[index] - a[index]); });	
		}
	}
};

// remove elements matching target from array
// target defaults to empty string
Array.filter = function(a)
{
	var i, elem, target = (arguments.length > 1) ? arguments[1] : '';
	for (i = 0; i < a.length; ++i)
	{
		if (a[i] == target)
		{
			a.splice(i, 1);
			--i;
		}
	}
	return a;
};

Array.in_array = function(a, target)
{
	if($.inArray(target, a)!=-1){
		return true;
	}
	return false;
};
