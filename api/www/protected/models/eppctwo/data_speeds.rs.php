<?php

class mod_eppctwo_data_speeds extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,11    ,''		),
			new rs_col('speed'                      ,'double'                ,''    ,''		)
			);
	}
}
?>
