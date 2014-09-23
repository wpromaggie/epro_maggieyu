<?php

class wid_client_billing extends widget_base
{
	public $client, $department, $menu, $page_offset;
	
	function __construct($_client = null)
	{
		util::load_lib('as', 'billing');
		
		$this->department = g::$p1;
		$this->page_offset = array_search('billing', g::$pages) + 1;
		
		if ($_client)
		{
			$this->client = $_client;
		}
		else if (array_key_exists('cl_id', $_REQUEST))
		{
			if ($this->department == 'partner')
			{
				$this->client = new clients_partner(array('id' => $_REQUEST['cl_id']));
			}
			else
			{
				$this->client = new clients(array('id' => $_REQUEST['cl_id']));
			}
		}
		
		$this->menu = array(
			array('history'       ,'History'),
			array('charge'        ,'Charge'),
			array('record_payment','Record Payment'),
			array('refund'        ,'Refund'),
			array('cards'         ,'Cards'),
			array('invoice'       ,'Invoice')
		);
		
		if ($this->department == 'ppc')
		{
			$this->menu[] = array('rollover', 'Rollover');
		}
		
		// get part types for payment pages
		if (in_array(g::$pages[$this->page_offset], array('charge', 'record_payment', 'refund', 'edit_payment', 'invoice')))
		{
			$this->part_types = client_payment_part::get_enum_vals('type');
		}
	}
	
	public function call_member($func, $default = '', $arguments = NULL)
	{
		if (method_exists($this, $func))
		{
			$this->$func($arguments);
			return true;
		}
		else if (!empty($default))
		{
			$this->$default($arguments);
			return true;
		}
		return false;
	}
	
	public function page_menu($menu, $base_url = null)
	{
		$base_url = implode('/', array_slice(g::$pages, 0, $this->page_offset)).'/';
		$default_page = $menu[0][0];
		$ml = '';
		for ($i = 0; list($key, $text) = $menu[$i]; ++$i)
		{
			$ml_class = ((empty(g::$pages[$this->page_offset]) && $key == $default_page) || implode('/', array_slice(g::$pages, $this->page_offset)) == $key) ? ' class="on"' : '';
			$ml .= '<a href="'.cgi::href($base_url.$key).'?cl_id='.$this->client->id.'"'.$ml_class.'>'.$text.'</a>';
		}
		return '
			<div id="page_menu">
				'.$ml.'
			</div>
		';
	}
	
	private function print_head()
	{
		echo '
			<h1>
				<i>'.$this->client->name.'</i>
				:: '.implode(' :: ', array_map(array('util', 'display_text'), array_slice(g::$pages, 1))).'
				'.((user::is_developer()) ? ' ('.$this->client->id.')('.$this->client->data_id.')' : '').'
			</h1>
			'.$this->page_menu($this->menu).'
		';
	}
	
	public function output()
	{
		$this->print_head();
		$this->call_member('display_'.g::$pages[$this->page_offset], 'display_history');
	}
	
	public function display_history()
	{
		$payments = db::select("
			select p.id id, p.amount total, p.date_received, p.date_attributed, p.notes, group_concat(pp.type separator '\t') part_types, group_concat(pp.amount separator '\t') part_amounts
			from eppctwo.client_payment p, eppctwo.client_payment_part pp
			where p.client_id = '{$this->client->id}' && p.id = pp.client_payment_id
			group by p.id
		", 'ASSOC');
		echo '<div id="history_wrapper" ejo></div>';
		cgi::add_js_var('dept', $this->department);
		cgi::add_js_var('part_types', client_payment_part::$part_types);
		cgi::add_js_var('payments', $payments);
	}
	
	public function display_edit_payment()
	{
		$payment = new client_payment(array('id' => $_GET['pid']));
		// deleted?
		if (!$payment->is_in_db())
		{
			return;
		}
		$payment_parts = db::select("
			select type, amount
			from eppctwo.client_payment_part
			where client_payment_id = '".db::escape($payment->id)."'
		", 'NUM', 0);
		
		$ml_top = '';
		if (user::is_admin()) {
			$uname = db::select_one("select realname from eppctwo.users where id = '{$payment->user_id}'");
			$ml_top .= '
				<tr>
					<td><b>Submitted By</b></td>
					<td>'.$uname.'</td>
				</tr>
				<tr>
					<td><b>3rd Party ID</b></td>
					<td>'.$payment->fid.'</td>
				</tr>
			';
		}
		$ml_top .= '
			<tr>
				<td><b>Pay Method</b></td>
				<td>'.cgi::html_select('pay_method', client_payment::get_enum_vals('pay_method'), $payment->pay_method).'</td>
			</tr>
		';
		$this->print_payment_form('edit_payment', $ml_top, $payment, $payment_parts);
	}
	
	public function display_charge()
	{
		$ml_top = $this->ml_card_overview($this->client->cc_id);
		$this->print_payment_form('charge', $ml_top);
	}
	
	public function display_refund()
	{
		$ml_top = $this->ml_card_overview($this->client->cc_id);
		$this->print_payment_form('refund', $ml_top);
	}
	
	public function ml_card_overview($cc_id)
	{
		$cc = billing::cc_get_display($cc_id);
		return '
			<tr>
				<td><b>Card Type</b></td>
				<td>'.$cc['cc_type'].'</td>
			</tr>
			<tr>
				<td><b>Card Exp</b></td>
				<td>'.$cc['cc_exp_month'].'/'.$cc['cc_exp_year'].'</td>
			</tr>
			<tr>
				<td><b>Card Num</b></td>
				<td>'.$cc['cc_number'].'</td>
			</tr>
		';
	}
	
	public function display_record_payment()
	{
		$ml_top = '
			<tr>
				<td><b>Pay Method</b></td>
				<td>'.cgi::html_select('pay_method', client_payment::get_enum_vals('pay_method'), 'check').'</td>
			</tr>
		';
		$this->print_payment_form('record_payment', $ml_top);
	}
	
	public function register_default_payment_values_function($func)
	{
		$this->default_vals_func = $func;
	}
	
	private function print_payment_form($action, $ml_top = '', $payment = null, $payment_parts = array())
	{
		$default_vals = ($this->default_vals_func) ? call_user_func($this->default_vals_func) : null;
		$ml_parts = '';
		foreach ($this->part_types as $type)
		{
			if (array_key_exists($type, $payment_parts))
			{
				$amount = $payment_parts[$type];
			}
			else if ($action != 'edit_payment' && $default_vals && array_key_exists($type, $default_vals) && $default_vals[$type])
			{
				$amount = $default_vals[$type];
			}
			else
			{
				$amount = '';
			}
			$ml .= '
				<tr>
					<td><b>'.$type.'</b></td>
					<td><input type="text" class="part_amount" name="amount_'.util::simple_text($type).'" value="'.$amount.'" /></td>
				</tr>
			';
		}
		
		if ($action == 'edit_payment')
		{
			$ml_delete_submit = '<input type="submit" a0="action_widget_delete_payment_submit" value="Delete" />';
			$ml_receipt_submit = '<input type="submit" a0="action_widget_payment_receipt_submit" value="Receipt" />';
			if (user::is_developer()) {
				$ml_move_submit = '<input type="submit" id="move_payment_button" a0="action_widget_payment_move_submit" value="Move" />';
			}
		}
		
		if ($payment)
		{
			$date_received = $payment->date_received;
			$date_attributed = $payment->date_attributed;
			$notes = $payment->notes;
		}
		else
		{
			$date_received = date(util::DATE);
			$date_attributed = $date_received;
			$notes = '';
		}
		
		$ml_extra = '';
		if ($action != 'edit_payment' && $this->client->next_bill_date)
		{
			$ml_extra = '
				<tr>
					<td><b>Next Bill Date</b></td>
					<td>
						<input type="checkbox" id="do_update_next_bill_date" name="do_update_next_bill_date" value="1" checked />
						<input type="text" name="next_bill_date" value="'.util::delta_month($this->client->next_bill_date, 1, $this->client->bill_day).'" class="date_input" />
					</td>
				</tr>
			';
		}
		$ml_date_received = '';
		if (user::is_admin()) {
			$ml_date_received = '
				<tr>
					<td><b>Date Received</b></td>
					<td><input type="text" class="date_input" name="date_received" value="'.$date_received.'" /></td>
				</tr>
			';
		}
		
		?>
		<table id="payment_table" ejo>
			<tbody>
				<?= $ml_top ?>
				<?= $ml ?>
				<?= $ml_date_received ?>
				<tr>
					<td><b>Date Attributed</b></td>
					<td><input type="text" class="date_input" name="date_attributed" value="<?php echo $date_attributed; ?>" /></td>
				</tr>
				<tr>
					<td><b>Notes</b></td>
					<td><input type="text" class="notes" name="notes" value="<?php echo $notes; ?>" /></td>
				</tr>
				<tr>
					<td><b>Total</b></td>
					<td id="total"></td>
				</tr>
				<?php echo $ml_extra; ?>
				<tr>
					<td></td>
					<td>
						<input type="submit" a0="action_widget_<?php echo $action; ?>_submit" value="Submit" />
						<?php echo $ml_delete_submit; ?>
						<?php echo $ml_receipt_submit; ?>
						<?php echo $ml_move_submit; ?>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}
	
	public function action_widget_edit_payment_submit()
	{
		$payment_id = $_GET['pid'];
		// delete previous payment parts
		/*
		db::exec("
			delete from eppctwo.client_payment_part
			where client_payment_id = '".db::escape($payment_id)."'
		");
		*/
		db::delete("eppctwo.client_payment_part",
					"client_payment_id = :client_payment_id",
					array('client_payment_id'=>db::escape($payment_id)));
		$_POST['update_id'] = $payment_id;
		as_lib::record_payment($this->client, $_POST, array(
			'do_charge' => false,
			'pay_method' => $_POST['pay_method'],
			'success_msg' => 'Payment Updated'
		));
	}
	
	public function action_widget_delete_payment_submit()
	{
		$payment_id = $_GET['pid'];
		// delete previous payment parts
		db::exec("
			delete from eppctwo.client_payment_part
			where client_payment_id = '".db::escape($payment_id)."'
		");
		db::exec("
			delete from eppctwo.client_payment
			where id = '".db::escape($payment_id)."'
		");
		feedback::add_success_msg('Payment Deleted');
	}
	
	public function action_widget_payment_move_submit()
	{
		$payment_id = $_GET['pid'];
		$new_cl_id = $_POST['move_id'];

		if ($payment_id && $new_cl_id) {
			$cl_check = db::select_one("select count(*) from clients where id = '".db::escape($new_cl_id)."'");
			if (empty($cl_check)) {
				feedback::add_error_msg("Could not find client for id <i>$new_cl_id</i>");
			}
			else {
				db::update("eppctwo.client_payment_part", array('client_id' => $new_cl_id), "client_payment_id = '".db::escape($payment_id)."'");
				db::update("eppctwo.client_payment", array('client_id' => $new_cl_id), "id = '".db::escape($payment_id)."'");
				feedback::add_success_msg('Payment Moved');
			}
		}
		else {
			feedback::add_error_msg("Empty pid ($payment_id) or new client id ($new_cl_id)");
		}
	}
	
	public function action_widget_record_payment_submit()
	{
		as_lib::record_payment($this->client, $_POST, array(
			'do_charge' => false,
			'pay_method' => $_POST['pay_method'],
			'success_msg' => 'Payment Recorded'
		));
		$this->check_bill_date_update();
	}
	
	public function action_widget_charge_submit()
	{
		$_POST['pay_id'] = $this->client->cc_id;
		as_lib::record_payment($this->client, $_POST, array(
			'success_msg' => 'Payment Successfully Charged'
		));
		$this->check_bill_date_update();
	}
	
	public function action_widget_refund_submit()
	{
		$_POST['pay_id'] = $this->client->cc_id;
		as_lib::record_payment($this->client, $_POST, array(
			'charge_type' => BILLING_CC_REFUND,
			'success_msg' => 'Refund Successfully Processed'
		));
		$this->check_bill_date_update();
	}

	public function action_widget_payment_receipt_submit()
	{
		header("location:receipt?cl_id=".$_GET['cl_id']."&pid=".$_GET['pid']);
	}
	
	public function check_bill_date_update()
	{
		if ($_POST['do_update_next_bill_date'])
		{
			$this->client->last_bill_date = $this->client->next_bill_date;
			$this->client->next_bill_date = $_POST['next_bill_date'];
			
			$this->client->put(array('cols' => array('last_bill_date', 'next_bill_date')));
			feedback::add_success_msg('Next Bill Date Updated');
		}
	}
	
	public function display_cards()
	{
		$cc_ids = db::select("
			select id
			from eppctwo.ccs
			where foreign_table = 'clients' && foreign_id = '{$this->client->id}'
		");
		
		$ml_active_cc = $this->ml_card_full($this->client->cc_id, array('active' => true));
		
		$ml_other_ccs = '';
		for ($i = 0, $ci = count($cc_ids); $i < $ci; ++$i)
		{
			$cc_id = $cc_ids[$i];
			if ($cc_id != $this->client->cc_id)
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
		if (user::is_admin())
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
		util::load_lib($dept, 'sbs', 'account');
		
		$account = product::get_account($dept, $ac_id, array('select' => "account.client_id"));
		$tmpccs = ccs::get_client_ccs($account->client_id);
		
		$ccs = array();
		foreach ($tmpccs as $tmpcc)
		{
			$cc = billing::cc_get_display($tmpcc->id);
			$ccs[] = array($tmpcc->id, "{$cc['cc_number']}, {$cc['cc_exp_month']}/{$cc['cc_exp_year']}");
		}
		echo json_encode($ccs);
	}
	
	public function action_copy_card()
	{
		$cc_data = db::select_row("
			select *
			from eppctwo.ccs
			where id = '".db::escape($_POST['copy_cc_id'])."'
		", 'ASSOC');
		
		unset($cc_data['id']);
		$cc_data['foreign_table'] = 'clients';
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
		return (user::is_admin() || ($this->department == 'ppc' && user::has_role('Leader', 'ppc')));
	}
	
	public function ajax_get_full_cards()
	{
		if ($this->user_has_full_card_access())
		{
			$response = array();
			$cc_ids = db::select("
				select id
				from eppctwo.ccs
				where foreign_table = 'clients' && foreign_id = '{$this->client->id}'
			");
			for ($i = 0, $ci = count($cc_ids); $i < $ci; ++$i)
			{
				$cc_id = $cc_ids[$i];
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
		
		$cc = new ccs(array(
			'id' => $cc_id,
			'cc_exp_month' => $exp_month,
			'cc_exp_year' => $exp_year,
			'cc_code' => $code
		));
		$cc->put();
		
		feedback::add_success_msg('Expriation and CVC updated');
	}
	
	public function action_cards_add()
	{
		$cc = ccs::new_from_post();
		
		// make sure we don't have an id set
		unset($cc->id);
		$cc->foreign_table = 'clients';
		$cc->foreign_id = $this->client->id;
		
		$cc->put();
		
		if ($_POST['make_active'])
		{
			$this->client->cc_id = $cc->id;
			db::exec("
				update eppctwo.clients
				set cc_id = {$cc->id}
				where id = '{$this->client->id}'
			");
		}
		
		feedback::add_success_msg('New Credit Card Added');
	}
	
	public function action_cards_activate()
	{
		$cc_id = $_POST['cc_id'];
		$this->client->cc_id = $cc_id;
		db::exec("
			update eppctwo.clients
			set cc_id = $cc_id
			where id = '{$this->client->id}'
		");
		
		feedback::add_success_msg('Credit Card Activated');
	}
	
	public function action_cards_delete()
	{
		$cc = new ccs(array('id' => $_POST['cc_id']));
		$cc->delete();
		
		feedback::add_success_msg('Credit Card Deleted');
	}
	
	public function display_rollover()
	{
		$ppc_client = new clients_ppc(array('client' => $this->client->id));
		$next_bill_date = util::delta_month($ppc_client->next_bill_date, 1, $ppc_client->bill_day);
		?>
		<table>
			<tbody>
				<tr>
					<td><b>Current Month</b></td>
					<td><?php echo date(util::US_DATE, strtotime($ppc_client->prev_bill_date)).' - '.date(util::US_DATE, strtotime($ppc_client->next_bill_date)); ?></td>
				</tr>
				<tr>
					<td><b>Who Pays Clicks</b></td>
					<td><?php echo $ppc_client->who_pays_clicks; ?></td>
				</tr>
				<tr>
					<td><b>Current Budget</b></td>
					<td><?php echo util::format_dollars($ppc_client->budget); ?></td>
				</tr>
				<tr>
					<td><b>Current Carryover</b></td>
					<td><?php echo util::format_dollars($ppc_client->carryover); ?></td>
				</tr>
				<tr>
					<td><b>Current Adjustment</b></td>
					<td><?php echo util::format_dollars($ppc_client->adjustment); ?></td>
				</tr>
				<tr>
					<td><b>Spend</b></td>
					<td><?php echo util::format_dollars($ppc_client->mo_spend); ?></td>
				</tr>
				<tr>
					<td><b>Budget</b></td>
					<td><input type="text" name="rollover_budget" value="<?php echo round($ppc_client->budget, 2); ?>" /></td>
				</tr>
				<tr>
					<td><b>Carryover</b></td>
					<td><input type="text" name="rollover_carryover" value="<?php echo round($ppc_client->actual_budget - $ppc_client->mo_spend, 2); ?>" /></td>
				</tr>
				<tr>
					<td><b>Adjustment</b></td>
					<td><input type="text" name="rollover_adjustment" value="<?php echo round($ppc_client->adjustment, 2); ?>" /></td>
				</tr>
				<tr>
					<td><b>Next Bill Date</b></td>
					<td><input type="text" name="rollover_next_bill_date" value="<?php echo $next_bill_date; ?>" /></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" a0="action_rollover" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}
	
	public function action_rollover()
	{
		$ppc_client = new clients_ppc(array('client' => $this->client->id));
		$rollover = new ppc_rollover(array(
			'client_id' => $this->client->id,
			'user_id' => user::$id,
			'd' => date(util::DATE),
			'budget' => $_POST['rollover_budget'],
			'carryover' => $_POST['rollover_carryover'],
			'adjustment' => $_POST['rollover_adjustment'],
			'next_bill_date' => $_POST['rollover_next_bill_date'],
		));
		$rollover->put();
		
		$ppc_client->budget = $_POST['rollover_budget'];
		$ppc_client->carryover = $_POST['rollover_carryover'];
		$ppc_client->adjustment = $_POST['rollover_adjustment'];
		$ppc_client->prev_bill_date = $ppc_client->next_bill_date;
		$ppc_client->next_bill_date = $_POST['rollover_next_bill_date'];
		$ppc_client->calc_actual_budget();
		$ppc_client->put();
		
		ppc_lib::calculate_cdl_vals($this->client->id, $ppc_client->prev_bill_date, $ppc_client->next_bill_date);
		
		feedback::add_success_msg('Rollover Completed');
	}

	private function ml_client_contact_select($client_id)
	{
		//Get all the contacts
		$client_contacts = contacts::get_all(array('where'=>"client_id='$client_id'"));

		//Figure out who the billing contact is
		$billing_contact_id = db::select_one("
			SELECT billing_contact_id
			FROM eppctwo.clients_ppc
			WHERE client='$client_id'
		");

		//Make a select for the contacts
		$contact_select = "<select id='contact_select' ejo=''>\n";
		foreach ($client_contacts as $contact) {
			$selected = ($contact->id == $billing_contact_id)?' selected="selected"':'';
			$contact_select .= "<option value='{$contact->id}' $selected>{$contact->name}</option>\n";
		}
		$contact_select .= "</select>\n";

		//Stick contacts into javascript
		cgi::add_js_var('client_contacts', $client_contacts->a);

		return $contact_select;
	}

	public function display_invoice()
	{
		//Get client and contact info
		$client_name = $this->client->name;
		$client_id = $this->client->id;

		//build contact select for top of payment table
		$table_contact_select = '
		<tr>
			<td><b>Contact</b></td>
			<td>'.$this->ml_client_contact_select($client_id).'</td>
		</tr>
		';

		?>
		<h2>Invoice PDF</h2>

		<div id="invoice_data">
			<?php $this->print_payment_form('none', $table_contact_select);?>
		</div>

		<div id="invoice_edit">
			<div>
				<label>Client:</label><input type="text" name="pdf_client" value="<?php echo $client_name;?>"><br/>
				<label>Contact Name:</label><input type="text" name="pdf_contact"><br/>
				<label>Inv Date:</label><input type="text" name="pdf_date" class="date_input" value="<?php echo date(util::DATE);?>"><br/>
				<label>Inv Number:</label><input type="text" name="pdf_invoice_num"><br/>
			</div>
			
			<div>
				<label>Address 1:</label><input type="text" name="pdf_address_1"><br/>
				<label>Address 2:</label><input type="text" name="pdf_address_2"><br/>
				<label>Address 3:</label><input type="text" name="pdf_address_3"><br/>
			</div>
			
			<?php echo $this->ml_pdf_wpro_phone(); ?>

			<div id="pdf_charges" ejo>
				<label>Charges</label>
				<table>
					<tr><th>Amount</th><th>Description</th></tr>
				</table>
				<input type="button" id="pdf_add_charge" value="Add Charge">
			</div>

			<div>
				<label>Notes</label><br/>
				<input type="text" name="pdf_notes">
			</div>

			<input type="hidden" name="pdf_type" value="invoice">
			<input type="submit" a0="action_gen_pdf" value="Generate Invoice">
		</div>
		
		<div class="clear"></div>
		<?php
	}

	public function display_receipt()
	{
		//Get client info and contact info
		$client_name = $this->client->name;
		$client_id = $this->client->id;

		//Build up charges for receipt
		$payment_parts = db::select("
			select type, amount
			from eppctwo.client_payment_part
			where client_payment_id = '".db::escape($_GET['pid'])."'
		", 'ASSOC');

		cgi::add_js_var('receipt_charges', $payment_parts);

		//Get any notes on this payment
		$payment_notes = db::select_one("
			SELECT notes
			FROM eppctwo.client_payment
			WHERE id = '".db::escape($_GET['pid'])."'
		");

		?>
		<h2>Receipt PDF</h2>

		<div id="receipt_edit">
			<div>
				<label>Contact:</label>
				<?php echo $this->ml_client_contact_select($client_id);?>
			</div>

			<div>
				<label>Client:</label><input type="text" name="pdf_client" value="<?php echo $client_name;?>"><br/>
				<label>Contact Name:</label><input type="text" name="pdf_contact"><br/>
				<label>Receipt Date:</label><input type="text" name="pdf_date" class="date_input" value="<?php echo date(util::DATE);?>"><br/>
				<label>Receipt Number:</label><input type="text" name="pdf_invoice_num"><br/>
			</div>

			<div>
				<label>Address 1:</label><input type="text" name="pdf_address_1"><br/>
				<label>Address 2:</label><input type="text" name="pdf_address_2"><br/>
				<label>Address 3:</label><input type="text" name="pdf_address_3"><br/>
			</div>
			
			<?php echo $this->ml_pdf_wpro_phone(); ?>
			
			<div id="pdf_charges" ejo>
				<label>Charges</label>
				<table>
					<tr><th>Amount</th><th>Description</th></tr>
				</table>
				<input type="button" id="pdf_add_charge" value="Add Charge">
			</div>

			<div>
				<label>Notes</label><br/>
				<input type="text" name="pdf_notes" value="<?php echo $payment_notes;?>">
			</div>
		</div>

		<input type="hidden" name="pdf_type" value="receipt">
		<input type="submit" a0="action_gen_pdf" value="Generate PDF">

		<div class="clear"></div>
		<?php
	}
	
	private function ml_pdf_wpro_phone()
	{
		switch ($this->department)
		{
			case ('partner'): $default_phone = 'Toll-Free: 800.723.0308 - Fax: 310.356.3874'; break;
			default      : $default_phone = 'Toll-Free: 866.WPROMOTE - Tel: 310.421.4844 - Fax: 310.356.3228'; break;
		}
		?>
		<div>
			<label>Wpromote Phone:</label><input type="text" name="pdf_wpro_phone" value="<?php echo $default_phone; ?>" /><br/>
		</div>
		<?php
	}

	public function action_gen_pdf()
	{
		util::load_lib('pdf');

		if ($_POST['pdf_type'] == 'invoice') {
			$pdf = new InvoicePDF();
		} else {
			$pdf = new ReceiptPDF();
		}

		$pdf->client = $_POST['pdf_client'];
		$pdf->contact = $_POST['pdf_contact'];
		$pdf->date = $_POST['pdf_date'];
		$pdf->invoice_num = $_POST['pdf_invoice_num'];

		$pdf->address_1 = $_POST['pdf_address_1'];
		$pdf->address_2 = $_POST['pdf_address_2'];
		$pdf->address_3 = $_POST['pdf_address_3'];
		
		$pdf->wpro_phone = $_POST['pdf_wpro_phone'];

		$charge_amounts = $_POST['pdf_chg_amt'];
		$charge_descriptions = $_POST['pdf_chg_desc'];
		$pdf->charges = array();
		for ($i=0; $i<sizeof($charge_amounts); $i++) {
			$pdf->charges[$charge_descriptions[$i]] = preg_replace("/[^\d\.]/", '', $charge_amounts[$i]);
		}

		$pdf->notes = $_POST['pdf_notes'];

		$pdf->MakeThingsHappen();
		$pdf->Output();
		exit;
	}
	
}

?>