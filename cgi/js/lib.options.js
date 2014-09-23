
Options.is_options = function(x)
{
	return ((typeof(x) == 'object') && (x instanceof Options));
}

Options.on = function(argv, flag)
{
	var opts;
	
	if (argv.length == 0)
	{
		return false;
	}
	opts = argv[argv.length - 1];
	if (!Options.is_options(opts))
	{
		return false;
	}
	return ((arguments.length == 2) ? opts.exists(flag) : opts.equals(flag, arguments[2]));
}

function Options(opts)
{
	var i;
	
	for (i in opts)
	{
		this[i] = opts[i];
	}
}

Options.prototype.exists = function(flag)
{
	return (!Types.is_undefined(this[flag]));
}

Options.prototype.equals = function(member, target_val)
{
	return (this[flag] == target_val);
}

