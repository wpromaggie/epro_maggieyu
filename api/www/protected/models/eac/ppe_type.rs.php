<?php

class mod_eac_ppe_type extends mod_eac_payment_enum
{
	public static $db, $cols, $primary_key, $uniques;
	
	public static $enum_col = 'type';
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$uniques = array(
			array(self::$enum_col)
		);
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'char'                  ,8     ,''		),
			new rs_col('type'                       ,'char'                  ,32    ,''		)
		);
	}
}

?>
