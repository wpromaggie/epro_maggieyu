<?php


class reduce_market_data_x_filter extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	public static function set_table_definition()
	{
		self::$db = 'delly';
		self::$primary_key = array('spec_id', 'account_id');
		self::$indexes = array('account_id');
		self::$cols = self::init_cols(
			new rs_col('filter_id' ,'int' ,null,null,rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('account_id','char',16  ,''  ,rs::READ_ONLY)
		);
	}
}
?>