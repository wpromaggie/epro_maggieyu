<?php

class mod_eppctwo_sb_languages extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array);
		self::$cols = self::init_cols(
			new rs_col('code'                       ,'varchar'               ,5     ,''		),
			new rs_col('name'                       ,'varchar'               ,32    ,''		)
			);
	}
}
?>
