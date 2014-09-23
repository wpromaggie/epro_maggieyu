<?php

class mod_eac_ap_wd extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'char'                  ,16    ,''		)
			);
	}
}
?>
