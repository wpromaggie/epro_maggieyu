
String.prototype.trim = function()
{
	var re_start, re_end;
	
	if (arguments.length > 0)
	{
		re_start = new RegExp('^'+arguments[0]+'+');
		re_end = new RegExp(arguments[0]+'+$');
	}
	// default is trim whitespace
	else
	{
		re_start = new RegExp('^\\s+');
		re_end = new RegExp('\\s+$');
	}
	return this.replace(re_start, '').replace(re_end, '');
};

String.prototype.simple_text = function()
{
	if (!this || !this.toLowerCase)
	{
		return;
	}
	return (this.toLowerCase().replace(/[^\w -]/g, '').replace(/[ -]/g, '_'));
};

String.prototype.display_text = function()
{
	if (!this || !this.replace)
	{
		return '';
	}
	return this.replace(/_/g, ' ').replace(/\b(\w)/g, function(match){ return match.toUpperCase(); });
};

String.prototype.entity_text = function()
{
	if (!this || !this.replace)
	{
		return '';
	}
	return this.replace(/\"/g, '&quot;');
}

String.prototype.pad = function(len, pad_char, left_right)
{
	var str = String(this);
  while (str.length < len)
  {
		str = (left_right == "LEFT") ? (pad_char + str) : (str + pad_char);
	}
	return str;
};

String.parse_query = function(q)
{
	var kv_pairs, kv_pair, i, r;
	
	i = q.indexOf('?');
	if (i != -1)
	{
		q = q.substr(i + 1);
	}
	if (empty(q))
	{
		return {};
	}
	r = {};
	kv_pairs = q.split('&');
	for (i = 0; i < kv_pairs.length; ++i)
	{
		kv_pair = kv_pairs[i].split('=');
		r[kv_pair[0]] = (kv_pair[1]) ? unescape(kv_pair[1]) : '';
	}
	return r;
};

String.to_query = function(obj)
{
	var k, v, q;
	
	q = '';
	for (k in obj)
	{
		if (q)
		{
			q += '&';
		}
		q += escape(k)+'='+escape(obj[k]);
	}
	return q;
};

String.repeat = function(repeater, count)
{
	var i, s;
	
	s = '';
	for (i = 0; i < count; ++i)
	{
		s += repeater;
	}
	return s;
};