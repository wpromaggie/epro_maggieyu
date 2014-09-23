<?php

class mod_eppctwo_gs_urls extends rs_object
{
	public static $db, $cols, $primary_key, $has_one;

	public static $status_options = array('New','Incomplete','Active','Cancelled','Declined','OnHold','NonRenewing','BillingFailure');
	public static $plan_options = array('Starter', 'Core', 'Premier', 'Pro');
	public static $pay_option_options = array('standard','3_0','6_1','12_3');
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$cols = self::init_cols(
			new rs_col('id'                     ,'bigint'  ,null,null   ,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('client_id'              ,'varchar' ,32  ,''     ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('url'                    ,'varchar' ,256 ,''     ,rs::NOT_NULL),
			new rs_col('oid'                    ,'varchar' ,32  ,''     ,rs::NOT_NULL),
			new rs_col('status'                 ,'enum'    ,16  ,''     ,rs::NOT_NULL),
			new rs_col('plan'                   ,'enum'    ,null,''     ,rs::NOT_NULL),
			new rs_col('pay_option'             ,'enum'    ,8   ,''     ,rs::NOT_NULL),
			new rs_col('sales_rep'              ,'int'     ,null,null   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('account_rep'            ,'int'     ,null,0      ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('partner'                ,'varchar' ,32  ,''     ,rs::NOT_NULL),
			new rs_col('source'                 ,'varchar' ,64  ,''     ,rs::NOT_NULL),
			new rs_col('subid'                  ,'varchar' ,32  ,''    ,rs::NOT_NULL),
			new rs_col('signup_date'            ,'date'    ,null,rs::DD ,rs::NOT_NULL),
			new rs_col('cancel_date'            ,'date'    ,null,rs::DD ,rs::NOT_NULL),
			new rs_col('de_activation_date'     ,'date'    ,null,rs::DD ,rs::NOT_NULL),
			new rs_col('created'                ,'datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('bill_day'               ,'tinyint' ,null,0      ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('first_bill_date'        ,'date'    ,null,rs::DD ,rs::NOT_NULL),
			new rs_col('next_bill_date'         ,'date'    ,null,rs::DD ,rs::NOT_NULL),
			new rs_col('last_bill_date'         ,'date'    ,null,rs::DD ,rs::NOT_NULL),
			new rs_col('cc_id'                  ,'bigint'  ,null,0      ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('boo_id'                 ,'int'     ,null,null   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('boo_note'               ,'varchar' ,512 ,''     ,rs::NOT_NULL),
			new rs_col('is_billing_failure'     ,'bool'    ,null,0      ,rs::NOT_NULL),
			new rs_col('alt_recur_amount'       ,'double'  ,null,0      ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('contract_length'        ,'int'     ,2   ,0      ,rs::NOT_NULL | rs::UNSIGNED)
		);
		self::$primary_key = array('id');
		self::$has_one = array('clients');
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
	
	public function is_free_trial()
	{
		return false;
	}
}

?>
