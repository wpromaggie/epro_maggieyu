
(function(window){ var _cookie = {

	set: function(name, value, s)
	{
		var d;

		if (s == 0)
		{
			document.cookie = name+"="+value+"; path=/";
		}
		else
		{
			d = new Date();
			d = new Date(d.getTime() + (s * 1000));
			document.cookie = name+"="+value+"; expires="+d.toUTCString()+"; path=/";
		}
	},

	get: function(key)
	{
		var cookie_str, i1, i2;

		cookie_str = document.cookie;
		i1 = cookie_str.indexOf(key + '=');
		if (i1 == -1) return null;
		if (i1 > 0 && cookie_str.charAt(i1 - 1) != " ") return null;
		i2 = cookie_str.indexOf(';', i1);
		if (i2 == -1) i2 = cookie_str.length;

		return cookie_str.substring(i1 + key.length + 1, i2);
	}

}; window.cookie = _cookie; })(window);