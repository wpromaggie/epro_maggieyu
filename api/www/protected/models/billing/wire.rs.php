<?php


class mod_billing_wire extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'billing';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'            ,'bigint',null,null,rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT)
		);
	}
}


?>