<?php

class mod_eppctwo_user_access extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('user','access');
		self::$cols = self::init_cols(
			new rs_col('user'                       ,'int'                   ,10    ,''		),
			new rs_col('access'                     ,'varchar'               ,64    ,''		)
			);
	}
}
?>
