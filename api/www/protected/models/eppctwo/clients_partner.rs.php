<?php


class mod_eppctwo_clients_partner extends mod_eppctwo_clients
{
	public static $db, $cols, $primary_key, $extends;
	
	public static $status_options = array('New','Incomplete','Active','Cancelled','Declined','OnHold','NonRenewing','BillingFailure');

	public static $recurring_payment_types = array('Partner PPC Budget', 'Partner PPC', 'Partner SMO', 'Partner SEO');
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$extends = array('clients');
		self::$cols = self::init_cols(
			new rs_col('id'                ,'bigint'  ,20  ,null   ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('manager'           ,'varchar',64  ,''      ,rs::NOT_NULL),
			new rs_col('partner_ppc_fee'   ,'double' ,null,0       ,rs::NOT_NULL),
			new rs_col('partner_ppc_budget','double' ,null,0       ,rs::NOT_NULL),
			new rs_col('partner_smo_fee'   ,'double' ,null,0       ,rs::NOT_NULL),
			new rs_col('partner_seo_fee'   ,'double' ,null,0       ,rs::NOT_NULL),
			new rs_col('status'            ,'enum'   ,16  ,'Active',rs::NOT_NULL),
			new rs_col('url'               ,'varchar',128 ,''      ,rs::NOT_NULL),
			new rs_col('bill_day'          ,'tinyint',3   ,'0'     ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('signup_date'       ,'date'   ,null,rs::DD  ,rs::NOT_NULL),
			new rs_col('cancel_date'       ,'date'   ,null,rs::DD  ,rs::NOT_NULL),
			new rs_col('de_activation_date','date'   ,null,rs::DD  ,rs::NOT_NULL),
			new rs_col('first_bill_date'   ,'date'   ,null,rs::DD  ,rs::NOT_NULL),
			new rs_col('next_bill_date'    ,'date'   ,null,rs::DD  ,rs::NOT_NULL),
			new rs_col('last_bill_date'    ,'date'   ,null,rs::DD  ,rs::NOT_NULL)
		);
	}
	
	public static function manager_form_input($table, $col, $val)
	{
		$options = self::manager_options($table, $col, $val);
		array_unshift($options, array('', ' - None - '));
		return cgi::html_select($table.'_'.$col->name, $options, $val);
	}
	
	public static function manager_options($table = '', $col = null, $val = '')
	{
		$options = db::select("
			select u.username u0, u.realname u1
			from users u, user_guilds ug
			where
				ug.guild_id = 'partner' &&
				u.id = ug.user_id
			order by u1 asc
		");
		return $options;
	}
}
?>
