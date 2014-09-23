<?php

class mod_account_service extends mod_account
{
	public function pre_output()
	{
		parent::pre_output();
		util::load_lib('as', $this->dept);
		if ($this->no_base_class) {
			// move page index up one
			// so methods are called as if base class exists
			$this->page_index++;
		}
	}

	public function get_menu()
	{
		return new Menu(array(
			new MenuItem('Info'        ,'info', array('query_keys' => array('aid'))),
			new MenuItem('Billing'     ,'billing', array('query_keys' => array('aid'))),
			new MenuItem('Contacts'    ,'contacts', array('query_keys' => array('aid'))),
		),
			'/account/service/'.$this->dept.'/',
			array('/service/client_list/'.$this->dept, util::display_text($this->dept))
		);
	}
	
	protected function head()
	{
		$args = func_get_args();
		if (empty($args) || empty($args[0])) {
			$args = g::$pages;
		}
		$page = array_pop($args);
		if ($page == 'index' && count($args)) {
			$page = array_pop($args);
		}
		echo '
			<h1>
				<i>'.$this->account->name.'</i>
				'.((empty($page) || $page == 'index') ? '' : ' :: '.util::display_text($page)).'
				'.((user::is_developer()) ? ' ('.$this->account->client_id.')('.$this->aid.')' : '').'
			</h1>
		';
		if (g::$p2 == 'info') {
			echo '<p><span>Client Accounts: </span>'.as_lib::get_client_department_links($this->account->client_id, array('sub_url' => 'info'))."</p>\n";
			echo as_lib::get_client_survey_links($this->account->client_id);
		}
		if ($this->page_menu) {
			// for page menu, also include the current page
			$page_base = ltrim($this->base_url, '/');
			echo $this->page_menu($this->page_menu, $page_base, 'aid='.$this->aid);
		}
	}

	public function pre_output_billing()
	{
		util::load_lib('billing');
		$this->base_url = $this->base_url.g::$pages[$this->page_index + 1].'/';
		$this->page_menu = array(
			// hist: renamed to void conflict with billing_history js object in ../account.js
			array('hist'          ,'History'),
			array('charge'        ,'Charge'),
			array('record_payment','Record Payment'),
			array('refund'        ,'Refund'),
			array('cards'         ,'Cards'),
			array('invoice'       ,'Invoice')
		);
		$this->part_types = client_payment_part::get_enum_vals('type');
		cgi::add_css('service.billing.css', 'modules/account/service/');
		cgi::add_js('service.billing.js', 'modules/account/service/');
		cgi::add_js_var('billing_url', $this->base_url);
	}

	// once billing is normalized, we can get rid of this and use
	// billing function in account
	public function display_billing()
	{
		$this->call_member('display_billing_'.g::$pages[$this->page_index + 2], 'display_billing_hist');
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

	private function ml_card_full($cc_id, $opts)
	{
		if (!$cc_id) {
			return '<p>No card</p>';
		}
		$cc = billing::cc_get_display($cc_id);
		
		$ml_buttons = '';
		if (!$opts['active']) {
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

	private function user_has_full_card_access()
	{
		return (user::is_admin() || ($this->department == 'ppc' && user::has_role('Leader', 'ppc')));
	}

	public function display_billing_hist()
	{
		$payments = db::select("
			select p.id id, p.amount total, p.date_received, p.date_attributed, p.notes, group_concat(pp.type separator '\t') part_types, group_concat(pp.amount separator '\t') part_amounts
			from eppctwo.client_payment p, eppctwo.client_payment_part pp
			where
				p.client_id = :cid &&
				p.id = pp.client_payment_id
			group by p.id
		", array(
			'cid' => $this->account->client_id
		), 'ASSOC');
		echo '<div id="w_history"></div>';
		cgi::add_js_var('dept', $this->dept);
		cgi::add_js_var('part_types', client_payment_part::$part_types);
		cgi::add_js_var('payments', $payments);
	}

	public function display_billing_charge()
	{
		$ml_top = $this->ml_card_overview($this->account->cc_id);
		$this->print_payment_form('charge', $ml_top);
	}

	public function display_billing_record_payment()
	{
		$ml_top = '
			<tr>
				<td><b>Pay Method</b></td>
				<td>'.cgi::html_select('pay_method', client_payment::get_enum_vals('pay_method'), 'check').'</td>
			</tr>
		';
		$this->print_payment_form('record_payment', $ml_top);
	}

	public function display_billing_refund()
	{
		$ml_top = $this->ml_card_overview($this->account->cc_id);
		$this->print_payment_form('refund', $ml_top);
	}

	public function display_billing_cards()
	{
		$cc_ids = db::select("
			select id
			from eppctwo.ccs
			where foreign_table = 'clients' && foreign_id = :cid
		", array(
			"cid" => $this->account->client_id
		));
		
		$ml_active_cc = $this->ml_card_full($this->account->cc_id, array('active' => true));
		
		$ml_other_ccs = '';
		for ($i = 0, $ci = count($cc_ids); $i < $ci; ++$i) {
			$cc_id = $cc_ids[$i];
			if ($cc_id != $this->account->cc_id) {
				$ml_other_ccs .= $this->ml_card_full($cc_id, array('active' => false));
			}
		}
		if (empty($ml_other_ccs)) {
			$ml_other_ccs = '<p>No Other Credit Cards</p>';
		}
		
		if ($this->user_has_full_card_access()) {
			$ml_show_full_card = '
				<div id="show_full_cards" ejo>
					<span><a href="">Show Full Cards</a></span>
					<span class="loading">'.cgi::loading().'</span>
				</div>
			';
		}
		if (user::is_admin()) {
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
		<?= $ml_show_full_card ?>
		<?= $ml_copy_card ?>
		
		<h2>Active Credit Card</h2>
		<?= $ml_active_cc ?>
		
		<h2>Other Credit Cards</h2>
		<?= $ml_other_ccs ?>
		
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

	public function action_cards_update()
	{
		$cc_id = $_POST['cc_id'];
		list($exp_month, $exp_year, $code) = util::list_assoc($_POST, 'ccs_cc_exp_month_'.$cc_id, 'ccs_cc_exp_year_'.$cc_id, 'ccs_cc_code_'.$cc_id);
		
		ccs::update_all(array(
			'set' => array(
				'cc_exp_month' => $exp_month,
				'cc_exp_year' => $exp_year,
				'cc_code' => $code
			),
			'where' => "id = :id",
			'data' => array("id" => $cc_id)
		));
		
		feedback::add_success_msg('Expriation and CVC updated');
	}
	
	public function action_cards_add()
	{
		$cc = ccs::new_from_post();
		
		// make sure we don't have an id set
		unset($cc->id);
		$cc->foreign_table = 'clients';
		$cc->foreign_id = $this->account->client_id;
		
		$cc->insert();
		
		if ($_POST['make_active']) {
			$this->account->update_from_array(array(
				'cc_id' => $cc->id
			));
		}
		
		feedback::add_success_msg('New Credit Card Added');
	}
	
	public function action_cards_activate()
	{
		$this->account->update_from_array(array(
			'cc_id' => $_POST['cc_id']
		));
		
		feedback::add_success_msg('Credit Card Activated');
	}
	
	public function action_cards_delete()
	{
		ccs::delete_all(array(
			'where' => "id = :ccid",
			'data' => array("ccid" => $_POST['cc_id'])
		));

		feedback::add_success_msg('Credit Card Deleted');
	}

	public function action_copy_card()
	{
		$cc_data = db::select_row("
			select *
			from eppctwo.ccs
			where id = :ccid
		", array(
			"ccid" => $_POST['copy_cc_id']
		), 'ASSOC');
		
		unset($cc_data['id']);
		$cc_data['foreign_table'] = 'clients';
		$cc_data['foreign_id'] = $this->account->client_id;
		
		$r = db::insert("eppctwo.ccs", $cc_data);
		if ($r) {
			feedback::add_success_msg("Card successfully copied");
		}
		else {
			feedback::add_error_msg("Error copying card: ".db::last_error());
		}
	}
	

	public function display_billing_invoice()
	{
		//build contact select for top of payment table
		$table_contact_select = '
			<tr>
				<td><b>Contact</b></td>
				<td>'.$this->ml_client_contact_select($this->account->client_id).'</td>
			</tr>
		';

		?>
		<h2>Invoice PDF</h2>

		<div id="invoice_data">
			<?php $this->print_payment_form('none', $table_contact_select);?>
		</div>

		<div id="invoice_edit">
			<div>
				<label>Client:</label><input type="text" name="pdf_client" value="<?= $this->account->name ?>"><br/>
				<label>Contact Name:</label><input type="text" name="pdf_contact"><br/>
				<label>Inv Date:</label><input type="text" name="pdf_date" class="date_input" value="<?php echo date(util::DATE);?>"><br/>
				<label>Inv Number:</label><input type="text" name="pdf_invoice_num"><br/>
			</div>
			
			<div>
				<label>Address 1:</label><input type="text" name="pdf_address_1"><br/>
				<label>Address 2:</label><input type="text" name="pdf_address_2"><br/>
				<label>Address 3:</label><input type="text" name="pdf_address_3"><br/>
			</div>
			
			<?= $this->print_pdf_wpro_phone() ?>

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

	public function display_billing_receipt()
	{
		$pid = $_GET['pid'];

		//Build up charges for receipt
		$payment_parts = db::select("
			select type, amount
			from eppctwo.client_payment_part
			where client_payment_id = :pid
		", array(
			"pid" => $pid
		), 'ASSOC');

		cgi::add_js_var('receipt_charges', $payment_parts);

		//Get any notes on this payment
		$payment_notes = db::select_one("
			SELECT notes
			FROM eppctwo.client_payment
			WHERE id = :pid
		", array(
			"pid" => $pid
		));

		?>
		<h2>Receipt PDF</h2>

		<div id="receipt_edit">
			<div>
				<label>Contact:</label>
				<?= $this->ml_client_contact_select($this->account->client_id) ?>
			</div>

			<div>
				<label>Client:</label><input type="text" name="pdf_client" value="<?= $this->account->name ?>"><br/>
				<label>Contact Name:</label><input type="text" name="pdf_contact"><br/>
				<label>Receipt Date:</label><input type="text" name="pdf_date" class="date_input" value="<?= date(util::DATE) ?>"><br/>
				<label>Receipt Number:</label><input type="text" name="pdf_invoice_num"><br/>
			</div>
			
			<div>
				<label>Address 1:</label><input type="text" name="pdf_address_1"><br/>
				<label>Address 2:</label><input type="text" name="pdf_address_2"><br/>
				<label>Address 3:</label><input type="text" name="pdf_address_3"><br/>
			</div>
			
			<?= $this->print_pdf_wpro_phone() ?>

			<div id="pdf_charges" ejo>
				<label>Charges</label>
				<table>
					<tr><th>Amount</th><th>Description</th></tr>
				</table>
				<input type="button" id="pdf_add_charge" value="Add Charge">
			</div>

			<div>
				<label>Notes</label><br/>
				<input type="text" name="pdf_notes" value="<?= $payment_notes ?>">
			</div>

			<input type="hidden" name="pdf_type" value="receipt">
			<input type="submit" a0="action_gen_pdf" value="Generate Receipt">
		</div>
		
		<div class="clear"></div>
		<?php
	}

	private function print_pdf_wpro_phone()
	{
		switch ($this->dept) {
			case ('partner'): $default_phone = 'Toll-Free: 800.723.0308 - Fax: 310.356.3874'; break;
			default:          $default_phone = 'Toll-Free: 866.WPROMOTE - Tel: 310.421.4844 - Fax: 310.356.3228'; break;
		}
		?>
		<div>
			<label>Wpromote Phone:</label><input type="text" name="pdf_wpro_phone" value="<?= $default_phone ?>" /><br/>
		</div>
		<?php
	}

	private function ml_client_contact_select($client_id)
	{
		//Get all the contacts
		$client_contacts = contacts::get_all(array(
			'where' => "client_id = :cid",
			'data' => array("cid" => $client_id)
		));

		//Make a select for the contacts
		$contact_select = "<select id='contact_select' ejo=''>\n";
		foreach ($client_contacts as $contact) {
			$contact_select .= "<option value='{$contact->id}'>{$contact->name}</option>\n";
		}
		$contact_select .= "</select>\n";

		//Stick contacts into javascript
		cgi::add_js_var('client_contacts', $client_contacts->to_array());

		return $contact_select;
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

	public function action_edit_payment_submit()
	{
		$payment_id = $_GET['pid'];
		db::delete(
			"eppctwo.client_payment_part",
			"client_payment_id = :client_payment_id",
			array('client_payment_id' => $payment_id)
		);
		$_POST['update_id'] = $payment_id;
		as_lib::record_payment($this->account, $_POST, array(
			'do_charge' => false,
			'pay_method' => $_POST['pay_method'],
			'success_msg' => 'Payment Updated'
		));
	}
	
	public function action_delete_payment_submit()
	{
		db::dbg();
		$payment_id = $_GET['pid'];
		client_payment_part::delete_all(array(
			'where' => "client_payment_id = :pid",
			'data' => array("pid" => $payment_id)
		));
		client_payment::delete_all(array(
			'where' => "id = :pid",
			'data' => array("pid" => $payment_id)
		));
		feedback::add_success_msg('Payment Deleted');
	}
	
	public function action_payment_move_submit()
	{
		db::dbg();
		$payment_id = $_GET['pid'];
		$new_cl_id = $_POST['move_id'];

		if ($payment_id && $new_cl_id) {
			$cl_check = db::select_one("select count(*) from eac.client where id = :cid", array("cid" => $new_cl_id));
			if (empty($cl_check)) {
				feedback::add_error_msg("Could not find client for id <i>$new_cl_id</i>");
			}
			else {
				db::update("eppctwo.client_payment_part", array('client_id' => $new_cl_id), "client_payment_id = :pid", array("pid" => $payment_id));
				db::update("eppctwo.client_payment", array('client_id' => $new_cl_id), "id = :pid", array("pid" => $payment_id));
				feedback::add_success_msg('Payment Moved');
			}
		}
		else {
			feedback::add_error_msg("Empty pid ($payment_id) or new client id ($new_cl_id)");
		}
	}
	
	public function action_record_payment_submit()
	{
		as_lib::record_payment($this->account, $_POST, array(
			'do_charge' => false,
			'pay_method' => $_POST['pay_method'],
			'success_msg' => 'Payment Recorded'
		));
		$this->check_bill_date_update();
	}
	
	public function action_charge_submit()
	{
		$_POST['pay_id'] = $this->account->cc_id;
		as_lib::record_payment($this->account, $_POST, array(
			'success_msg' => 'Payment Successfully Charged'
		));
		$this->check_bill_date_update();
	}
	
	public function action_refund_submit()
	{
		$_POST['pay_id'] = $this->account->cc_id;
		as_lib::record_payment($this->account, $_POST, array(
			'charge_type' => BILLING_CC_REFUND,
			'success_msg' => 'Refund Successfully Processed'
		));
		$this->check_bill_date_update();
	}

	public function check_bill_date_update()
	{
		if ($_POST['do_update_next_bill_date']) {
			$this->account->update_from_array(array(
				'prev_bill_date' => $this->account->next_bill_date,
				'next_bill_date' => $_POST['next_bill_date']
			));
			feedback::add_success_msg('Next Bill Date Updated');
		}
	}

	public function action_payment_receipt_submit()
	{
		header("location:receipt?cl_id=".$_GET['cl_id']."&pid=".$_GET['pid']);
	}

	public function display_billing_edit_payment()
	{
		$payment = new client_payment(array('id' => $_GET['pid']));
		// deleted?
		if (!$payment->is_in_db()) {
			return;
		}
		$payment_parts = db::select("
			select type, amount
			from eppctwo.client_payment_part
			where client_payment_id = :pid
		", array(
			"pid" => $payment->id
		), 'NUM', 0);
		
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

	private function print_payment_form($action, $ml_top = '', $payment = null, $payment_parts = array())
	{
		if (method_exists($this, 'get_default_payment_values')) {
			$default_vals = $this->get_default_payment_values();
		}
		// $default_vals = ($this->default_vals_func) ? call_user_func($this->default_vals_func) : null;
		$ml_parts = '';
		foreach ($this->part_types as $type) {
			if (array_key_exists($type, $payment_parts)) {
				$amount = $payment_parts[$type];
			}
			else if ($action != 'edit_payment' && $default_vals && array_key_exists($type, $default_vals) && $default_vals[$type]) {
				$amount = $default_vals[$type];
			}
			else {
				$amount = '';
			}
			$ml .= '
				<tr>
					<td><b>'.$type.'</b></td>
					<td><input type="text" class="part_amount" name="amount_'.util::simple_text($type).'" value="'.$amount.'" /></td>
				</tr>
			';
		}
		
		if ($action == 'edit_payment') {
			$ml_delete_submit = '<input type="submit" a0="action_delete_payment_submit" value="Delete" />';
			$ml_receipt_submit = '<input type="submit" a0="dummy" a0href="'.$this->base_url.'receipt?'.$_SERVER['QUERY_STRING'].'" value="Receipt" />';
			if (user::is_developer()) {
				$ml_move_submit = '<input type="submit" id="move_payment_button" a0="action_payment_move_submit" value="Move" />';
			}
		}
		
		if ($payment) {
			$date_received = $payment->date_received;
			$date_attributed = $payment->date_attributed;
			$notes = $payment->notes;
		}
		else {
			$date_received = date(util::DATE);
			$date_attributed = $date_received;
			$notes = '';
		}
		
		$ml_extra = '';
		// don't show next bill date for ppc, they have a rollover page specifically for that
		if ($action != 'edit_payment' && $this->account->next_bill_date && $this->account->dept != 'ppc') {
			$ml_extra = '
				<tr>
					<td><b>Next Bill Date</b></td>
					<td>
						<input type="checkbox" id="do_update_next_bill_date" name="do_update_next_bill_date" value="1" checked />
						<input type="text" name="next_bill_date" value="'.util::delta_month($this->account->next_bill_date, 1, $this->account->bill_day).'" class="date_input" />
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
		if ($action == 'edit_payment' || $action == 'record_payment') {
			$ml_disclaimer = '
				<tr>
					<td colspan="2">
						* '.util::display_text($action).':
						This action will only update the local<br />
						database, no money will be transferred to/from the client.
					</td>
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
					<td><input type="text" class="date_input" name="date_attributed" value="<?= $date_attributed ?>" /></td>
				</tr>
				<tr>
					<td><b>Notes</b></td>
					<td><input type="text" class="notes" name="notes" value="<?= $notes ?>" /></td>
				</tr>
				<tr>
					<td><b>Total</b></td>
					<td id="total"></td>
				</tr>
				<?= $ml_extra ?>
				<tr>
					<td></td>
					<td>
						<input type="submit" a0="action_<?= $action ?>_submit" value="<?= util::display_text($action) ?> Submit" />
						<?= $ml_delete_submit ?>
						<?= $ml_receipt_submit ?>
						<?= $ml_move_submit ?>
					</td>
				</tr>
				<?= $ml_disclaimer ?>
			</tbody>
		</table>
		<?php
	}

	public function ajax_get_full_cards()
	{
		if ($this->user_has_full_card_access()) {
			$response = array();
			$cc_ids = db::select("
				select id
				from eppctwo.ccs
				where foreign_table = 'clients' && foreign_id = :cid
			", array(
				"cid" => $this->account->client_id
			));
			for ($i = 0, $ci = count($cc_ids); $i < $ci; ++$i) {
				$cc_id = $cc_ids[$i];
				$cc_actual = billing::cc_get_actual($cc_id);
				$code = $cc_actual['cc_code'];
				$response[$cc_id] = $cc_actual['cc_number'].((is_numeric($code)) ? ', '.$code : '');
			}
			echo json_encode($response);
		}
	}

	public function ajax_copy_get_cards()
	{
		list($dept, $ac_id) = util::list_assoc($_POST, 'dept', 'ac_id');
		util::load_lib($dept, 'sbs');
		
		$account = product::get_account($dept, $ac_id, array('select' => "account.client_id"));
		$tmpccs = ccs::get_client_ccs($account->client_id);
		
		$ccs = array();
		foreach ($tmpccs as $tmpcc) {
			$cc = billing::cc_get_display($tmpcc->id);
			$ccs[] = array($tmpcc->id, "{$cc['cc_number']}, {$cc['cc_exp_month']}/{$cc['cc_exp_year']}");
		}
		echo json_encode($ccs);
	}

	public function pre_output_info()
	{
		$this->secondary_managers = $this->register_widget('secondary_managers', array('account_id' => $this->aid, 'dept' => $this->dept));
		$this->info_ignore_cols = array(
			'plan', 'signup_dt', 'prepay_roll_date', 'de_activation_date', 'prepay_paid_months', 'prepay_free_months',
			'is_billing_failure', 'contract_length', 'partner', 'source', 'subid',
			'sales_rep'
		);
		if (user::is_admin() || user::has_role('Leader', 'sales')) {
			util::load_rs('sales');
			$this->sales_info = new sales_client_info(array('account_id' => $this->aid));
		}
	}
	
	public function display_info()
	{
		// account form
		$ml_account = $this->account->html_form(array(
			'ignore' => $this->info_ignore_cols,
			'action' => 'account_info_submit'
		));
		// sales info
		$ml_sales = '';
		if (user::is_admin() || user::has_role('Leader', 'sales')) {
			$ml_sales = '
				<div class="left">
					<h2>Sales</h2>
					'.$this->sales_info->html_form().'
				</div>
			';
		}
		$ml_admin_links = '';
		if (user::is_admin()) {
			$ml_admin_links = '
				<p>
					<a href="'.$this->href('move_account?aid='.$this->aid).'">Move Account</a> |
					<a href="'.$this->href('add_service?aid='.$this->aid).'">Add Service</a>
				</p>
			';
		}
		?>
		<p><span>Client Accounts: </span><?= as_lib::get_client_department_links($this->account->client_id, array('sub_url' => 'info')) ?></p>
		<?= as_lib::get_client_survey_links($this->account->client_id) ?>
		<?= $ml_admin_links ?>
		<div class="left">
			<h2>Account Info</h2>
			<?= $ml_account ?>
		</div>
		<?= $ml_sales ?>
		<?= $this->secondary_managers->output() ?>
		<div class="clr"></div>
		<?php
	}

	public function pre_output_add_service()
	{
		$this->current_services = account::get_all(array(
			'select' => "dept",
			'where' => "client_id = :cid",
			'data' => array("cid" => $this->account->client_id)
		));
		$this->dept_options = array_diff(account::$org['service'], $this->current_services->dept);
	}

	public function display_add_service()
	{
		$depts_val = ($_POST['depts']) ? $_POST['depts'] : $this->dept;
		?>
		<table>
			<tbody>
				<tr>
					<td><label>Current Services:</label></td>
					<td><?= implode(', ', $this->current_services->dept) ?></td>
				</tr>
				<tr>
					<td><label>Services to add:</label></td>
					<td><?= cgi::html_checkboxes('depts', $this->dept_options, $depts_val, array('separator' => ' &nbsp; ', 'toggle_all' => false)) ?></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" a0="action_add_service" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	public function display_add_service_success()
	{
		$ml_new_depts = '';
		foreach ($this->new_accounts as $account) {
			$ml_new_depts .= '
				<p>
					<a href="'.cgi::href('account/service/'.$account->dept.'/info?aid='.$account->id).'">'.$account->dept.'</a>
				<p>
			';
		}
		echo $ml_new_depts;
	}

	public function action_add_service()
	{
		$depts = cgi::get_posted_checkbox_value($_POST['depts'], $this->dept_options);

		if (empty($depts)) {
			return feedback::add_error_msg('Please select at least one service');
		}
		$this->new_accounts = array();
		foreach ($depts as $dept) {
			util::load_lib($dept);
			$class = "as_{$dept}";
			$account = $class::create(array(
				'client_id' => $this->account->client_id,
				'name' => $this->account->name,
				'division' => 'service',
				'dept' => $dept,
				'status' => $this->account->staus,
				'signup_dt' => date(util::DATE_TIME)
			));
			$this->new_accounts[] = $account;
		}
		feedback::add_success_msg("New services added");
		$this->switch_display('add_service_success');
	}

	public function action_sales_client_info_submit()
	{
		if (!$this->sales_info) {
			return;
		}
		$updated = $this->sales_info->put_from_post();
		if ($updated) {
			feedback::add_success_msg('Sales info updated: '.implode(', ', array_map(array('util', 'display_text'), $updated)));
		}
		else {
			feedback::add_error_msg('No sales fields changed');
		}
	}

	public function action_move_account()
	{
		$dst_client = $_POST['dst_client'];
		// set all client objects
		$this->move_account_set_client_objects(true);

		// set local vars for what we're moving, eg $selected_credit_cards
		if ($this->is_multiple_accounts) {
			$cbox_keys = array('accounts', 'credit_cards', 'contacts', 'surveys', 'saps');
			foreach ($cbox_keys as $key) {
				$var_key = "selected_{$key}";
				$$var_key = cgi::get_posted_checkbox_value($_POST['move_'.$key]);
			}
		}

		// accounts
		// track the departments for the accounts we are moving
		// so we can figure out which payments to move
		$move_depts = array();
		$move_ac_ids = array();
		foreach ($this->client_accounts as $acnt) {
			if (!$this->is_multiple_accounts || $selected_accounts == '*' || in_array($acnt->value, $selected_accounts)) {
				$move_depts[] = $acnt->dept;
				$move_ac_ids[] = $acnt->id;
				$acnt->update_from_array(array(
					'client_id' => $dst_client
				));
			}
		}
		// update any tables that have both account id and client id
		db::update(
			"eppctwo.sales_client_info",
			array('client_id' => $dst_client),
			"account_id in (:ac_ids)",
			array('ac_ids' => $move_ac_ids)
		);

		// move payment parts that belong to the account for this department only
		// user does not select payments, they are moved automatically
		$payments = client_payment::get_all(array(
			'select' => array(
				"client_payment" => array("id as pid", "user_id", "pay_id", "pay_method", "fid", "date_received", "date_attributed", "amount as pamount", "notes", "sales_notes"),
				"cpps" => array("id as ppid", "client_payment_id", "type", "amount as ppamount", "rep_pay_num", "rep_comm")
			),
			'join_many' => array("client_payment_part as cpps" => "client_payment.id = cpps.client_payment_id"),
			'where' => "client_payment.client_id = :cid",
			'data' => array("cid" => $this->account->client_id),
			'de_alias' => true
		));
		foreach ($payments as $payment) {
			$parts_to_move = array();
			$amount_to_move = 0;
			// see which payment parts we are moving
			foreach ($payment->cpps as $cpp) {
				$dept = client_payment_part::$part_types[$cpp->type];
				if (!$this->is_multiple_accounts || $selected_accounts == '*' || in_array($dept, $move_depts)) {
					$parts_to_move[] = $cpp;
					$amount_to_move += $cpp->amount;
				}
			}
			$move_count = count($parts_to_move);
			if ($move_count > 0) {
				// all payment parts moved, just update client id
				if ($move_count == $payment->cpps->count()) {
					$payment->update_from_array(array(
						'client_id' => $dst_client
					));
					$payment->cpps->update_from_array(array(
						'client_id' => $dst_client
					));
				}
				// only some payment parts moved
				// update client id and client_payment_id on payment parts
				else {
					// update amount on original payment, subtract what we are moving
					$payment->update_from_array(array(
						'amount' => $payment->amount - $amount_to_move
					));
					// create new payment with amount that is being moved and new client id
					unset($payment->id);
					$payment->client_id = $dst_client;
					$payment->amount = $amount_to_move;
					$payment->insert();

					// update payment parts with new client id and pointing to new payment
					foreach ($parts_to_move as $cpp) {
						$cpp->update_from_array(array(
							'client_payment_id' => $payment->id,
							'client_id' => $dst_client
						));
					}
				}
			}
		}

		// credit cards
		foreach ($this->ccs as $cc) {
			$id = $cc[0];
			if (!$this->is_multiple_accounts || ($selected_credit_cards == '*' || in_array($id, $selected_credit_cards))) {
				db::update(
					"eppctwo.ccs",
					array("foreign_id" => $dst_client),
					"id = :id",
					array("id" => $id)
				);
			}
		}

		// contacts
		foreach ($this->contacts as $contact) {
			$id = $contact->value;
			if (!$this->is_multiple_accounts || ($selected_contacts == '*' || in_array($id, $selected_contacts))) {
				db::update(
					"eppctwo.contacts",
					array("client_id" => $dst_client),
					"id = :id",
					array("id" => $id)
				);
			}
		}

		// surveys
		foreach ($this->surveys as $survey) {
			$id = $survey[0];
			if (!$this->is_multiple_accounts || ($selected_surveys == '*' || in_array($id, $selected_surveys))) {
				db::update(
					"surveys.client_surveys",
					array("client_id" => $dst_client),
					"id = :id",
					array("id" => $id)
				);
			}
		}

		// saps
		foreach ($this->saps as $sap) {
			$id = $sap[0];
			if (!$this->is_multiple_accounts || ($selected_saps == '*' || in_array($id, $selected_saps))) {
				list($id, $db) = explode('-', $id);
				db::update(
					"{$db}.prospects",
					array("client_id" => $dst_client),
					"id = :id",
					array("id" => $id)
				);
			}
		}

		feedback::add_success_msg('Account(s) moved');
	}

	public function pre_output_move_account()
	{
		util::load_lib('billing');
		$this->client_select = $this->register_widget('client_select', array(
			'form_key' => 'dst_client',
			'ignore_cl_ids' => array($this->account->client_id)
		));
	}

	public function display_move_account()
	{
		if ($this->action == 'action_move_account') {
			$this->print_moved_accounts_success_page();
			return;
		}
		$this->print_move_destination();
		$this->print_move_things_tied_to_client_id();
		?>
		<input type="submit" id="move_account_submit" a0="action_move_account" value="Submit" />
		<input type="hidden" id="is_multiple_accounts" name="is_multiple_accounts" value="<?= $this->is_multiple_accounts ?>" />
		<?php
	}

	private function print_moved_accounts_success_page()
	{
		?>
		<a href="<?= $this->href('info?aid='.$this->aid) ?>">Back to Info Page</a>
		<?php
	}

	private function print_move_destination()
	{
		?>
		<div>
			<h2>Select client to move account to</h2>
			<?= $this->client_select->output() ?>
		</div>
		<?php
	}

	private function print_move_accounts()
	{
		// multiple accounts, let user select which accounts they want to move
		if ($this->is_multiple_accounts) {
			echo '
				<div>
					<h2>This account belongs to a client with other accounts. Please select all accounts you would like to move</h2>
					'.cgi::html_checkboxes('move_accounts', $this->client_accounts->to_array(), $this->aid, array('toggle_all' => false)).'
				</div>
			';
		}
		// only 1 account, add as hidden field
		else {
			echo '<input type="hidden" name="move_accounts" id="move_accounts" value="'.$this->aid.'" />';
		}
		?>
		<?php
	}

	private function print_move_things_tied_to_client_id()
	{
		$this->move_account_set_client_objects();

		$this->print_move_accounts();

		// only 1 account, don't need to show options, we're moving everything
		if (!$this->is_multiple_accounts) {
			return;
		}

		$this->print_move_options('Credit Cards', $this->ccs);
		$this->print_move_options('Contacts', $this->contacts->to_array());
		$this->print_move_options('Surveys', $this->surveys);
		$this->print_move_options('SAPs', $this->saps);
	}

	private function move_account_set_client_objects($do_set_all = false)
	{
		$this->client_accounts = account::get_all(array(
			'select' => array("account" => array("id as value", "concat(name, ' - ', dept) as text", "id", "dept")),
			'where' => "client_id = :cid",
			'data' => array("cid" => $this->account->client_id),
			'order_by' => "text asc"
		));
		// only 1 account, nothing to do
		$this->is_multiple_accounts = ($this->client_accounts->count() > 1);
		if (!$this->is_multiple_accounts && !$do_set_all) {
			return;
		}

		// credit cards
		$ccids = db::select("
			select id
			from eppctwo.ccs
			where foreign_table = 'clients' && foreign_id = :cid
		", array(
			"cid" => $this->account->client_id
		));
		$this->ccs = array();
		foreach ($ccids as $ccid) {
			$cc = billing::cc_get_display($ccid);
			$this->ccs[] = array($ccid, $cc['cc_number'].', '.$cc['cc_exp_month'].'/'.$cc['cc_exp_year'].', '.$cc['name']);
		}

		// contacts
		$this->contacts = contacts::get_all(array(
			'select' => array("contacts" => array("id as value", "concat(name, if(email = '', '', concat(', ', email)), if(phone = '', '', concat(', ', phone))) as text")),
			'where' => "client_id = :cid",
			'data' => array("cid" => $this->account->client_id)
		));

		// surveys
		$this->surveys = db::select("
			select id, urlkey
			from surveys.client_surveys
			where client_id = :cid
		", array(
			"cid" => $this->account->client_id
		));

		// saps
		$this->saps = db::select("
			(select concat(id, '-contracts') as id_and_db, url_key
			from contracts.prospects
			where client_id = :cid)
			union all
			(select concat(id, '-eppctwo') as id_and_db, url_key
			from eppctwo.prospects
			where client_id = :cid)
			order by url_key asc
		", array(
			"cid" => $this->account->client_id
		));
	}

	public function print_move_options($type, $options)
	{
		if (count($options) == 0) {
			return;
		}
		?>
		<h3><?= $type ?></h3>
		<?= cgi::html_checkboxes(util::simple_text('move_'.$type), $options, false, array('box_first' => true)) ?>
		<?php
	}

	public function action_account_info_submit()
	{
		$updates = $this->account->put_from_post(array('update' => true));
		if ($updates === false) {
			return feedback::add_error_msg(db::last_error());
		}
		if ($updates) {
			feedback::add_success_msg('Info Updated: '.implode(', ', array_map(array('util', 'display_text'), $updates)));
			return $updates;
		}
		else {
			return feedback::add_error_msg('Nothing Updated');
		}
	}

	public function pre_output_contacts()
	{
		$this->contacts_widget = $this->register_widget('contacts', array('cid' => $this->account->client_id));
	}

	public function display_contacts()
	{
		$this->contacts_widget->output();
	}
	
}

?>