
/*
 * ajax wrapper using jquery $.post()
 */

/*
 * send an ajax request to the server. sends to the current url.
 * @param server_func: the function to call on the server. must be a public member of the module
 * @param callback_func: the javascript function to call when request completes
 * @param request_data: the data to send to the server
 * @param callback_obj: default=window. if object is passed in and callback_func is a string, treat callback_func as method of that object
 * @param in_parallel: if this call is a part of multiple parallel requests, set field in request_data object
 * @return: void
 */

function ajax_post(server_func, callback_func, request_data, callback_obj, in_parallel)
{
	request_data._ajax_func_ = server_func;
	if (in_parallel)
	{
		request_data._parallel_ = 1;
	}
	
	$.post(String(window.location), request_data, function(response){ ajax_post_callback_wrapper(request_data, response, callback_func, callback_obj); });
}

function ajax_post_callback_wrapper(request, response, callback_func, callback_obj)
{
	var response_obj;
	
	try
	{
		response_obj = eval('('+response+')');
	}
	catch (err)
	{
		response_obj = response;
	}
	ajax_check_dbg(response);
	if (typeof(callback_func) == 'function')
	{
		callback_func(request, response_obj);
	}
	else
	{
		if (empty(callback_obj))
		{
			callback_obj = window;
		}
		callback_obj[callback_func](request, response_obj);
	}
}

function ajax_is_error(response)
{
	return (response && typeof(response) == 'object' && response.msg && response.msg.type == 'error');
}

function ajax_check_dbg(response)
{
	if (dbg_is_on())
	{
		dbg(response);
	}
}

function ajax_post_dbg(server_func, callback_func, request_data, data_type, callback_obj)
{
	var key;
	
	dbg('AJAX REQUEST');
	for (key in request_data)
	{
		dbg(key, request_data[key]);
	}
	ajax_post(server_func, ajax_post_dbg_callback, request_data, 'text');
}

function ajax_post_dbg_callback(request, response)
{
	dbg('AJAX RESPONSE');
	dbg(response);
}

/*
 * legacy stuff.. die die die
 */

var ajax_reqs = [];

function ajax_send(request, callback_func)
{
	var ajax_req;
	
	ajax_req = (window.XMLHttpRequest) ? new XMLHttpRequest() : new SimpleAJAX(); // ie6 fail
	
	// ajax index field
	request._id_ = ajax_reqs.length;
	
	ajax_req.request = request;
	ajax_req.onreadystatechange = function() { ajax_receive(request, callback_func); }
	ajax_req.open("POST", window.location, true);
	ajax_req.send(JSON.stringify(request));
	
	ajax_reqs.push(ajax_req);
}

function ajax_receive(request, callback_func)
{
	var ajax_req;
	
	ajax_req = ajax_reqs[request._id_];
	if (empty(ajax_req)) return;
	
	if (ajax_req.readyState == 4 && callback_func)
		callback_func(request, ajax_req.responseText);
	
	ajax_req = null;
}
