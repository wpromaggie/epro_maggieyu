
// billing history and payment form
var bh, pf;

function billing_history()
{
	this.aid = window.location.get['aid'];
	this.init_data_and_cols();
	this.history_table = $('#w_history_table').table({
		cols:this.cols,
		data:this.data,
		sort_init:'date_attributed',
		sort_dir_init:'desc',
		id_key_tr_attr:'pid',
		id_key:'pid'
	});
	
	this.history_table.find('.date_received_link').bind('click', this, function(e){ e.data.edit_click($(this)); return false; });
	bh = this;
}

billing_history.NOTES_DISPLAY_MAX_LEN = 25;

billing_history.prototype.edit_click = function($a)
{
	var new_query,
		d = this.history_table.data('table').get_row_data($a),
		loc = window.location,
		query = window.location.search,
		params = String.parse_query(query);
	
	$a.blur();
	this.history_table.find('tr.edit_row').removeClass('edit_row');
	$a.closest('tr').addClass('edit_row');
	
	params.pid = d['pid'];
	window.location.replace_path(window.location.pathname+'?'+String.to_query(params));
	
	pf.init_edit(d);
};

billing_history.prototype.get_data_from_pid = function(pid)
{
	var $row = this.history_table.find('tr[pid="'+pid+'"]');
	return this.history_table.data('table').get_row_data($row);
};

billing_history.prototype.init_data_and_cols = function()
{
	var i, j, one_pp_for_account, p, pp, compound_type,
		pay_part_cols = [];
		compound_types = {};
	
	this.cols = [
		{key:'date_received'},
		{key:'date_attributed'},
		{key:'event'},
		{key:'total',format:'dollars'},
	];
	this.data = [];
	for (i = 0; i < globals.history.length; ++i) {
		p = globals.history[i];
		p.total = 0;
		one_pp_for_account = false;
		for (j = 0; j < p.payment_part.length; ++j) {
			pp = p.payment_part[j];
			if (pp.account_id == this.aid) {
				one_pp_for_account = true;
				compound_type = pp.dept.toUpperCase()+' '+pp.type;
				if (!compound_types[compound_type]) {
					compound_types[compound_type] = 1;
					pay_part_cols.push({key:compound_type,format:'dollars'});
				}
				p[compound_type] = pp.part_amount;
				p.total += Number(pp.part_amount);
			}
		}
		if (one_pp_for_account) {
			p._display_date_received = '<a class="date_received_link" href="">'+p.date_received+'</a>';
			if (p.notes.length > billing_history.NOTES_DISPLAY_MAX_LEN) {
				p._display_notes = '<span class="notes" title="'+p.notes+'">'+p.notes.substr(0, billing_history.NOTES_DISPLAY_MAX_LEN)+'</span>';
			}
			this.data.push(p);
		}
	}
	// sort and add on pay part cols
	pay_part_cols.sort(function(a, b){ return a.key > b.key });
	this.cols = this.cols.concat(pay_part_cols);
	
	// lastly, notes
	this.cols.push({key:'notes',type:'alpha'});
};

function payment_form()
{
	this.init_accounts();
	this.init_dept_and_type_map();
	this.$pp_table = $('#payment_parts');
	this.pp_count = 0;
	
	$('#add_payment_part_row_button').bind('click', this, function(e){ e.data.add_payment_part_row(); return false; });
	$('input[name="action"]').bind('change', this, function(e){ e.data.action_change(); });
	$('input[name="pay_method"]').bind('change', this, function(e){ e.data.pay_method_change(); });
	$('input[name="event"]').bind('change', this, function(e){ e.data.event_change(); });
	$('#do_update_bill_dates').bind('click', this, function(e){ e.data.do_update_bill_dates_click($(this)); });
	$('#do_amortize').bind('click', this, function(e){ e.data.do_amortize_click($(this)); });
	$('input[a0="action_payment_form_submit"]').bind('click', this, function(e){ return e.data.verify_and_submit(); });
	$('#edit_delete_button').bind('click', this, function(e){ return e.data.edit_delete_click(); });
	$('#edit_cancel_button').bind('click', this, function(e){ e.data.edit_cancel_click(); return false; });
	
	// actions only for new
	this.find('#action-Charge,#action-Refund').closest('div').addClass('new');
	
	if (window.location.get['payment_id']) {
		this.init_edit(window.get['payment_id']);
	}
	else {
		this.init_new();
	}
	// init pay method
	$('input[name="pay_method"]').change();
	$('input[name="event"]').change();
	
	pf = this;
}

payment_form.prototype.init_accounts = function()
{
	var i, account;
	
	this.account_options = [['', ' - Account - ']];
	this.account_to_dept = {};
	
	for (i = 0; i < globals.accounts.length; ++i)
	{
		account = globals.accounts[i];
		this.account_options.push([account.id, account.dept+', '+account.url]);
		this.account_to_dept[account.id] = account.dept;
	}
};

payment_form.prototype.verify_and_submit = function()
{
	// should not be duplicate (account, dept, type) pps
	
	// all pps should have valid amount
	
	// must be at least one valid pp
	$('#num_pps_added').val(String(this.pp_count));
	return true;
};

payment_form.prototype.get_cbox_val = function(name)
{
	return this.find('input[name="'+name+'"]:checked').val();
};

payment_form.prototype.pay_method_change = function()
{
	var rows,
		checked = this.get_cbox_val('pay_method');
	
	// hide what is showing
	this.find('tr.pay_method_details:visible').hide();
	
	// show corresponding checked
	rows = this.find('tr.pay_method_details.'+checked.toLowerCase());
	rows.show();
	rows.filter(':eq(0)').find('input,select').focus();
};

payment_form.prototype.do_amortize_click = function($cbox)
{
	$('#new_amortize_months').attr('disabled', !$cbox.is(':checked'));
};

payment_form.prototype.do_update_bill_dates_click = function($cbox)
{
	$('#prev_bill_date').attr('disabled', !$cbox.is(':checked'));
	$('#next_bill_date').attr('disabled', !$cbox.is(':checked'));
};

payment_form.prototype.event_change = function()
{
	var $cbox = $('#do_update_bill_dates'),
		event = this.get_cbox_val('event');
	
	if ($cbox.length > 0) {
		if (event == 'Rollover') {
			if (!$cbox.is(':checked')) {
				$cbox[0].click();
			}
		}
		else {
			if ($cbox.is(':checked')) {
				$cbox[0].click();
			}
		}
	}
};

payment_form.prototype.action_change = function()
{
	var self = this;
	
	this.$pp_table.find('.pp_account').each(function(){
		self.sync_pp_types($(this));
	});
};

payment_form.prototype.sync_pp_types = function($account_select)
{
	var dept, types,
		action = this.get_cbox_val('action'),
		pp_id = $account_select.closest('.pp_row').attr('pp_id'),
		account_id = $account_select.val();
	
	if (account_id) {
		dept = this.account_to_dept[account_id];

		// only allowed type when processing a refund is 'Refund'
		if (action == 'Refund') {
			types = ['Refund'];
		}
		// Charge or Record
		else {
			// get clone
			types = this.dept_and_type[dept].slice(0);
			if (action == 'Charge') {
				types = Array.filter(types, 'Refund');
			}
		}

		$('#dept_'+pp_id).val(dept);
		if (types) {
			if ($('#type_'+pp_id).length > 0) {
				$('#type_'+pp_id).html(html.options(types));
			}
			else {
				$('#w_type_'+pp_id).html(html.select('type_'+pp_id, types, false, 'class="pp_type"'));
				$('#type_'+pp_id).bind('change', this, function(e){ e.data.type_change($(this)); return false; });
			}
		}
	}
	else {
		$('#w_type_'+pp_id).empty();
	}
};

payment_form.prototype.init_dept_and_type_map = function()
{
	var i, dept, type;
	
	this.dept_options = [''];
	this.dept_and_type = {};
	for (dept in globals.dept_and_type)
	{
		this.dept_options.push(dept);
		this.dept_and_type[dept] = [];
		for (i = 0; i < globals.dept_and_type[dept].length; ++i)
		{
			this.dept_and_type[dept].push(globals.dept_and_type[dept][i].type);
		}
	}
};

// mixed: id of payment or papyment data
payment_form.prototype.init_edit = function(mixed)
{
	var d, i, k, $elem;
	
	if (Types.is_string(mixed))
	{
		d = bf.get_data_from_pid(mixed);
	}
	else
	{
		d = mixed;
	}
	
	this.find('.edit').show();
	this.find('.new').hide();
	
	// only edit action is record
	this.find('#action-Record').click();
	
	// input vals
	$('#edit_pid').val(d.pid);
	for (k in d)
	{
		$elem = this.find('[name="'+k+'"]');
		if ($elem.length)
		{
			switch ($elem.attr('type'))
			{
				case ('radio'): $elem.filter('[value="'+d[k]+'"]').click(); break;
				default: $elem.val(d[k]); break;
			}
		}
	}
	// payment parts
	this.$pp_table.empty();
	for (i = 0; i < d.payment_part.length; ++i)
	{
		this.add_payment_part_row(d.payment_part[i]);
	}
	// field name changes depending on pay_method, set explicitly
	$('#'+this.get_cbox_val('pay_method').toLowerCase()+'_id').val(d.pay_id);
	this.pay_method_change();
	this.amount_change();
};

payment_form.prototype.edit_cancel_click = function()
{
	window.location.replace_path(window.location.pathname+'?aid='+window.location.get['aid']);
	this.init_new();
};

payment_form.prototype.edit_delete_click = function()
{
	return (confirm('Delete this payment?'));
};

payment_form.prototype.init_new = function()
{
	if (bh)
	{
		bh.find('tr').removeClass('edit_row');
	}
	this.$pp_table.empty();
	this.find('.edit').hide();
	this.find('.new').show();
	
	$('#edit_pid').val('');
	if (window.globals.default_payments && window.globals.default_payments.length > 0) {
		this.init_default_payments();
		this.amount_change();
	}
	else {
		this.add_payment_part_row();
	}
	this.do_amortize_click($('#do_amortize'));
};

payment_form.prototype.init_default_payments = function()
{
	var i, dp, pp_id;
	
	if (window.globals.default_payments) {
		for (i = 0; i < globals.default_payments.length; ++i) {
			dp = globals.default_payments[i];
			dp.type = 'Management';
			this.add_payment_part_row(dp);
		}
	}
};

payment_form.prototype.add_payment_part_row = function()
{
	var pp = (arguments.length > 0) ? arguments[0] : false,
		pp_id = this.pp_count++;
	
	this.$pp_table.append('\
		<tr class="pp_row" pp_id="'+pp_id+'">\
			<td>\
				'+html.select('account_'+pp_id, this.account_options, false, 'class="pp_account"')+'\
				<input type="hidden" name="dept_'+pp_id+'" id="dept_'+pp_id+'" value="" /> \
			</td>\
			<td id="w_type_'+pp_id+'"></td>\
			<td><input class="amount" type="text" name="amount_'+pp_id+'" id="amount_'+pp_id+'" value="" /></td>\
			<td><input type="submit" name="pp_minus_'+pp_id+'" id="pp_minus_'+pp_id+'" value=" - " /></td>\
		</tr>\
	');
	
	$('#account_'+pp_id).bind('change', this, function(e){ e.data.account_change($(this)); });
	$('#amount_'+pp_id).bind('keyup', this, function(e){ e.data.amount_change(); });
	$('#pp_minus_'+pp_id).bind('click', this, function(e){ e.data.pp_minus_click($(this)); return false; });
	
	if (pp)
	{
		$('#account_'+pp_id).val(pp.account_id).change();
		$('#type_'+pp_id).val(pp.type);
		$('#amount_'+pp_id).val(pp.part_amount);
	}
	
	// select current account? window.location.get['aid']
	
	return pp_id;
};

payment_form.prototype.pp_minus_click = function($button)
{
	$button.closest('.pp_row').remove();
	this.amount_change();
};

payment_form.prototype.account_change = function($select)
{
	this.sync_pp_types($select);
};

payment_form.prototype.type_change = function($select)
{
	var pp_id = $select.closest('.pp_row').attr('pp_id');
	
	if ($select.val())
	{
		$('#amount_'+pp_id).select();
	}
};

payment_form.prototype.amount_change = function()
{
	var i, str, num,
		total = 0,
		do_report_error = (arguments.length > 0) ? arguments[0] : false,
		amounts = $('.pp_row .amount');
		
	
	for (i = 0; i < amounts.length; ++i)
	{
		str = $(amounts[i]).val();
		num = Number(str.replace(/[^-\d\.]/g, ''));
		
		if (!num)
		{
			if (do_report_error)
			{
				this.show_error('Payment part '+(i + 1)+' does not appear to be a valid amount ('+str+')');
			}
		}
		else
		{
			total += num;
		}
	}
	$('#total_cell').text(Format.dollars(total));
};
