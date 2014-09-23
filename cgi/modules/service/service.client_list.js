
function w_client_list()
{
	this.dept = this.attr('dept');
	this.init_data();
	$('#w_client_list').table({
		data:this.data,
		cols:this.get_cols(),
		show_totals:false,
		sort_init:this.get_sort_init()
	});
	
	// check for alternate filter options
	if (globals.alt_filter_options) {
		this.init_alternate_filter_options();
	}
	else {
		$('#manager_filter span').html(html.select('manager', globals.managers, $f('selected_manager')));
	}

	$('#ppc_data_pull td.running').each(function(){
		e2.job_status_widget($(this));
	});
	
	this.add_manager_listener();
	$('#status_filter select').bind('change', this, function(e){ e.data.get_client_list(); });
	$('#search').on('keydown', this.search_keydown.bind(this));
}

w_client_list.prototype.search_keydown = function(e)
{
	// make sure we submit the correct button if user presses enter
	if (e.which == 13) {
		e.preventDefault();
		$('#search_submit')[0].click();
	}
};

w_client_list.prototype.add_manager_listener = function()
{
	$('#manager').on('change', this, function(e){ e.data.get_client_list(); });
};

w_client_list.prototype.get_client_list = function()
{
	var filter_type = $('#filter_type_select').val();
	if (empty(filter_type)) {
		filter_type = 'manager';
	}
	e2.http_get(e2.url(window.location.pathname+'?manager='+$('#manager').val()+'&status='+$('#status').val()+'&filter_type='+filter_type));
};

w_client_list.prototype.init_data = function()
{
	var i, cl, page;
	
	this.data = [];
	for (i = 0; i < globals.clients.length; ++i) {
		cl = globals.clients[i];
		
		switch (globals.dept) {
			case ('partner'): page = '/info'; break;
			default:          page = ''; break;
		}
		cl._display_name = '<a href="'+e2.url('/account/service/'+this.dept+'?aid='+cl.id)+'">'+cl.name+'</a>';
		this.data.push(cl);
	}
};

w_client_list.prototype.init_alternate_filter_options = function()
{
	// add manager options to alternates
	globals.alt_filter_options.manager = globals.managers;
	
	// convert manager label to drop down
	var key, filter_keys = [];
	for (key in globals.alt_filter_options) {
		filter_keys.push([key, key.display_text()]);
	}
	filter_keys.sort();
	$('#manager_filter label').html(html.select('filter_type_select', filter_keys, $('#filter_type').val()));
	$('#filter_type_select').bind('change', this, function(e){ e.data.filter_type_change($(this)); });
	
	// set filter values
	this.filter_type_change($('#filter_type_select'), {init:true});
};

w_client_list.prototype.filter_type_change = function(filter_key_select)
{
	var opts = (arguments.length > 1) ? arguments[1] : {};
	$('#manager_filter span').html(html.select('manager', globals.alt_filter_options[filter_key_select.val()], (opts && opts.init) ? $f('selected_manager') : ''));
	this.add_manager_listener();
};

w_client_list.prototype.get_cols = function()
{
	var commons_cols, dept_cols_func;
	
	common_cols = [
		{key:'name'},
		{key:'next_bill_date'}
	];
	
	dept_cols_func = 'get_cols_'+globals.dept;
	if (this[dept_cols_func]) {
		return common_cols.concat(this[dept_cols_func]());
	}
	else {
		return common_cols;
	}
};

w_client_list.prototype.get_sort_init = function()
{
	dept_cols_func = 'get_sort_init_'+globals.dept;
	if (this[dept_cols_func]) {
		return this[dept_cols_func]();
	}
	else {
		return 'name';
	}
};

w_client_list.prototype.get_sort_init_ppc = function()
{
	return 'next_bill_date';
};

w_client_list.prototype.get_cols_ppc = function()
{
	return [
		{key:'bill_day'      },
		{key:'budget'        ,format:'dollars'},
		{key:'mo_spend'      ,format:'dollars'},
		{key:'yd_spend'      ,format:'dollars'},
		{key:'should_spend'  ,format:'dollars',calc:this.calc_ppc_should_spend.bind(this)},
		{key:'remaining'     ,format:'dollars',calc:this.calc_ppc_remaining.bind(this)},
		{key:'yar'           ,format:'n2'     ,calc:this.calc_ppc_yar.bind(this)}
	];
};

w_client_list.prototype.get_cols_partner = function()
{
	return [
		{key:'url'           }
	];
};

w_client_list.prototype.get_cols_seo = function()
{
	return [
		{key:'manager'     },
		{key:'link_builder'},
		{key:'url'         }
	];
};

w_client_list.prototype.calc_ppc_should_spend = function(data, key)
{
	return (this.safe_div(data.budget, data.days_in_month));
};

w_client_list.prototype.calc_ppc_remaining = function(data, key)
{
	return (this.safe_div(data.budget - data.mo_spend, data.days_remaining));
};

w_client_list.prototype.calc_ppc_yar = function(data, key)
{
	return (this.safe_div(data.yd_spend, this.calc_ppc_remaining(data, 'remaining')) * 100);
};

w_client_list.prototype.safe_div = function(x, y)
{
	return ((isNaN(x) || isNaN(y) || !y || y == '0') ? 0 : (x / y));
};

w_client_list.prototype.format_bill_day = function(val, data)
{
	return data.bill_day;
};

function add_client()
{
	this.find('#add_client_search').bind('click', this, function(e){ e.data.search(); return false; });
	this.find('#search_results_table input.add').bind('click', this, function(e){ e.data.add_client($(this)); return false; });
}

add_client.prototype.search = function()
{
	window.location.qset({
		keep:['add_client'],
		put:{
			q:$f('q')
		}
	});
};

add_client.prototype.add_client = function(button)
{
	var tr;
	
	tr = button.closest('tr');
	$f('add_client_id', tr.attr('cl_id'));
	$f('add_client_name', tr.find('td.cl_name').text());
	f_submit();
};

// ph for ejo
function download_client_list()
{
}
