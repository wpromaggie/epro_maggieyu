<?php

class pe_event extends payment_enum
{
	public static $db, $cols, $primary_key, $uniques;
	
	public static $enum_col = 'event';
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$uniques = array(
			array(self::$enum_col)
		);
		self::$cols = self::init_cols(self::$enum_col);
	}
}

?>