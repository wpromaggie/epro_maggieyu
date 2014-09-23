<?php

class mod_eppctwo_affiliate_networks extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$uniques = array(array(;''));

		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,11    ,''		),
			new rs_col('name'                       ,'varchar'               ,255   ,''		),
			new rs_col('nickname'                   ,'varchar'               ,64    ,''		)
			);
	}
}
?>
