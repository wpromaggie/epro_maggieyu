
function dbg()
{
	console.log.apply(console, Array.prototype.slice.call(arguments, 0));
}

//maybe use in util.js... discuss with kev
function empty(v)
{
	var i, type;
	
	type = typeof(v);
	switch (type)
	{
		case ("string"): return (v.match(/^\s*$/gi) != null);
		case ("number"): return (v == 0);
		case ("boolean"): return !v;
		case ("undefined"): return true;
		case ("object"):
			for (i in v) return false;
			return true;
	}
	
	// some unknown type, return false
	return false;
}
