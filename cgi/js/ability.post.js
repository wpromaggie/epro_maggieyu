
function post_ability(){}

post_ability.prototype.post = function(func_name, request_data)
{
	var self = this,
		callback_func = arguments.length > 2 ? arguments[2] : func_name+'_callback'
	;
	
	request_data._ajax_func_ = 'ajax_'+func_name;
	$.post(String(window.location), request_data, function(response) { self.post_callback(request_data, response, callback_func); });
};

post_ability.prototype.post_callback = function(request, response, callback_mixed)
{
	var response_obj;
	
	try {
		response_obj = eval('('+response+')');
	}
	catch (err) {
		response_obj = response;
	}
	//ajax_check_dbg(response);
	if ($.type(callback_mixed) == 'function') {
		callback_mixed(request, response_obj);
	}
	else if (this[callback_mixed]) {
		this[callback_mixed](request, response_obj);
	}
};
