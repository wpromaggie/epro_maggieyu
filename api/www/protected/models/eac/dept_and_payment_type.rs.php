<?php
class mod_eac_dept_and_payment_type extends mod_eac_multi_col_enum
{
	public static $db, $cols, $primary_key, $uniques;
	
	public static $enum_cols = array('dept', 'type');
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$uniques = array(self::$enum_cols);
		self::$cols = self::init_cols(self::$enum_cols);
	}
}
?>
