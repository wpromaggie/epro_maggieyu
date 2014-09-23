
(function(window){ var format = {

dollars:function(n)
{
	var i, is_negative, thousands_separator, decimal_symbol, currency_symbol, loop_end;
	
	if (isNaN(n)) return ((typeof(n) == 'undefined') ? '' : n);
	is_negative = (n < 0);
	n = this.decimal(n, 2);
	
	thousands_separator = ',';
	decimal_symbol = '.';
	currency_symbol = '$';
	
	n = n.replace(/\./, decimal_symbol);
	loop_end = (is_negative) ? 1 : 0;
	for (i = n.indexOf(decimal_symbol) - 3; i > loop_end; i -= 3)
	{
		n = n.substring(0, i) + thousands_separator + n.substr(i);
	}
	return ((is_negative) ? ('-'+currency_symbol+n.replace('-', '')) : currency_symbol + n);
},

nempty:function(n)
{
	return ((isNaN(n)) ? '' : n);
},

n0:function(n)
{
	return (Math.round(n));
},

n1:function(n)
{
	return (this.decimal(n, 1));
},

n2:function(n)
{
	return (this.decimal(n, 2));
},

percent:function(n)
{
	return (this.decimal(n, 2)+'%');
},

string:function(n)
{
	return String(n);
},

decimal:function(n, decimals)
{
	var i, d;
	
	if (decimals == 0)
	{
		return Math.round(n);
	}
	
	n = String(Math.round(n * Math.pow(10, decimals)) * Math.pow(10, -decimals));
	
	d = n.indexOf('.');
	if (d == -1)
	{
		n += '.';
		for (i = 0; i < decimals; i++)
		{
			n += '0';
		}
		return n;
	}
	
	if (d == 0)
	{
		n = '0' + n;
		d++;
	}
	else if (d == 1 && n.charAt(0) == '-')
	{
		n = '-0' + n.substring(1, n.length);
		d++;
	}
	
	for (n = n.substring(0, d + decimals + 1); n.length <= (d + decimals); n += '0');
	return n;
}

}; window.Format = format; })(window);
