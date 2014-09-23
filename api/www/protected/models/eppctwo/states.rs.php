<?php

class mod_eppctwo_states extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('short');
		self::$cols = self::init_cols(
			new rs_col('short'                      ,'varchar'               ,4     ,''		),
			new rs_col('text'                       ,'varchar'               ,64    ,''		)
			);
	}
}
?>
