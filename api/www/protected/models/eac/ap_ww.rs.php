<?php
class mod_eac_ap_ww extends mod_eac_product
{
	public static $db, $cols, $primary_key;
	
	public static $plan_options = array('None', '3_Page', '5_Page');
	
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