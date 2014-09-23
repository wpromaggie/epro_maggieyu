<?php
// conv types might have differently spelled names in different markets but actually
// represent the same action
class mod_eppctwo_conv_type_market extends rs_object
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('conv_type_id', 'market');
		self::$cols = self::init_cols(
			new rs_col('conv_type_id','char',8,'',rs::READ_ONLY),
			new rs_col('market'      ,'char',2 ,'',rs::NOT_NULL),
			new rs_col('market_name' ,'char',64,'',rs::NOT_NULL)
		);
	}
}
?>
