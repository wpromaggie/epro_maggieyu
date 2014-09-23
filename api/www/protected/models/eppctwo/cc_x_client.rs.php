<?php

class mod_eppctwo_cc_x_client extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('cc_id', 'client_id');
		self::$cols = self::init_cols(
			new rs_col('cc_id'    ,'bigint',null,0 , rs::READ_ONLY),
			new rs_col('client_id','char'  ,16  ,'', rs::READ_ONLY)
		);
	}
}
?>
