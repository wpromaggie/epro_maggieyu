<?php

class mod_sbr_new_order extends mod_sbr
{
	public function pre_output_index()
	{
		util::load_lib('billing');
		
		$this->ignore_depts = array('ww');
		
		// todo: add column to plans db to indicate if plan is active or not
		
		$default_plans = array('Starter', 'Core', 'Premier');
		$this->plans = array();
		foreach (sbs_lib::$departments as $dept) {
			$plan_table = "eppctwo.{$dept}_plans";
			if (db::table_exists($plan_table)) {
				$dept_plans = $default_plans;
				if ($dept == 'sb') {
					$dept_plans[] = 'Express';
				}
				if ($this->get_pmode() == 'edit' && ($dept == 'gs' || $dept == 'ql')) {
					$dept_plans[] = 'Pro';
				}
				$this->plans[$dept] = db::select("
					select *
					from {$plan_table}
					where name in ('".implode("','", $dept_plans)."')
					order by budget asc
				", 'ASSOC', 'name');
			}
			else {
				$this->plans[$dept] = false;
			}
		}

		$this->coupons = db::select("
			select code, type, value, value_type, contract_length
			from eppctwo.coupons
			where status = 'active'
			order by code asc
		", 'ASSOC', 'code');

		if (array_key_exists('prospect_id', $_REQUEST) && !(array_key_exists('a0', $_POST) && $_POST['a0'] == 'action_new_order_submit')) {
			$prospect = db::select_row("
				select name, prospect_company, email, phone, url, ppc_contract_length, ql_pro_budget, ql_pro_mgmt_fee, ql_pro_setup_fee, ql_pro_ct_fee, gs_pro_mgmt_fee, gs_pro_setup_fee
				from eppctwo.prospects
				where id = '".db::escape($_REQUEST['prospect_id'])."'
			", 'ASSOC');

			if ($prospect) {
				$cc = db::select_row("
					select cc_type, name cc_name, cc_number cc_number_text, cc_exp_month, cc_exp_year, cc_code, country cc_country, zip cc_zip
					from eppctwo.ccs
					where foreign_table = 'prospects' && foreign_id = '".db::escape($_REQUEST['prospect_id'])."'
				", 'ASSOC');
				$cc['cc_number_text'] = billing::decrypt_val($cc['cc_number_text']);
				$cc['cc_code'] = billing::decrypt_val($cc['cc_code']);
				billing::cc_obscure($cc, 'cc_number_text');

				$this->set_form_fields(array_merge($prospect, $cc));

				$prods = array();
				if (!empty($prospect['gs_pro_mgmt_fee'])) {
					$prods[] = array(
						'coupon_codes' => '',
						'plan' => 'Pro',
						'dept' => 'gs',
						'contract_length' => $prospect['ppc_contract_length'],
						'monthly' => $prospect['gs_pro_mgmt_fee'],
						'setup' => $prospect['gs_pro_setup_fee'],
						'first_month' => $prospect['gs_pro_mgmt_fee'],
						'url' => $prospect['url'],
						'division' => 'product'
					);
				}
				if (!empty($prospect['ql_pro_mgmt_fee'])) {
					$prods[] = array(
						'coupon_codes' => '',
						'plan' => 'Pro',
						'dept' => 'ql',
						'company_name' => $prospect['prospect_company'],
						'budget' => $prospect['ql_pro_budget'],
						'contract_length' => $prospect['ppc_contract_length'],
						'monthly' => $prospect['ql_pro_mgmt_fee'],
						'setup' => $prospect['ql_pro_setup_fee'] + $prospect['ql_pro_ct_fee'],
						'first_month' => $prospect['ql_pro_mgmt_fee'],
						'url' => $prospect['url'],
						'division' => 'product'
					);
				}
				cgi::add_js_var('prods', $prods);
			}
		}
	}
	
	public function display_index()
	{
		$ml_auto_fill = (!user::is_developer()) ? '' : '
			<tr>
				<td><strong>Dev Auto Fill</strong></td>
				<td>
					<input type="submit" class="auto_fill_button" ccnum="'.BILLING_TEST_SUCCESS.'" value="Success" />
					<input type="submit" class="auto_fill_button" ccnum="'.BILLING_TEST_FAILURE.'" value="Fail" />
				</td>
			</tr>
		';

		?>
		<h1>New Order</h1>
		<table>
			<tbody id="outer_order_table">
				<?= $ml_auto_fill ?>
				<?php
				
				$this->print_header('Lead Info');
				$this->print_rep_input();
				$this->print_partner_source_input();
				
				$this->print_header('Contact');
				$this->print_text_input_row('name');
				$this->print_text_input_row('email');
				$this->print_text_input_row('phone');
				
				$this->print_header('Products');
				$this->print_product_input();
				
				$this->print_header('Billing');
				$this->print_billing();
				
				$this->print_admin_edit_options();
				$this->print_dbg_row();
				
				?>
				<tr>
					<td></td>
					<td><input id="big_submit" type="submit" a0="action_new_order_submit" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		<input type="hidden" name="oid" id="oid" value="<?= $this->oid ?>" />
		<input type="hidden" name="prod_nums" id="prod_nums" value="" />
		<?php
		cgi::add_js_var('plans', $this->plans);
		cgi::add_js_var('coupons', $this->coupons);
		cgi::add_js_var('pmode', $this->get_pmode());
		cgi::add_js_var('common_billing_keys', product::$common_billing_keys);
		$this->ml_load_prev();
	}

	private function is_user_order_doctor()
	{
		return ($this->is_user_leader() || user::has_role('SBP Order Doctor'));
	}

	// price mode, allow custom edits for Leaders
	private function get_pmode()
	{
		return ($this->is_user_order_doctor() ? 'edit' : 'calc');
	}
	
	// can be loaded either from
	// 1. post
	// 2. database (saved from earlier order)
	private function ml_load_prev()
	{
		if ($this->prod_nums)
		{
			$prods = array();
			for ($new_prod_num = 0, $prod_count = count($this->prod_nums); $new_prod_num < $prod_count; ++$new_prod_num)
			{
				$old_prod_num = $this->prod_nums[$new_prod_num];
				$account = product::create_from_post($old_prod_num);
				$prods[] = $account;
			}
			cgi::add_js_var('prods', $prods);
		}
	}
	
	public function action_new_order_submit()
	{
		$this->do_process_new_order();
	}
	
	public function sts_new_order()
	{
		$this->do_process_new_order(array(
			'is_sts' => true
		));
	}
	
	private function do_process_new_order($opts = array())
	{
		util::set_opt_defaults($opts, array(
			'is_sts' => false
		));
		if ($_POST['dbg']) {
			e($_POST);
			db::dbg();
			echo "<hr /><hr />\n";
		}
		
		$this->prod_nums = explode("\t", $_POST['prod_nums']);
		if ($this->prod_nums == '')
		{
			feedback::add_error_msg('Could not find any products');
			$this->set_form_fields($_POST);
			return false;
		}
		if (empty($_POST['email']))
		{
			feedback::add_error_msg('Please enter an email address.');
			$this->set_form_fields($_POST);
			return false;
		}
		
		$billing_info = $this->do_calc_billing();

		// get actual prospect credit card if another cc was not input
		if (strpos($_POST['cc_number_text'], '*') !== false && array_key_exists('prospect_id', $_REQUEST)) {
			$cc = db::select_row("
				select cc_number cc_number_text, cc_code
				from eppctwo.ccs
				where foreign_table = 'prospects' && foreign_id = '".db::escape($_REQUEST['prospect_id'])."'
			", 'ASSOC');
			if ($cc) {
				$_POST['cc_number_text'] = billing::decrypt_val($cc['cc_number_text']);
				$_POST['cc_code'] = billing::decrypt_val($cc['cc_code']);
			}
		}

		// load data from $_POST, pass in billing info
		$order = product_order::create($_POST, $billing_info);
		if ($order->process())
		{
			$wpro_data = $order->get_wpro_data();
			if ($opts['is_sts']) {
				echo serialize($wpro_data);
			}
			else {
				if (array_key_exists('prospect_id', $_REQUEST)) {
					db::update("eppctwo.prospects", array(
						'client_id' => $order->client->id,
						'status' => 'Charged'
					), "id = '".db::escape($_REQUEST['prospect_id'])."'");
				}
				// if only ql pro, nothing to send to wpro
				if ($wpro_data) {
					util::wpro_post('account', 'new_order', $wpro_data, $_POST['dbg']);
				}
				feedback::add_success_msg('Order successfully processed!');
				return true;
			}
		}
		else {
			$error = $order->get_error();
			// send error response back to other server
			if ($opts['is_sts']) {
				echo serialize(array('error' => $error));
			}
			// could not process order, set form fields from $_POST
			// so form can be reloaded with submitted values
			else {
				feedback::add_error_msg($error);
				$this->set_form_fields($_POST);
				return false;
			}
		}
	}
	
	private function set_form_fields(&$data)
	{
		list($this->partner, $this->source, $this->name, $this->email, $this->phone, $this->cc_type, $this->cc_name, $this->cc_number_text, $this->cc_exp_month, $this->cc_exp_year, $this->cc_code, $this->cc_country, $this->cc_zip) = util::list_assoc($data, 'partner', 'source', 'name', 'email', 'phone', 'cc_type', 'cc_name', 'cc_number_text', 'cc_exp_month', 'cc_exp_year', 'cc_code', 'cc_country', 'cc_zip');
	}
	
	private function print_header($title)
	{
		echo '
			<tr>
				<td class="header">'.$title.'</td>
			</tr>
		';
	}
	
	private function print_text_input_row($field, $display = false)
	{
		echo '
			<tr>
				<td>'.(($display) ? $display : util::display_text($field)).'</td>
				<td><input type="text" name="'.$field.'" value="'.$this->$field.'" /></td>
			</tr>
		';
	}
	
	private function print_dbg_row()
	{
		if (!user::is_developer()) {
			return;
		}
		echo '
			<tr>
				<td>Debug?</td>
				<td><input type="checkbox" name="dbg" value="1"'.(($_POST['dbg']) ? ' checked' : '').' /></td>
			</tr>
		';
	}

	private function print_admin_edit_options()
	{
		if ($this->get_pmode() != 'edit') {
			return;
		}
		echo '
			<tr>
				<td>Do Not Send Email</td>
				<td><input type="checkbox" name="do_not_send_email" value="1"'.(($_POST['do_not_send_email']) ? ' checked' : '').' /></td>
			</tr>
			<tr>
				<td>Do Not Charge</td>
				<td><input type="checkbox" name="do_not_charge" value="1"'.(($_POST['do_not_charge']) ? ' checked' : '').' /></td>
			</tr>
		';
	}
	
	private function print_partner_source_input()
	{
		$partners = db::select("
			select id
			from eppctwo.sbr_partner
			where status = 'On'
		");
		
		$sources = db::select("
			select sbr_partner_id, id
			from eppctwo.sbr_source
			where status = 'On' && sbr_partner_id in ('".implode("','", $partners)."')
			order by id asc
		", 'NUM', array(0));
		
		array_unshift($partners, '');
		cgi::add_js_var('sources', $sources);
		cgi::add_js_var('source', $this->source);
		
		?>
		<tr>
			<td>Partner</td>
			<td><?php echo cgi::html_select('partner', $partners, $this->partner); ?></td>
		</tr>
		<tr>
			<td>Source</td>
			<td id="source_td" ejo></td>
		</tr>
		<?php
	}
		
	private function print_country_select()
	{
		$countries = db::select("
			select a2, country
			from eppctwo.countries
		");
		echo '
			<tr>
				<td>Cc Country</td>
				<td>'.cgi::html_select('cc_country', $countries, ($this->cc_country) ? $this->cc_country : 'US').'</td>
			</tr>
		';
	}
	
	private function print_rep_input()
	{
		if ($this->is_user_order_doctor()) {
			$reps = db::select("
				select u.id, u.realname, ar.users_id
				from eppctwo.users u, eppctwo.sbr_active_rep ar
				where
					u.password <> '' &&
					u.id = ar.users_id
				order by u.realname asc
			");
			$ml_rep = cgi::html_select('sales_rep', $reps, user::$id);
		}
		else {
			$ml_rep = '
				<input type="hidden" name="sales_rep" id="sales_rep" value="'.user::$id.'" />
				<span>'.db::select_one("select realname from users where id = '".db::escape(user::$id)."'").'
			';
		}
		echo '
			<tr>
				<td>Rep</td>
				<td>'.$ml_rep.'</td>
			</tr>
		';
	}
	
	private function print_product_input()
	{
		$ml_add_service = '';
		foreach (sbs_lib::$departments as $dept) {
			if (!in_array($dept, $this->ignore_depts)) {
				$ml_add_service .= '<input class="add_product" type="submit" dept="'.$dept.'" value="+'.strtoupper($dept).'" />';
			}
		}
		?>
		<tr>
			<td colspan=5><?= $ml_add_service ?></td>
		</tr>
		<tr>
			<td colspan=5 id="products_td">
				<div id="w_products"></td>
				<div class="clr"></div>
			</td>
		</tr>
		<?php
	}
	
	private function print_billing()
	{
		$year = date('Y');
		$years = range($year, $year + 15);
		
		$month_options = array(
			array('01', '01 - Jan'),
			array('02', '02 - Feb'),
			array('03', '03 - Mar'),
			array('04', '04 - Apr'),
			array('05', '05 - May'),
			array('06', '06 - Jun'),
			array('07', '07 - Jul'),
			array('08', '08 - Aug'),
			array('09', '09 - Sep'),
			array('10', '10 - Oct'),
			array('11', '11 - Nov'),
			array('12', '12 - Dec')
		);
		?>
		<tr>
			<td>Monthly Total</td>
			<td id="monthly_total">$0.00</td>
		</tr>
		<tr>
			<td>Today's Total</td>
			<td id="today_total">$0.00</td>
		</tr>
		<tr>
			<td>Cc Type</td>
			<td><?php echo cgi::html_select('cc_type', array('amex', 'visa', 'mc', 'disc'), $this->cc_type); ?></td>
		</tr>
		<?php
		$this->print_text_input_row('cc_name', 'Billing Name');
		$this->print_text_input_row('cc_number_text', 'Cc Number');
		?>
		<tr>
			<td>Exp Month</td>
			<td><?php echo cgi::html_select('cc_exp_month', $month_options, $this->cc_exp_month); ?></td>
		</tr>
		<tr>
			<td>Exp Year</td>
			<td><?php echo cgi::html_select('cc_exp_year', $years, $this->cc_exp_year); ?></td>
		</tr>
		<?php
		$this->print_text_input_row('cc_code', 'Security Code');
		$this->print_country_select();
		$this->print_text_input_row('cc_zip');
	}
	
	public function ajax_calc_billing()
	{
		$billing_info = $this->do_calc_billing();
		echo json_encode($billing_info);
	}
	
	private function do_calc_billing()
	{
		$info = array(
			'totals' => array(
				'monthly' => 0,
				'today' => 0
			)
		);
		
		if ($_POST['prod_nums'] != '')
		{
			$prod_nums = explode("\t", $_POST['prod_nums']);
			foreach ($prod_nums as $num)
			{
				$prod_info = product::calc_order_billing($_POST, array('num' => $num, 'can_edit' => ($this->get_pmode() == 'edit')));
				$info['prods'][$num] = $prod_info;
				foreach ($info['totals'] as $k => $v)
				{
					$info['totals'][$k] += $prod_info[$k];
				}
			}
		}
		
		return $info;
	}
}

?>