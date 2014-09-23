<?php

class mod_eppctwo_secondary_manager extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	
	public static $pay_method_options;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('account_id', 'user_id');
		self::$indexes = array(
			array('user_id')
		);
		self::$cols = self::init_cols(
			new rs_col('client_id' ,'bigint',null,0 ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('dept'      ,'char'  ,16  ,'',rs::NOT_NULL),
			new rs_col('account_id','char'  ,16  ,'',rs::NOT_NULL),
			new rs_col('user_id'   ,'int'   ,null,0 ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY)
		);
	}
}


?>
