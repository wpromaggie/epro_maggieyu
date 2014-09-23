
function sales_rep_breakdown()
{
	var self = this;
	
	// switch from signup commission to recurring commission
	this.COMMISSION_PAYMENTS_COUNT_MAX = 12;
	this.GRACE_PERIOD_CUTOFF_DATE = window.globals.grace_period_cutoff_date;
	this.GRACE_PERIOD_FIRST_VALID_PAYMENT = 7;
	
	$('#breakdown').html(e2.loading());
	$('#download').bind('click', this, function(e){ e.data.download_click(); return false; });
	window.setTimeout(function(){ self.init(); }, 256);
}

sales_rep_breakdown.prototype.download_click = function()
{
	var data, headers, self = this;

	// did not have great luck using pure js download, we'll send data back to server
	// gross, but much better imho than re-computing everything in php when we have already
	// done the work in js
	data = '';
	headers = '';
	$('div.rep').each(function(){
		var row_data, i, col, val, matches, $cells,
			$div = $(this),
			rep_name = $div.find('h1').text(),
			$rows = $div.find('.wpro_table tbody tr'),
			rep_cols = self.all_cols_to_rep_col[rep_name]
		;

		// only need to set headers once
		if (headers === '') {
			headers = 'Rep';
			for (j = 0; j < self.cols.length; ++j) {
				headers += ','+self.cols[j].key.display_text();
			}
		}
		for (i = 0; i < $rows.length; ++i) {
			$cells = $($rows[i]).find('td');
			row_data = rep_name;
			for (j = 0; j < self.cols.length; ++j) {
				if (rep_cols[j]) {
					col = self.cols[j];
					// +1, first column just has row number
					val = $($cells[rep_cols[j]]).text();
					if (col.format == 'dollars') {
						val = val.replace(/[$,]/g, '');
					}
					else if (col.key == 'notes') {
						matches = val.match(/^(.*)edit\s*$/);
						if (matches) {
							val = matches[1].trim();
						}
					}
				}
				// rep doesn't have any data for this key
				else {
					val = '';
				}
				// let's just get rid of commas instead of escaping them
				row_data += ','+val.replace(/,/g, '');
			}
			data += row_data+"\n";
		}
	});
	$('#dldata').val(headers+"\n"+data);
	e2.action_submit('action_download');
};

sales_rep_breakdown.prototype.init = function()
{
	this.init_cols();
	this.init_data();
	this.display_data();

	$('.rep').on('click', this, function(e) { return e.data.rep_click($(e.target)); });
};

sales_rep_breakdown.prototype.rep_click = function($target)
{
	if ($target.is('.pay_num_link')) {
		this.pay_num_click($target);
		return false;
	}
	if ($target.is('.pay_comm_link')) {
		this.pay_comm_click($target);
		return false;
	}
	if ($target.is('.notes_link')) {
		this.notes_click($target);
		return false;
	}
	else {
		return true;
	}
};

sales_rep_breakdown.prototype.init_cols = function()
{
	var i, type, dept;
	
	this.cols = [
		{key:'date'},
		{key:'client'},
		{key:'com_type'},
		{key:'total',format:'dollars'},
		{key:'total_com',format:'dollars'}
	];

	// for download, we need to be able to map back from rep column to full column
	this.all_cols_to_rep_col = {};
	
	// so we know which reps need which columns
	this.rep_dept_map = {};
	
	// more than one management type can map to the same department
	// we only care about department, don't add more than once
	this.depts = {};
	for (i = 0; i < globals.management_part_types.length; ++i)
	{
		type = globals.management_part_types[i];
		dept = globals.type_to_dept[type];
		if (!this.depts[dept])
		{
			this.depts[dept] = 1;
			this.cols.push({key:dept,format:'dollars'});
			this.cols.push({key:dept+'_com',display:'Com',format:'dollars'});
			this.cols.push({key:dept+'_num',display:'#',totals_val:'-'});
		}
	}
	if (window.globals.is_multiview)
	{
		this.cols.push({key:'notes',type:'alpha'});
	}
};

sales_rep_breakdown.prototype.init_data = function()
{
	var i, p, preps, prep, part_types, part_amounts, part_ids, part_pay_nums, part_pay_comms, pay_nums_for_this_payment, type, amount, amount_com, dept, pay_num, rep, has_valid_commission_payment, ac_info
		start_date = $('#start_date').val(),
		pay_nums = {},
		first_payments = {};
	
	// track user set pay numbers
	this.user_pay_num_hist = {};
	
	// data is broken down by rep
	this.data = {};
	
	for (i = 0; i < globals.payments.length; ++i) {
		p = globals.payments[i];
		preps = {};

		part_ids = p.part_ids.split("\t");
		part_types = p.part_types.split("\t");
		part_amounts = p.part_amounts.split("\t");
		part_pay_nums = p.rep_pay_nums.split("\t");
		part_pay_comms = p.rep_comms.split("\t");
		
		has_valid_commission_payment = false;
		pay_nums_for_this_payment = {};
		for (j = 0; j < part_types.length; ++j) {
			type = part_types[j];
			dept = globals.type_to_dept[type];

			// should not happen
			if (!(p.cl_id in globals.ac_info)) {
				// Feedback.add_error_msg('no client?? ('+type+', '+dept+'): '+String.to_query(p));
				continue;
			}
			// what if a client was charged for something that they don't have an account for?
			// eg a ppc client is getting charged seo part types even though there is no
			// seo account associated with the client
			ac_info = false;
			if (dept in globals.ac_info[p.cl_id]) {
				ac_info = globals.ac_info[p.cl_id][dept];
			}
			else {
				var other_dept;
				for (other_dept in globals.ac_info[p.cl_id]) {
					// doesn't matter which one we use
					ac_info = globals.ac_info[p.cl_id][other_dept];
					break;
				}
				// most likely cause: no sales rep assigned
				if (ac_info === false) {
					// Feedback.add_error_msg('no account info?? ('+type+', '+dept+'): '+String.to_query(p));
					continue;
				}
			}

			// different accounts can be associated with different sales reps
			// for the same client/payment
			if (!preps[ac_info.rep]) {
				// init payment for this rep
				prep = $.extend({}, p);
				prep.client = ac_info.name;
				prep.com_type = ac_info.com_type;
				prep.has_valid_commission_payment = false;
				prep.total = 0;
				prep.total_com = 0;
				prep.rep = globals.sales_reps[ac_info.rep];

				preps[ac_info.rep] = prep;
			}
			else {
				prep = preps[ac_info.rep];
			}

			pay_num = Number(part_pay_nums[j]);
			
			// how many payments of this type for this client?
			// check for manually set
			if (pay_num) {
				prep[dept+'_num_user_set'] = 1;
				pay_nums[prep.cl_id][dept] = pay_num;
				accumulate(this.user_pay_num_hist, [prep.cl_id, dept], 1);
			}
			// only accumulate if first time we are seeing a payment type for this department for this payment
			else if (!pay_nums_for_this_payment[dept]) {
				pay_nums_for_this_payment[dept] = 1;
				accumulate(pay_nums, [prep.cl_id, dept], 1);
			}
			if (pay_nums[prep.cl_id][dept] == 1) {
				accumulate(first_payments, [prep.cl_id, dept], prep.date);
			}
			
			// only need to do this stuff for payments we actually might show
			if (prep.date >= start_date) {
				if (
					(
						(this.was_pay_num_prev_set_for_dept(prep, dept) || !(first_payments[prep.cl_id][dept] < this.GRACE_PERIOD_CUTOFF_DATE && pay_nums[prep.cl_id][dept] < this.GRACE_PERIOD_FIRST_VALID_PAYMENT)) &&
						pay_nums[prep.cl_id][dept] <= this.COMMISSION_PAYMENTS_COUNT_MAX
					)
					||
					$('#do_show_all').is(':checked')
				) {
					prep.has_valid_commission_payment = true;
					
					// multiple payment types per department possible?
					amount = Number(part_amounts[j]);
					// check for manually set
					if (part_pay_comms[j] != -1) {
						amount_com = Number(part_pay_comms[j]);
						prep[dept+'_comm_user_set'] = 1;
					}
					else {
						amount_com = (globals.com_vals[dept][ac_info.com_type]) ? (globals.com_vals[dept][ac_info.com_type] * amount * .01) : 0;
					}
					prep.total += amount;
					prep.total_com += amount_com;
					
					accumulate(prep, [dept], amount);
					accumulate(prep, [dept+'_com'], amount_com);
					prep[dept+'_num'] = pay_nums[prep.cl_id][dept];
					prep[dept+'_part_id'] = part_ids[j];
					prep['_display_'+dept+'_num'] = this.get_pay_num_display(prep, dept);
					prep['_display_'+dept+'_com'] = this.get_pay_comm_display(prep, dept);
					
					accumulate(this.rep_dept_map, [dept, ac_info.rep], 1);
				}
			}
		}
		for (rep in preps) {
			prep = preps[rep];
			if (prep.has_valid_commission_payment) {
				prep._display_client = this.get_client_display(prep);
				prep._display_date = this.get_payment_date_display(prep);
				prep._display_notes = this.get_notes_display(prep);
				// add rep to array if first time we have seen them
				if (!this.data[rep]) {
					this.data[rep] = [];
				}
				this.data[rep].push(prep);
			}
		}
	}
};

sales_rep_breakdown.prototype.was_pay_num_prev_set_for_dept = function(p, dept)
{
	return (this.user_pay_num_hist[p.cl_id] && this.user_pay_num_hist[p.cl_id][dept]);
};

sales_rep_breakdown.prototype.get_notes_display = function(p)
{
	var notes_text, is_empty;
	if (p.notes)
	{
		notes_text = html.encode(p.notes);
		is_empty = 0;
	}
	else
	{
		notes_text = '';
		is_empty = 1;
	}
	return '\
		<p class="note">\
			<span class="notes_text">'+notes_text+'</span>\
			<a pid="'+p.pid+'" href="" is_empty="'+is_empty+'" class="notes_link">edit</a>\
		</p>\
	';
};

sales_rep_breakdown.prototype.get_payment_date_display = function(p)
{
	var dept, url, aid;

	if (window.globals.is_multiview) {
		dept = this.get_outgoing_dept(p);
		
		if (typeof globals.ac_info[p.cl_id][dept] != 'undefined'){
			aid = globals.ac_info[p.cl_id][dept]['id'];
		} else {
			aid = 'undefined';
		}
		url = e2.url('/account/service/'+dept+'/billing/edit_payment?aid='+aid+'&pid='+p.pid);
		return '<a href="'+url+'" target="_blank">'+p.date+'</a>';
	}
	else {
		return p.date;
	}
};

sales_rep_breakdown.prototype.get_client_display = function(p)
{
	var dept = this.get_outgoing_dept(p);
	
	if (window.globals.is_multiview)
	{
		return p.client;
	}
	else
	{
		return '<a href="'+e2.url('/'+dept+'/client?cl_id='+p.cl_id)+'" target="_blank">'+p.client+'</a>';
	}
};

sales_rep_breakdown.prototype.get_outgoing_dept = function(p)
{
	var type, dept;
	
	for (type in globals.type_to_dept) {
		dept = globals.type_to_dept[type];
		if (dept && dept in p) {
			return dept;
		}
	}
	return 'ppc';
};

sales_rep_breakdown.prototype.get_pay_num_display = function(row, dept)
{
	var val = row[dept+'_num'];
	
	if (isNaN(val))
	{
		return ((val) ? val : '');
	}
	else
	{
		if (window.globals.is_multiview)
		{
			return '<a href="" class="pay_num_link'+((row[dept+'_num_user_set']) ? ' user_set' : '')+'" part_id="'+row[dept+'_part_id']+'">'+val+'</a>';
		}
		else
		{
			return val;
		}
	}
};

sales_rep_breakdown.prototype.pay_num_click = function(link)
{
	var new_num = prompt('New payment number (set to 0 to clear):', link.text());
	if (new_num)
	{
		this.post('set_new_pay_num', {
			id:link.attr('part_id'),
			rep_pay_num:new_num
		});
	}
};

sales_rep_breakdown.prototype.set_new_pay_num_callback = function(request, response)
{
	Feedback.add_success_msg('Payment Number Updated');
};

sales_rep_breakdown.prototype.get_pay_comm_display = function(row, dept)
{
	var val = row[dept+'_com'];
	if (isNaN(val)) {
		return ((val) ? val : '');
	}
	else {
		if (window.globals.is_multiview) {
			return '<a href="" class="pay_comm_link'+((row[dept+'_comm_user_set']) ? ' user_set' : '')+'" part_id="'+row[dept+'_part_id']+'">'+Format.dollars(val)+'</a>';
		}
		else {
			return '<span class="'+((row[dept+'_comm_user_set']) ? ' user_set' : '')+'">'+Format.dollars(val)+'</span>';
		}
	}
};

sales_rep_breakdown.prototype.pay_comm_click = function(link)
{
	var new_num = prompt('New commission amount (set to -1 to return to default):', link.text());
	if (new_num) {
		this.post('set_new_pay_comm', {
			id:link.attr('part_id'),
			rep_comm:new_num
		});
	}
};

sales_rep_breakdown.prototype.set_new_pay_comm_callback = function(request, response)
{
	var $link = $('a.pay_comm_link[part_id="'+request.id+'"]');
	if (request.rep_comm == -1) {
		$link.removeClass('user_set');
	}
	else {
		$link.addClass('user_set');
	}
	Feedback.add_success_msg('Payment Commission Updated. You can refresh page to see updates.');
};

sales_rep_breakdown.prototype.notes_click = function(link)
{
	var cur_text = (link.attr('is_empty') == '1') ? '' : link.siblings('.notes_text').text(),
		new_text = prompt('Enter notes:', cur_text);
	if (typeof(new_text) == 'string')
	{
		this.post('set_notes', {
			id:link.attr('pid'),
			sales_notes:new_text
		});
	}
};

sales_rep_breakdown.prototype.set_notes_callback = function(request, response)
{
	var notes_text, is_empty,
		link = $('a[pid="'+request.id+'"]');
		
	if (request.sales_notes)
	{
		notes_text = html.encode(request.sales_notes);
		is_empty = 0;
	}
	else
	{
		notes_text = '';
		is_empty = 1;
	}
	link.siblings('.notes_text').text(notes_text);
	link.blur();
	link.attr('is_empty', is_empty);
	Feedback.add_success_msg('Notes Updated');
};

sales_rep_breakdown.prototype.display_data = function()
{
	var rep_id, rep_name, rep_key, loc, col, rep_cols, do_add_col;
	
	loc = $('#breakdown');
	loc.html('');
	for (rep_id in globals.sales_reps) {
		if (empty(this.data[rep_id])) {
			continue;
		}
		rep_name = globals.sales_reps[rep_id];
		this.all_cols_to_rep_col[rep_name] = {};

		loc.append('\
			<div class="rep">\
				<h1>'+rep_name+'</h1>\
				<table id="rep_'+rep_id+'"></table>\
			</div>\
		');
		
		rep_cols = [];
		for (i = 0; i < this.cols.length; ++i) {
			col = this.cols[i];
			do_add_col = false;
			// a dept column, next two column are com and #
			if (this.depts[col.key]) {
				// rep has at least one sale for this dept
				if (this.rep_dept_map[col.key] && this.rep_dept_map[col.key][rep_id]) {
					do_add_col = true;
				}
				// no sales, don't add to cols, also skip next two cols which are related
				else {
					i += 2;
				}
			}
			// not a dept col, add it
			else {
				do_add_col = true;
			}
			if (do_add_col) {
				rep_cols.push($.extend(true, {}, col));
				this.all_cols_to_rep_col[rep_name][i] = rep_cols.length;
			}
		}

		$('#rep_'+rep_id).table({
			data:this.data[rep_id],
			cols:rep_cols,
			sort_init:'date',
			sort_dir_init:'desc'
		});
	}
};

