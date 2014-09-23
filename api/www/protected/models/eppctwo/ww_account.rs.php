<?php

class mod_eppctwo_ww_account extends rs_object
{
	public static $db, $cols, $primary_key, $has_one;
	
	public static $plan_options = array('None', '3_Page', '5_Page');
	public static $landing_page_options = array('None', 'One_Time', '6_Months');
	public static $contract_length_options = array('0', '6', '12');
	public static $status_options = array('New','Incomplete','Active','Cancelled','Declined','OnHold','NonRenewing','BillingFailure');
	public static $pay_option_options = array('standard','3_0','6_1','12_3');
	
	public static $pricing = array(
		'landing_page' => array(
			'One_Time' => array('down' => 895, 'recur' => 0),
			'6_Months' => array('down' => 149, 'recur' => 149)
		),
		'3_Page' => array(
			0  => array('down' => 1495, 'recur' => 0),
			6  => array('down' => 249, 'recur' => 249),
			12 => array('down' => 149, 'recur' => 149)
		),
		'5_Page' => array(
			0  => array('down' => 1995, 'recur' => 0),
			6  => array('down' => 349, 'recur' => 349),
			12 => array('down' => 249, 'recur' => 249)
		),
		'extra_pages' => array('down' => 250, 'recur' => 0)
	);
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$cols = self::init_cols(
			new rs_col('id'                  ,'bigint'  ,null,null   ,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('client_id'           ,'varchar' ,32  ,''     ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('url'                 ,'varchar' ,256 ,''     ,rs::NOT_NULL),
			new rs_col('oid'                 ,'varchar' ,32  ,''     ,rs::NOT_NULL),
			new rs_col('status'              ,'enum'    ,16  ,''     ,rs::NOT_NULL),
			new rs_col('plan'                ,'char'    ,16  ,''     ,rs::NOT_NULL),
			new rs_col('pay_option'          ,'enum'    ,8   ,''     ,rs::NOT_NULL),
			new rs_col('sales_rep'           ,'int'     ,null,null   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('account_rep'         ,'int'     ,null,0      ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('partner'             ,'varchar' ,32  ,''     ,rs::NOT_NULL),
			new rs_col('source'              ,'varchar' ,64  ,''     ,rs::NOT_NULL),
			new rs_col('trial_length'        ,'tinyint' ,null,0     ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('trial_amount'        ,'double'  ,null,0     ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('trial_auth_amount'   ,'double'  ,null,0     ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('trial_auth_id'       ,'varchar' ,64  ,''    ,rs::NOT_NULL),
			new rs_col('signup_date'         ,'date'    ,null,rs::DD ,rs::NOT_NULL),
			new rs_col('cancel_date'         ,'date'    ,null,rs::DD ,rs::NOT_NULL),
			new rs_col('de_activation_date'  ,'date'    ,null,rs::DD ,rs::NOT_NULL),
			new rs_col('created'             ,'datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('bill_day'            ,'tinyint' ,null,0      ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('first_bill_date'     ,'date'    ,null,rs::DD ,rs::NOT_NULL),
			new rs_col('next_bill_date'      ,'date'    ,null,rs::DD ,rs::NOT_NULL),
			new rs_col('last_bill_date'      ,'date'    ,null,rs::DD ,rs::NOT_NULL),
			new rs_col('cc_id'               ,'bigint'  ,null,0      ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('is_billing_failure'  ,'bool'    ,null,0      ,rs::NOT_NULL),
			new rs_col('landing_page'        ,'char'    ,16  ,0      ,rs::NOT_NULL),
			new rs_col('landing_page_date'   ,'date'    ,null,rs::DD ,rs::NOT_NULL),
			new rs_col('contract_date'       ,'date'    ,null,rs::DD ,rs::NOT_NULL),
			new rs_col('extra_pages'         ,'smallint',null,0      ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('alt_recur_amount'    ,'double'  ,null,0      ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('contract_length'     ,'int'     ,2   ,0      ,rs::NOT_NULL | rs::UNSIGNED)
		);
		self::$primary_key = array('id');
		self::$has_one = array('clients');
	}
	
	public static function get_billing_amounts($plan, $contract_length, $landing_page, $extra_pages)
	{
		$first_month = $recurring = 0;
		if ($plan != 'None')
		{
			$first_month += ww_account::$pricing[$plan][$contract_length]['down'];
			$recurring += ww_account::$pricing[$plan][$contract_length]['recur'];
		}
		if ($landing_page != 'None')
		{
			$first_month += ww_account::$pricing['landing_page'][$landing_page]['down'];
			$recurring += ww_account::$pricing['landing_page'][$landing_page]['recur'];
		}
		if ($extra_pages)
		{
			$first_month += ww_account::$pricing['extra_pages']['down'] * $extra_pages;
			$recurring += ww_account::$pricing['extra_pages']['recur'] * $extra_pages;
		}
		return array(
			'first_month' => $first_month,
			'recurring' => $recurring
		);
	}
	
	public static function get_recurring_amount($plan, $contract_length, $landing_page, $extra_pages)
	{
		$amounts = ww_account::get_billing_amounts($plan, $contract_length, $landing_page, $extra_pages);
		return $amounts['recurring'];
	}
	
	public static function get_first_month_amount($plan, $contract_length, $landing_page, $extra_pages)
	{
		$amounts = ww_account::get_billing_amounts($plan, $contract_length, $landing_page, $extra_pages);
		return $amounts['first_month'];
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
	
	public static function plan_input($table, $col, $val)
	{
		return cgi::html_select($table.'_'.$col->name, ww_account::$plan_options, $val);
	}
	
	public function is_free_trial()
	{
		return false;
	}
}

?>
