<?php

class mod_eac_as_email extends mod_eac_service
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                   ,'char'    ,16  ,''    ,rs::READ_ONLY)
		);
	}
}
?>
