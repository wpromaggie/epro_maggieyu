<?php

class mod_eppctwo_sales_client_info extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	
	public static $types = array(
		'Inbound',
		'Outbound',
		'SPL'
	);
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('account_id');
		self::$indexes = array(
			array('client_id')
		);
		self::$cols = self::init_cols(
			new rs_col('client_id' ,'char',16  ,''       ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('account_id','char',16  ,''       ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('sales_rep' ,'int' ,null,null     ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('type'      ,'enum',null,'Inbound',0,self::$types)
		);
	}
	
	public static function sales_rep_form_input($table, $col, $val)
	{
		$options = db::select("
			select u.id u0, u.realname u1
			from users u
			order by u1 asc
		");
		array_unshift($options, array('', ' - Select - '));
		return cgi::html_select($table.'_'.$col->name, $options, $val);
	}
}

?>
