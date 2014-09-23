
function sbr_new_order()
{
	this.prod_count = 0;
	this.pmode = globals.pmode;
	this.init_max_keywords();
	this.init_coupon_options();
	
	$('.add_product').bind('click', this, function(e){ e.data.add_product_click($(this)); return false; });
	$('input.auto_fill_button').bind('click', this, function(e){ e.data.auto_fill_button_click($(this)); return false; });
	$('#big_submit').bind('click', this, function(e){ return e.data.submit(); });
}

sbr_new_order.DEFAULT_PLAN = 'Core';
sbr_new_order.DEFAULT_WHO_CREATES = 'Expert';

sbr_new_order.prototype.auto_fill_button_click = function($button)
{
	$('#sales_rep option:eq('+Math.rand_int(0, $('#sales_rep option').length)+')').attr('selected', true);

	$f('name', (this.rand_str(Math.rand_int(4, 7))+' '+this.rand_str(Math.rand_int(3, 10))).display_text());
	$f('email', this.rand_str(3)+'@'+this.rand_str(2)+'.'+(['com', 'net', 'org'])[Math.rand_int(0, 2)]);
	$f('phone', Math.rand_int(100, 999)+'.'+Math.rand_int(100, 999)+'.'+Math.rand_int(1000, 9999));

	$('#cc_type option:eq('+Math.rand_int(0, $('#cc_type option').length)+')').attr('selected', true);
	$f('cc_name', 'Auto '+this.rand_str(Math.rand_int(3, 10)).display_text());
	$f('cc_number_text', $button.attr('ccnum'));
	$('#cc_exp_month option:eq('+Math.rand_int(0, 11)+')').attr('selected', true);
	$('#cc_exp_year option:eq('+Math.rand_int(0, 5)+')').attr('selected', true);
	$f('cc_code', Math.rand_int(100, 9999));
	$f('cc_zip', Math.rand_int(10000, 99999));
};

sbr_new_order.prototype.rand_str = function(len)
{
	var i, str = '';

	for (i = 0; i < len; ++i) {
		str += String.fromCharCode(Math.rand_int(97, 122));
	}
	return str;
};

sbr_new_order.prototype.init_max_keywords = function()
{
	var plan_name, plan;
	
	this.max_keywords = 0;
	
	for (plan_name in globals.plans['ql'])
	{
		plan = globals.plans['ql'][plan_name];
		if (plan.num_keywords > this.max_keywords)
		{
			this.max_keywords = Number(plan.num_keywords);
		}
	}
};

sbr_new_order.prototype.init_coupon_options = function()
{
	var code, coupon;
	
	this.coupon_count = 0;
	this.coupon_options = [['', ' - Select - ']];
	for (code in globals.coupons)
	{
		coupon = globals.coupons[code];
		this.coupon_options.push([code, code+' ('+coupon.type+', '+coupon.contract_length+')']);
	}
};

// we need post ability to init prods
sbr_new_order.prototype.post_register = function()
{
	if (globals.prods)
	{
		this.load_prods();
	}
};

sbr_new_order.prototype.load_prods = function()
{
	var i, j, prod, $prod, $input, key, coupons;
	
	for (i = 0; i < globals.prods.length; ++i)
	{
		prod = globals.prods[i];
		
		$('input.add_product[dept="'+prod.dept+'"]').click();
		$prod = $('#prod_'+i);
		
		// this will load everything except coupons
		for (key in prod)
		{
			$input = $prod.find('[name="'+key+'_'+i+'"]');
			if ($input.length)
			{
				if ($input.is('[type="radio"]'))
				{
					$input.filter('[value="'+prod[key]+'"]')[0].click();
				}
				else if ($input.is('[type="checkbox"]'))
				{
					if (prod[key])
					{
						$input[0].click();
					}
				}
				else
				{
					$input.val(prod[key]);
				}
			}
		}
		
		// load coupons
		coupons = Array.filter(prod.coupon_codes.split("\t"));
		for (j = 0; j < coupons.length; ++j)
		{
			$prod.find('.coupon_button.plus').click();
			$prod.find('select.coupon:eq('+j+')').val(coupons[j]);
		}
		// update billing
		this.update_billing($prod, (!empty(prod.monthly) || !empty(prod.setup) || !empty(prod.first_month)));
	}
};

sbr_new_order.prototype.add_product_click = function(button)
{
	var $prod, dept = button.attr('dept');
	
	$('#w_products').append('\
		<table class="prod_table" dept="'+dept+'" prod_num="'+this.prod_count+'" id="prod_'+this.prod_count+'">\
			<tbody>\
				<tr>\
					<td colspan=2>\
						<b class="prod_header"> : '+dept.toUpperCase()+' : </b>\
						<a href="" class="prod_cancel">Cancel</a>\
						<input type="hidden" name="dept_'+this.prod_count+'" id="dept_'+this.prod_count+'" value="'+dept+'" />\
					</td>\
				</tr>\
				'+this.ml_plan(dept)+'\
				<tr>\
					<td>URL</td>\
					<td><input type="text" name="url_'+this.prod_count+'" id="url_'+this.prod_count+'" /></td>\
				</tr>\
				'+this.dept_hook(dept, 'ml_other')+'\
				<tr>\
					<td>Comments</td>\
					<td><textarea class="comments" name="comments_'+this.prod_count+'" id="comments_'+this.prod_count+'" /></textarea></td>\
				</tr>\
				<tr class="hide">\
					<td>Is Trial</td>\
					<td><input type="hidden" name="is_trial_'+this.prod_count+'" id="is_trial_'+this.prod_count+'" /></td>\
				</tr>\
				<tr>\
					<td>Coupons</td>\
					<td>\
						<div>\
							<input type="submit" class="coupon_button plus" value=" + " />\
						</div>\
						<div class="w_coupons"></div>\
						<input type="hidden" name="coupon_codes_'+this.prod_count+'" id="coupon_codes_'+this.prod_count+'" value="" />\
					</td>\
				</tr>\
				<tr>\
					<td>Monthly</td>\
					<td>'+this.ml_billing_field('monthly')+'</td>\
				</tr>\
				<tr>\
					<td>Setup</td>\
					<td>'+this.ml_billing_field('setup')+'</td>\
				</tr>\
				<tr>\
					<td>First Month</td>\
					<td>'+this.ml_billing_field('first_month')+'</td>\
				</tr>\
				'+this.dept_hook(dept, 'ml_billing')+'\
				<tr>\
					<td>Today</td>\
					<td id="today_'+this.prod_count+'" format="dollars"></td>\
				</tr>\
				<tr>\
					<td>Contract Length</td>\
					<td>'+html.select('contract_length_'+this.prod_count, Array.range(0, 24))+'</td>\
				</tr>\
			</tbody>\
		</table>\
	');
	
	$prod = $('#prod_'+this.prod_count);
	$prod.find('.plan_radio').bind('click', this, function(e){ e.data.plan_click($(this)); });
	$prod.find('.billing_edit').bind('change', this, function(e){ e.data.billing_edit_change($(this)); });
	$prod.find('.prod_cancel').bind('click', this, function(e){ e.data.prod_cancel_click($(this)); return false; });
	$prod.find('.coupon_button.plus').bind('click', this, function(e){ e.data.coupon_button_plus_click($(this)); return false; });
	
	this.dept_hook(dept, 'post_init', [$prod]);
	
	// initialize plan with a click
	$prod.find('.plan_radio[value="'+sbr_new_order.DEFAULT_PLAN+'"]')[0].click();
	
	this.prod_count++;
};

sbr_new_order.prototype.ml_billing_field = function(prefix)
{
	var key = prefix+'_'+this.prod_count;

	if (this.pmode == 'edit') {
		return '<input class="billing_edit" type="text" name="'+key+'" id="'+key+'" format="n2" />';
	}
	else {
		return '<span type="text" id="'+key+'" format="dollars"></span>';
	}
};

sbr_new_order.prototype.sb_ml_billing = function()
{
	return '\
		<tr>\
			<td>Fan Page</td>\
			<td id="fan_page_'+this.prod_count+'" format="dollars"></td>\
		</tr>\
	';
};

sbr_new_order.prototype.update_billing = function($updated_prod)
{
	var i, key, req, $input, $prods = $('#w_products .prod_table'),
		is_billing_edit_change = (arguments.length > 1) ? arguments[1] : false,
		self = this;
	
	this.set_dynamic_fields($prods);
	// must be same keys as when order is actually submitted
	
	// some meta stuff
	req = {
		prod_nums:$('#prod_nums').val(),
		update_prod_num:$updated_prod.attr('prod_num') 
	}
	// loop over each prod and set billing related fields
	$prods.each(function(){
		var $prod = $(this),
			pnum = $prod.attr('prod_num');
		
		for (i = 0; i < globals.common_billing_keys.length; ++i)
		{
			key = globals.common_billing_keys[i]+'_'+pnum;
			$input = $('[name="'+key+'"]');
			// reset editable billing fields when something else is changed
			if (!is_billing_edit_change && $input.is('.billing_edit')) {
				req[key] = 'reset_me';
			}
			else {
				req[key] = ($input.is('[type="radio"]')) ? $input.filter(':checked').val() : $input.val();
			}
		}
		$.extend(req, self.dept_hook($prod.attr('dept'), 'other_billing_info', [$prod], {}));
	});
	
	this.post('calc_billing', req);
};

sbr_new_order.prototype.calc_billing_callback = function(request, response)
{
	var billing_info, billing_key, elem, update_func, format_func, formatted;
	
	//dbg(request, response); return;
	
	// when prod is cancelled, no update prod, so check that we have one
	if (request.update_prod_num) {
		billing_info = response.prods[request.update_prod_num];
		for (billing_key in billing_info) {
			elem = $('#'+billing_key+'_'+request.update_prod_num);
			if (elem.length > 0) {
				update_func = elem.is(':input') ? 'val' : 'text';
				format_func = elem.attr('format') ? elem.attr('format') : 'string';
				formatted = Format[format_func](billing_info[billing_key]);
				elem[update_func](formatted);
			}
		}
	}
	
	for (billing_key in response.totals) {
		$('#'+billing_key+'_total').text(Format.dollars(response.totals[billing_key]));
	}
};

sbr_new_order.prototype.plan_click = function($radio)
{
	var $prod = this.get_prod($radio),
		dept = $prod.attr('dept'),
		plan = $radio.val();

	// is_trial hidden for now, set depending on dept/plan
	if (dept == 'sb' && plan == 'Express') {
		$prod.find('#is_trial_'+$prod.attr('prod_num')).val("1");
	}
	else {
		$prod.find('#is_trial_'+$prod.attr('prod_num')).val("0");
	}
	this.dept_hook(dept, 'plan_click', [$prod, plan]);
	this.update_billing($prod);
};

sbr_new_order.prototype.billing_edit_change = function($input)
{
	var $prod = this.get_prod($input);
	this.update_billing($prod, true);
};

sbr_new_order.prototype.ml_plan = function(dept)
{
	var plan_name, dept_plans = [];
	
	if (!globals.plans[dept])
	{
		return '<tr><td colspan=2><input type="hidden" name="plan_'+this.prod_count+'" id="plan_'+this.prod_count+'" value="" /></td></tr>';
	}
	else
	{
		for (plan_name in globals.plans[dept])
		{
			dept_plans.push(plan_name);
		}
		return '\
			<tr>\
				<td>Plan</td>\
				<td>'+html.radios('plan_'+this.prod_count, dept_plans, '', {attrs:'class="plan_radio"'})+'</td>\
			</tr>\
		';
	}
};

/*
 * optional arguments
 * arg[2]: array of user datas passed to hook via apply(). default []
 * arg[3]: return value if no hook exists. default ''
 */
sbr_new_order.prototype.dept_hook = function(dept, func)
{
	var func = this[dept+'_'+func];
	if (func)
	{
		return func.apply(this, (arguments.length > 2) ? arguments[2] : []);
	}
	else
	{
		return (arguments.length > 3) ? arguments[3] : '';
	}
};

sbr_new_order.prototype.ql_post_init = function($prod)
{
	var $who_creates = $prod.find('input[name="who_creates_'+this.prod_count+'"]');
	
	$who_creates.bind('click', this, function(e){ e.data.who_creates_click($(this)); });
};

sbr_new_order.prototype.sb_post_init = function($prod)
{
	var $fan_page = $prod.find('#is_fan_page_'+this.prod_count);
	
	$fan_page.bind('click', this, function(e){ e.data.fan_page_click($(this)); });
};

sbr_new_order.prototype.sb_other_billing_info = function($prod)
{
	var key = 'is_fan_page_'+$prod.attr('prod_num'),
		$fan_page = $prod.find('#'+key),
		req = {};
	
	// only set anything if checked to mimic post
	if ($fan_page.is(':checked'))
	{
		req[key] = 1;
	}
	return req;
};

sbr_new_order.prototype.fan_page_click = function($cbox)
{
	this.update_billing(this.get_prod($cbox));
};

sbr_new_order.prototype.get_prod = function($elem)
{
	return $elem.closest('.prod_table');
};

sbr_new_order.prototype.who_creates_click = function($radio)
{
	var $prod = this.get_prod($radio);
	
	// just call plan click, which needs to inspeact who creates
	this.ql_plan_click($prod, $('input[name="plan_'+$prod.attr('prod_num')+'"]:checked').val());
};

sbr_new_order.prototype.ql_plan_click = function($prod, plan_name)
{
	var dept = $prod.attr('dept'),
		plan_info = globals.plans[dept][plan_name],
		who_creates = $prod.find('input[name="who_creates_'+$prod.attr('prod_num')+'"]:checked').val(),
		kw_inputs = $prod.find('.kw_input');
	
	if (who_creates == 'Expert') {
		$prod.find('.user_info').hide();
		kw_inputs.attr('disabled', true);
	}
	else {
		$prod.find('.user_info').show();
		kw_inputs.slice(0, plan_info.num_keywords).attr('disabled', false).closest('tr').show();
		kw_inputs.slice(plan_info.num_keywords).attr('disabled', true).closest('tr').hide();
	}

	if (plan_name == 'Pro') {
		$('tr.ql_pro_row').show();
	}
	else {
		$('tr.ql_pro_row').hide();
	}
};

sbr_new_order.prototype.ql_ml_other = function()
{
	return '\
		<tr class="ql_pro_row">\
			<td>Company Name</td>\
			<td><input type="text" name="company_name_'+this.prod_count+'" id="company_name_'+this.prod_count+'" value="" /></td>\
		</tr>\
		<tr class="ql_pro_row">\
			<td>Budget</td>\
			<td><input type="text" name="budget_'+this.prod_count+'" id="budget_'+this.prod_count+'" value="" /></td>\
		</tr>\
		<tr>\
			<td>Who Creates</td>\
			<td>'+html.radios('who_creates_'+this.prod_count, ['Expert', 'User'], sbr_new_order.DEFAULT_WHO_CREATES)+'</td>\
		</tr>\
		<tr class="user_info">\
			<td>Ad Title</td>\
			<td><input type="text" name="title_'+this.prod_count+'" id="title_'+this.prod_count+'" value="" /></td>\
		</tr>\
		<tr class="user_info">\
			<td>Description</td>\
			<td>\
				<p><input type="text" name="desc1_'+this.prod_count+'" id="desc1_'+this.prod_count+'" value="" /></p>\
				<p><input type="text" name="desc2_'+this.prod_count+'" id="desc2_'+this.prod_count+'" value="" /></p>\
			</td>\
		</tr>\
		'+this.ml_keywords()+'\
	';
};

sbr_new_order.prototype.ml_keywords = function()
{
	var i, ml = '';
	
	for (i = 0; i < this.max_keywords; ++i)
	{
		ml += '\
			<tr class="user_info">\
				<td>Keywords '+(i + 1)+'</td>\
				<td><input type="text" class="kw_input" name="kw'+i+'_'+this.prod_count+'" id="kw'+i+'_'+this.prod_count+'" value="" /></td>\
			</tr>\
		';
	}
	return ml;
};

sbr_new_order.prototype.sb_ml_other = function()
{
	return '\
		<tr>\
			<td>Fan Page</td>\
			<td>\
				<input type="checkbox" name="is_fan_page_'+this.prod_count+'" id="is_fan_page_'+this.prod_count+'" value="1" />\
				<label for="is_fan_page_'+this.prod_count+'">Yes</label>\
			</td>\
		</tr>\
	';
};

sbr_new_order.prototype.coupon_button_plus_click = function(button)
{
	var w = button.closest('td').find('.w_coupons');
	
	w.append('\
		<p>\
			'+html.select('coupon_'+this.coupon_count, this.coupon_options, '', 'class="coupon"')+'\
			<input type="submit" id="minus_button_'+this.coupon_count+'" class="coupon_button minus" value=" - " />\
		</p>\
	');
	w.find('#coupon_'+this.coupon_count).bind('change', this, function(e){ e.data.coupon_change($(this)); });
	w.find('#minus_button_'+this.coupon_count).bind('click', this, function(e){ e.data.coupon_button_minus_click($(this)); return false; });
	this.coupon_count++;
};

sbr_new_order.prototype.coupon_change = function($select)
{
	var i, $coupon, cinfo, selected_type,
		selected = globals.coupons[$select.val()],
		$prod = this.get_prod($select),
		$coupons = $prod.find('select.coupon');
	
	if (!selected) {
		return false;
	}
	selected_type = selected.type;
	for (i = 0; i < $coupons.length; ++i) {
		$coupon = $($coupons[i]);
		if ($coupon.attr('id') != $select.attr('id')) {
			cinfo = globals.coupons[$coupon.val()];
			if (cinfo.type == selected_type) {
				if (confirm('The previously selected coupon '+$coupon.val()+' has the same type ('+selected_type+') as '+$select.val()+'. Replace?')) {
					$coupon.closest('p').find('.minus').click();
				}
				else {
					$select.val('');
					return false;
				}
			}
		}
	}
	this.update_billing($prod);
};

sbr_new_order.prototype.coupon_button_minus_click = function($button)
{
	var $prod = this.get_prod($button);
	$button.closest('p').remove();
	this.update_billing($prod);
};

sbr_new_order.prototype.prod_cancel_click = function(a)
{
	a.closest('.prod_table').remove();
	this.update_billing($());
};


sbr_new_order.prototype.get_coupon_codes = function($prod)
{
	var coupon_codes = [];
	$prod.find('select.coupon').each(function(){
		coupon_codes.push($(this).val());
	});
	return coupon_codes.join("\t");
};

sbr_new_order.prototype.set_dynamic_fields = function(prods)
{
	var i, $prod, prod_num, coupon_codes,
		prod_nums = [];
	
	for (i = 0; i < prods.length; ++i)
	{
		$prod = $(prods[i]);
		prod_num = $prod.attr('prod_num');
		prod_nums.push(prod_num);
		
		$('#coupon_codes_'+prod_num).val(this.get_coupon_codes($prod));
	}
	
	$('#prod_nums').val(prod_nums.join("\t"));
};

sbr_new_order.prototype.submit = function()
{
	var prods = $('#w_products .prod_table');
	
	if (prods.length == 0)
	{
		Feedback.add_error_msg('Please select at least 1 product');
		return false;
	}
	
	this.set_dynamic_fields(prods);
	return true;
};
