<?php

class mod_account_product extends mod_account
{
	public function pre_output()
	{
		parent::pre_output();
		$this->quick_search = $this->register_widget('quick_search');
		util::load_lib('sbs', $this->dept);
	}
	
	public function print_header_row_2()
	{
		$this->quick_search->output(false);
	}
	
	public function get_menu()
	{
		return product::cgi_get_header_row3_menu();
	}
	
	protected function get_page_menu()
	{
		return array(
			array('', 'Dashboard'),
			array('su', 'SU'),
			array('billing', 'Billing'),
			array('cards', 'Cards'),
			array('email_samples', 'Email Samples'),
			array('order_sheet', 'Order Sheet'),
			array('saps', 'SAPs')
		);
	}
	
	public function head()
	{
		echo '
			<h1>
				<i>'.$this->account->url.'</i>
				('.$this->account->client_id.')('.$this->account->id.')
			</h1>
			'.$this->page_menu($this->get_page_menu(), 'account/product/'.$this->dept.'/', 'aid='.$this->account->id).'
			'.sbs_lib::get_client_department_links($this->account->client_id, array('aid' => $this->account->id)).'
		';
	}
	
	public function action_big_button_cancel()
	{
		$this->account->status = 'Cancelled';
		$update_cols = array('status');
		
		$today = date(util::DATE);
		// cancel button can be hit without non-renew being hit
		// check dates and set them if they are not set
		
		// cancel date is empty or over 30 days ago
		if (util::empty_date($this->account->cancel_date) || ((strtotime($today) - strtotime($this->account->cancel_date)) > 2592000)) {
			$this->account->cancel_date = $today;
			$update_cols[] = 'cancel_date';
		}
		if (util::empty_date($this->account->de_activation_date)) {
			$this->account->de_activation_date = $today;
			$update_cols[] = 'de_activation_date';
		}
		
		$this->account->update(array('cols' => $update_cols));
		
		$this->do_update_wpro_account(array(
			'id' => $this->account->id,
			'status' => $this->account->status
		));
		$this->quick_note('Cancelled');
		$this->hook('cancel');
		feedback::add_success_msg('Account Cancelled');
	}
	
	public function action_big_button_activate()
	{
		$this->account->status = 'Active';
		$this->account->update(array('cols' => array('status')));
		
		$this->do_update_wpro_account(array(
			'id' => $this->account->id,
			'status' => $this->account->status
		));
		$this->quick_note('Activated');
		$this->send_activation_email();
		feedback::add_success_msg('Activated!');
	}
	
	protected function do_update_wpro_account($data)
	{
		util::wpro_post('account', $this->dept.'_update_account', $data);
	}
	
	protected function send_activation_email()
	{
		sbs_lib::send_email($this->account, 'Activation');
	}
	
	public function action_big_button_non_renewing()
	{
		$this->account->status = 'NonRenewing';
		$this->account->cancel_date = date(util::DATE);
		$this->account->de_activation_date = $this->account->next_bill_date;
		$this->account->update(array('cols' => array('de_activation_date', 'cancel_date', 'status')));
		
		$this->do_update_wpro_account(array(
			'id' => $this->account->id,
			'status' => $this->account->status
		));
		$this->quick_note('Non-Renewed');
		
		feedback::add_success_msg('Account Set To Stop Renewing, Cancel Date Set, Non-Renew Note Added');
		if (!$this->account->next_bill_date || $this->account->next_bill_date == '0000-00-00')
		{
			feedback::add_error_msg('Next Bill Date was empty - will not appear in cancel calendar');
		}
	}
	
	public function action_big_button_re_activate()
	{
		$update_cols = array('status', 'cancel_date', 'de_activation_date');
		$this->account->status = 'Active';
		$this->account->cancel_date = '0000-00-00';
		$this->account->de_activation_date = '0000-00-00';
		
		$wpro_data = array(
			'id' => $this->account->id,
			'status' => $this->account->status
		);
		if ($_POST['re_activate_do_set_dates'])
		{
			$update_cols = array_merge($update_cols, array('bill_day', 'prev_bill_date', 'next_bill_date'));
			$this->account->bill_day = $_POST['re_activate_bill_day'];
			$this->account->prev_bill_date = $_POST['re_activate_prev_bill_date'];
			$this->account->next_bill_date = $_POST['re_activate_next_bill_date'];
			
			$wpro_data['bill_day'] = $this->account->bill_day;
		}
		
		$this->account->update(array('cols' => $update_cols));
		$this->do_update_wpro_account($wpro_data);
		$this->quick_note('Re-Activated');
		feedback::add_success_msg('Re-Activated!');
	}
	
	public function action_big_button_nrc()
	{
		$contract_end_date = sbs_lib::get_contract_end_date($this->account);
		if ($contract_end_date === false) {
			feedback::add_error_msg('Could not determine end of contract, please check contract length and first bill date. No action taken.');
			return false;
		}
		$this->account->update_from_array(array(
			'status' => 'NonRenewing',
			'cancel_date' => date(util::DATE),
			'de_activation_date' => $contract_end_date
		));
		
		$this->do_update_wpro_account(array(
			'id' => $this->account->id,
			'status' => $this->account->status
		));
		$this->quick_note('Non-Renewed Contract');
		
		feedback::add_success_msg('Account set to stop renewing at end of contract, Cancel Date set, non-renew contract note added');
	}
	
	public function action_big_button_just_testing()
	{
		$pps = payment_part::get_all(array('where' => "account_id = '{$this->account->id}'"));
		$pps->amount = 0;
		$pps->update(array('cols' => array('amount')));
		
		$ps = payment::get_all(array('where' => "id in ('".implode("','", array_unique($pps->payment_id))."')"));
		$ps->amount = 0;
		$ps->notes = 'test';
		$ps->update(array('cols' => array('amount', 'notes')));
		
		$this->account->update_from_array(array('status' => 'Declined'));
		
		feedback::add_success_msg('Payments 0\'d out, account Declined');
	}
	
	public function pre_output_index()
	{
		$this->contact = new contacts(array('client_id' => $this->account->client_id));
		$this->contact->get();
		
		$this->account_notes = $this->register_widget('account_notes', array('ac_id' => $this->account->id, 'dept' => $this->dept));
	}
	
	public function display_index()
	{
		$trial_cols = array('trial_length', 'trial_amount', 'trial_auth_amount', 'trial_auth_id');
		$op_cols = array('is_op', 'is_op_done');
		
		$plans_for_calc = db::select("
			select name, budget
			from eppctwo.{$this->dept}_plans
			order by name asc
		", 'NUM', 0);

		?>
		<? $this->call_member('index_print_pre_big_buttons_hook') ?>
		<?php $this->print_big_buttons(); ?>
		<div id="product_dashboard" class="lft" clear_me="1" ejo>
			<table class="dashboard_table">
				
				<!-- contact info -->
				<tbody>
					<?php echo $this->contact->html_form(array(
						'table' => false,
						'ignore' => array('authentication', 'last_login', 'client_id', 'id', 'password', 'status', 'notes')
					)); ?>
				</tbody>

				<!-- order sheet info -->
				<tbody>
					<?= $this->print_order_sheet_info_for_dash() ?>
				</tbody>
				
			</table>
                    
			<table class="dashboard_table">
				
				<!-- url info -->
				<tbody>
					<?php echo $this->account->html_form(array(
						'table' => false,
						'relatives' => false,
						'ignore' => array_merge(array('name', 'cc_id', 'is_3_day_done', 'is_7_day_done'), $trial_cols, $op_cols)
					)); ?>
				</tbody>
			</table>
                    
			<div class="lft">
				<?php $this->account_notes->output(); ?>
			</div>
			
			<div class="lft">
				<?php $this->print_upgrade_calc(); ?>
			</div>
			
			<div class="clr"></div>
			<div class="center">
				<input type="submit" a0="action_dashboard_submit" value="Submit Changes" />
			</div>
		</div>
		<?php
		cgi::add_js_var('plans', $plans_for_calc);
	}

	private function print_order_sheet_info_for_dash()
	{
		$order = new product_order(array('id' => $this->account->oid));
		
		if (!$order->is_in_db()) {
			$ml = '
				<tr>
					<td colspan="2">No Data</td>
				</tr>
			';
		}
		else {
			$os_keys = array('name', 'phone');
			$data = unserialize($order->data);
			$ml = '';
			foreach ($os_keys as $key) {
				$v = $data[$key];
				$format_func = 'order_format_'.$k;
				if (method_exists($this, $format_func)) {
					$v = $this->$format_func($v);
				}
				$ml .= '
					<tr>
						<td>'.util::display_text($key).'</td>
						<td>'.$v.'</td>
					</tr>
				';
			}
		}
		echo '
			<tr>
				<td colspan="2"><br /><b>Order Sheet Info</b></td>
			</tr>
			'.$ml.'
		';
	}
	
	public function action_dashboard_submit()
	{
		// contact
		$contact_updates = $this->contact->put_from_post(array('update' => true));
		if ($contact_updates) {
			$wpro_keys = array('name', 'email', 'phone');
			$wpro_data = array();
			foreach ($wpro_keys as $key) {
				if (in_array($key, $contact_updates)) {
					$wpro_data[$key] = $this->contact->$key;
				}
			}
			if ($wpro_data) {
				$wpro_data['id'] = $this->account->client_id;
				util::wpro_post('account', 'client_update', $wpro_data);
			}
			feedback::add_success_msg('Contact info updated: '.implode(', ', array_map(array('util', 'display_text'), $contact_updates)));
		}
		
		// url
		$account_updates = $this->account->put_from_post(array('update' => true));
		if ($account_updates)
		{
			feedback::add_success_msg('Account info updated: '.implode(', ', array_map(array('util', 'display_text'), $account_updates)));
			
			// also add to bf table if is_billing_failure switched on
			if (in_array('is_billing_failure', $account_updates))
			{
				$bf = new sbs_billing_failure(array(
					'department' => $this->dept,
					'account_id' => $this->account->id
				));
				if ($this->account->is_billing_failure)
				{
					$bf->first_fail = date(util::DATE);
					$bf->last_fail = date(util::DATE);
					$bf->details = 'Manually set via dashboard';
					$bf->num_fails = 1;
					$bf->put();
				}
				else
				{
					$bf->delete();
				}
			}
			
			$wpro_updates = array();
			
			// don't need to do anything except copy these
			$simple_keys = array('status', 'alt_num_keywords', 'bill_day', 'url', 'plan', 'has_ads', 'has_soci');
			foreach ($simple_keys as $key) {
				if (in_array($key, $account_updates)) {
					$wpro_updates[$key] = $this->account->$key;
				}
			}
			// send to wpro
			if ($wpro_updates) {
				// need to do some other stuff for these
				if (array_key_exists('plan', $wpro_updates)) {
					$wpro_updates['plan'] = sbs_lib::e2_plan_to_wpro_plan($wpro_updates['plan']);
				}
				$wpro_updates['id'] = $this->account->id;
				$this->do_update_wpro_account($wpro_updates);
			}
		}
	}
	
	private function print_big_buttons()
	{
		$show_all = ($_POST['a0'] == 'action_big_button_show_all');
		$buttons = array();
		$status = $this->account->status;
		if ($status == 'Active' || $show_all) $buttons[] = 'Non Renewing';
		if ($status == 'Active' || $show_all) $buttons[] = 'NRC';
		if ($status != 'Cancelled' || $show_all) $buttons[] = 'Cancel';
		if ($status == 'New' || $show_all) $buttons[] = 'Activate';
		if ($status == 'New' || $show_all) $buttons[] = 'Just Testing';
		if ($status == 'Cancelled' || $status == 'NonRenewing' || $show_all) $buttons[] = 'Re-Activate';
		$buttons[] = 'Move Account';
		$buttons[] = 'Merge Account';
		
		//$this->add_dept_big_buttons($buttons, $show_all);
		
		if (!$show_all) $buttons[] = 'Show All';
		$ml = '';
		foreach ($buttons as $button)
		{
			$ml .= '<input type="submit" a0="action_big_button_'.util::simple_text($button).'" value="'.$button.'" />'."\n";
		}
		?>
		<div id="big_buttons">
			<?php echo $ml; ?>
		</div>
		<?php
	}
	
	private function print_upgrade_calc()
	{
		?>
		<h2>Upgrade Calculator</h2>
		<table>
			<tbody>
				<tr>
					<td>From</td>
					<td id="_upgrade_from"></td>
				</tr>
				<tr>
					<td>To</td>
					<td id="_upgrade_to"></td>
				</tr>
				<tr>
					<td>Last Bill Date</td>
					<td><input type="text" class="date_input" name="upgrade_date_last" id="upgrade_date_last" value="<?php echo $this->account->last_bill_date; ?>" /></td>
				</tr>
				<tr>
					<td>Next Bill Date</td>
					<td><input type="text" class="date_input" name="upgrade_date_next" id="upgrade_date_next" value="<?php echo $this->account->next_bill_date; ?>" /></td>
				</tr>
				<tr>
					<td>"Today"</td>
					<td><input type="text" class="date_input" name="upgrade_date_today" id="upgrade_date_today" value="<?php echo date(util::DATE); ?>" /></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" id="upgrade_calc_button" value="Calculate" /></td>
				</tr>
				<tr>
					<td></td>
					<td id="upgrade_calc_display"></td>
				</tr>
			</tbody>
		</table>
		<?php
	}
	
	public function sts_update_url_ccs()
	{
		foreach ($_POST as $account_field => $cc_id)
		{
			if (preg_match("/^url_(\d+)$/", $account_field, $matches))
			{
				$account_id = $matches[1];
				db::update("eppctwo.{$this->account_table}", array('cc_id' => $cc_id), "id = '$account_id'");
			}
		}
		
		sbs_lib::client_update($this->dept, $account_id, 'sbs-cc', array(
			'action' => 'url-ccs'
		));
	}
	
	public function display_order_sheet()
	{
		$order = new product_order(array('id' => $this->account->oid));
		
		// init with some other stuff
		$data = array(
			'oid' => $this->account->oid,
			'timestamp' => $order->ts
		);
		
		$data = array_merge(unserialize($order->data), $data);
		$ignore_keys = array(
			'module_select',
			'client',
			'cc_number_text',
			'cc_code',
			'prod_nums',
			'a0',
			'cl_type'
		);
		$ml_data = '';
		foreach ($data as $k => $v) {
			if (!in_array($k, $ignore_keys)) {
				$format_func = 'order_format_'.$k;
				if (method_exists($this, $format_func)) {
					$v = $this->$format_func($v);
				}
				$ml_data .= '
					<tr>
						<td>'.util::display_text($k).'</td>
						<td>'.$v.'</td>
					</tr>
				';
			}
		}
		
		?>
		<table>
			<tbody>
				<?= $ml_data ?>
			</tbody>
		</table>
		<?php
	}

	public function display_saps()
	{
		$cl_ids = array($this->account->client_id);
		// check for ppc client id
		$ql_pro_x = new ql_pro_x_ppc(array('ql_account_id' => $this->account->id), array('do_get' => true));
		if (isset($ql_pro_x->ppc_client_id)) {
			$cl_ids[] = $ql_pro_x->ppc_client_id;
		}
		$contracts = db::select("
			SELECT create_date, url_key, status, url
			FROM eppctwo.prospects
			WHERE client_id in ('".implode("','", $cl_ids)."')
			ORDER BY create_date DESC
		", 'ASSOC');

		$ml_data = '';
		foreach ($contracts as $c){
			$ml_data .= '
				<tr>
					<td><a href="'.util::get_sap_url($c['url_key']).'" target="_blank">'.$c['url_key'].'</a></td>
					<td>'.$c['url'].'</td>
					<td>'.$c['status'].'</td>
					<td>'.$c['create_date'].'</td>
				</tr>
			';
		}

		?>

		<h1>SAP Links</h1>
		<table class="wpro_table">
			<thead>
				<tr>
					<th>SAP Link</th>
					<th>URL</th>
					<th>Status</th>
					<th>Created</th>
				</tr>
			</thead>
			<tbody>
				<?= $ml_data ?>
			</tbody>
		</table>
		<?
	}
	
	protected function order_format_sales_rep($x)
	{
		return db::select_one("
			select realname
			from eppctwo.users
			where id = :id
		", array('id' => $x));
	}
	
	public function display_email_samples()
	{
		$tpls = email_template_mapping::get_all(array('where' => "
			department = '{$this->account->dept}' &&
			plan = '{$this->account->dept}-{$this->account->plan}'
		"));
		$ml = '';
		foreach ($tpls as $tpl)
		{
			$ml .= '
				<p>
					<a href="'.cgi::href('product/email_templates/sample?tkey='.$tpl->tkey.'&mid='.$tpl->id.'&aid='.$this->account->id).'" target="_blank">
						'.$tpl->action.'
					</a>
				</p>
			';
		}
		?>
		<h1>Account Email Templates</h1>
		<?php echo $ml; ?>
		<?php
	}
	
	public function display_su()
	{
		$ml_re_tie = '<input type="submit" a0="action_su_retie" value=" Re-Tie to SU " />';
		
		$cl_info = util::wpro_post('account', 'client_su', array('cid' => $this->account->client_id), dbg::is_on());
		

		if (!is_array($cl_info))
		{
			//Secret Universe Error
			feedback::add_error_msg('Warning: Unable to retrieve remote id. Issue connecting to remote server.');
			if (user::is_developer()) {
				e(array('cl_info'=>$cl_info));
				e(array('ml_re_tie'=>$ml_re_tie));
			}
		}
		list($last_login) = db::select_row("
			select co.last_login
			from eppctwo.contacts co
			where co.client_id = '{$this->account->client_id}'
		");
		if ($cl_info['pass_set_key'])
		{
			$ml_pass_set = sbs_lib::client_pass_set_link($cl_info['pass_set_key']);
		}
		else
		{
			$ml_pass_set = '<input type="submit" class="small_button" a0="action_su_login_pass_set_key_submit" value="Reset Pass and Get URL" />';
		}
		$actual_su_params = 'url_id='.$this->account->id.'&cl_id='.$this->account->client_id.'&su_key='.$cl_info['su_key'].'&su_key_time='.htmlentities($cl_info['su_key_time']);
		?>
		<?= $ml_re_tie ?>
		<table>
			<tbody>
				<tr>
					<td>Name</td>
					<td><?php echo $cl_info['name']; ?></td>
				</tr>
				<tr>
					<td>Email</td>
					<td><?php echo $cl_info['email']; ?></td>
				</tr>
				<tr>
					<td>Last Login</td>
					<td><?php echo ((util::empty_date_time($last_login)) ? 'NEVER' : $last_login); ?></td>
				</tr>
				<tr>
					<td>Password?</td>
					<td><?php echo (($cl_info['password']) ? 'Yes' : 'No'); ?></td>
				</tr>
				<tr>
					<td>Set PW Link:</td>
					<td><?php echo $ml_pass_set; ?></td>
				</tr>
				<tr>
					<td id="actual_su_td" ejo><a href="?<?php echo $actual_su_params; ?>" target="_blank">Actual SU</a></td>
					<td></td>
				</tr>
			</tbody>
		</table>
		<?php
		$this->hook('print_su_account_info');
	}

	public function action_su_login_pass_set_key_submit()
	{
		$pass_set_key = sbs_lib::generate_pass_set_key();
		contacts::update_all(array(
			'set' => array('authentication' => $pass_set_key),
			'where' => "client_id = :cid",
			'data' => array('cid' => $this->account->client_id)
		));
		util::wpro_post('account', 'client_update', array(
			'id' => $this->account->client_id,
			'pass_set_key' => $pass_set_key,
			'password' => ''
		));
		feedback::add_success_msg('Pass Set Key Updated');
	}

	public function action_su_retie()
	{
		util::load_lib('billing');

		$contact = db::select_row("
			select client_id id, email, password, authentication as pass_set_key, name, phone
			from eppctwo.contacts
			where client_id = :cid
		", array('cid' => $this->account->client_id), 'ASSOC');
		util::wpro_post('account', 'client_update', $contact);

		$cc_ids = ccs::get_all(array(
			'select' => array("ccs" => array("*")),
			'join' => array("cc_x_client" => "ccs.id = cc_x_client.cc_id"),
			'where' => "cc_x_client.client_id = :cid",
			'data' => array('cid' => $this->account->client_id)
		));
		
		for ($i = 0, $ci = $cc_ids->count(); $i < $ci; ++$i) {
			$cc = $cc_ids->i($i);
			$cc->number_text = billing::decrypt_val($cc->cc_number);

			$this->hook_cc_new($cc);
		}

		$account_data = $this->account->to_array();
		$account_data['plan'] = sbs_lib::e2_plan_to_wpro_plan($account_data['plan']);
		$account_data['created'] = date(util::DATE, strtotime($account_data['signup_dt']));
		$this->do_update_wpro_account($account_data);

		feedback::add_success_msg('SU Re-Tied');
	}

	public function action_actual_su()
	{
		list($su_key, $su_key_time) = util::list_assoc($_GET, 'su_key', 'su_key_time');
		if (util::empty_date_time($su_key_time) || ((time() - strtotime($su_key_time)) > 86400))
		{
			$r = util::wpro_post('account', 'set_su_key', array('cid' => $this->account->client_id));
			$su_key = $r['su_key'];
		}
		$protocol = (util::is_dev()) ? 'http' : 'https';
		cgi::redirect($protocol.'://'.\epro\WPRO_DOMAIN.'/account/login?su_key='.$su_key);
	}
	
	protected function hook_print_billing_extras()
	{
		$num_months = ($this->account->prepay_paid_months) ? $this->account->prepay_paid_months + $this->account->prepay_free_months : 1;
		$next_bill_date = util::delta_month($this->account->next_bill_date, $num_months, $this->account->bill_day);
		?>
		<tr class="new">
			<td>Update Bill Dates</td>
			<td>
				<table>
					<tbody>
						<tr>
							<td><input type="checkbox" id="do_update_bill_dates" name="do_update_bill_dates" value="1" /></td>
							<td><input type="text" class="date_input" name="prev_bill_date" id="prev_bill_date" value="<?= $this->account->next_bill_date ?>" /></td>
						</tr>
						<tr>
							<td></td>
							<td><input type="text" class="date_input" name="next_bill_date" id="next_bill_date" value="<?= $next_bill_date ?>" /></td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
		<?php
	}
	
	protected function hook_do_record_payment($pps)
	{
		if ($_POST['do_update_bill_dates']) {
			account::update_all(array(
				"set" => array(
					'prev_bill_date' => $_POST['prev_bill_date'],
					'next_bill_date' => $_POST['next_bill_date']
				),
				"where" => "id in ('".implode("','", array_unique($pps->account_id))."')"
			));
		}
	}
	
	protected function hook_cc_new($cc)
	{
		$cc = $cc->to_array();
		$num = (isset($cc['number_text'])) ? $cc['number_text'] : $cc['cc_number'];
		util::wpro_post('account', 'cc_save', array(
			'client_id'     => $this->account->client_id,
			'id'            => $cc['id'],
			'cc_name'       => $cc['name'],
			'cc_first_four' => substr($num, 0, 4),
			'cc_last_four'  => substr($num, strlen($num) - 4),
			'cc_exp_month'  => $cc['cc_exp_month'],
			'cc_exp_year'   => $cc['cc_exp_year'],
			'cc_country'    => $cc['country'],
			'cc_zip'        => $cc['zip']
		));
	}
	
	protected function hook_cc_update($update_data)
	{
		util::wpro_post('account', 'cc_save', $update_data);
	}
	
	protected function hook_cc_activate($cc_id)
	{
		util::wpro_post('account', 'cc_activate', array(
			'dept' => $this->account->dept,
			'aid' => $this->account->id,
			'cc_id' => $cc_id
		));
	}
	
	protected function hook_cc_delete($cc_id)
	{
		util::wpro_post('account', 'cc_delete', array(
			'id' => $cc_id
		));
	}

	public function ajax_merge_ac_get_url()
	{
		$prods = product::get_all(array(
			'select' => "dept, url",
			'where' => "account.id = :aid",
			'data' => array('aid' => $_POST['id'])
		));
		if ($prods->count()) {
			$prod = $prods->i(0);
			echo $prod->dept.', '.$prod->url;
		}
	}

	public function action_merge_account($value='')
	{
		util::load_lib('billing');
		$src_account = new product(array('id' => $_POST['merge_ac_id']));

		// check client is actually new
		if ($src_account->client_id == $this->account->client_id) {
			feedback::add_error_msg($merge_url.' is already part of this account');
			return false;
		}
		
		// update client email if the same to avoid login conflicts
		$src_email = db::select_one("select email from eppctwo.contacts where client_id = '{$src_account->client_id}'");
		$dst_email = db::select_one("select email from eppctwo.contacts where client_id = '{$this->account->client_id}'");
		if ($src_email == $dst_email) {
			$non_conflict_email = '-'.$src_email;
			db::update("eppctwo.contacts",
				array('email' => $non_conflict_email),
				"client_id = :cid",
				array('cid' => $src_account->client_id)
			);
			util::wpro_post('account', 'client_update', array(
				'id' => $src_account->client_id,
				'email' => $non_conflict_email
			));
		}
		// check client cards to see if one matches the url we are merging
		$merged_cc = billing::cc_get_actual($src_account->cc_id);
		$ccs = ccs::get_all(array(
			'select' => array("ccs" => array("*")),
			'join' => array("cc_x_client" => "ccs.id = cc_x_client.cc_id"),
			'where' => "cc_x_client.client_id = :cid",
			'data' => array('cid' => $this->account->client_id)
		));
		$is_new_cc = true;
		foreach ($ccs as $cc) {
			$cc = billing::cc_get_actual($cc);
			if ($cc->cc_number == $merged_cc['cc_number']) {
				$is_new_cc = false;
				$src_account->update_from_array(array('cc_id' => $cc->id));

				// todo: eac version ofonly ql has active card functionality on wpro
				// if ($type == 'ql')
				// {
				// 	util::wpro_post('account', 'sbs_set_active_card', array(
				// 		'department' => $type,
				// 		'cc_id' => $cc_id,
				// 		'url_id' => $src_account->id
				// 	));
				// }
				break;
			}
		}
		// didn't have cc in dst account, tie in src cc
		if ($is_new_cc) {
			cc_x_client::create(array(
				'cc_id' => $src_account->cc_id,
				'client_id' => $this->account->client_id
			));
			$this->hook_cc_update(array(
				'id' => $src_account->cc_id,
				'client_id' => $this->account->client_id
			));
		}
		// payments
		$ps = payment::get_all(array(
			'select' => array(
				"payment" => array("*"),
				"payment_part" => array("id as ppid", "payment_id", "account_id", "division", "dept", "type", "is_passthru", "amount as ppamount", "rep_pay_num")
			),
			'join_many' => array(
				"payment_part" => "payment.id = payment_part.payment_id"
			),
			'where' => "payment.client_id = '{$src_account->client_id}'",
			'order_by' => "payment.ts desc",
			'de_alias' => true
		));
		foreach ($ps as $p) {
			$account_pps = payment_part::new_array();
			foreach ($p->payment_part as $pp) {
				if ($pp->account_id == $src_account->id) {
					$account_pps->push($pp);
				}
			}

			// if all payment parts are from src account, just update client id
			if ($account_pps->count() == $p->payment_part->count()) {
				$p->update_from_array(array('client_id' => $this->account->client_id));
			}
			// otherwise we need to move the pps to new client
			else if ($account_pps->count() > 0) {
				// update old payment amount
				$acpps_amount = array_sum($account_pps->amount);
				$p->update_from_array(array('amount' => $p->amount - $acpps_amount));
				// unset id and set amount to account pps amount to create new payment
				unset($p->id);
				$p->client_id = $this->account->client_id;
				$p->amount = $acpps_amount;
				$p->note .= (empty($p->note)) ? 'payment split' : ' (payment split)';
				$p->insert();
				foreach ($account_pps as $pp) {
					// delete the old one
					$pp->delete();
					// unset id and update payment id
					unset($pp->id);
					$pp->payment_id = $p->id;
					$pp->insert();
				}
			}
		}

		// last, update client id in sbs table and on wpro to point account to new client
		$src_account->update_from_array(array('client_id' => $this->account->client_id));
		util::wpro_post('account', $src_account->dept.'_update_account', array(
			'id' => $src_account->id,
			'client_id' => $this->account->client_id
		));

		feedback::add_success_msg('Merged: '.$src_account->url);
	}

	public function action_move_to_partner()
	{
		util::load_lib('as', 'partner');
		
		// check notes to see if we've already moved this client?

		// create partner account
		$account_data = array(
			'client_id' => $this->account->client_id,
			'division' => 'service',
			'dept' => 'partner',
			'name' => $_REQUEST['move_client_name'],
			'manager' => 0,
			'cc_id' => $this->account->cc_id,
			'url' => $this->account->url,
			'signup_dt' => date(util::DATE_TIME),
			'bill_day' => $this->account->bill_day,
			'prev_bill_date' => $this->account->prev_bill_date,
			'next_bill_date' => $this->account->next_bill_date
		);
		$new_account = as_partner::create($account_data);

		// get payments
		$payments = payment::get_all(array(
			'select' => array(
				"payment" => array("id as pid", "user_id", "fid", "pay_method", "pay_id", "date_received", "date_attributed", "amount", "event", "notes"),
				"payment_part" => array("id as ppid", "account_id", "dept", "type", "amount as part_amount")
			),
			'join_many' => array(
				"payment_part" => "payment.id = payment_part.payment_id && payment.amount > 0"
			),
			'where' => "payment.client_id = '{$this->account->client_id}'",
			'order' => "payment.ts desc"
		));

		// insert small biz payments into agency payments table
		// zero out amounts in small biz
		foreach ($payments as $p) {
			$partner_pps = array();
			$small_biz_pp_ids = array();
			$amount_moved = 0;
			foreach ($p->payment_part as $pp) {
				if (!isset($_POST["pp-{$pp->ppid}"])) {
					continue;
				}
				$small_biz_pp_ids[] = $pp->ppid;
				if ($pp->type == 'Setup') {
					$partner_type = 'Partner Start-Up Fee';
				}
				else {
					switch ($pp->dept) {
						case ('ql'): $partner_type = 'Partner PPC'; break;
						case ('gs'): $partner_type = 'Partner SEO'; break;
						case ('sb'): $partner_type = 'Partner SMO'; break;
					}
				}
				$partner_pps[$partner_type]['amount'] += $pp->part_amount;
				$amount_moved += $pp->part_amount;
			}
			if (empty($partner_pps)) {
				continue;
			}
			$pid = db::insert("eppctwo.client_payment", array(
				'client_id' => $this->account->client_id,
				'user_id' => $p->user_id,
				'pay_id' => $p->pay_id,
				'pay_method' => $p->pay_method,
				'fid' => $p->fid,
				'date_received' => $p->date_received,
				'date_attributed' => $p->date_attributed,
				'amount' => $amount_moved,
				'notes' => $p->notes
			));
			foreach ($partner_pps as $pp_type => $partner_pp) {
				$partner_pp['client_id'] = $this->account->client_id;
				$partner_pp['type'] = $pp_type;
				$partner_pp['client_payment_id'] = $pid;
				db::insert("eppctwo.client_payment_part", $partner_pp);
			}
			// zero out small biz
			db::update("eac.payment"     , array('amount' => db::literal('amount - '.$amount_moved)), "id = '{$p->pid}'");
			db::update("eac.payment_part", array('amount' => 0), "id in (:ppids)", array('ppids' => $small_biz_pp_ids));
		}
		
		// product note
		$this->quick_note("Moved to Partner ({$this->account->client_id})");

		// cancel small biz
		$this->account->update_from_array(array('status' => 'Cancelled'));

		feedback::add_success_msg('Client moved to Partner');
	}

	public function ajax_move_account_get_payment_parts()
	{
		$pps = payment_part::get_all(array(
			'select' => array(
				"payment_part" => array("id as ppid", "dept", "type", "amount"),
				"payment" => array("id as pid", "date_attributed", "event")
			),
			'join' => array(
				"payment" => "payment.id = payment_part.payment_id"
			),
			'where' => "payment_part.account_id = '{$this->account->id}' && payment_part.amount > 0",
			'order' => "payment.ts desc",
			'flatten' => true
		));

		echo $pps->json_encode();
	}
}

?>