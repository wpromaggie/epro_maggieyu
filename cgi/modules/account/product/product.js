
function product_dashboard()
{
	this.dept = globals.pages[2];
	this.form_prefix = 'ap_'+this.dept;

	this.init_upgrade_calc();

	$('#big_buttons [a0="big_button_move_account"],[a0="action_big_button_move_account"]').bind('click', this, function(e){ e.data.move_account(e); return false; });
	$('#big_buttons [a0="big_button_merge_account"],[a0="action_big_button_merge_account"]').bind('click', this, function(e){ e.data.merge_account(e); return false; });
	$('#big_buttons [a0="action_big_button_re_activate"]').bind('click', this, function(e){ e.data.re_activate(e); return false; });
}

product_dashboard.prototype.init_upgrade_calc = function()
{
	var plan, options;
	
	options = [['', ' - Select - ']];
	for (plan in globals.plans)
	{
		options.push([plan, plan+' ('+Format.dollars(globals.plans[plan])+')']);
	}
	$('#_upgrade_from').html(html.select('upgrade_from', options));
	$('#_upgrade_to').html(html.select('upgrade_to', options));
	
	$('#upgrade_from').val($f(this.form_prefix+'_plan'));
	$('#upgrade_calc_button').bind('click', this, function(e){ e.data.upgrade_calc(); return false; });
};

product_dashboard.prototype.upgrade_calc = function()
{
	var months_free, months_paid, plan_from, plan_to, days_in_month, amount_from, amount_to, date_from, date_to, num_days, dif_per_day, upgrade_amount;
	
	plan_from = $f('upgrade_from');
	plan_to = $f('upgrade_to');
	amount_from = Number($f(this.form_prefix+'_alt_recur_amount'));
	if (isNaN(amount_from) || amount_from == 0)
	{
		amount_from = globals.plans[plan_from];
	}
	amount_to = globals.plans[plan_to];
	
	date_last = Date.str_to_js($f('upgrade_date_last'));
	date_next = Date.str_to_js($f('upgrade_date_next'));
	date_today = Date.str_to_js($f('upgrade_date_today'));
	
	months_free = Number($f(this.form_prefix+'_prepay_free_months'));
	months_paid = Number($f(this.form_prefix+'_prepay_paid_months'));
	days_in_pay_period = Math.round((date_next - date_last) / 86400000);
	days_remaining = Math.round((date_next - date_today) / 86400000);
	
	if (days_remaining == 0)
	{
		upgrade_amount = 0;
	}
	else
	{
		days_in_month = 30.4167;
		if (months_free > 0)
		{
			days_in_pay_period -= Math.round(months_free * days_in_month);
			days_remaining -= Math.round(months_free * days_in_month);
		}
		dif_per_day = (amount_to - amount_from) / days_in_month;
		upgrade_amount = dif_per_day * days_remaining;
		
		if (months_free == 0 && months_paid == 3)
		{
			upgrade_amount -= (dif_per_day * Math.min(days_remaining, 30)) / 2;
		}
	}
	
	$('#upgrade_calc_display').text(Format.dollars(upgrade_amount));
};

product_dashboard.prototype.merge_account = function(e)
{
	$.box({
		title:'Merge Account',
		id:'merge_ac',
		event:e,
		close:true,
		content:'\
			<table>\
				<tbody>\
					<tr id="ac_id_tr">\
						<td>ID:</td>\
						<td><input type="text" id="merge_ac_id" name="merge_ac_id" /></td>\
						<td class="l"><input type="submit" id="merge_ac_verify_submit" class="small_button" value="Verify" /></td>\
					</tr>\
					<tr id="merge_verify_tr">\
						<td>URL:</td>\
						<td colspan=2></td>\
					</tr>\
					<tr id="submit_tr">\
						<td></td>\
						<td><input type="submit" a0="action_merge_account" value="Submit" /></td>\
					</tr>\
				</tbody>\
			</table>\
		'
	});
	$('#merge_ac_id').focus();
	$('#merge_ac table tr:gt(0)').hide();
	$('#merge_ac_verify_submit').bind('click', this, function(e){ e.data.merge_ac_verify_click($(this)); return false; });
	$('#merge_ac [a0]').bind('click', null, function(e){ e2.action_submit($(this).attr('a0')); });
};

product_dashboard.prototype.move_account = function(e)
{
	this.post('move_account_get_payment_parts', {}, this.move_account_get_payment_parts_callback.bind(this, e));
}

product_dashboard.prototype.move_account_get_payment_parts_callback = function(e, request, response)
{
	var i, pp, options, ml_pps = '';
	if (response && response.length > 0) {
		options = [];
		for (i = 0; i < response.length; ++i) {
			pp = response[i];
			options.push([pp.ppid, pp.date_attributed+', '+pp.dept+', '+pp.event+', '+pp.type+', '+Format.dollars(pp.amount)]);
		}
		ml_pps = '\
			<tr><td colspan=5>Move Payments</td></tr>\
			<tr><td colspan=5>'+html.checkboxes('pp', options, false, {toggle_all:true})+'</td></tr>\
		';
	}
	var $box = $.box({
		title:'Move Account',
		id:'w_move_ac',
		event:e,
		close:true,
		content:'\
			<table>\
				<tbody>\
					<tr>\
						<td>Name of new Partner account:</td>\
						<td><input type="text" id="move_client_name" name="move_client_name" /></td>\
					</tr>\
					'+ml_pps+'\
					<tr>\
						<td></td>\
						<td class="l"><input type="submit" a0="action_move_to_partner" class="small_button" value="Submit" /></td>\
					</tr>\
				</tbody>\
			</table>\
		'
	});
	$box.find('#move_client_name').focus();
	e2.cboxes_init($box);
	e2.auto_action_submit($box);
};

product_dashboard.prototype.merge_ac_verify_click = function(button)
{
	this.post('merge_ac_get_url', {
		id:$('#merge_ac_id').val()
	});
};

product_dashboard.prototype.merge_ac_get_url_callback = function(request, response)
{
	if (response)
	{
		$('#merge_verify_tr td:eq(1)').text(response);
		$('#merge_ac table tr:gt(0)').show();
	}
	else
	{
		$('#merge_verify_tr td:eq(1)').text('Could not verify, please check account type and ID.');
		$('#merge_verify_tr').show();
		$('#submit_tr').hide();
	}
};


product_dashboard.prototype.re_activate = function(e)
{
	var today, today_day, prev_bill_date, next_bill_date;
	
	today = new Date();
	today_date = today.getDate();
	prev_bill_date = Date.js_to_str(today);
	next_bill_date = Date.delta_month(prev_bill_date, 1, today_date);
	$.box({
		title:'Re-Activate',
		id:'re_activate_dates',
		event:e,
		close:true,
		content:'\
			<table>\
				<tbody>\
					<tr>\
						<td>Bill Day:</td>\
						<td><input type="text" id="re_activate_bill_day" name="re_activate_bill_day" value="'+today_date+'" /></td>\
					</tr>\
					<tr>\
						<td>Prev Bill Date:</td>\
						<td><input type="text" class="date_input" id="re_activate_prev_bill_date" name="re_activate_prev_bill_date" value="'+prev_bill_date+'" /></td>\
					</tr>\
					<tr>\
						<td>Next Bill Date:</td>\
						<td><input type="text" class="date_input" id="re_activate_next_bill_date" name="re_activate_next_bill_date" value="'+next_bill_date+'" /></td>\
					</tr>\
					<tr>\
						<td>Update Dates:</td>\
						<td><input type="checkbox" id="re_activate_do_set_dates" name="re_activate_do_set_dates" value="1" checked /></td>\
					</tr>\
					<tr id="submit_tr">\
						<td></td>\
						<td><input type="submit" a0="action_big_button_re_activate" value="Submit" /></td>\
					</tr>\
				</tbody>\
			</table>\
		'
	});
	$('#re_activate_dates #re_activate_bill_day').focus();
	$('#re_activate_dates .date_input').date_picker();
	$('#re_activate_dates #re_activate_do_set_dates').bind('click', this, function(e){ e.data.re_activate_toggle_set_dates($(this)); });
	$('#re_activate_dates [a0]').bind('click', null, function(e){ e2.action_submit($(this).attr('a0')); });
};

product_dashboard.prototype.re_activate_toggle_set_dates = function(cbox)
{
	$('#re_activate_dates input[type="text"]').attr('disabled', !cbox.is(':checked'));
};


function actual_su_td()
{
	$('#actual_su_td a').bind('click', this, function(e){ return e.data.actual_su_click($(this)); });
}

actual_su_td.prototype.actual_su_click = function(a)
{
	a.blur();
	$('#f').attr('target', '_blank');
	e2.action_submit('action_actual_su');
	$('#f').attr('target', '_top');
	return false;
};

