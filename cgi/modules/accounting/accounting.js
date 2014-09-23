
// 70 days without a payment = cancelled
payments_wrapper.prototype.CANCEL_CUTOFF = 86400000 * 70;

payments_wrapper.prototype.churn_types = ['Active', 'New', 'Cancel'];

function payments_form()
{
	this.find('tr td:first-child').css('font-weight', 'bold');
}

function payments_wrapper()
{
	var self = this;
	
	this.init_form();
	this.init_data();
	
	$('#payments').table({
		data:this.data,
		cols:this.cols,
		sort_init:$f('date_type'),
		sort_dir_init:'desc',
		show_totals_top:true,
		click_tr:this.row_click.bind(this),
		show_download:true
	});
	
	if (this.is_details)
	{
		this.highlight_dates_click($('#highlight_dates'));
	}
}

payments_wrapper.prototype.init_form = function()
{
	this.date_type = $f('date_type').toLowerCase();
	this.do_show_both_date_types = $('#do_show_both_date_types').is(':checked');
	this.time_period = $f('time_period');
	this.is_time_period = (this.time_period != 'Off');
	this.is_details = !this.is_time_period;
	this.do_show_sales_reps = $('#do_show_sales_reps').is(':checked');
	
	this.depts = $f('department').split("\t");
	this.manager = globals.manager;
	
	if (this.is_details)
	{
		$('#highlight_dates_tr').show();
		$('#highlight_dates').bind('click', this, function(e){ e.data.highlight_dates_click($(this)); });
	}
	
	this.init_form_manager_select();
};

payments_wrapper.prototype.highlight_dates_click = function(checkbox)
{
	if (checkbox.is(':checked'))
	{
		$('#highlight_dates_legend').show();
		this.highlight_dates();
	}
	else
	{
		$('#highlight_dates_legend').hide();
		this.find('tr').removeClass('first_payment').removeClass('final_payment');
	}
};

payments_wrapper.prototype.highlight_dates = function()
{
	var i, d, first_last, dept, dates, all_first, all_last, cl_map;
	
	// if same client made more than one payment on first or final day
	cl_map = {'first':{},'last':{}};
	for (i = 0; i < globals.payments_data.length; ++i)
	{
		d = globals.payments_data[i];
		first_last = globals.first_last_payments[d.cl_id];
		if (first_last)
		{
			all_first = all_last = true;
			for (dept in first_last)
			{
				dates = first_last[dept];
				if (all_first && dates[0] != d[this.date_type])
				{
					all_first = false;
				}
				if (all_last && dates[1] != d[this.date_type])
				{
					all_last = false;
				}
			}
			if (all_first && !cl_map['first'][d.cl_id])
			{
				cl_map['first'][d.cl_id] = 1;
				this.find('tbody tr:eq('+i+')').addClass('first_payment');
			}
			if (all_last && !cl_map['last'][d.cl_id])
			{
				cl_map['last'][d.cl_id] = 1;
				this.find('tbody tr:eq('+i+')').addClass('final_payment');
			}
		}
		//dbg(data[i]);
	}
};

payments_wrapper.prototype.init_form_manager_select = function()
{
	$('.manager_select').bind('change', this, function(e){ e.data.manager_select_change($(this)); });
};

payments_wrapper.prototype.manager_select_change = function(select)
{
	$('.manager_select[id!="'+select.attr('id')+'"]').val('');
};

payments_wrapper.prototype.init_data = function()
{
	if (this.is_time_period) {
		this.init_data_time_periods();
	}
	else {
		this.init_data_clients();
	}
};

payments_wrapper.prototype.init_data_clients = function()
{
	var i, j, row, payment, dept, type, part_types, part_amounts, ac_dept_info;
	
	// init cols
	this.cols = [];
	if (this.do_show_both_date_types) {
		this.cols.push({key:'attributed',classes:'nowrap'});
		this.cols.push({key:'received',classes:'nowrap'});
	}
	else {
		this.cols.push({key:this.date_type,classes:'nowrap'});
	}
	this.cols.push({key:'client',classes:'nowrap'});
	this.cols.push({key:'cl_id',classes:'nowrap'});
	
	this.init_sales_rep_cols();
	
	for (type in globals.manager_depts) {
		if ($('#department-'+type).is(':checked')) {
			this.cols.push({key:type+'_manager',type:'alpha',classes:'nowrap'});
		}
	}
	this.cols.push({key:'total',format:'dollars'});
	
	this.data = [];
	this.part_types = {};
	for (i = 0; i < globals.payments.length; ++i) {
		// init row
		payment = globals.payments[i];
		
		row = {
			pid:payment.pid,
			cl_id:payment.cl_id,
			client:'',
			total:payment.total,
			notes:payment.notes
		};
		
		if (this.do_show_both_date_types) {
			row.attributed = payment.attributed;
			row.received = payment.received;
		}
		else {
			row[this.date_type] = payment[this.date_type];
		}
		
		// add sales rep if we are showing
		if (this.do_show_sales_reps) {
			if (payment.sales_reps) {
				row.sales_reps = payment.sales_reps;
			}
		}
		
		// init managers to empty string
		for (dept in globals.manager_depts) {
			row[dept+'_manager'] = '';
		}
		
		// add in part types
		part_types = payment.part_types.split("\t");
		part_amounts = payment.part_amounts.split("\t");
		for (j = 0; j < part_types.length; ++j) {
			type = part_types[j];
			dept = globals.type_to_dept[type];
			
			if(typeof globals.ac_info[row.cl_id] != 'undefined'){
				if (typeof globals.ac_info[row.cl_id][dept] != 'undefined') {
					ac_dept_info = globals.ac_info[row.cl_id][dept];
					if (!row.client || row.client.length == 0) {
						row.client = ac_dept_info.name;
						console.log("SUCCESS ADD | client_id: "+row.cl_id+" client_name: "+row.client+" dept: "+dept+" type: "+type+" object: "+JSON.stringify(globals.ac_info[row.cl_id]));	
					}
					if (ac_dept_info.manager) {
						row[dept+'_manager'] = ac_dept_info.manager;
					}
				}else{
					console.log("FAILED DEPT | client_id: "+row.cl_id+" client_name: "+JSON.stringify(globals.ac_info[row.cl_id])+" dept: "+dept+" type: "+type);
				}
			}else{
				console.log("FAILED ID | client_id: "+row.cl_id+" client_name: "+row.name+" dept: "+dept+" type: "+type);
			}

			this.part_types[type] = 1;
			row[type] = part_amounts[j];
		}
		this.data.push(row);
	}
	
	// this will keep part types in same order
	for (type in globals.type_to_dept) {
		if (this.part_types[type]) {
			this.cols.push({key:type,format:'dollars',type:'numeric'});
		}
	}
	this.cols.push({key:'notes'});
};


payments_wrapper.prototype.init_sales_rep_cols = function()
{
	var i, cl_id, rep, reps, sorted;
	
	if (this.do_show_sales_reps) {
		this.cols.push({key:'sales_reps',classes:'nowrap'});
	}
};

payments_wrapper.prototype.time_period_check_sales_rep = function(data, payment)
{
	var rep;
	
	if (this.do_show_sales_reps && globals.sales_reps[payment.cl_id])
	{
		rep = globals.sales_reps[payment.cl_id];
		accumulate(data, [rep], Number(payment.total));
	}
};

payments_wrapper.prototype.init_data_time_periods = function()
{
	var i, j, row, payment, dept, type, part_types, part_amounts, tmp_data, time_period, cl_active, cl_amounts;
	
	// init cols
	this.cols = [
		{key:'date'},
		{key:'total',format:'dollars'}
	];
	
	//this.init_sales_rep_cols();
	
	// loop over payments, group by time period
	tmp_data = {};
	cl_active = {};
	cl_amounts = {};
	for (i = 0; i < globals.payments.length; ++i)
	{
		payment = globals.payments[i];
		time_period = e2.get_time_period_from_date(this.time_period, payment[this.date_type]);
		if (!tmp_data[time_period])
		{
			tmp_data[time_period] = {
				date:time_period,
				total:0
			};
		}
		tmp_data[time_period].total += Number(payment.total);
		
		part_types = payment.part_types.split("\t");
		part_amounts = payment.part_amounts.split("\t");
		for (j = 0; j < part_types.length; ++j)
		{
			type = part_types[j];
			dept = globals.type_to_dept[type];
			
			// active
			if (!cl_active[payment.cl_id])
			{
				cl_active[payment.cl_id] = {};
			}
			if (!cl_active[payment.cl_id][time_period])
			{
				cl_active[payment.cl_id][time_period] = {};
				this.set_churn_add(tmp_data, payment[this.date_type], 'Active');
			}
			if (!cl_active[payment.cl_id][time_period][dept])
			{
				cl_active[payment.cl_id][time_period][dept] = 1;
				this.set_churn_add(tmp_data, payment[this.date_type], 'Active '+dept);
			}
			
			//this.time_period_check_sales_rep(tmp_data[time_period], payment);
			
			accumulate(cl_amounts, [payment.cl_id, time_period, dept], Number(part_amounts[j]));
			accumulate(tmp_data, [time_period, type], Number(part_amounts[j]));
		}
	}
	
	// if amount client payed in time period was <= 0 (refunds), do not mark as active
	for (i in cl_amounts)
	{
		for (time_period in cl_amounts[i])
		{
			for (dept in cl_amounts[i][time_period])
			{
				if (cl_amounts[i][time_period][dept] <= 0)
				{
					tmp_data[time_period]['Active']--;
					tmp_data[time_period]['Active '+dept]--;
				}
			}
		}
	}
	
	// new, cancelled data
	this.set_churn_new_and_cancel(tmp_data);
	
	this.data = [];
	this.part_types = {};
	this.churn_cols = {};
	for (time_period in tmp_data)
	{
		for (i in tmp_data[time_period])
		{
			if (i.match(new RegExp('^('+this.churn_types.join('|')+') ')))
			{
				this.churn_cols[i] = 1;
			}
			if (globals.type_to_dept[i])
			{
				this.part_types[i] = 1;
			}
		}
		this.data.push(tmp_data[time_period]);
	}
	
	// loop like this so part types stay in same order
	for (type in globals.type_to_dept)
	{
		if (this.part_types[type])
		{
			this.cols.push({key:type,format:'dollars',type:'numeric'});
		}
	}
	
	// add global churn cols
	for (i = 0; i < this.churn_types.length; ++i)
	{
		this.cols.push({key:this.churn_types[i],type:'numeric'});
	}
	
	// dept churn cols
	for (i = 0; i < globals.depts.length; ++i)
	{
		dept = globals.depts[i];
		for (j = 0; j < this.churn_types.length; ++j)
		{
			if (this.churn_cols[this.churn_types[j]+' '+dept])
			{
				this.cols.push({key:this.churn_types[j]+' '+dept,type:'numeric'});
			}
		}
	}
};

payments_wrapper.prototype.set_churn_new_and_cancel = function(data)
{
	var cl_id, cl_depts, cl_first, cl_last, is_all_cancelled, type, dept, dates, date, time_period, first, last, last_date, cancel_cutoff;
	
	for (cl_id in globals.first_last_payments) {
		// client can have multiple payments in same department
		// but we only want to count once
		// also track which depts client has cancelled
		cl_depts = {};
		cl_first = cl_last = null;
		for (type in globals.first_last_payments[cl_id]) {
			dept = globals.type_to_dept[type];
			if (dept && Array.in_array(this.depts, dept) && !cl_depts[dept]) {
				cl_depts[dept] = {cancelled:false};
				
				first = globals.first_last_payments[cl_id][type][0];
				last = globals.first_last_payments[cl_id][type][1];
				
				if (!cl_first || first < cl_first) cl_first = first;
				if (!cl_last || last > cl_last) cl_last = last;
				
				last_date = Date.str_to_js(last);
				if ((last_date.getTime() + this.CANCEL_CUTOFF) < Date.now()) {
					cl_depts[dept].cancelled = true;
					this.set_churn_add(data, last, 'Cancel '+dept);
				}
				this.set_churn_add(data, first, 'New '+dept);
			}
		}
		if (!empty(cl_depts)) {
			this.set_churn_add(data, cl_first, 'New');
			is_all_cancelled = true;
			for (dept in cl_depts) {
				if (!cl_depts[dept].cancelled) {
					is_all_cancelled = false;
					break;
				}
			}
			if (is_all_cancelled) {
				this.set_churn_add(data, cl_last, 'Cancel');
			}
		}
	}
};

payments_wrapper.prototype.set_churn_add = function(data, date, type)
{
	var time_period;
	
	//dbg(date, time_period, type);
	time_period = e2.get_time_period_from_date(this.time_period, date);
	
	// only need to add if we have data for this time period
	if (data[time_period])
	{
		if (!data[time_period][type])
		{
			data[time_period][type] = 0;
		}
		data[time_period][type]++;
	}
};

payments_wrapper.prototype.row_click = function(e, tr, data)
{
	var dept, url, aid;
	
	if (this.is_time_period) {
		return;
	}
	
	dept = this.get_outgoing_dept(data);
	aid = globals.ac_info[data.cl_id][dept].id;
	url = e2.url('/account/service/'+dept+'/billing/edit_payment?aid='+aid+'&pid='+data.pid);
	
	new_window = window.open(url, '_blank');
	new_window.focus();
};

payments_wrapper.prototype.get_outgoing_dept = function(data)
{
	var type, dept;
	
	for (type in globals.type_to_dept)
	{
		dept = globals.type_to_dept[type];
		if (dept && data[type])
		{
			return ((type == 'QuickList Pro') ? 'ppc' : dept);
		}
	}
	return 'ppc';
};

function search_results_tbody()
{
	$('#search_results_tbody .billing_name').bind('click', this, function(e){ e.data.billing_name_click($(this)); return false; });
}

search_results_tbody.prototype.billing_name_click = function(a)
{
	if (!a.attr('has_retrieved_accounts'))
	{
		var $tr = a.closest('tr');
		a.attr('has_retrieved_accounts', 1);
		$tr.after('\
			<tr>\
				<td></td>\
				<td colspan=100 class="account_results"><img src="'+loading_gif()+'" /></td>\
			</tr>\
		');
		this.post('get_account_links', {
			row_index:$tr.prevAll().length,
			cl_id:a.attr('cl_id')
		});
	}
};

search_results_tbody.prototype.get_account_links_callback = function(request, response)
{
	var $parent_row = $('#search_results_tbody tr:eq('+request.row_index+')'),
		$td = $parent_row.next(':eq(0)').find('.account_results');
	$td.html('<p>Client Accounts: '+response.html+'</p>');
dbg($parent_row.length, $td.length, request, response);
	if (response.plain_text) {
		$td.find('a').each(function(){
			var $a = $(this),
				text = $a.text();
			;
			$a.wrap('<span />');
			var $span = $a.closest('span');
			$a.remove();
			$span.text(text);
		});
	}
};

function accounting_ppc_spend_index()
{
	this.data = window.globals.data;
	
	this.sub_table('market');
	this.sub_table('who_pays');
	
	$('#all_data').table({
		data:this.data,
		cols:[
			{key:'market'},
			{key:'who_pays'},
			{key:'spend',format:'dollars'},
		]
	});
}

accounting_ppc_spend_index.prototype.sub_table = function(key)
{
	var sub_data, i, j, d, dsub, k, v, q;
	
	sub_data = [];
	for (i = 0; i < this.data.length; ++i)
	{
		d = this.data[i];
		for (j = 0; j < sub_data.length; ++j)
		{
			dsub = sub_data[j];
			
			// found key we are looking for
			if (dsub[key] == d[key])
			{
				for (k in d)
				{
					v = d[k];
					if (Types.is_numeric(v))
					{
						dsub[k] = Number(dsub[k]) + Number(v);
					}
				}
				break;
			}
		}
		// didn't find out key
		if (j == sub_data.length)
		{
			dsub = $.extend(true, {}, d);
			q = key+'='+escape(dsub[key])+'&start_date='+$('#start_date').val()+'&end_date='+$('#end_date').val();
			dsub['_display_'+key] = '<a href="'+e2.url('/accounting/ppc_spend/account_list?'+q)+'" target="_blank">'+((dsub[key]) ? dsub[key] : '(none)')+'</a>';
			sub_data.push(dsub);
		}
	}
	$('#by_'+key).table({
		data:sub_data,
		cols:[
			{key:key},
			{key:'spend',format:'dollars'}
		]
	});
};

