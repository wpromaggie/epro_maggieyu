<?php

class mod_eppctwo_sales_hierarch extends rs_object
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id' ,'bigint',null,null,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY | rs::AUTO_INCREMENT),
			new rs_col('pid','int'   ,null,0   ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('cid','int'   ,null,0   ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY)
		);
	}
}
?>
