<?php

class ql_url extends rs_object
{
	public static $db, $cols, $primary_key, $has_one;
	
	public static $status_options = array('New','Incomplete','Active','Cancelled','Declined','OnHold','NonRenewing','BillingFailure');
	public static $plan_options = array('Basic','Bo1','Bo2','Bo3','Bo4','Core','Core_297','Gold','GoldQL','LAL','LAL1','LALgold','LALplatinum','LALsilver','Plat','PlatQL','Plus','Premier','SMOplat','Starter','Starter_149');
	public static $pay_option_options = array('standard','3_0','6_1','12_3');
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		
		self::$cols = self::init_cols(
			new rs_col('client_id'              ,'varchar' ,32  ,''    ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('id'                     ,'bigint'  ,null,null  ,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('wpro_cl_id'             ,'bigint'  ,null,'0'   ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('url'                    ,'varchar' ,256 ,''    ,rs::NOT_NULL),
			new rs_col('status'                 ,'enum'    ,16  ,''    ,rs::NOT_NULL),
			new rs_col('cc_id'                  ,'bigint'  ,null,0     ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('plan'                   ,'enum'    ,16  ,''    ,rs::NOT_NULL),
			new rs_col('oid'                    ,'varchar' ,32  ,''    ,rs::NOT_NULL),
			new rs_col('pay_option'             ,'enum'    ,8   ,''    ,rs::NOT_NULL),
			new rs_col('sales_rep'              ,'int'     ,11  ,null  ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('account_rep'            ,'int'     ,null,0     ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('partner'                ,'varchar' ,32  ,''    ,rs::NOT_NULL),
			new rs_col('source'                 ,'varchar' ,64  ,''    ,rs::NOT_NULL),
			new rs_col('subid'                  ,'varchar' ,32  ,''    ,rs::NOT_NULL),
			new rs_col('partner_data'           ,'varchar' ,128 ,''    ,rs::NOT_NULL),
			new rs_col('signup_date'            ,'date'    ,null,rs::DD,rs::NOT_NULL),
			new rs_col('trial_length'           ,'tinyint' ,null,0     ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('trial_amount'           ,'double'  ,null,0     ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('trial_auth_amount'      ,'double'  ,null,0     ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('trial_auth_id'          ,'varchar' ,64  ,''    ,rs::NOT_NULL),
			new rs_col('is_3_day_done'          ,'bool'    ,null,0     ,rs::NOT_NULL),
			new rs_col('is_7_day_done'          ,'bool'    ,null,0     ,rs::NOT_NULL),
			new rs_col('cancel_date'            ,'date'    ,null,rs::DD,rs::NOT_NULL),
			new rs_col('de_activation_date'     ,'date'    ,null,rs::DD,rs::NOT_NULL),
			new rs_col('bill_day'               ,'tinyint' ,3   ,'0'   ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('first_bill_date'        ,'date'    ,null,rs::DD,rs::NOT_NULL),
			new rs_col('next_bill_date'         ,'date'    ,null,rs::DD,rs::NOT_NULL),
			new rs_col('last_bill_date'         ,'date'    ,null,rs::DD,rs::NOT_NULL),
			new rs_col('multi_month_bill_date'  ,'date'    ,null,rs::DD,rs::NOT_NULL),
			new rs_col('country_origin'         ,'varchar' ,4   ,''    ,rs::NOT_NULL),
			new rs_col('last_report_review_date','date'    ,null,rs::DD,rs::NOT_NULL),
			new rs_col('do_report'              ,'bool'    ,null,0     ,rs::NOT_NULL),
			new rs_col('is_op'                  ,'bool'    ,null,0     ,rs::NOT_NULL),
			new rs_col('is_op_done'             ,'bool'    ,null,0     ,rs::NOT_NULL),
			new rs_col('is_billing_paused'      ,'bool'    ,null,0     ,rs::NOT_NULL),
			new rs_col('is_billing_failure'     ,'bool'    ,null,0     ,rs::NOT_NULL),
			new rs_col('alt_recur_amount'       ,'double'  ,null,0     ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('alt_num_keywords'       ,'smallint',null,0     ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('contract_length'        ,'int'     ,2   ,0     ,rs::NOT_NULL | rs::UNSIGNED)
		);
		self::$primary_key = array('id');
		self::$has_one = array('clients');
	}
	
	public function is_free_trial()
	{
		return (is_numeric($this->trial_length) && $this->trial_length > 0);
	}
	
	public static function sales_rep_form_input($table, $col, $val)
	{
		return sbs_lib::sales_rep_form_input($table, $col, $val);
	}
	
	public static function account_rep_form_input($table, $col, $val)
	{
		return sbs_lib::account_rep_form_input($table, $col, $val);
	}
	
	public static function partner_form_input($table, $col, $val)
	{
		return sbs_lib::partner_form_input($table, $col, $val);
	}
	
	public static function source_form_input($table, $col, $val)
	{
		return sbs_lib::source_form_input($table, $col, $val);
	}
	
}

class ql_url_note extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(array('ql_url_id'));
		self::$cols = self::init_cols(
			new rs_col('id'       ,'int'     ,null,null   ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY | rs::AUTO_INCREMENT),
			new rs_col('ql_url_id','int'     ,null,0      ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('users_id' ,'int'     ,null,0      ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('dt'       ,'datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('note'     ,'varchar' ,512 ,''     ,rs::NOT_NULL)
		);
	}
}

class ql_new_order extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static $who_creates = array('Expert', 'User');
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('ql_url_id');
		self::$cols = self::init_cols(
			new rs_col('ql_url_id'    ,'int'     ,null,null   ,rs::NOT_NULL | rs::READ_ONLY | rs::UNSIGNED),
			new rs_col('dt'           ,'datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('ip'           ,'varchar' ,40  ,''     ,rs::NOT_NULL),
			new rs_col('browser'      ,'varchar' ,200 ,''     ,rs::NOT_NULL),
			new rs_col('referer'      ,'varchar' ,200 ,''     ,rs::NOT_NULL),
			new rs_col('order_count'  ,'tinyint' ,null,0      ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('discount'     ,'varchar' ,32 ,''      ,rs::NOT_NULL),
			new rs_col('plan'         ,'varchar' ,32  ,''     ,rs::NOT_NULL),
			new rs_col('trial_length' ,'tinyint' ,null,0      ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('trial_amount' ,'double'  ,null,0      ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('name'         ,'varchar' ,128 ,''     ,rs::NOT_NULL),
			new rs_col('email'        ,'varchar' ,128 ,''     ,rs::NOT_NULL),
			new rs_col('phone'        ,'varchar' ,32  ,''     ,rs::NOT_NULL),
			new rs_col('url'          ,'varchar' ,128 ,''     ,rs::NOT_NULL),
			new rs_col('who_creates'  ,'enum'    ,8   ,''     ,rs::NOT_NULL),
			new rs_col('title'        ,'varchar' ,32  ,''     ,rs::NOT_NULL),
			new rs_col('desc1'        ,'varchar' ,64  ,''     ,rs::NOT_NULL),
			new rs_col('desc2'        ,'varchar' ,64  ,''     ,rs::NOT_NULL),
			new rs_col('keywords'     ,'text'    ,null,''     ,rs::NOT_NULL),
			new rs_col('comments'     ,'varchar' ,256 ,''     ,rs::NOT_NULL),
			new rs_col('is_op'        ,'bool'    ,null,0      ,rs::NOT_NULL),
			new rs_col('is_op_done'   ,'bool'    ,null,0      ,rs::NOT_NULL),
			new rs_col('pay_option'   ,'enum'    ,8   ,''     ,rs::NOT_NULL),
			new rs_col('partner'      ,'varchar' ,32  ,''     ,rs::NOT_NULL),
			new rs_col('cc_type'      ,'varchar' ,16  ,''     ,rs::NOT_NULL),
			new rs_col('cc_name'      ,'varchar' ,16  ,''     ,rs::NOT_NULL),
			new rs_col('cc_first_four','char'    ,4   ,''     ,rs::NOT_NULL),
			new rs_col('cc_last_four' ,'char'    ,4   ,''     ,rs::NOT_NULL),
			new rs_col('cc_exp_month' ,'varchar' ,16  ,''     ,rs::NOT_NULL),
			new rs_col('cc_exp_year'  ,'varchar' ,16  ,''     ,rs::NOT_NULL),
			new rs_col('cc_country'   ,'varchar' ,16  ,''     ,rs::NOT_NULL),
			new rs_col('cc_zip'       ,'varchar' ,16  ,''     ,rs::NOT_NULL),
			new rs_col('setup_fee'    ,'int'     ,null,0      ,rs::NOT_NULL),
			new rs_col('coupon_code'  ,'varchar' ,16  ,''     ,rs::NOT_NULL),
			new rs_col('contract_length'        ,'int'     ,2   ,0     ,rs::NOT_NULL | rs::UNSIGNED)
		);
	}
	
	public static function get_pay_option_options()
	{
		return ql_url::$pay_option_options;
	}
}

class ql_data_source extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('market','campaign','ad_group');
		self::$indexes = array(
			array('account_id')
		);
		self::$cols = self::init_cols(
			new rs_col('account_id','varchar',32,'' ,rs::NOT_NULL),
			new rs_col('market'   ,'varchar',4 ,'' ,rs::NOT_NULL),
			new rs_col('account'  ,'varchar',32,'' ,rs::NOT_NULL),
			new rs_col('campaign' ,'varchar',32,'' ,rs::NOT_NULL),
			new rs_col('ad_group' ,'varchar',32,'' ,rs::NOT_NULL)
		);
	}

	public function get_ad_group_query($prefix = '')
	{
		if ($this->ad_group) {
			return "{$prefix}id = '{$this->ad_group}'";
		}
		else {
			return "{$prefix}campaign_id = '{$this->campaign}'";
		}
	}

	public function get_entity_query($prefix = '')
	{
		if ($this->ad_group) {
			return "{$prefix}ad_group_id = '{$this->ad_group}'";
		}
		else {
			return "{$prefix}campaign_id = '{$this->campaign}'";
		}
	}
}

class lal_payment extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array(id);
		self::$cols = self::init_cols(
			new rs_col('id'    ,'int'   ,null,null  ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY | rs::AUTO_INCREMENT),
			new rs_col('date'  ,'date'  ,null,rs::DD,rs::NOT_NULL),
			new rs_col('amount','double',null,0     ,rs::NOT_NULL)
		);
	}
}

class ql_ad extends rs_object
{
	public static $db, $cols, $primary_key, $indexes, $uniques;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$uniques = array(
			array('market', 'ad_group_id', 'ad_id')
		);
		self::$indexes = array(
			array('account_id')
		);
		self::$cols = self::init_cols(
			new rs_col('id'         ,'int'    ,null,null,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY | rs::AUTO_INCREMENT),
			new rs_col('market'     ,'char'   ,2   ,''  ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('ad_group_id','char'   ,32  ,''  ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('ad_id'      ,'char'   ,32  ,''  ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('account_id' ,'bigint' ,null,0   ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('is_su'      ,'tinyint',null,0   ,rs::NOT_NULL | rs::UNSIGNED)
		);
	}
}

?>