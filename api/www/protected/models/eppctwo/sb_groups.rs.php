<?php

class mod_eppctwo_sb_groups extends rs_object
{
	public static $db, $cols, $primary_key, $has_one;
	
	public static $status_options = array('New','Incomplete','Active','Cancelled','Declined','OnHold','NonRenewing','BillingFailure');
	public static $plan_options = array('Starter', 'Core', 'Premier', 'silver', 'gold', 'platinum', 'Express');
	public static $pay_option_options = array('standard','3_0','6_1','12_3');
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$has_one = array('clients');
		self::$cols = self::init_cols(
			new rs_col('id'                   ,'int'     ,10  ,null  ,rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT),
			new rs_col('client_id'            ,'bigint'  ,20  ,null  ,rs::NOT_NULL),
			new rs_col('contact_id'           ,'bigint'  ,20  ,null  ,rs::NOT_NULL),
			new rs_col('cc_id'                ,'bigint'  ,20  ,null  ,rs::NOT_NULL),
			new rs_col('d'                    ,'date'    ,null,RS::DD,rs::NOT_NULL),
			new rs_col('t'                    ,'time'    ,null,RS::DT,rs::NOT_NULL),
			new rs_col('url'                  ,'varchar' ,100 ,null  ,rs::NOT_NULL),
			new rs_col('status'               ,'enum'    ,16  ,''    ,rs::NOT_NULL),
			new rs_col('plan'                 ,'enum'    ,null,''    ,rs::NOT_NULL),
			new rs_col('oid'                  ,'varchar' ,12  ,null  ,rs::NOT_NULL),
			new rs_col('pay_option'           ,'enum'    ,8   ,''    ,rs::NOT_NULL),
			new rs_col('sales_rep'            ,'int'     ,11  ,null  ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('account_rep'          ,'int'     ,null,0     ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('partner'              ,'varchar' ,16  ,null  ,rs::NOT_NULL),
			new rs_col('source'               ,'varchar' ,64  ,''    ,rs::NOT_NULL),
			new rs_col('subid'                ,'varchar' ,32  ,''    ,rs::NOT_NULL),
			new rs_col('rdt'                  ,'varchar' ,16  ,null  ,rs::NOT_NULL),
			new rs_col('signup_date'          ,'date'    ,null,null  ,rs::NOT_NULL),
			new rs_col('cancel_date'          ,'date'    ,null,null  ,rs::NOT_NULL),
			new rs_col('de_activation_date'   ,'date'    ,null,rs::DD,rs::NOT_NULL),
			new rs_col('bill_day'             ,'smallint',2   ,null  ,rs::NOT_NULL),
			new rs_col('first_bill_date'      ,'date'    ,null,null  ,rs::NOT_NULL),
			new rs_col('next_bill_date'       ,'date'    ,null,null  ,rs::NOT_NULL),
			new rs_col('last_bill_date'       ,'date'    ,null,null  ,rs::NOT_NULL),
			new rs_col('display_name'         ,'varchar' ,35  ,null  ,rs::NOT_NULL),
			new rs_col('daily_budget'         ,'decimal' ,null,null  ,rs::NOT_NULL),
			new rs_col('start_time'           ,'date'    ,null,null  ,rs::NOT_NULL),
			new rs_col('stop_time'            ,'date'    ,null,null  ,rs::NOT_NULL),
			new rs_col('run_status'           ,'enum'    ,null,null  ,rs::NOT_NULL ,array('active','paused')),
			new rs_col('country'              ,'varchar' ,16  ,''    ,rs::NOT_NULL),
			new rs_col('processed'            ,'enum'    ,null,null  ,rs::NOT_NULL ,array('new','processed','canceled','deleted','incomplete','declined')),
			new rs_col('edit_status'          ,'enum'    ,null,null  ,rs::NOT_NULL ,array('new order','current','wpro','client','cleared')),
			new rs_col('last_client_update'   ,'datetime',null,null  ,rs::NOT_NULL),
			new rs_col('latest_payment_status','varchar' ,64  ,null  ,rs::NOT_NULL),
			new rs_col('comments'             ,'varchar' ,512 ,null  ,rs::NOT_NULL),
			new rs_col('wpro_comments'        ,'varchar' ,128 ,null  ,rs::NOT_NULL),
			new rs_col('is_likepage'          ,'bool'    ,null,'0'   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('is_likepage_done'     ,'bool'    ,null,'0'   ,rs::NOT_NULL),
			new rs_col('trial_length'         ,'tinyint' ,3   ,'0'   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('trial_amount'         ,'double'  ,null,'0'   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('trial_auth_amount'    ,'double'  ,null,'0'   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('trial_auth_id'        ,'varchar' ,64  ,''    ,rs::NOT_NULL),
			new rs_col('is_7_day_done'        ,'bool'    ,null,0     ,rs::NOT_NULL),
			new rs_col('setup_fee'            ,'int'     ,4   ,'0'   ,rs::NOT_NULL),
			new rs_col('coupon_code'          ,'varchar' ,16  ,null  ,rs::NOT_NULL),
			new rs_col('is_billing_failure'   ,'bool'    ,null,0     ,rs::NOT_NULL),
			new rs_col('alt_recur_amount'     ,'double'  ,null,null  ,rs::NOT_NULL),
			new rs_col('contract_length'      ,'int'     ,2   ,0     ,rs::NOT_NULL | rs::UNSIGNED)
		);
	}
	
	public function is_free_trial()
	{
		return (is_numeric($this->trial_length) && $this->trial_length > 0);
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
	
	public static function sales_rep_form_input($table, $col, $val)
	{
		return sbs_lib::sales_rep_form_input($table, $col, $val);
	}
}
?>
