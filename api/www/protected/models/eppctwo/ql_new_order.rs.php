<?php

class mod_eppctwo_ql_new_order extends rs_object
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
?>
