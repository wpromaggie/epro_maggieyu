
function atable()
{
	this.init_settings();
	this.init_cols();
	this.init_data();
	
	this.tbl = $('#atable').table({
		data:this.data,
		cols:this.cols,
		sort_init:this.default_sort,
		sort_dir_init:'asc',
		show_download:true
	}).data('table');
	
	this.init_actions();
}

atable.prototype.init_settings = function()
{
	$('#f').attr('method', 'get');
	this.buckets = globals.buckets;
	this.reverse_buckets = {};
	this.active_events = {'Activation':1, 'Rollover':1};
	this.events = ['Activation', 'Rollover'];
	this.do_show_type = false;
};

atable.prototype.init_actions = function()
{
	$('#form_submit').bind('click', this, function(e){ e.data.form_submit(); });
	$('#atable').find('.wpro_table tbody a').bind('click', this, function(e){ e.data.a_click($(this)); return false; });
};

atable.prototype.a_click = function($a)
{
	var i, bucket,
		d = this.tbl.get_row_data($a),
		td_index = $a.closest('td').prevAll().length,
		col = $('#atable thead tr th:nth('+td_index+')').text(),
		q = {
			col:col,
			start_date:$('#start_date').val(),
			end_date:$('#end_date').val()
		};
	
	for (i = 0; i < this.buckets.length; ++i) {
		bucket = this.buckets[i];
		q[bucket] = this.reverse_bucket_val(d, bucket);
	}
	e2.new_window($a.attr('href')+'?'+String.to_query(q));
};

atable.prototype.is_href_col = function(col)
{
	return (col == 'lost' || col.indexOf('#') != -1);
};

atable.prototype.form_submit = function()
{
	$('#buckets input[type="checkbox"]').attr('disabled', true);
};

atable.prototype.truncate_col = function(col)
{
	// nm
	return col;
	//return col.substr(0, 4);
};

atable.prototype.init_cols = function()
{
	var i;
	
	this.cols = [];
	for (i = 0; i < this.buckets.length; ++i) {
		this.cols.push({key:this.buckets[i],type:'alpha'});
	}
	this.cols = this.cols.concat([
		{key:'active'},
		{key:'lost'},
		{key:'total_$',format:'dollars'}
	]);
	for (i = 0; i < this.events.length; ++i) {
		this.cols.push({key:this.truncate_col(this.events[i]) + '_$',format:'dollars'});
		this.cols.push({key:this.truncate_col(this.events[i]) + '_#'});
	}
};

atable.prototype.get_num = function(x)
{
	return (!isNaN(x)) ? Number(x) : 0;
};

atable.prototype.init_payment_parts = function(p, pps)
{
	return (this.do_show_types) ? this.init_payment_parts_typed(p, pps) : this.init_payment_parts_grouped(p, pps);
};

atable.prototype.init_payment_parts_typed = function(p, pps)
{
	var i, tmp_data, d;
	
	tmp_data = [];
	for (i = 0; i < pps.length; ++i) {
		// flatten
		d = $.extend({}, p, pps[i]);
		delete(d.payment_part);
		
		tmp_data.push(d);
	}
	return tmp_data;
};

atable.prototype.init_payment_parts_grouped = function(p, pps)
{
	var i, tmp_obj, d;
	
	tmp_obj = {};
	for (i = 0; i < pps.length; ++i) {
		// flatten
		d = $.extend({}, p, pps[i]);
		delete(d.payment_part);
		
		accumulate(tmp_obj, [d.account_id], d, ['contract', 'sales_rep']);
	}
	return flatten(tmp_obj, ['account_id']);
};

// rev and count for each event
atable.prototype.init_events = function(d, agg_vals)
{
	var i, event, trunc_event, matches;
	
	agg_vals.total_$ = 0;
	for (i = 0; i < this.events.length; ++i) {
		event = this.events[i];
		trunc_event = this.truncate_col(event);
		
		if (d.event == event) {
			agg_vals.total_$ += Number(d.amount);
			agg_vals[trunc_event+'_$'] = d.amount;
			matches = d.notes.match(/^ai(\d+)/i);
			// amortized and not the first amortization installment
			if (matches && matches[1] != 1) {
				agg_vals[trunc_event+'_#_amortized'] = 1;
			}
			else {
				agg_vals[trunc_event+'_#'] = 1;
			}
		}
		else {
			agg_vals[trunc_event+'_$'] = 0;
			agg_vals[trunc_event+'_#'] = 0;
		}
	}
};

// treat each payment part separately
atable.prototype.init_data = function()
{
	var i, j, d, tmp_data, tmp_agg, bucket_vals, agg_vals;
	
	// convert complex globals data to simpler array of objects
	tmp_data = [];
	for (i = 0; i < globals.data.length; ++i) {
		d = globals.data[i];
		tmp_data = tmp_data.concat(this.init_payment_parts(d, d.payment_part));
	}
	// tack on de activation data
	tmp_data = tmp_data.concat(globals.de_activated);
	
	// aggregate and calculate
	tmp_agg = {};
	for (i = 0; i < tmp_data.length; ++i) {
		d = tmp_data[i];
		bucket_vals = [];
		for (j = 0; j < this.buckets.length; ++j) {
			bucket_vals.push(this.get_bucket_val(d, this.buckets[j]));
		}
		agg_vals = {
			active:((this.active_events[d.event]) ? 1 : 0),
			lost:this.get_num(d.de_activated)
		};
		this.init_events(d, agg_vals);
		accumulate(tmp_agg, bucket_vals, agg_vals);
	}
	// flatten back to array of objcets
	this.data = flatten(tmp_agg, this.buckets);
	
	// display links for counts greater than 0
	this.init_data_display();
};

atable.prototype.init_data_display = function(d, bucket)
{
	var i, j, d, text;

	for (i = 0; i < this.data.length; ++i) {
		d = this.data[i];
		for (j in d) {
			if (this.is_href_col(j)) {
				text = d[j];
				if (j != 'lost') {
					text += ' / '+((d[j+'_amortized']) ? d[j+'_amortized'] : 0);
				}
				d['_display_'+j] = (d[j] > 0 || d[j+'_amortized'] > 0) ? '<a href="'+e2.url('/sbr/accounting/account_list')+'">'+text+'</a>' : text;
			}
		}
	}
};

atable.prototype.get_bucket_val = function(d, bucket)
{
	var val = d[bucket];
	switch (bucket) {
		case ('sales_rep'):
			return (globals.reps[val]) ? globals.reps[val] : '(none)';
		
		default:
			return val;
	}
};

atable.prototype.reverse_bucket_val = function(d, bucket)
{
	var rep_id, rep_name, val = d[bucket];
	switch (bucket) {
		case ('sales_rep'):
			if (!this.reverse_buckets.sales_rep) {
				this.reverse_buckets.sales_rep = {};
			}
			if (!this.reverse_buckets.sales_rep[val]) {
				for (rep_id in globals.reps) {
					if (globals.reps[rep_id] == val) {
						this.reverse_buckets.sales_rep[val] = rep_id;
					}
				}
			}
			return (this.reverse_buckets.sales_rep[val]) ? this.reverse_buckets.sales_rep[val] : 0;
		
		default:
			return val;
	}
};

function report()
{
	this.$date_fields = $('#signup_start_date,#signup_end_date');
	this.$signup_filter = $('#signup_date_filter');
	this.$signup_filter.bind('click', this, function(e){ e.data.signup_filter_click(); });
	this.signup_filter_click();
}

report.prototype.signup_filter_click = function()
{
	if (this.$signup_filter.is(':checked')) {
		this.$date_fields.attr('disabled', false);
		this.$date_fields.closest('tr').show();
	}
	else {
		this.$date_fields.attr('disabled', true);
		this.$date_fields.closest('tr').hide();
	}
};
