<?php

class mod_eac_account extends rs_object
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

	/**
	 * get_account_details_by_aid
	 * @param $aid
	 */
	public static function get_account_details_by_cid($cid){
		$q = "SELECT 
				c.name,
				c.id AS c_client_id,
				a.id AS aid,
				a.client_id,
				a.cc_id,
				a.division,
				a.dept,
				a.data_id
			FROM eac.account a
			LEFT JOIN eac.client c on c.`id` = a.client_id
			WHERE client_id IN ('{$cid}')";
		return db::select($q,'ASSOC');
	}

	/**
	 * get_account_details_by_aid
	 * @param $aid
	 */
	public static function get_account_details_by_aid($aid){
		$q = "SELECT 
				c.name,
				c.id AS c_client_id,
				a.id AS aid,
				a.client_id,
				a.cc_id,
				a.division,
				a.dept,
				a.data_id
			FROM eac.account a
			LEFT JOIN eac.client c on c.`id` = a.client_id
			WHERE a.id IN ('{$aid}')";
		return db::select($q,'ASSOC');
	}

	/**
	 * get_clients_by_department()
	 * @param $dept
	 */
	public static function get_clients_by_department($dept){
		$q = "SELECT 
				c.name,
				c.id AS client_id,
				a.id AS account_id,
				a.division,
				a.status,
				a.url,
				u.realname,
				a.signup_dt
			FROM `eac`.`account` a
			LEFT JOIN `eac`.`client` c ON c.id = a.client_id
			LEFT JOIN `eppctwo`.`users` u ON u.id = a.manager 
			WHERE a.dept = '{$dept}'";
			Logger($q);
		return db::select($q,'ASSOC');
	}
}
?>
