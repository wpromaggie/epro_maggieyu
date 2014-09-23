
(function(window){ var types = {

is_array:function(x)
{
	return ((typeof(x) == 'object') && (x instanceof Array));
},

is_function:function(x)
{
	return ((typeof(x) == 'function'));
},

is_defined:function(x)
{
	return (typeof(x) != 'undefined');
},

is_undefined:function(x)
{
	return (typeof(x) == 'undefined');
},

is_string:function(x)
{
	return (typeof(x) == 'string');
},

is_object:function(x)
{
	return (typeof(x) == 'object');
},

is_numeric:function(x)
{
	return (!isNaN(this.to_number(x)));
},

to_number:function(x)
{
	if (!isNaN(x)) return Number(x);
	if (this.is_string(x))
	{
		x = x.replace(/[\$,]/g, '');
		if (!isNaN(x)) return Number(x);
	}
	return NaN;
}

}; window.Types = types; })(window);
