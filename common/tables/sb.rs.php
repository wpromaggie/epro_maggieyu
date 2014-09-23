<?php

class sb_groups extends rs_object
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


class sb_new_order extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('account_id');
		self::$cols = self::init_cols(
			new rs_col('account_id'   ,'int'     ,null,null   ,rs::NOT_NULL | rs::READ_ONLY | rs::UNSIGNED),
			new rs_col('dt'           ,'datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('ip'           ,'varchar' ,40  ,''     ,rs::NOT_NULL),
			new rs_col('browser'      ,'varchar' ,200 ,''     ,rs::NOT_NULL),
			new rs_col('referer'      ,'varchar' ,200 ,''     ,rs::NOT_NULL),
			new rs_col('discount'     ,'varchar' ,32 ,''      ,rs::NOT_NULL),
			new rs_col('plan'         ,'varchar' ,32  ,''     ,rs::NOT_NULL),
			new rs_col('trial_length' ,'tinyint' ,null,0      ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('trial_amount' ,'double'  ,null,0      ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('name'         ,'varchar' ,128 ,''     ,rs::NOT_NULL),
			new rs_col('email'        ,'varchar' ,128 ,''     ,rs::NOT_NULL),
			new rs_col('phone'        ,'varchar' ,32  ,''     ,rs::NOT_NULL),
			new rs_col('url'          ,'varchar' ,128 ,''     ,rs::NOT_NULL),
			new rs_col('comments'     ,'varchar' ,256 ,''     ,rs::NOT_NULL),
			new rs_col('is_likepage'  ,'bool'    ,null,0      ,rs::NOT_NULL),
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
		return sb_groups::$pay_option_options;
	}
}

class sb_fb_extras extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('ad_id');
		self::$cols = self::init_cols(
			new rs_col('ad_id'                   ,'bigint' ,null,0 ,rs::NOT_NULL | rs::READ_ONLY | rs::UNSIGNED),
			new rs_col('campaign_id'             ,'char'   ,32  ,'',rs::NOT_NULL),
			new rs_col('campaign_run_status'     ,'char'   ,16  ,'',rs::NOT_NULL),
			new rs_col('campaign_name'           ,'char'   ,128 ,'',rs::NOT_NULL),
			new rs_col('campaign_time_start'     ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('campaign_time_stop'      ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('campaign_daily_budget'   ,'char'   ,8   ,'',rs::NOT_NULL),
			new rs_col('campaign_lifetime_budget','char'   ,16  ,'',rs::NOT_NULL),
			new rs_col('campaign_type'           ,'char'   ,32  ,'',rs::NOT_NULL),
			new rs_col('ad_status'               ,'char'   ,16  ,'',rs::NOT_NULL),
			new rs_col('demo_link'               ,'varchar',512 ,'',rs::NOT_NULL),
			new rs_col('rate_card'               ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('max_bid_social'          ,'char'   ,8   ,'',rs::NOT_NULL),
			new rs_col('conversion_specs'        ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('related_page'            ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('image_hash'              ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('creative_type'           ,'char'   ,16  ,'',rs::NOT_NULL),
			new rs_col('app_platform_type'       ,'char'   ,16  ,'',rs::NOT_NULL),
			new rs_col('link_object_id'          ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('story_id'                ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('auto_update'             ,'char'   ,8   ,'',rs::NOT_NULL),
			new rs_col('url_tags'                ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('page_types'              ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('connections'             ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('excluded_connections'    ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('friends_of_connections'  ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('locales'                 ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('likes_and_interests'     ,'varchar',1024,'',rs::NOT_NULL),
			new rs_col('excluded_user_adclusters','varchar',1024,'',rs::NOT_NULL),
			new rs_col('broad_category_clusters' ,'varchar',1024,'',rs::NOT_NULL),
			new rs_col('custom_audiences'        ,'varchar',128 ,'',rs::NOT_NULL),
			new rs_col('targeted_entities'       ,'varchar',128 ,'',rs::NOT_NULL),
			new rs_col('broad_age'               ,'char'   ,8   ,'',rs::NOT_NULL),
			new rs_col('actions'                 ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('image'                   ,'char'   ,64  ,'',rs::NOT_NULL)
		);
	}
}

class sb_exp_contacts extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
	
		self::$db = 'eppctwo';
		self::$primary_key = array('account_id');
		
		
		self::$cols = self::init_cols(
			new rs_col('account_id'  ,'char'    ,16 ,''     ,rs::NOT_NULL),
			new rs_col('name'         ,'varchar' ,128 ,''     ,rs::NOT_NULL),
			new rs_col('url'          ,'varchar' ,256 ,''     ,rs::NOT_NULL),
			new rs_col('street'       ,'varchar' ,64  ,''     ,rs::NOT_NULL),
			new rs_col('city'       ,'varchar' ,32  ,''     ,rs::NOT_NULL),
			new rs_col('state'       ,'varchar' ,32  ,''     ,rs::NOT_NULL),
			new rs_col('zip'       ,'varchar' ,16  ,''     ,rs::NOT_NULL),
			new rs_col('phone'        ,'varchar' ,32  ,''     ,rs::NOT_NULL),
			new rs_col('email'        ,'varchar' ,64 ,''     ,rs::NOT_NULL),
			new rs_col('hour_start'     ,'varchar' ,16 ,''     ,rs::NOT_NULL),
			new rs_col('hour_end'     ,'varchar' ,16 ,''     ,rs::NOT_NULL),
			new rs_col('fb_exists'     ,'tinyint' ,1 ,0     ,rs::NOT_NULL),
			new rs_col('fb_email'     ,'varchar' ,256 ,''     ,rs::NOT_NULL),
			new rs_col('fb_email'     ,'varchar' ,64 ,''     ,rs::NOT_NULL),
			new rs_col('fb_pass'     ,'varchar' ,32 ,''     ,rs::NOT_NULL),
			new rs_col('url_key'     ,'varchar' ,32 ,''     ,rs::NOT_NULL)
		);
		
	}
	
	public static function create($data, $opts = array())
	{
		$data['url_key'] = self::generate_url_key();
		return parent::create($data, $opts);
	}
	
	public static function get_express_link($account)
	{
		$sb_exp_contact = new sb_exp_contacts(array('account_id' => $account->id));
		
		$protocol = (util::is_dev()) ? 'http' : 'https';
		return ($protocol.'://'.\epro\WPRO_DOMAIN.'/account/socialboost-express-follow-up?k='.$sb_exp_contact->url_key);
	}
	
	public static function generate_url_key()
	{
		while (1)
		{
			$key = md5(mt_rand());
			if (self::count(array("where" => "authentication = '$key'")) == 0)
			{
				return $key;
			}
		}
	}
}

?>