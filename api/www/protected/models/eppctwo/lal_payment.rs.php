<?php

class mod_eppctwo_lal_payment extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array(id);
		self::$cols = self::init_cols(
			new rs_col('id'    ,'int'   ,null,null  ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY | rs::AUTO_INCREMENT),
			new rs_col('date'  ,'date'  ,null,rs::DD,rs::NOT_NULL),
			new rs_col('amount','double',null,0     ,rs::NOT_NULL)
		);
	}
}
?>
