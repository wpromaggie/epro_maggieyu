<?php
/*
 * division and dept aren't really enums..
 * https://www.google.com/search?q=gen-spec+relational+modeling
 */
class account extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static $status_options = array('New','Incomplete','Active','Paused','Cancelled','Declined','OnHold','NonRenewing','BillingFailure');
	
	// division -> dept
	public static $org = array(
		'service' => array(
			'ppc',
			'seo',
			'smo',
			'partner',
			'email',
			'webdev'
		),
		'product' => array(
			'ql',
			'sb',
			'gs'
		)
	);
	
	// lazy load
	public static $dept_to_division;
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                ,'char'    ,16  ,''     ,rs::READ_ONLY),
			new rs_col('division'          ,'enum'    ,16  ,''     ,rs::READ_ONLY),
			new rs_col('dept'              ,'enum'    ,16  ,''     ,rs::READ_ONLY),
			new rs_col('client_id'         ,'char'    ,16  ,''     ,rs::READ_ONLY),
			new rs_col('data_id'           ,'char'    ,8   ,'-1'   ,rs::READ_ONLY),
			new rs_col('cc_id'             ,'bigint'  ,20  ,null   ,rs::READ_ONLY | rs::UNSIGNED),
			new rs_col('name'              ,'char'    ,128 ,''     ),
			new rs_col('status'            ,'enum'    ,24  ,'New'  ),
			new rs_col('url'               ,'char'    ,255 ,''     ),
			new rs_col('plan'              ,'char'    ,32  ,''     ),
			new rs_col('manager'           ,'int'     ,11  ,0      ,rs::UNSIGNED),
			new rs_col('sales_rep'         ,'int'     ,11  ,0      ,rs::UNSIGNED),
			new rs_col('bill_day'          ,'tinyint' ,3   ,0      ,rs::UNSIGNED),
			new rs_col('signup_dt'         ,'datetime',null,rs::DDT),
			new rs_col('prev_bill_date'    ,'date'    ,null,rs::DD ),
			new rs_col('next_bill_date'    ,'date'    ,null,rs::DD ),
			new rs_col('prepay_roll_date'  ,'date'    ,null,rs::DD ),
			new rs_col('cancel_date'       ,'date'    ,null,rs::DD ),
			new rs_col('de_activation_date','date'    ,null,rs::DD ),
			new rs_col('prepay_paid_months','tinyint' ,3   ,0      ,rs::UNSIGNED),
			new rs_col('prepay_free_months','tinyint' ,3   ,0      ,rs::UNSIGNED),
			new rs_col('contract_length'   ,'tinyint' ,3   ,0      ,rs::UNSIGNED),
			new rs_col('is_billing_failure','bool'    ,null,0      ),
			new rs_col('partner'           ,'char'    ,64          ),
			new rs_col('source'            ,'char'    ,64          ),
			new rs_col('subid'             ,'char'    ,64          )
		);
	}
	
	// a and 9 random digits
	// [100 mil to 1 bil)
	protected function uprimary_key($i)
	{
		return 'A'.mt_rand(100000000, 999999999);
	}
	
	// k$: do we actually need to set $class::$depts?
	// confusing. must be called via service
	public static function get_depts()
	{
		$class = get_called_class();
		if (!$class::$depts) {
			$class::$depts = account::$org[$class];
		}
		return $class::$depts;
	}
	
	private static function init_dept_to_division_map()
	{
		if (empty(self::$dept_to_division)) {
			self::$dept_to_division = array();
			foreach (self::$org as $division => &$division_depts) {
				foreach ($division_depts as $dept) {
					self::$dept_to_division[$dept] = $division;
				}
			}
		}
		ksort(self::$dept_to_division);
	}
	
	public static function is_service($dept)
	{
		return (self::dept_to_division($dept) === 'service');
	}

	public static function get_all_depts()
	{
		self::init_dept_to_division_map();
		return array_keys(self::$dept_to_division);
	}

	public static function is_dept($x)
	{
		self::init_dept_to_division_map();
		return ($x && array_key_exists($x, self::$dept_to_division));
	}

	// default to service
	public static function dept_to_division($dept)
	{
		self::init_dept_to_division_map();
		return ($dept && array_key_exists($dept, self::$dept_to_division) ? self::$dept_to_division[$dept] : false);
	}
	
	public static function get_dept_to_division_map()
	{
		self::init_dept_to_division_map();
		return self::$dept_to_division;
	}
	
	public static function get_division_depts($div)
	{
		self::init_dept_to_division_map();
	}
	
	public function set_division()
	{
		if (!isset($this->dept)) {
			$this->dept = self::get_dept_from_class();
		}
		$this->division = self::dept_to_division($this->dept);
	}
	
	public static function get_dept_from_class()
	{
		$class = get_called_class();
		$pos = strpos($class, '_');
		return ($pos !== false) ? substr($class, $pos + 1) : false;
	}

	public static function get_dept_from_url()
	{
		$depts = self::get_depts();
		for ($i = 0, $ci = count(g::$pages); $i < $ci; ++$i) {
			$page = g::$pages[$i];
			if (in_array($page, $depts)) {
				return $page;
			}
		}
		return false;
	}

	public function get_href($path = false)
	{
		if (!isset($this->division)) {
			$this->set_division();
		}
		$href = "/account/{$this->division}/{$this->dept}";
		if ($path) {
			$href .= "/{$path}";
		}
		$href .= "?aid={$this->id}";
		return $href;
	}
}

class service extends account
{
	public static $db, $cols, $primary_key;

	// getter in account
	protected static $depts;

	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                   ,'char'    ,16  ,''    ,rs::READ_ONLY)
		);
	}

	public static function get_manager_options()
	{
		$dept = self::get_dept_from_class();
		return db::select("
			select u.id, u.realname
			from users u, user_guilds ug
			where
				ug.guild_id = :dept &&
				u.id = ug.user_id &&
				u.password <> ''
			order by realname asc
		", array(
			"dept" => $dept
		));
	}

	public static function manager_form_input($table, $col, $val)
	{
		$options = self::get_manager_options();
		array_unshift($options, array('', ' - None - '));
		return cgi::html_select($table.'_'.$col->name, $options, $val);
	}

	public static function sales_rep_form_input($table, $col, $val)
	{
		$dept = self::get_dept_from_class();
		$options = db::select("
			select u.id, u.realname
			from users u, user_guilds ug
			where
				ug.guild_id = 'sales' &&
				u.id = ug.user_id &&
				u.password <> ''
			order by realname asc
		");
		array_unshift($options, array('', ' - None - '));
		return cgi::html_select($table.'_'.$col->name, $options, $val);
	}

	public static function display_text($service)
	{
		switch (strtolower($service)) {
			case ('ppc'):
			case ('seo'):
			case ('smo'):
				return strtoupper($service);
			case ('partner'):
			case ('email'):
			case ('webdev'):
			default:
				return ucwords($service);
		}
	}
}

class product extends account
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

class ap_ql extends product
{
	public static $db, $cols, $primary_key;
	
	public static $plan_options = array('Basic','Bo1','Bo2','Bo3','Bo4','Core','Core_297','Gold','GoldQL','LAL','LAL1','LALgold','LALplatinum','LALsilver','Plat','PlatQL','Plus','Premier','SMOplat','Starter','Starter_149');
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'              ,'char'    ,16  ,''    ,rs::READ_ONLY),
			new rs_col('alt_num_keywords','smallint',5   ,0     ,rs::UNSIGNED),
			new rs_col('is_3_day_done'   ,'bool'    ,null,0     ),
			new rs_col('is_7_day_done'   ,'bool'    ,null,0     )
		);
	}
	
	public function load_from_post($prod_num)
	{
		parent::load_from_post($prod_num, array('who_creates', 'title', 'desc1', 'desc2'));
		for ($i = 0; ($keybase = 'kw'.$i) && ($key = $keybase.'_'.$prod_num) && array_key_exists($key, $_POST); ++$i)
		{
			$this->$keybase = $_POST[$key];
		}
	}

	public function get_data_sources()
	{
		return ql_data_source::get_all(array(
			'select' => "market, account, campaign, ad_group",
			'where' => "account_id = :aid",
			'key_col' => "market",
			'data' => array("aid" => $this->id)
		));
	}

	public static $plan_budgets = false;

	public static function get_plan_budget($plan)
	{
		if (empty(self::$plan_budgets)) {
			self::$plan_budgets = db::select("
				select name, budget
				from eppctwo.ql_plans
			", 'NUM', 0);
		}
		return (array_key_exists($plan, self::$plan_budgets)) ? self::$plan_budgets[$plan] : false;
	}

	public function update_data($data_sources = false, $eac_to_mac = false)
	{
		// set data sources if not passed in
		if (!$data_sources) {
			$tmp = db::select("
				select ds.market, ds.account_id, ds.account, ds.campaign, ds.ad_group
				from eac.ap_ql q, eac.account a, eppctwo.ql_data_source ds
				where
					a.id = :aid &&
					q.id = a.id &&
					q.id = ds.account_id
			", array('aid' => $this->id));
			$eac_to_mac = array();
			$data_sources = array();
			for ($i = 0; list($market, $cl_id, $ac_id, $ca_id, $ag_id) = $tmp[$i]; ++$i) {
				if (!isset($eac_to_mac[$market][$cl_id])) $eac_to_mac[$market][$cl_id] = $ac_id;

				if (!empty($ag_id)) $data_sources[$market][$cl_id]['ad_group_id'][] = $ag_id;
				else if (!empty($ca_id)) $data_sources[$market][$cl_id]['campaign_id'][] = $ca_id;
				else $data_sources[$market][$cl_id]['account_id'][] = $ac_id;
			}
			// also clear data for this account
			db::delete("eppctwo.ql_spend", "account_id = :aid", array('aid' => $this->id));
		}

		if ($this->alt_recur_amount) {
			$budget = $this->alt_recur_amount;
			$pay_option_months = $this->prepay_paid_months;
			if ($pay_option_months > 1) {
				$budget /= $pay_option_months;
			}
		}
		else {
			$budget = self::get_plan_budget($this->plan);
		}
		if (!is_numeric($budget) || $budget < 0) {
			$msg = "Error getting account budget: $this->id, $this->url, $this->plan";
			if (class_exists('feedback')) {
				feedback::add_error_msg($msg);
			}
			else {
				echo "$msg\n";
			}
			return false;
		}
		
		if (!is_numeric($this->bill_day) || $this->bill_day < 1 || $this->bill_day > 31) {
			$msg = "Error updating data, bad bill day: $this->id, $this->url, $this->bill_day";
			if (class_exists('feedback')) {
				feedback::add_error_msg($msg);
			}
			else {
				echo "$msg\n";
			}
			return false;
		}
		
		// must have last bill date set
		if (!preg_match("/^\d\d\d\d-\d\d-\d\d$/", $this->prev_bill_date)) {
			$msg = "Error updating data, previous bill date: $this->id, $this->url, $this->prev_bill_date";
			if (class_exists('feedback')) {
				feedback::add_error_msg($msg);
			}
			else {
				echo "$msg\n";
			}
			return false;
		}
		// even for multi-month plans, we just want to look at one month at a time
		$today = date(util::DATE);
		for (
			$month_start = $this->prev_bill_date, $month_end = util::delta_month($month_start, 1, $this->bill_day);
			$month_end < $today;
			$month_start = $month_end, $month_end = util::delta_month($month_start, 1, $this->bill_day)
		);
		$month_end = date(util::DATE, strtotime("$month_end -1 day"));
		
		// if next bill date is over 1 month away
		$days_to_date = $days_remaining = $days_in_month = 0;
		for ($date = $month_start; $date <= $month_end; $date = date(util::DATE, strtotime("$date +1 day"))) {
			if ($date < $today) {
				$days_to_date++;
			}
			else {
				$days_remaining++;
			}
		}
		$days_in_month = $days_to_date + $days_remaining;
		
		$prev_month_start = util::delta_month($month_start, -1, $this->bill_day);
		$prev_month_end = date(util::DATE, strtotime("$month_start -1 day"));

		$cols = array('imps', 'clicks', 'cost');
		$q_select = array();
		foreach ($cols as $col) {
			$q_select[] = "sum($col) as $col";
		}
		$ac_data = array_combine($cols, array_fill(0, count($cols), 0));
		$yesterday_data = array_combine($cols, array_fill(0, count($cols), 0));
		$spend_prev_month = 0;
		foreach (ql_lib::$markets as $market) {
			foreach ($cols as $col) {
				$yesterday_data["{$market}_{$col}"] = 0;
			}
			if (isset($data_sources[$market][$this->id])) {
				$market_ac_id = $eac_to_mac[$market][$this->id];

				// build query of ids that belong to this account
				$q_id_where = array();
				foreach ($data_sources[$market][$this->id] as $db_key => $db_vals) {
					$q_id_where[] = "{$db_key} in (:{$db_key})";
				}
				// data this fiscal month
				$d = db::select_row("
					select ".implode(", ", $q_select)."
					from {$market}_objects.all_data_Q{$market_ac_id}
					where
						data_date between '$month_start' and '$month_end' &&
						".implode(" && ", $q_id_where)."
				", $data_sources[$market][$this->id], 'ASSOC');
				// no data tables for this account
				if ($d === false) {
					continue;
				}
				foreach ($d as $k => $v) {
					$ac_data[$k] += $v;
				}
				// data from yesterday
				$d = db::select_row("
					select ".implode(", ", $q_select)."
					from {$market}_objects.all_data_Q{$market_ac_id}
					where
						data_date = '".date(util::DATE, \epro\NOW - 86400)."' &&
						".implode(" && ", $q_id_where)."
				", $data_sources[$market][$this->id], 'ASSOC');

				foreach ($d as $k => $v) {
					$yesterday_data[$k] += $v;
					$yesterday_data["{$market}_{$k}"] += $v;
				}
				
				// spend previous fiscal month
				$tmp = db::select_one("
					select sum(cost)
					from {$market}_objects.all_data_Q{$market_ac_id}
					where
						data_date between '$prev_month_start' and '$prev_month_end' &&
						".implode(" && ", $q_id_where)."
				", $data_sources[$market][$this->id]);
				if (!empty($tmp)) $spend_prev_month += $tmp;
			}
		}
		
		$spend_remaining = $budget - $ac_data['cost'];
		$daily_to_date = ($days_to_date > 0) ? ($ac_data['cost'] / $days_to_date) : 0;
		$daily_remaining = ($days_remaining > 0 && $spend_remaining > 0) ? ($spend_remaining / $days_remaining) : 0;
		
		$ql_spend_data = array_merge($yesterday_data, array(
			'account_id' => $this->id,
			'days_to_date' => $days_to_date,
			'days_remaining' => $days_remaining,
			'days_in_month' => $days_in_month,
			'imps_to_date' => $ac_data['imps'],
			'spend_to_date' => $ac_data['cost'],
			'spend_remaining' => $spend_remaining,
			'spend_prev_month' => $spend_prev_month,
			'daily_to_date' => $daily_to_date,
			'daily_remaining' => $daily_remaining
		));

		db::insert("eppctwo.ql_spend", $ql_spend_data);
	}
}

class ap_sb extends product
{
	public static $db, $cols, $primary_key;
	
	const FAN_PAGE_AMOUNT = 100;
	
	public static $plan_options = array('Express', 'Starter', 'Core', 'Premier', 'silver', 'gold', 'platinum');
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'      ,'char',16  ,''    ,rs::READ_ONLY),
			new rs_col('has_ads' ,'bool',null,0 ,rs::NOT_NULL),
			new rs_col('has_soci','bool',null,0 ,rs::NOT_NULL)
		);
	}
	
	public static function get_activation_billing_keys()
	{
		$keys = parent::get_activation_billing_keys();
		$keys['fan_page'] = 'Fan Page';
		return $keys;
	}
	
	public static function calc_order_billing_set_dept_data(&$alldata, &$data, $num)
	{
		$keys = array('is_fan_page');
		foreach ($keys as $keybase)
		{
			$key = $keybase.((is_numeric($num)) ? '_'.$num : '');
			$data[$keybase] = $alldata[$key];
		}
	}
	
	public static function calc_order_billing_dept(&$billing, &$data)
	{
		if ($data['is_fan_page'])
		{
			$billing['fan_page'] = self::FAN_PAGE_AMOUNT;
			$billing['today'] += self::FAN_PAGE_AMOUNT;
		}
		else
		{
			$billing['fan_page'] = 0;
		}
	}
	
	public function load_from_post($prod_num)
	{
		parent::load_from_post($prod_num, array('is_fan_page'));
	}
}

class ap_gs extends product
{
	public static $db, $cols, $primary_key;
	
	public static $plan_options = array('Starter', 'Core', 'Premier', 'Pro');
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                   ,'char'    ,16  ,''    ,rs::READ_ONLY)
		);
	}
}

class ap_ww extends product
{
	public static $db, $cols, $primary_key;
	
	public static $plan_options = array('None', '3_Page', '5_Page');
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                   ,'char'    ,16  ,''    ,rs::READ_ONLY)
		);
	}
}

class as_ppc extends service
{
	public static $db, $cols, $primary_key;

	public static $who_pays_clicks_options = array('Wpromote','Client');

	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                 ,'char'   ,16  ,''    ,rs::READ_ONLY),
			new rs_col('notes'              ,'text'   ,null,null  ,0           ),
			new rs_col('revenue_tracking'   ,'bool'   ,null,false ,rs::NOT_NULL),
			new rs_col('facebook'           ,'bool'   ,null,false ,rs::NOT_NULL),
			new rs_col('google_mpc_tracking','bool'   ,null,false ,rs::NOT_NULL),
			new rs_col('conversion_types'   ,'bool'   ,null,false ,rs::NOT_NULL),
			new rs_col('who_pays_clicks'    ,'enum'   ,16  ,null  ,0           ),
			new rs_col('budget'             ,'double' ,null,0     ,rs::NOT_NULL),
			new rs_col('carryover'          ,'double' ,null,0     ,rs::NOT_NULL),
			new rs_col('adjustment'         ,'double' ,null,0     ,rs::NOT_NULL),
			new rs_col('actual_budget'      ,'double' ,null,0     ,rs::NOT_NULL)
		);
	}

	public static function create($data, $opts = array())
	{
		util::load_lib('ppc');
		util::set_opt_defaults($data, array(
			'division' => 'service',
			'dept' => self::get_dept_from_class(),
			'status' => 'Active'
		));
		$account = parent::create($data, $opts);
		if (!isset($opts['skip_object_tables']) || $opts['skip_object_tables'] === false) {
			$markets = util::get_ppc_markets();
			foreach ($markets as $market) {
				ppc_lib::create_market_object_tables($market, $account->id);
			}
		}
		ppc_cdl::create(array(
			'account_id' => $account->id
		));
		return $account;
	}

	public function calc_actual_budget()
	{
		$this->actual_budget = $this->budget + $this->carryover + $this->adjustment;
	}
	
	public function update_actual_budget()
	{
		$this->calc_actual_budget();
		$this->update(array('cols' => array('actual_budget')));
	}

	public static function actual_budget_form_input($table, $col, $val)
	{
		return '<span title="Cannot be directly edited. Change budget, carryover and/or adjustment to set &quot;Actual Budget&quot;">'.util::format_dollars($val).'</span>';
	}
}

class as_seo extends service
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                  ,'char',16  ,'',rs::READ_ONLY),
			new rs_col('link_builder_manager','int' ,11  ,0 ,rs::UNSIGNED),
			new rs_col('billing_reminder'    ,'bool',null,1 ,rs::NOT_NULL)
		);
	}

	public static function link_builder_manager_form_input($table, $col, $val)
	{
		return self::manager_form_input($table, $col, $val);
	}
}

class as_smo extends service
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                   ,'char'    ,16  ,''    ,rs::READ_ONLY)
		);
	}
}


class as_email extends service
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                   ,'char'    ,16  ,''    ,rs::READ_ONLY)
		);
	}
}

class as_webdev extends service
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                   ,'char'    ,16  ,''    ,rs::READ_ONLY)
		);
	}
}

class payment extends rs_object
{
	public static $db, $cols, $primary_key, $indexes, $has_many;
	
	public static $pay_method_options = array('CC','Check','Wire');
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$has_many = array('payment_part');
		self::$cols = self::init_cols(
			new rs_col('id'             ,'char'    ,16  ,''     ,rs::READ_ONLY),
			new rs_col('client_id'      ,'char'    ,16  ,''     ,rs::READ_ONLY),
			new rs_col('user_id'        ,'int'     ,null,0      ,rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('pay_id'         ,'bigint'  ,null,0      ,rs::UNSIGNED | rs::NOT_NULL), // the id of the object used to pay (eg cc id)
			new rs_col('pay_method'     ,'enum'    ,8   ,''     ),
			new rs_col('fid'            ,'char'    ,64  ,''     ,rs::READ_ONLY), // id returned by payment processor, if any
			new rs_col('ts'             ,'datetime',null,rs::DDT,rs::READ_ONLY), // timestamp processed
			new rs_col('date_received'  ,'date'    ,null,rs::DD ),
			new rs_col('date_attributed','date'    ,null,rs::DD ),
			new rs_col('event'          ,'enum'    ,32  ,''     ),
			new rs_col('amount'         ,'double'  ,null,0      ),
			new rs_col('notes'          ,'char'    ,255 ,''     )
		);
	}
	
	// 16 hex chars
	protected function uprimary_key($i)
	{
		$sha = sha1(mt_rand());
		return strtoupper(substr($sha, 0, 16));
	}
}

class payment_part extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('payment_id'),
			array('account_id')
		);
		self::$cols = self::init_cols(
			new rs_col('id'          ,'int'    ,null,null,rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('payment_id'  ,'char'   ,16  ,0   ,rs::READ_ONLY),
			new rs_col('account_id'  ,'char'   ,16  ,''  ,rs::READ_ONLY),
			new rs_col('division'    ,'enum'   ,32  ,''   ),
			new rs_col('dept'        ,'enum'   ,32  ,''   ),
			new rs_col('type'        ,'enum'   ,32  ,''   ),
			new rs_col('is_passthru' ,'bool'   ,null,0    ),
			new rs_col('amount'      ,'double' ,null,0    ),
			new rs_col('rep_pay_num' ,'tinyint',null,0    )
		);
	}
}

abstract class payment_part_enum extends rs_object
{
	protected static function init_cols($enum_col)
	{
		return parent::init_cols(
			new rs_col('id'     ,'char',8 ,''     ,rs::READ_ONLY),
			new rs_col($enum_col,'char',32,''      )
		);
	}
	
	// 4 hex digits
	protected function uprimary_key($i)
	{
		return strtoupper(substr(sha1(mt_rand()), 0, 4));
	}
	
	public function update_from_array($data)
	{
		$classname = get_called_class();
		$enum_col = $classname::$enum_col;
		$prev_val = $this->$enum_col;
		$r = parent::update_from_array($data);
		if ($r !== false) {
			// success, update payment parts
			$payments_updated = payment_part::update_all(array(
				'set' => array($enum_col => $data[$enum_col]),
				'where' => "$enum_col = '".db::escape($prev_val)."'"
			));
			if ($payments_updated && class_exists('feedback')) {
				feedback::add_success_msg($payments_updated.' payments updated');
			}
			return $r;
		}
		else {
			return false;
		}
	}
}

abstract class payment_enum extends rs_object
{
	protected static function init_cols($enum_col)
	{
		return parent::init_cols(
			new rs_col('id'     ,'char',8 ,''     ,rs::READ_ONLY),
			new rs_col($enum_col,'char',32,''      )
		);
	}
	
	// 4 hex digits
	protected function uprimary_key($i)
	{
		return strtoupper(substr(sha1(mt_rand()), 0, 4));
	}
	
	public function update_from_array($data)
	{
		$classname = get_called_class();
		$enum_col = $classname::$enum_col;
		$prev_val = $this->$enum_col;
		$r = parent::update_from_array($data);
		if ($r !== false) {
			// success, update payment parts
			$payments_updated = payment::update_all(array(
				'set' => array($enum_col => $data[$enum_col]),
				'where' => "$enum_col = '".db::escape($prev_val)."'"
			));
			if ($payments_updated && class_exists('feedback')) {
				feedback::add_success_msg($payments_updated.' payments updated');
			}
			return $r;
		}
		else {
			return false;
		}
	}
}

abstract class multi_col_enum extends rs_object
{
	protected static function init_cols($enum_cols, $mixed_col_len = false)
	{
		// optional, default 32, if passed in can be constant or array
		if (is_array($mixed_col_len))
		{
			$col_lens = $mixed_col_len;
		}
		else
		{
			$col_lens = array_fill(0, count($enum_cols), ($mixed_col_len) ? $mixed_col_len : 32);
		}
		// init with id col
		$args = array(new rs_col('id'     ,'char',8 ,''     ,rs::READ_ONLY));
		foreach ($enum_cols as $i => $enum_col)
		{
			$args[] = new rs_col($enum_col,'char',$col_lens[$i],'');
		}
		return call_user_func_array(array(parent, init_cols), $args);
	}
	
	// 6 hex digits
	protected function uprimary_key($i)
	{
		return strtoupper(substr(sha1(mt_rand()), 0, 6));
	}
}

class dept_and_payment_type extends multi_col_enum
{
	public static $db, $cols, $primary_key, $uniques;
	
	public static $enum_cols = array('dept', 'type');
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$uniques = array(self::$enum_cols);
		self::$cols = self::init_cols(self::$enum_cols);
	}
}



class ppe_type extends payment_part_enum
{
	public static $db, $cols, $primary_key, $uniques;
	
	public static $enum_col = 'type';
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$uniques = array(
			array(self::$enum_col)
		);
		self::$cols = self::init_cols(self::$enum_col);
	}
}

class ppe_dept extends payment_part_enum
{
	public static $db, $cols, $primary_key, $uniques;
	
	public static $enum_col = 'dept';
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$uniques = array(
			array(self::$enum_col)
		);
		self::$cols = self::init_cols(self::$enum_col);
	}
	
	private static function standardize_enum_col(&$data)
	{
		$tmp = util::simple_text($data[self::$enum_col]);
		$data[self::$enum_col] = preg_replace("/^[^a-z]+/", '', $tmp);
	}
	
	public function update_from_array($data)
	{
		self::standardize_enum_col($data);
		return parent::update_from_array($data);
	}
	
	public static function create($data, $opts = array())
	{
		self::standardize_enum_col($data);
		return parent::create($data, $opts);
	}
}

/*
 * when an order is submitted, create credit card so we can attempt to charge
 * if charge is successful, create everything else
 */
class product_order extends rs_object
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

// temporary table for joining a ql pro product account to a ppc agency account
class ql_pro_x_ppc extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('ql_account_id', 'ppc_client_id');
		self::$cols = self::init_cols(
			new rs_col('ql_account_id','char'  ,16  ,'', rs::READ_ONLY),
			new rs_col('ppc_client_id','bigint',null,0 , rs::READ_ONLY)
		);
	}
}

/*
 * couple classes for mapping old ids to new ones
 */

// id map for account/client ids
// todo: delete once everything seems to be running smoothly
class nto_account extends rs_object
{
	public static $db, $cols, $primary_key, $has_many;
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('naid');
		self::$cols = self::init_cols(
			new rs_col('dept','char'  ,16  ,''  ,rs::READ_ONLY),
			new rs_col('naid','char'  ,16  ,''  ,rs::READ_ONLY),
			new rs_col('ncid','char'  ,16  ,''  ,rs::READ_ONLY),
			new rs_col('oaid','bigint',null,null,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('ocid','bigint',20  ,null,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY)
		);
	}
}

// payments
class nto_payment extends rs_object
{
	public static $db, $cols, $primary_key, $has_many;
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('npid');
		self::$cols = self::init_cols(
			new rs_col('npid','char'  ,16  ,''  ,rs::READ_ONLY),
			new rs_col('opid','bigint',null,null,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY)
		);
	}
}

?>