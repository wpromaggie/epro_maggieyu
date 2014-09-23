
function dbg_start(event)
{
	$f('dbg_start', 1);
	f_submit();
}

function dbg_stop(event)
{
	$f('dbg_stop', 1);
	f_submit();
}

function dbg_is_on()
{
	return (!$.empty('#dbg_stop_link'));
}

function loading_gif()
{
	return ('/img/loading.gif');
}

function set_and_go(elem, form_key, elem_key)
{
	f[form_key].value = elem.getAttribute(elem_key);
	f_act(elem.getAttribute("href"));
	
	return false;
}

function client_change()
{
	window.location = '/'+globals.pages[0]+'/?cl_id='+$f('client');
}

function doc_click(event)
{
	// if a click reaches us here and date chooser is showing, hide it
	$('.time_picker').hide();
	$('.date_picker').hide();
}

// defaults to window.location
function parse_url()
{
	var url, i, protocol, domain, path, path_info;
	
	url = (arguments.length > 0) ? arguments[0] : String(window.location)
	
	matches = url.match(/^(.*):\/\/(.*?)(\/.*)$/);
	
	protocol = matches[1];
	domain = matches[2];
	path = matches[3];
	
	path_info = path.split("/");
	
	return {
		"protocol":protocol,
		"domain":domain,
		"path":path,
		"path_info":path_info
	};
}

function get_section()
{
	var url_info;
	
	url_info = parse_url();
	return (url_info.path_info.length > 1) ? url_info.path_info[1] : "";
}

function get_base()
{
	var url_info;
	
	url_info = parse_url();
	return (url_info.path_info.length > 0) ? url_info.path_info[0] : "";
}

// jquery style getter/setter for form inputs
function $f(name)
{
	var elem, cboxes;
	
	// set
	if (arguments.length > 1)
	{
		// default is to create if element with name is not present
		var do_create = (arguments.length < 3 || arguments[2]);
		if ($.empty('#f [name="'+name+'"]'))
		{
			if (do_create)
			{
				$('#f').append('<input type="hidden" name="'+name+'" value="'+arguments[1]+'" />');
			}
		}
		else
		{
			$('#f [name="'+name+'"]').val(arguments[1]);
		}
	}
	// get
	else
	{
		elem = $('#f [name="'+name+'"]');
		if      (elem.is('[type=radio]'))    return (elem.filter(':checked').val());
		else if (elem.is('[type=checkbox]')) return ((elem.is(':checked')) ? elem.val() : 0);
		else                                 return (elem.val());
	}
}

function f_submit()
{
	$('#f').get(0).submit();
}

function f_set_action(url_parts)
{
	var s, part;
	
	for (s = "", i = 0; i < url_parts.length; ++i)
	{
		part = url_parts[i];
		if (!empty(part)) s += part+((part[part.length-1] != "/") ? "/" : "");
	}
	f.action = s;
}


// set action and submit
function f_act()
{
	f_set_action(arguments);
	f_submit();
}

// just set action
function f_action()
{
	f_set_action(arguments);
}

var f;

function form_init()
{
	var body;
	
	body = $('body');
	
	$('input.rs_submit:not([a0])').each(function(){
		var action = $(this).attr('name');
		$(this).attr('a0', 'action_'+action);
	});
	
	e2.auto_focus(body);
	e2.auto_time_picker(body);
	e2.auto_date_picker(body);
	e2.auto_action_submit(body);
	e2.auto_select_submit(body);
	e2.auto_date_range(body);
	e2.cboxes_init(body);

	f = document.forms[0];
}

function submit_form()
{
	f.submit();
}

function module_select_change(event)
{
	var select;
	
	select = $(event.target);
	$f('client', '');
	window.location = select.val()+'/';
}

function jquery_init()
{
	// http://docs.jquery.com/Ajax/jQuery.ajax#toptions
	$.ajaxSetup({
		dataType:'text'
	});
}

function random_magic()
{
	$('[clear_me]').after('<div class="clr"></div>');
}

function div(x, y)
{
	return ((y == 0 || isNaN(x) || isNaN(y)) ? 0 : x / y);
}

// return true if first time seeing these keys
function accumulate(obj, keys, data)
{
	var i, do_not_accumulate = (arguments.length > 3) ? arguments[3] : [],
		key_index = (arguments.length > 4) ? arguments[4] : 0,
		key = keys[key_index],
		is_last_key = (key_index == (keys.length - 1));
	
	if (is_last_key) {
		if (!obj[key]) {
			obj[key] = data;
			return true;
		}
		else {
			if (Types.is_object(data)) {
				for (i in data) {
					if (!isNaN(data[i]) && !Array.in_array(do_not_accumulate, i) && data[i] !== '') {
						// check if we have something at this index in object, allows for sparse data objects
						obj[key][i] = ((obj[key][i]) ? Number(obj[key][i]) : 0) + Number(data[i]);
					}
					else {
						obj[key][i] = data[i];
					}
				}
			}
			else {
				// do not need extra number checks here? why would user be accumulating non-numeric col?
				if (!isNaN(data)) {
					obj[key] = Number(obj[key]) + Number(data);
				}
				else {
					obj[key] = data;
				}
			}
			return false;
		}
	}
	// not last key
	else {
		if (!obj[key]) {
			obj[key] = {};
		}
		return accumulate(obj[key], keys, data, do_not_accumulate, key_index + 1);
	}
}

function flatten(obj, key_names)
{
	var scalar_key_name = (arguments.length > 2) ? arguments[2] : false;
	return flatten.go(obj, key_names, scalar_key_name, [], 0, {});
}

flatten.go = function(obj, key_names, scalar_key_name, a, key_index, row)
{
	var k, v, clone;
	
	if (key_index == key_names.length) {
		clone = $.extend({}, row);
		if (Types.is_object(obj)) {
			for (k in obj) {
				clone[k] = obj[k];
			}
		}
		else {
			clone[(scalar_key_name) ? scalar_key_name : 'count'] = obj;
		}
		a.push(clone);
	}
	else {
		for (k in obj) {
			row[key_names[key_index]] = k;
			flatten.go(obj[k], key_names, scalar_key_name, a, key_index + 1, row);
		}
	}
	return a;
};

(function(window){

// create generic object to hold our e2 functions
var e2_def = {};

e2_def.init = function(messages)
{
	form_init();

	Feedback.init(globals.feedback);

	e2.init_menu();

	jquery_init();
	random_magic();
	window.location.init();
	
	e2.init_ejos();
	e2.init_modules();
	
	e2.init_chars_left();
	
	$('table').attr('cellpadding', 0);
	$('table').attr('cellspacing', 0);

	$('a.logout').click(function(){
		e2.logout();
	});
	
	// if body doesn't fill height, make it so
	if ($('#page_body').lentgh){
		if (($('#page_body').offset().top + $('#page_body').height()+ $('#page_foot').height()) < $(window).height())
		{
			$('#page_body').css('min-height', ($(window).height() - $('#page_body').offset().top - $('#page_foot').height() - 100));
		}
	}
	
	
	document.body.onclick = doc_click;
};

e2_def.get_time_period_from_date = function(time_period, date_str)
{
	switch (time_period.toLowerCase())
	{
		case ('year'):
		case ('yearly'):
			return date_str.substr(0, 4);
		
		case ('quarter'):
		case ('quarterly'):
			var year = date_str.substr(0, 4)
			var month = Number(date_str.substr(5, 2).replace(/^0/, ''));
			var quarter = Math.ceil(month / 3);
			var quarter_start_month = String(((quarter - 1) * 3) + 1).pad(2, '0', 'LEFT');
			return (year+'-'+quarter_start_month);
		
		case ('month'):
		case ('monthly'):
			return date_str.substr(0, 7);
		
		case ('week'):
		case ('weekly'):
			var date = Date.str_to_js(date_str);
			var time = date.getTime();
			var day_of_week = ((date.getDay() - 1) + 7) % 7; // make Monday 0 instead of Sunday.. -1 + 7.. js = bad with negative modulus
			var closest_monday = new Date(time - (day_of_week * 86400000));
			return Date.js_to_str(closest_monday);
		
		case ('all'):
		default:
			return 'All';
	}
};

e2_def.init_chars_left = function()
{
	$('.chars_left').each(function(){
		var elem, driver;
		
		elem = $(this);
		driver = $('#'+elem.attr('driver'));
		driver.keyup(function(){e2.show_chars_left($(this), $('[driver="'+this.id+'"]'));});
		e2.show_chars_left(driver, elem);
	});
};

e2_def.show_chars_left = function(driver, elem)
{
	var num_left, len, func, hook_func;
	
	if (driver.is('[length_func]'))
	{
		func = driver.attr('length_func').split('.');
		if (func.length > 1)
		{
			if (window[func[0]] && window[func[0]][func[1]])
			{
				len = window[func[0]][func[1]](driver);
			}
		}
	}
	else
	{
		len = driver.val().length;
	}
	hook_func = elem.data('length_hook');
	if (hook_func) {
		len = hook_func(len, driver, elem);
	}
	num_left = Number(elem.attr('max')) - len;
	if (num_left < 0)
	{
		elem.text((num_left * -1)+' over');
		if (!elem.is('.error_msg'))
		{
			elem.addClass('error_msg');
		}
	}
	else
	{
		elem.text(num_left+' left');
		if (elem.is('.error_msg'))
		{
			elem.removeClass('error_msg');
		}
	}
};

e2_def.ad_title_length = function(input)
{
	var title, matches;
	
	title = input.val();
	matches = title.match(/{keyword:(\s*)(.*)}/i);
	if (matches)
	{
		title = matches[2];
	}
	return title.length;
};

e2_def.auto_focus = function(parent)
{
	parent.find('[focus_me]').focus();
};

e2_def.auto_time_picker = function(parent)
{
	var keys = ['h_per_row', 'm_per_row', 'm_step'];

	parent.find('.time_input').each(function(){
		var i, k, opts = {},
			$this = $(this);

		for (i = 0; i < keys.length; ++i) {
			k = keys[i];
			if ($this.attr(k)) {
				opts[k] = $this.attr(k);
			}
		}
		$this.time_picker(opts);
	});
};

e2_def.auto_date_range = function(parent)
{
	parent.find('.date_range_placeholder').date_range();
};

e2_def.auto_date_picker = function(parent)
{
	parent.find('.date_input').date_picker();
};

e2_def.auto_action_submit = function(parent)
{
	parent.find('input[a0], button[type="submit"][a0]').bind('click', null, function(e){e2.a0_submit(e, $(this));});
	parent.find('select[a0href]').bind('change', null, function(e){e2.a0_submit(e, $(this)); $('#f').submit();});

	parent.find('a[a0]').bind('click', null, function(e){e2.a0_link_submit(e, $(this)); return false;});
};

e2_def.auto_select_submit = function(parent)
{
	$('[submit_on_change]').bind('change', null, function(e){submit_form();});
};

e2_def.a0_link_submit = function(e, elem)
{
	var data = elem.data();
	$.each(data, function(key, value) {
	    $('#f').append('<input type="hidden" name="'+key+'" value="'+value+'" />');
	});
	this.a0_submit(e, elem);
	$('#f').submit();
};

e2_def.a0_submit = function(e, elem)
{
	// branch to auto call ejo defined function for inputs with a0
	//var ejo_name, i, ejos, ejo;
	
	//ejo_name = elem.closest('[ejo]').att
	//for (ejo_name in window.ejos)
	//{
		//ejos = window.ejos[ejo_name];
		//for (i = 0; i < ejos.length; ++
		//dbg(ejo_name, ejos.length);
	//}
	
	// check for href
	if (elem.attr('a0href'))
	{
		$('#f').attr('action', elem.attr('a0href'));
	}
	
	if(elem.attr('a0')){
		$f('a0', elem.attr('a0'));
	}
};

e2_def.action_submit = function(action)
{
	$f('a0', action);
	f_submit();
};

e2_def.init_ejos = function()
{
	window.ejos = {};
	
	$('[ejo]').each(function(){
		var elem, ejo, ejo_name;
		
		elem = $(this);
		ejo_name = elem.attr('ejo');
		if (!ejo_name)
		{
			if (elem.attr('id'))
			{
				ejo_name = elem.attr('id');
			}
			else
			{
				return;
			}
		}
		
		if (typeof(window[ejo_name]) == 'function')
		{
			if (!window.ejos[ejo_name])
			{
				window.ejos[ejo_name] = [];
			}
			// hacks! this allows ejo to act as jquery object during construction
			// but there can be more than one ejo of the same type, so we need to tie jqo to the ejo prototype every time
			$.extend(window[ejo_name].prototype, $(this), new post_ability());
			ejo = new window[ejo_name]();
			$.extend(ejo, $(this), new post_ability());
			
			window.ejos[ejo_name].push(ejo);
		}
	});
};

e2_def.init_modules = function()
{
	var pages;
	
	window.modules = {};
	
	// use page menu to get active page as default page may not be in global pages array
	pages = $('#page_menu a.on').attr('href');
	if (pages)
	{
		if (pages.indexOf('?') != -1)
		{
			pages = pages.substr(0, pages.indexOf('?'));
		}
		pages = pages.trim('/').split('/');
	}
	else
	{
		pages = globals.pages;
	}
	e2_def.init_modules_recur(pages, 0);
};

e2_def.init_modules_recur = function(pages, start_i)
{
	var i, slice;
	
	for (i = start_i; i < pages.length; ++i)
	{
		slice = pages.slice(start_i, i + 1).join('_');
		if (typeof(window[slice]) == 'function')
		{
			e2.register(slice);
		}
	}
	if (start_i < pages.length)
	{
		e2_def.init_modules_recur(pages, start_i + 1);
	}
};

e2_def.get_ejo = function(target_ejo_name, search_func)
{
	var i, ejo;
	
	if (!window.ejos[target_ejo_name])
	{
		return false;
	}
	for (i = 0; i < window.ejos[target_ejo_name].length; ++i)
	{
		ejo = window.ejos[target_ejo_name][i];
		if (search_func(ejo))
		{
			return ejo;
		}
	}
	return false;
};

e2_def.register = function(slice)
{
	var mod;
	
	mod = new window[slice]();
	$.extend(mod, new post_ability());
	window.modules[slice] = mod;
	
	if (mod.post_register)
	{
		mod.post_register();
	}
};

e2_def.url = function(url)
{
	return ((url.charAt(0) == '/') ? url : '/'+url);
};

e2_def.http_get = function(url)
{
	window.location.assign(e2.url(url));
};

e2_def.new_window = function(url)
{
	new_window = window.open(e2.url(url), '_blank');
	new_window.focus();
};

e2_def.loading = function()
{
	return '<img src="/img/loading.gif" />';
};

e2_def.init_menu = function()
{
	var container, links, elem, i;
	
	// dbg
	$('a#dbg_start_link').click(function(e){dbg_start();return false;});
	$('a#dbg_stop_link').click(function(e){dbg_stop();return false;});
	
	// module select
	$('#module_select').change(function(e){module_select_change(e);});
	
	// row 2
	// client change
	$('select#client').change(client_change);
};

e2_def.cboxes_init = function(parent)
{
	parent.find('.cboxes_wrapper .toggle_all').bind('click', this, function(e){e.data.cboxes_toggle_all_click($(this));});
	parent.find('.cboxes_wrapper input[type="checkbox"]:not([class^="toggle_all"])').bind('click', this, function(e){e.data.cboxes_click($(this));});
	parent.find('.cboxes_wrapper').each(function(){
		e2.cboxes_set_val($(this));
	});
};

e2_def.cboxes_toggle_all_click = function(cbox)
{
	var wrapper = cbox.closest('.cboxes_wrapper');
	wrapper.find('input[type="checkbox"]:not([class^="toggle_all"])'+((cbox.is(':checked')) ? ':not(:checked)' : ':checked')).each(function(){
		this.click();
	});
};

e2_def.cboxes_click = function(cbox)
{
	e2.cboxes_set_val(cbox.closest('.cboxes_wrapper'));
	cbox.closest('span').toggleClass('on');
};

e2_def.cboxes_set_val = function(wrapper)
{
	var cboxes = wrapper.find('input[type="checkbox"]:not([class^="toggle_all"])'),
		checked = cboxes.filter(':checked'),
		separator = wrapper.attr('separator') ? wrapper.attr('separator') : "\t",
		val = (cboxes.length == checked.length && !wrapper.attr('no_star')) ? '*' : checked.map(function(){return $(this).val();}).get().join(separator),
		id = wrapper.attr('id'),
		$input = $('input[name="'+id+'"]')
	;
	
	// append hidden input if no input for val
	if ($input.length == 0) {
		wrapper.append('<input type="hidden" name="'+id+'" value="" />');
		$input = $('input[name="'+id+'"]');
	}
	$input.val(val);
};

e2_def.is_user_god = function()
{
	return ($('#h1_links a:contains("Admin")').length);
};

e2_def.logout = function()
{
	$f('go', 'logout');
	f_act('/');
};

e2_def.job_status_widget = function($container)
{
	var job_id = (arguments.length > 1) ? arguments[1] : $container.attr('job_id'),
		callback = (arguments.length > 2) ? arguments[2] : false
	;
	this.job_status_widget_update($container, job_id, callback);
};

e2_def.job_status_widget_update = function($container, job_id, callback)
{
	var req = {
		_ajax_func_:'ajax_job_status_widget',
		jid:job_id
	};
	$.post(e2.url('/delly'), req, this.job_status_widget_callback.bind(this, $container, job_id, callback));
};

e2_def.job_status_widget_callback = function($container, job_id, callback, response)
{
	var widget;

	$container.html(response);
	widget = $container.find('.job_status_widget');
	// still running, check back
	if (widget.is('.running')) {
		$container.prepend(e2.loading());
		window.setTimeout(this.job_status_widget_update.bind(this, $container, job_id, callback), 2345);
	}
	else if (callback) {
		callback(job_id);
	}
};

window.e2 = e2_def;

})(window);

$(document).ready(function(){e2.init();});
