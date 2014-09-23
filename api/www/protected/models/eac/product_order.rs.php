<?php
/*
 * when an order is submitted, create credit card so we can attempt to charge
 * if charge is successful, create everything else
 */
class mod_eac_product_order extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	
	/*
	 * - the data db field stores everything we get
	 * - a lot of it is account info we don't need as
	 *   they are passed along to the accounts so they can
	 *   create themselves
	 * - the fields here are mostly billing fields
	 */
	
	// the data in flat, associative array format
	public $data, $billing_info, $client;
	
	// billing info
	private $cc_name, $cc_type, $cc_number_text, $cc_exp_month, $cc_exp_year, $cc_country, $cc_zip;
	
	// some flags
	private $is_return_client, $is_update;
	
	// error handling
	private $is_error, $error;
	
	// ids and objects we set along the way
	private $contact, $cc_id, $billing_fid;
	
	// other stuff we set along the way
	// now is utime
	private $is_trial_only, $today, $now, $bill_day;
	
	// data to send to wpro so everything can be mirrored over there
	private $wpro_data;
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('email'),
			array('ts')
		);
		self::$cols = self::init_cols(
			new rs_col('id'             ,'char'    ,16  ,''     ,rs::READ_ONLY),
			new rs_col('sales_rep'      ,'int'     ,11  ,0      ,rs::UNSIGNED ),
			new rs_col('email'          ,'char'    ,64  ,''     ,rs::READ_ONLY),
			new rs_col('ts'             ,'datetime',null,rs::DDT,rs::READ_ONLY),
			new rs_col('is_success'     ,'bool'    ,null,0      ,rs::READ_ONLY),
			new rs_col('data'           ,'text'    ,null,''     ,rs::READ_ONLY)
		);
	}
	
	// returns IDs of the form AADDDD-DDDD where A is a letter A-Z and D is a digit 0-9
	protected function uprimary_key($i)
	{
		$rletters = mt_rand(0, 676);
		$letters = chr(65 + ($rletters % 26)).chr(65 + floor($rletters / 26));
		$rnumbers = str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
		return $letters.substr($rnumbers, 0, 4).'-'.substr($rnumbers, 4);
	}
	
	public function set_error($e)
	{
		$this->is_error = true;
		$this->error = $e;
	}
	
	public function get_error()
	{
		return $this->error;
	}
	
	public function is_error()
	{
		return $this->is_error;
	}
	
	public static function create(&$data, &$billing_info)
	{
		// re-submitting an old order
		if ($data['oid'])
		{
			$order = self::get_from_existing($data);
			if ($order->is_success)
			{
				$order->set_error('Order '.$order->id.' was already successfully processed');
			}
		}
		else
		{
			$order_data = array(
				'email' => $_POST['email'],
				'sales_rep' => user::$id,
				'ts' => date(util::DATE_TIME),
				'data' => serialize($data)
			);
			$order = parent::create($order_data);
			$order->is_update = false;
			// todo: check for (very) recent payments to make sure isn't a double submit?
		}
		if (!$order->is_error()) {
			$order->init($data, $billing_info);
		}
		return $order;
	}
	
	public static function get_from_existing(&$data)
	{
		$order = new product_order(array('id' => $this->data['oid']));
		$order->update_from_array(array('data' => serialize($data)));
		$order->is_update = true;
		return $order;
	}
	
	// should this be put in a constructor?
	public function init(&$data, &$billing_info)
	{
		$this->data = $data;
		$this->billing_info = $billing_info;
		
		$this->init_fields();
		$this->init_is_return_client();
		$this->init_cc();
	}
	
	private function init_fields()
	{
		foreach ($this as $k => $v)
		{
			if (array_key_exists($k, $this->data))
			{
				$this->$k = $this->data[$k];
			}
		}
	}
	
	private function init_is_return_client()
	{
		$contacts = contacts::get_all(array(
			'where' => "email = '".db::escape($this->data['email'])."'"
		));
		if ($contacts->count() > 0)
		{
			$this->is_return_client = true;
			$this->contact = $contacts->current();
			$this->client = new client(array('id' => $this->contact->client_id));
		}
		else
		{
			$this->is_return_client = false;
		}
	}

	private function init_cc()
	{
		if ($this->is_return_client && $this->client->id)
		{
			$ccs = ccs::get_all(array(
				'select' => array("ccs" => array("id", "cc_number")),
				'join' => array("cc_x_client" => "ccs.id = cc_x_client.cc_id"),
				'where' => "cc_x_client.client_id = '".db::escape($this->client->id)."'"
			));
			foreach ($ccs as $cc)
			{
				$tmp_cc_actual = util::decrypt($cc->cc_number);
				
				if ($tmp_cc_actual == $this->cc_number_text)
				{
					$this->cc_id = $cc->id;
					break;
				}
			}
		}
		$this->cc_db_data = array(
			'name' => $this->cc_name,
			'country' => $this->cc_country,
			'zip' => $this->cc_zip,
			'cc_number' => util::encrypt($this->cc_number_text),
			'cc_type' => $this->cc_type,
			'cc_exp_month' => $this->cc_exp_month,
			'cc_exp_year' => $this->cc_exp_year
		);
		if ($this->is_return_client && $this->cc_id)
		{
			db::update("eppctwo.ccs", $this->cc_db_data, "id = '{$this->cc_id}'");
		}
		else
		{
			$this->cc_id = db::insert("eppctwo.ccs", $this->cc_db_data);
		}
		$this->cc_db_data['id'] = $this->cc_id;
	}
	
	public function process()
	{
		if (!$this->is_error() && $this->process_billing()) {
			$this->create_accounts_and_record_payment_parts();
			$this->send_signup_emails();
			$this->set_wpro_data();
			return true;
		}
		else {
			return false;
		}
	}
	
	private function send_signup_emails()
	{
		if (isset($_POST['do_not_send_email']) && $_POST['do_not_send_email']) {
			return;
		}
		util::load_lib('sbs');
		
		if ($this->is_trial_only) {
			$first_month_msg = 'a '.util::format_dollars(product::TRIAL_PREAUTH_AMOUNT).' pre-authorization';
		}
		else {
			$first_month_msg = util::format_dollars($this->billing_info['totals']['today']);
		}
		$email_accounts = account::new_array();
		foreach ($this->accounts as $account) {
			if ($account->plan != 'Pro') {
				$email_accounts->push($account);
			}
		}
		if ($email_accounts->count() > 0) {
			$signup_email_result = sbs_lib::send_email($email_accounts, 'Signup', array(
				'[first_month_charge]' => $first_month_msg
			));
		}
	}
	
	private function set_wpro_data()
	{
		// client
		$client = array(
			'id' => $this->client->id,
			'email' => $this->contact->email,
			'pass_set_key' => $this->contact->authentication,
			'name' => $this->contact->name,
			'phone' => $this->contact->phone
		);
		
		// cc
		$cc_data = $this->cc_db_data;
		unset($cc_data['cc_number']);
		$cc_data['cc_first_four'] = substr($this->cc_number_text, 0, 4);
		$cc_data['cc_last_four'] = substr($this->cc_number_text, strlen($this->cc_number_text) - 4);
		
		$payment_info = array(
			'total' => $this->billing_info['totals']['today']
		);
		
		// don't send accounts ql pro accounts to wpro
		$wpro_accounts = account::new_array();
		foreach ($this->accounts as $account) {
			if (!($account->dept == 'ql' && $account->plan == 'Pro')) {
				$wpro_accounts->push($account);
			}
		}

		if ($wpro_accounts->count() > 0) {
			$this->wpro_data = array(
				'client' => json_encode($client),
				'cc' => json_encode($cc_data),
				'accounts' => $wpro_accounts->json_encode(),
				'payment' => json_encode($payment_info)
			);	
		}
	}
	
	public function get_wpro_data()
	{
		return $this->wpro_data;
	}
	
	/*
	 * also takes care of creating client, contact and payment if successful
	 */
	public function process_billing()
	{
		$amount_today = $this->billing_info['totals']['today'];

		// see if all products are trials
		$all_trials = true;
		foreach ($this->billing_info['prods'] as $prod_num => $prod_billing) {
			if (!$prod_billing['is_trial']) {
				$all_trials = false;
				break;
			}
		}
		if ($all_trials) {
			$this->is_trial_only = true;
			$this->processor_amount = product::TRIAL_PREAUTH_AMOUNT;
			$this->processor_charge_type = BILLING_CC_PREAUTH;
		}
		else {
			$this->is_trial_only = false;
			$this->processor_amount = $amount_today;
			$this->processor_charge_type = BILLING_CC_CHARGE;
		}
		if (
			// admins can put through an order without charging the card
			(isset($_POST['do_not_charge']) && $_POST['do_not_charge']) ||
			billing::charge($this->cc_id, $this->processor_amount, $this->processor_charge_type)
		) {
			// success! create client, payment
			$this->update_from_array(array('is_success' => 1), array('cols' => array('is_success')));
			$this->now = $_SERVER['REQUEST_TIME'];
			$this->today = date(util::DATE, $this->now);
			
			if (!$this->is_return_client) {
				$this->create_client();
				$this->create_contact();
			}
			$this->record_payment($amount_today);
			return true;
		}
		else {
			$this->set_error('Billing Error: '.billing::get_error());
			return false;
		}
	}
	
	private function create_client()
	{
		$this->client = client::create(array(
			'name' => $this->data['name']
		));
	}
	
	private function create_contact()
	{
		$pass_set_key = sbs_lib::generate_pass_set_key();
		$contact_db_data = array(
			'client_id' => $this->client->id,
			'name' => $this->data['name'],
			'email' => $this->data['email'],
			'password' => '',
			'phone' => $this->data['phone'],
			'zip' => $this->cc_zip,
			'country' => $this->cc_country,
			'status' => 'inactive',
			'authentication' => $pass_set_key
		);
		$this->contact = contacts::create($contact_db_data);
	}
	
	private function record_payment($amount_today)
	{
		$notes = ($this->is_trial_only) ? 'Pre-Auth' : '';
		$this->payment = payment::create(array(
			'client_id' => $this->client->id,
			'user_id' => user::$id,
			'pay_id' => $this->cc_id,
			'pay_method' => 'CC',
			'fid' => billing::$order_id,
			'ts' => date(util::DATE_TIME, $this->now),
			'date_received' => $this->today,
			'date_attributed' => $this->today,
			'event' => 'Activation',
			'amount' => $amount_today,
			'notes' => $notes
		));
		// tie client and cc
		// could be duplicate if return client, just fail silently
		cc_x_client::create(array(
			'cc_id' => $this->cc_id,
			'client_id' => $this->client->id
		));
	}
	
	public function create_accounts_and_record_payment_parts()
	{
		// set some things that are the same for all accounts
		$manager = $this->client->get_or_assign_product_manager();
		
		$bill_day = date('j');
		$next_bill_date = util::delta_month($this->today, 1, $bill_day);
		
		// let's loop over billing info since we need it anyway
		// it's already broken down by prod num
		$this->accounts = account::new_array();
		foreach ($this->billing_info['prods'] as $prod_num => $prod_billing) {
			// this should set all dept specific fields
			$account = product::create_from_post($prod_num);
			
			// set common account stuff
			$account->client_id = $this->client->id;
			$account->cc_id = $this->cc_id;
			$account->oid = $this->id;
			$account->do_report = ($account->dept == 'ql' && $account->plan == 'Pro') ? 0 : 1;
			$account->name = $account->url;
			$account->status = 'New';
			$account->manager = $manager;
			$account->sales_rep = $_POST['sales_rep'];
			$account->signup_dt = $this->payment->ts;
			$account->bill_day = $bill_day;
			$account->prev_bill_date = $this->today;
			$account->next_bill_date = $next_bill_date;
			$account->partner = $this->data['partner'];
			$account->source = $this->data['source'];
			$account->subid = $this->data['subid'];
			if ($prod_billing['monthly'] != sbs_lib::get_monthly_amount($account->dept, $account->plan)) {
				$account->alt_recur_amount = $prod_billing['monthly'];
			}

			if ($account->dept == 'sb') {
				$account->has_soci = 1;
				if ($account->plan == 'Premier') {
					$account->has_ads = 1;
				}
			}
			
			$account->insert();

			$account->record_activation_payment_parts($this->payment, $prod_billing);
			
			// if its a socialboost express account, create express entry
			if ($account->dept == 'sb' && $account->plan == 'Express') {
				$sb_exp_contact = $this->create_sb_express_contact($account);
				$account->socialboost_express_client = $sb_exp_contact;
			}
			if ($account->dept == 'ql' && $account->plan == 'Pro') {
				$this->create_ql_pro_agency_account($account, $_POST['company_name_'.$prod_num], $_POST['budget_'.$prod_num]);
			}
			
			$this->accounts->push($account);
		}
	}
	
	private function create_sb_express_contact($account)
	{
		util::load_rs('sb');
		return sb_exp_contacts::create(array(
		    'account_id' => $account->id,
		    'url' => $account->url,
		    'email' => $this->contact->email,
		    'phone' => $this->contact->phone
		));
	}

	private function create_ql_pro_agency_account($account, $company_name, $budget)
	{
		util::load_lib('as', 'ppc');

		$client = clients::create(array(
			'company' => 1,
			'name' => ($company_name) ? $company_name : $this->contact->name,
			'status' => 'On'
		));
		util::set_client_external_id($client->id);
		db::insert("eppctwo.clients_ppc", array(
			'company' => 1,
			'client' => $client->id,
			'url' => $account->url,
			'budget' => $budget,
			'who_pays_clicks' => 'Client'
		));

		// join ql pro to ppc
		ql_pro_x_ppc::create(array(
			'ql_account_id' => $account->id,
			'ppc_client_id' => $client->id
		));
	}
}

?>
