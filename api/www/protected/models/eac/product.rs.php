<?php

class mod_eac_product extends mod_eac_account
{
	public static $db, $cols, $primary_key;

	public static $default_contract_length = 6;
	
	public static $common_billing_keys = array('coupon_codes', 'plan', 'dept', 'is_trial', 'contract_length', 'monthly', 'setup', 'first_month');
	
	const TRIAL_PREAUTH_AMOUNT = 1.00;

	// getter in account
	protected static $depts;
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'              ,'char'  ,16  ,'',rs::READ_ONLY),
			new rs_col('oid'             ,'char'  ,16  ,'',rs::NOT_NULL),
			new rs_col('is_trial'        ,'bool'  ,null,0  ),
			new rs_col('do_report'       ,'bool'  ,null,1 ,rs::NOT_NULL),
			new rs_col('do_send_receipt' ,'bool'  ,null,0 ,rs::NOT_NULL),
			new rs_col('alt_recur_amount','double',null,0 ,rs::UNSIGNED)
		);
	}
	
	public function create_from_post($prod_num)
	{
		$dept = $_POST['dept_'.$prod_num];
		$account = product::make_empty($dept);
		$account->load_from_post($prod_num);
		return $account;
	}
	
	public function make_empty($dept)
	{
		$class = 'ap_'.$dept;
		return new $class();
	}
	
	// form key => payment type
	public static function get_activation_billing_keys()
	{
		return array(
			'setup' => 'Setup',
			'first_month' => 'Management'
		);
	}
	
	public static function calc_order_billing($alldata, $opts = array())
	{
		util::set_opt_defaults($opts, array(
			'num' => false,
			'can_edit' => false
		));
		$data = array();
		foreach (self::$common_billing_keys as $keybase) {
			$key = $keybase.((is_numeric($opts['num'])) ? '_'.$opts['num'] : '');
			$data[$keybase] = $alldata[$key];
		}
		$class = 'ap_'.$data['dept'];
		$class::calc_order_billing_set_dept_data($alldata, $data, $opts['num']);
		
		$budget = db::select_one("
			select budget
			from eppctwo.{$data['dept']}_plans
			where name = '".db::escape($data['plan'])."'
		");
		
		$billing = array(
			'is_trial' => $data['is_trial'],
			'monthly' => $budget,
			'setup' => ($data['is_trial']) ? 0 : $budget,
			'first_month' => ($data['is_trial']) ? 0 : $budget,
			'contract_length' => $data['contract_length']
		);
		
		// apply coupons
		if ($data['coupon_codes']) {
			$coupons = db::select("
				select type, value, value_type, contract_length
				from eppctwo.coupons
				where code in ('".str_replace("\t", "','", $data['coupon_codes'])."')
			");
			for ($i = 0; list($ctype, $cval, $cval_type, $ccon_len) = $coupons[$i]; ++$i) {
				// type
				if ($ctype == 'setup fee') {
					$bill_key = 'setup';
				}
				else if ($ctype == 'first month') {
					$bill_key = 'first_month';
				}
				else {
					// ??
					continue;
				}
				// amount
				if ($cval_type == 'dollars') {
					$billing[$bill_key] = $cval;
				}
				else if ($cval_type == 'percent') {
					$billing[$bill_key] -= $billing[$bill_key] * ($cval / 100);
				}
				else {
					// ?? 
					continue;
				}
				// update contract length
				$billing['contract_length'] = $ccon_len;
			}
		}

		// check if someone with edit access has changed values
		if ($opts['can_edit']) {
			foreach ($billing as $key => $val) {
				if (array_key_exists($key, $data) && is_numeric($data[$key])) {
					$billing[$key] = $data[$key];
				}
			}
		}
		
		// set what they owe today
		$billing['today'] = $billing['first_month'] + $billing['setup'];
		
		// apply dept specific fields
		$class::calc_order_billing_dept($billing, $data);
		
		return $billing;
	}
	
	protected static function calc_order_billing_set_dept_data($alldata, $num){}
	protected static function calc_order_billing_dept(&$billing, &$alldata){}
	
	public function load_from_post($prod_num, $dept_keys = array())
	{
		// can make these static if needed anywhere else
		$common_non_billing_keys = array('url', 'comments');
		$keys = array_merge(self::$common_billing_keys, $common_non_billing_keys, $dept_keys);
		foreach ($keys as $keybase) {
			$post_key = $keybase.((is_numeric($prod_num)) ? '_'.$prod_num : '');
			$this->$keybase = $_POST[$post_key];
		}
		if ($this->dept) {
			$this->set_division();
		}
	}
	
	public function record_activation_payment_parts($payment, $billing_info)
	{
		$keys = call_user_func(array(get_called_class(), 'get_activation_billing_keys'));
		foreach ($keys as $form_key => $payment_type)
		{
			if (
				array_key_exists($form_key, $billing_info) &&
				(
					// we have an amount
					($billing_info[$form_key] != 0)
					||
					// or record a payment of $0.00 for trials
					// just randomly picked setup, could as well have been mgmt
					($payment_type == 'Setup')
				)
			) {
				payment_part::create(array(
					'payment_id' => $payment->id,
					'account_id' => $this->id,
					'division' => $this->division,
					'dept' => $this->dept,
					'event' => 'Activation',
					'type' => $payment_type,
					'is_passthur' => 0,
					'amount' => $billing_info[$form_key]
				));
			}
		}
	}
	
	public static function plan_form_input($table, $col, $val)
	{
		$dept = self::get_dept_from_class();
		$plans = db::select("
			select name n0, name n1
			from eppctwo.{$dept}_plans
			order by n0 asc
		");
		return cgi::html_select($table.'_'.$col->name, $plans, $val);
	}
	
	public static function sales_rep_form_input($table, $col, $val)
	{
		$options = db::select("
			select u.id u0, u.realname u1
			from users u, user_guilds ug
			where
				ug.guild_id = 'sbr' &&
				u.id = ug.user_id
			order by u1 asc
		");
		array_unshift($options, array(0, ' - None - '));
		return cgi::html_select($table.'_'.$col->name, $options, $val);
	}
	
	public static function get_manager_options()
	{
		$options = db::select("
			select u.id u0, u.realname u1
			from eppctwo.users u, eppctwo.sbs_account_rep ar
			where
				u.id = ar.users_id
			order by u1 asc
		");
		return $options;
	}

	public static function manager_form_input($table, $col, $val)
	{
		$options = self::get_manager_options();
		array_unshift($options, array('', ' - None - '));
		return cgi::html_select($table.'_'.$col->name, $options, $val);
	}
	
	// wrapper to get_all for a given department
	public static function get_accounts($dept, $opts = array())
	{
		$class = 'ap_'.$dept;
		return $class::get_all($opts);
	}
	
	// wrapper to get for a department
	// can also pass in an account id for data
	public static function get_account($dept, $data = array(), $opts = array())
	{
		if (!is_array($data))
		{
			$data = array('id' => $data);
		}
		$class = 'ap_'.$dept;
		return new $class($data, $opts);
	}
	
	public static function cgi_get_header_row3_menu()
	{
		return new Menu(
			array(
				new MenuItem('Queues', 'queues'),
				new MenuItem('Calendars', 'calendars'),
				new MenuItem('Process Payments', 'process_payments'),
				new MenuItem('Email Templates', 'email_templates'),
				new MenuItem('Account Reps', 'account_reps'),
				new MenuItem('QL $spend', 'ql/spend'),
			),
			'product'
		);
	}
}
?>
