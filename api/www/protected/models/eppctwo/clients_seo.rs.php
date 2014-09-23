<?php
class mod_eppctwo_clients_seo extends rs_object
{
	public static $db, $cols, $primary_key, $has_one;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('client');
		self::$has_one = array('clients');
		self::$cols = self::init_cols(
			new rs_col('company'             ,'int'    ,11  ,0     ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('client'              ,'varchar',32  ,''    ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('manager'             ,'varchar',64  ,''    ,rs::NOT_NULL),
			new rs_col('status'              ,'enum'   ,null,'On'  ,rs::NOT_NULL ,array('On', 'Cancelled', 'Incomplete', 'Off')),
			new rs_col('billing_contact_id'  ,'bigint' ,20  ,null  ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('link_builder_manager','varchar',64  ,''    ,rs::NOT_NULL),
			new rs_col('billing_reminder'    ,'bool'   ,null,1     ,rs::NOT_NULL),
			new rs_col('bill_day'            ,'tinyint',null,0     ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('prev_bill_date'      ,'date'   ,null,rs::DD,rs::NOT_NULL),
			new rs_col('next_bill_date'      ,'date'   ,null,rs::DD,rs::NOT_NULL),
			new rs_col('url'                 ,'varchar',128 ,''    ,rs::NOT_NULL)
		);
	}
	
	public static function manager_form_input($table, $col, $val)
	{
		$options = self::manager_options($table, $col, $val);
		return cgi::html_select($table.'_'.$col->name, $options, $val);
	}
	
	public static function link_builder_manager_form_input($table, $col, $val)
	{
		$options = self::manager_options($table, $col, $val);
		return cgi::html_select($table.'_'.$col->name, $options, $val);
	}
	
	private static function manager_options($table, $col, $val)
	{
		$options = db::select("
			select u.username u0, u.realname u1
			from users u, user_guilds ug
			where
				ug.guild_id = 'seo' &&
				u.id = ug.user_id
			order by u1 asc
		");
		array_unshift($options, array('', ' - None - '));
		return $options;
	}
}


?>
