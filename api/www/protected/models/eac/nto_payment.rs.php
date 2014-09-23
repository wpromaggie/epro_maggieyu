<?php
// payments
class mod_eac_nto_payment extends rs_object
{
	public static $db, $cols, $primary_key, $has_many;
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('npid');
		self::$cols = self::init_cols(
			new rs_col('npid','char'  ,16  ,''  ,rs::READ_ONLY),
			new rs_col('opid','bigint',null,null,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY)
		);
	}
}

?>
