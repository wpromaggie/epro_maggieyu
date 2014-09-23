<?php

class mod_account extends module_base
{
	// the account id and object, set in pre output if we have an id
	protected $aid, $account;
	
	// some type info
	protected $division, $dept;
	
	public function pre_output()
	{
		// strip off mod_account, get division and dept
		list($this->division, $this->dept) = explode('_', substr(get_called_class(), strlen(__CLASS__) + 1));
		// if no dept, try to get from url
		if (!$this->dept) {
			$this->no_base_class = true;
			$this->dept = g::$pages[2];
		}
		if ($this->dept) {
			$rsclass = 'a'.$this->division[0].'_'.$this->dept;
			if (array_key_exists('aid', $_REQUEST)) {
				$this->aid = $_REQUEST['aid'];
				$this->account = new $rsclass(array('id' => $this->aid));
			}
		}
	}
	
	public function display_index()
	{
		e($this);
	}
	
	public function pre_output_billing()
	{
		util::load_lib('billing','account','eac');
	}
	
	public function pre_output_cards()
	{
		util::load_lib('billing');
		cgi::add_js('account.cards.js');
	}
	
	public function display_billing()
	{
		$history = payment::get_all(array(
			'select' => array(
				"payment" => array("id as pid", "pay_method", "pay_id", "date_received", "date_attributed", "event", "notes"),
				"payment_part" => array("account_id", "dept", "type", "amount as part_amount")
			),
			'join_many' => array(
				"payment_part" => "payment.id = payment_part.payment_id"
			),
			'where' => "payment.client_id = '{$this->account->client_id}'",
			'order_by' => "payment.ts desc"
		));
		
		$events = pe_event::get_all(array(
			'select' => "event",
			'order_by' => "event asc"
		));
		
		$dept_and_type = dept_and_payment_type::get_all(array(
			'select' => "dept, type",
			'order_by' => "dept asc, type asc",
			'key_col' => 'dept',
			'key_grouped' => true,
		));
		
		$account_options = account::get_all(array(
			'select' => array("account" => array("id as value", "concat(dept, ', ', url) as text")),
			'where' => "client_id = '{$this->account->client_id}'",
			'order_by' => "dept asc, url asc"
		));
		
		$client_accounts = account::get_all(array(
			'select' => array("account" => array("id", "dept", "url")),
			'where' => "client_id = '{$this->account->client_id}'",
			'order_by' => "dept asc, url asc"
		));
		
		$cc_options = client::get_cc_options($this->account->client_id);
		
		$payment_actions = array(
			'Charge',
			'Refund',
			'Record'
		);
		
		cgi::add_js_var('accounts', $client_accounts);
		cgi::add_js_var('dept_and_type', $dept_and_type);
		cgi::add_js_var('history', $history);
		cgi::add_js_var('default_payments', $this->get_default_payments());
		?>
		<fieldset class="first" id="billing_history" ejo>
			<legend>History</legend>
			<div id="w_history_table"></div>
		</fieldset>
		
		<fieldset id="payment_form" ejo>
			<legend><span class="new">New</span><span class="edit">Edit</span> Payment</legend>
			<div id="edit_note" class="edit">
				* You are editing a payment. No actual money will be charged/refunded,
				just our local database will be updated.
			</div>
			<table>
				<tbody>
					<tr>
						<td>Action</td>
						<td><?= cgi::html_radio('action', $payment_actions, 'Charge', array('separator' => '', 'wrapper' => 'div')) ?></td>
					</tr>
					<tr>
						<td>Pay Method</td>
						<td><?= cgi::html_radio('pay_method', payment::$pay_method_options, 'CC', array('separator' => '', 'wrapper' => 'div')) ?></td>
					</tr>
					<tr class="pay_method_details cc">
						<td>CC</td>
						<td><?= cgi::html_select('cc_id', $cc_options, $this->account->cc_id) ?></td>
					</tr>
					<tr class="pay_method_details check">
						<td>Check #</td>
						<td><input type="text" name="check_id" id="check_id" value="" /></td>
					</tr>
					<tr class="pay_method_details wire">
						<td>Wire #</td>
						<td><input type="text" name="wire_id" id="wire_id" value="" /></td>
					</tr>
					<tr>
						<td>Event</td>
						<td><?= cgi::html_radio('event', $events->event, 'Rollover', array('separator' => '', 'wrapper' => 'div')) ?></td>
					</tr>
					<tr>
						<td>Date Received</td>
						<td><input type="text" class="date_input" name="date_received" id="date_received" value="<?= date(util::DATE) ?>" /></td>
					</tr>
					<tr>
						<td>Date Attributed</td>
						<td><input type="text" class="date_input" name="date_attributed" id="date_attributed" value="<?= date(util::DATE) ?>" /></td>
					</tr>
					<tr>
						<td>Notes</td>
						<td><input type="text" name="notes" id="notes" value="" /></td>
					</tr>
					<tr class="new">
						<td>Amortize</td>
						<td>
							<input type="checkbox" id="do_amortize" name="do_amortize" value="1"<?= (($this->account->prepay_paid_months) ? ' checked="1"' : '') ?> />
							<?= cgi::html_select('new_amortize_months', range(2, 24), $this->account->prepay_paid_months + $this->account->prepay_free_months) ?>
							months
						</td>
					</tr>
					<tr>
						<td></td>
						<td>
							<table>
								<thead>
									<tr>
										<th>Account</th>
										<th>Type</th>
										<th>Amount</th>
									</tr>
								</thead>
								<tbody id="payment_parts"></tbody>
							</table>
							<div><input type="submit" id="add_payment_part_row_button" value=" + " /></div>
						</td>
					</tr>
					<tr id="total_row">
						<td>Total</td>
						<td id="total_cell"></td>
					</tr>
					<?= $this->hook('print_billing_extras') ?>
					<tr>
						<td></td>
						<td>
							<input type="submit" a0="action_payment_form_submit" value="Submit" />
							<input type="submit" a0="action_delete_payment_submit" class="edit" id="edit_delete_button" value="Delete" />
							<input type="submit" class="edit" id="edit_cancel_button" value="Cancel" />
						</td>
					</tr>
					<tr class="edit"><td colspan="5"><br /></td></tr>
					<tr class="edit">
						<td>Amortize Months</td>
						<td><?= cgi::html_select('amortize_months', range(2, 24), $this->account->prepay_paid_months + $this->account->prepay_free_months) ?></td>
					</tr>
					<tr class="edit">
						<td></td>
						<td><input type="submit" a0="action_amortize_submit" value="Amortize" /></td>
					</tr>
				</tbody>
			</table>
			<input type="hidden" name="num_pps_added" id="num_pps_added" value="" />
			<input type="hidden" name="edit_pid" id="edit_pid" value="" />
		</fieldset>
		<div class="clr"></div>
		<?php
	}

	public function action_amortize_submit()
	{
		$payments = payment::get_all(array(
			"select" => array(
				"payment" => array("id as pid", "client_id", "user_id", "pay_id", "pay_method", "fid", "ts", "date_received", "date_attributed", "event", "notes", "amount"),
				"payment_part" => array("id as ppid", "account_id", "division", "dept", "type", "is_passthru", "amount as part_amount", "rep_pay_num")
			),
			"join_many" => array(
				"payment_part" => "payment.id = payment_part.payment_id"
			),
			"where" => "payment.id = :pid",
			"de_alias" => true,
			"data" => array(
				'pid' => $_REQUEST['pid']
			)
		));
		$payment = $payments->current();
		if (sbs_lib::amortize_multi_month_payment($payment, $_POST['amortize_months'])) {
			feedback::add_success_msg('Payment Amortized');
		}
	}

	private function get_default_payments()
	{
		# get other accounts for this client that share bill date and credit card
		$accounts = product::get_all(array(
			"select" => array(
				"account" => array("id", "dept", "plan", "prepay_paid_months", "prepay_free_months"),
				"product" => array("alt_recur_amount")
			),
			"where" => "
				(
					account.id = '{$this->account->id}' &&
					account.status not in ('Incomplete', 'Cancelled', 'Declined')
				)
				||
				(
					account.client_id = '{$this->account->client_id}' &&
					account.cc_id = '{$this->account->cc_id}' &&
					account.next_bill_date = '{$this->account->next_bill_date}' &&
					(
						(account.status = 'Active') ||
						(account.status = 'NonRenewing' && account.de_activation_date != '{$this->account->next_bill_date}')
					)
				)
			"
		));
		$default_payments = array();
		foreach ($accounts as $account) {
			$default_payments[] = array(
				'account_id' => $account->id,
				'part_amount' => sbs_lib::get_recurring_amount($account)
			);
		}
		return $default_payments;
	}
	
	private function is_payment_processor_action($action)
	{
		return (
			$action == 'Charge' ||
			$action == 'Refund'
		);
	}
	
	private function do_init_payment_parts($num_pps_added, &$total_amount, $action, $edit_pps = false)
	{
		// if an edit, make array tracking new, updated and deleted
		if ($edit_pps) {
			$pps = array(
				'new' => false,
				'updated' => false,
				'deleted' => false
			);
		}
		else {
			$pps = payment_part::new_array();
		}
		if (!is_numeric($num_pps_added)) {
			$num_pps_added = 100;
		}
		$total_amount = 0;
		for ($i = 0; $i < $num_pps_added; ++$i) {
			if (array_key_exists('account_'.$i, $_POST)) {
				list($ac_id, $dept, $type, $amount_tmp) = util::list_assoc($_POST, 'account_'.$i, 'dept_'.$i, 'type_'.$i, 'amount_'.$i);
				
				// skip over empty rows
				if (!$ac_id || !$dept || !$type) {
					continue;
				}
				
				$amount = preg_replace("/[^-\d\.]/", '', $amount_tmp);
				if (!is_numeric($amount)) {
					feedback::add_error_msg('Could not get amount for payment part '.$dept.', '.$type);
					return false;
				}
				if ($action == 'Refund') {
					$amount *= -1;
				}
				$total_amount += $amount;
				
				// edit, only push if new
				$new_pointer = false;
				if ($edit_pps) {
					for ($j = 0, $cj = $edit_pps->count(); $j < $cj; ++$j) {
						$pp = $edit_pps->i($j);
						if ($ac_id == $pp->account_id && $type == $pp->type) {
							if ($amount != $pp->amount) {
								if (!$pps['updated']) {
									$pps['updated'] = payment_part::new_array();
								}
								$pp->amount = $amount;
								$pps['updated']->push($pp);
							}
							$edit_pps->splice($j, 1);
							break;
						}
					}
					// made it all the way through
					if ($j == $cj) {
						if (!$pps['new']) {
							$pps['new'] = payment_part::new_array();
						}
						$new_pointer = &$pps['new'];
					}
				}
				// not an edit, always new
				else {
					$new_pointer = &$pps;
				}
				// we have a new pp to create
				if ($new_pointer) {
					$new_pointer->push(new payment_part(array(
						'account_id' => $ac_id,
						'division' => account::dept_to_division($dept),
						'dept' => $dept,
						'type' => $type,
						'amount' => $amount
					)));
					// stop pointing for next iteration
					unset($new_pointer);
				}
			}
		}
		// it's an edit, anything left was not part of the edit: delete
		if ($edit_pps && $edit_pps->count()) {
			$pps['deleted'] = $edit_pps;
		}
		return $pps;
	}
	
	public function action_payment_form_submit()
	{
		$edit_pid = $event = $action = $pay_method = $date_received = $date_attributed = $note = $num_pps_added = false;
		extract($_POST, EXTR_IF_EXISTS);
		
		$pay_id = $_POST[strtolower($pay_method).'_id'];
		if ($edit_pid)
		{
			$edit_pps = payment_part::get_all(array(
				"where" => "payment_id = :pid",
				"data" => array('pid' => $edit_pid)
			));
			$pp_updates = $this->do_init_payment_parts($num_pps_added, $total_amount, $action, $edit_pps);
			if ($pp_updates === false)
			{
				return false;
			}
			
			// hack hack hack
			// should we have option for update_from_array that tracks what is different?
			$_POST['amount'] = $total_amount;
			$_POST['pay_id'] = $pay_id;
			$payment = new payment(array('id' => $edit_pid));
			$payment_fields_updated = $payment->put_from_post(array('update' => true, 'do_use_table_prefix' => false));
			if ($payment_fields_updated)
			{
				feedback::add_success_msg("Payment fields updated: ".implode(', ', $payment_fields_updated));
			}
			
			// pps actions
			if ($pp_updates['new'])
			{
				$pp_updates['new']->payment_id = $payment->id;
				$pp_updates['new']->insert();
				feedback::add_success_msg($pp_updates['new']->count()." payment parts added");
			}
			if ($pp_updates['updated'])
			{
				$pp_updates['updated']->update(array('cols' => array('amount')));
				feedback::add_success_msg($pp_updates['updated']->count()." payment parts updated");
			}
			if ($pp_updates['deleted'])
			{
				$pp_updates['deleted']->delete(array('use_pk' => true));
				feedback::add_success_msg($pp_updates['deleted']->count()." payment parts deleted");
			}
		}
		// new payment
		else {
			$pps = $this->do_init_payment_parts($num_pps_added, $total_amount, $action);
			if ($pps === false) {
				return false;
			}
			switch ($action) {
				case ('Charge'):
					$db_amount = $total_amount;
					$processor_amount = $total_amount;
					$processor_action = BILLING_CC_CHARGE;
					break;
					
				case ('Refund'):
					// pps are made negative for refunds, good for db,
					// however processor requires positive amounts
					$db_amount = $total_amount;
					$processor_amount = $total_amount * -1;
					$processor_action = BILLING_CC_REFUND;
					break;
					
				case ('Record'):
					$db_amount = $total_amount;
					break;
			}
			
			$payment = new payment(array_merge($_POST, array(
				'client_id' => $this->account->client_id,
				'user_id' => user::$id,
				'pay_method' => $pay_method,
				'pay_id' => $pay_id,
				'ts' => date(util::DATE_TIME),
				'amount' => $db_amount
			)));
			
			// send payment to processor
			$do_record_payment = true;
			if ($pay_method == 'CC' && $this->is_payment_processor_action($action)) {
				// get bf info
				$bf = new sbs_billing_failure(array(
					'department' => $this->dept,
					'account_id' => $this->account->id
				));
				$is_bf = (isset($bf->first_fail));

				if (billing::charge($pay_id, $processor_amount, $processor_action)) {
					
					if ($is_bf && $action != 'Refund') {
						$bf->delete();
						$this->account->update_from_array(array('is_billing_failure' => 0));
						feedback::add_success_msg('Removed From Billing Failure Queue');
					}
					
					$payment->fid = billing::$order_id;
					feedback::add_success_msg('Payment for '.util::format_dollars($total_amount).' successfully '.$action.'ed');
				}
				else {
					// todo: record failed payments. prob all processor actions should be logged by billing lib
					
					$billing_error = billing::get_error(false);
					if ($action != 'Refund') {
						if (!$is_bf) {
							feedback::add_msg('Account added to BF queue');
							$bf->first_fail = date(util::DATE);
							$bf->num_fails = 0;
						}
						$bf->details = $billing_error;
						$bf->last_fail = date(util::DATE);
						$bf->num_fails++;
						$bf->put();
						
						$this->account->update_from_array(array('is_billing_failure' => 1));
					}
					
					$do_record_payment = false;
					feedback::add_error_msg('Error processing payment: '.$billing_error);
				}
			}
			// not a cc payment
			else {
				$payment->fid = '';
			}
			// record payment locally
			if ($do_record_payment) {
				
				$this->hook('do_record_payment', $pps);
				
				if ($payment->insert()) {
					$pps->payment_id = $payment->id;
					$pps->insert();
					feedback::add_success_msg('Payment recorded in database');

					if ($_POST['do_amortize']) {
						$payment->payment_part = $pps;
						if (sbs_lib::amortize_multi_month_payment($payment, $_POST['new_amortize_months'])) {
							feedback::add_success_msg('Payment Amortized');
						}
					}
				}
				else {
					feedback::add_error_msg("Error recording payment in database: ".rs::get_error());
				}
			}
		}
	}
	
	public function action_delete_payment_submit()
	{
		$pid = $_POST['edit_pid'];
		payment::delete_all(array("where" => "id = :id", "data" => array('id' => $pid)));
		payment_part::delete_all(array("where" => "payment_id = :id", "data" => array('id' => $pid)));
		feedback::add_success_msg('Payment deleted');
	}
	
	public function display_cards()
	{
		$cc_ids = ccs::get_all(array(
			'select' => array("ccs" => array("id")),
			'join' => array("cc_x_client" => "ccs.id = cc_x_client.cc_id"),
			'where' => "cc_x_client.client_id = :cid",
			'data' => array('cid' => $this->account->client_id)
		));
		
		$ml_active_cc = $this->ml_card_full($this->account->cc_id, array('active' => true));
		
		$ml_other_ccs = '';
		for ($i = 0, $ci = $cc_ids->count(); $i < $ci; ++$i)
		{
			$cc_id = $cc_ids->i($i)->id;
			if ($cc_id != $this->account->cc_id)
			{
				$ml_other_ccs .= $this->ml_card_full($cc_id, array('active' => false));
			}
		}
		if (empty($ml_other_ccs))
		{
			$ml_other_ccs = '<p>No Other Credit Cards</p>';
		}
		
		if ($this->user_has_full_card_access())
		{
			$ml_show_full_card = '
				<div id="show_full_cards" ejo>
					<span><a href="">Show Full Cards</a></span>
					<span class="loading">'.cgi::loading().'</span>
				</div>
			';
		}
		if (0 && user::is_admin())
		{
			$depts = array('ql', 'sb', 'gs');
			
			$ml_copy_card = '
				<div id="copy_card" ejo>
					<div><a href="" id="copy_card_link">Copy Card From Another Account</a></div>
					<div id="copy_card_form">
						<table>
							<tbody>
								<tr>
									<td>Department</td>
									<td>'.cgi::html_select('copy_dept', $depts).'</td>
								</tr>
								<tr>
									<td>Account ID</td>
									<td><input type="text" id="copy_ac_id" name="copy_ac_id" value="" /></td>
								</tr>
								<tr>
									<td></td>
									<td><input type="submit" id="copy_get_cards_button" value="Get Cards" /></td>
								</tr>
								<tr class="card_select_row">
									<td>Card</td>
									<td id="copy_cc_select_td"></td>
								</tr>
								<tr class="card_select_row">
									<td></td>
									<td><input type="submit" a0="action_copy_card" value="Copy Card" /></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			';
		}
		?>
		<?php echo $ml_show_full_card; ?>
		<?php echo $ml_copy_card; ?>
		
		<h2>Active Credit Card</h2>
		<?php echo $ml_active_cc; ?>
		
		<h2>Other Credit Cards</h2>
		<?php echo $ml_other_ccs; ?>
		
		<h2>Add New Credit Card</h2>
		<table class="form_table">
			<tbody>
				<?php echo ccs::html_form_new(array(
					'table' => false,
					'ignore' => array('id', 'status', 'foreign_table', 'foreign_id')
				)); ?>
				<tr>
					<td>Make Active</td>
					<td><input type="checkbox" name="make_active" checked /></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" a0="action_cards_add" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}
	
	public function ajax_copy_get_cards()
	{
		list($dept, $ac_id) = util::list_assoc($_POST, 'dept', 'ac_id');
		util::load_lib($dept);
		util::load_lib('sbs');
		
		$account = product::get_account($dept, $ac_id);
		
		$cc_ids = db::select("
			select id
			from eppctwo.ccs
			where foreign_table = 'clients' && foreign_id = '{$account->client_id}'
		");
		
		$ccs = array();
		foreach ($cc_ids as $cc_id)
		{
			$cc = billing::cc_get_display($cc_id);
			$ccs[] = array($cc_id, "{$cc['cc_number']}, {$cc['cc_exp_month']}/{$cc['cc_exp_year']}");
		}
		echo json_encode($ccs);
	}
	
	public function action_copy_card()
	{
		$cc_data = db::select_row("
			select *
			from eppctwo.ccs
			where id = :id
		", array('id' => $_POST['copy_cc_id']), 'ASSOC');
		
		unset($cc_data['id']);
		$cc_data['foreign_id'] = $this->client->id;
		
		$r = db::insert("eppctwo.ccs", $cc_data);
		if ($r)
		{
			feedback::add_success_msg("Card successfully copied");
		}
		else
		{
			feedback::add_error_msg("Error copying card:".db::last_error());
		}
	}
	
	private function user_has_full_card_access()
	{
		return (user::is_admin() || user::has_role('CC Admin', '*', array('leader' => false)));
	}
	
	public function ajax_get_full_cards()
	{
		if ($this->user_has_full_card_access())
		{
			$response = array();
			$cc_ids = ccs::get_all(array(
				'select' => array("ccs" => array("id")),
				'join' => array("cc_x_client" => "ccs.id = cc_x_client.cc_id"),
				'where' => "cc_x_client.client_id = :cid",
				'data' => array('cid' => $this->account->client_id)
			));
			for ($i = 0, $ci = $cc_ids->count(); $i < $ci; ++$i)
			{
				$cc_id = $cc_ids->i($i)->id;
				$cc_actual = billing::cc_get_actual($cc_id);
				$code = $cc_actual['cc_code'];
				$response[$cc_id] = $cc_actual['cc_number'].((is_numeric($code)) ? ', '.$code : '');
			}
			echo json_encode($response);
		}
	}
	
	private function ml_card_full($cc_id, $opts)
	{
		if (!$cc_id)
		{
			return '<p>No card</p>';
		}
		$cc = billing::cc_get_display($cc_id);
		
		$ml_buttons = '';
		if (!$opts['active'])
		{
			$ml_activate = '
				<span class="buttons">
					<input type="submit" class="small_button" a0="action_cards_activate" value="Activate" />
					<input type="submit" class="small_button" a0="action_cards_delete" value="Delete" />
				</span>
				';
		}
		
		list($cols) = ccs::attrs('cols');
		return '
			<div class="cc_table_wrapper" cc_id="'.$cc_id.'" ejo="card">
				<table class="big">
					<tbody>
						<tr>
							<td>Billing Name</td>
							<td>'.$cc['name'].$ml_activate.'</td>
						</tr>
						<tr>
							<td>Type</td>
							<td>'.$cc['cc_type'].'</td>
						</tr>
						<tr>
							<td>Number</td>
							<td class="cc_number">'.$cc['cc_number'].'</td>
						</tr>
						<tr>
							<td>Exp Month</td>
							<td>'.ccs::cc_exp_month_form_input('ccs', $cols['cc_exp_month'], $cc['cc_exp_month'], array('suffix' => $cc_id)).'</td>
						</tr>
						<tr>
							<td>Exp Year</td>
							<td>
								'.ccs::cc_exp_year_form_input('ccs', $cols['cc_exp_year'], $cc['cc_exp_year'], array('suffix' => $cc_id)).'
								&nbsp; <input type="submit" class="small_button" a0="action_cards_update" value="Update Exp and Code" />
							</td>
						</tr>
						<tr>
							<td>CVC</td>
							<td class="cc_code"><input type="text" name="ccs_cc_code_'.$cc_id.'" value="'.$cc['cc_code'].'" /></td>
						</tr>
						<tr>
							<td>Country</td>
							<td>'.$cc['country'].'</td>
						</tr>
						<tr>
							<td>Zip</td>
							<td>'.$cc['zip'].'</td>
						</tr>
					</tbody>
				</table>
			</div>
		';
	}
	
	public function action_cards_update()
	{
		$cc_id = $_POST['cc_id'];
		list($exp_month, $exp_year, $code) = util::list_assoc($_POST, 'ccs_cc_exp_month_'.$cc_id, 'ccs_cc_exp_year_'.$cc_id, 'ccs_cc_code_'.$cc_id);
		
		$cc = new ccs(array('id' => $cc_id));
		$update_data = array(
			'cc_exp_month' => $exp_month,
			'cc_exp_year' => $exp_year
		);
		if (is_numeric($code)) {
			$update_data['cc_code'] = $code;
		}
		$cc->update_from_array($update_data);
		$update_data['id'] = $cc_id;
		$this->hook('cc_update', $update_data);
		
		feedback::add_success_msg('Credit card updated');
	}
	
	public function action_cards_add()
	{
		$cc = ccs::new_from_post();
		
		// make sure we don't have an id set
		unset($cc->id);
		$cc->insert();
		$cc->number_text = $_POST['ccs_cc_number'];
		cc_x_client::create(array(
			'cc_id' => $cc->id,
			'client_id' => $this->account->client_id
		));
		$this->hook('cc_new', $cc);
		
		if ($_POST['make_active'])
		{
			$this->account->update_from_array(array(
				'cc_id' => $cc->id
			));
			$this->hook('cc_activate', $cc->id);
		}
		feedback::add_success_msg('New Credit Card Added');
	}
	
	public function action_cards_activate()
	{
		$cc_id = $_POST['cc_id'];
		$this->account->update_from_array(array('cc_id' => $cc_id));
		$this->hook('cc_activate', $cc_id);
		
		feedback::add_success_msg('Credit Card Activated');
	}
	
	public function action_cards_delete()
	{
		$cc_id = $_POST['cc_id'];
		ccs::delete_all(array('where' => "id = :id", 'data' => array('id' => $cc_id)));
		cc_x_client::delete_all(array('where' => "cc_id = :id", 'data' => array('id' => $cc_id)));
		$this->hook('cc_delete', $cc_id);
		
		feedback::add_success_msg('Credit Card Deleted');
	}
	
	protected function quick_note($note_text)
	{
		account_note::create(array(
			'ac_type' => $this->account->dept,
			'ac_id' => $this->account->id,
			'users_id' => user::$id,
			'dt' => date(util::DATE_TIME),
			'note' => $note_text
		));
	}
	
}

?>