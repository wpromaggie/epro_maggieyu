
window.add_onload_func = function(new_func)
{
	var cur_func;
	
	cur_func = window.onload;
	window.onload = function() {
		if (cur_func) cur_func();
		new_func();
	}
}

window.location.qsa = function(x)
{
	var href;
	
	href = this.href;
	if (empty(this.search))
	{
		href += '?';
	}
	else
	{
		href += '&';
	}
	this.assign(href+x);
};

window.location.qset = function(opts)
{
	var i, key, q;
	
	q = {};
	if (opts.keep) {
		// keep what is in array
		if ($.type(opts.keep) === 'array') {
			for (i = 0; i < opts.keep.length; ++i) {
				key = opts.keep[i];
				q[key] = (this.get[key]) ? escape(this.get[key]) : '';
			}
		}
		// keep everything
		else {
			q = this.get;
		}
	}
	if (opts.put) {
		for (key in opts.put) {
			q[key] = escape(opts.put[key]);
		}
	}
	this.assign(this.pathname+'?'+String.to_query(q));
};

window.location.replace_path = function(new_path)
{
	window.history.replaceState('', '', new_path);
	window.location.set_get();
};

window.location.set_get = function()
{
	window.location.get = String.parse_query(window.location.search);
};

window.location.assign_search = function(search)
{
	if (Types.is_object(search)) {
		search = String.to_query(search);
	}
	window.location.assign(window.location.pathname+'?'+search);
};

window.location.init = function()
{
	window.location.set_get();
	if (window.globals && window.globals.url_params_for_removal) {
		var i, param;
		for (i = 0; i < window.globals.url_params_for_removal.length; ++i) {
			param = window.globals.url_params_for_removal;
			delete(window.location.get[param]);
		}
		window.location.replace_path('?'+String.to_query(window.location.get));
	}
};
