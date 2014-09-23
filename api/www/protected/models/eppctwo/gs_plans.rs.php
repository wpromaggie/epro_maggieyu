<?php

class mod_eppctwo_gs_plans extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('name');
		self::$cols = self::init_cols(
			new rs_col('name'                       ,'varchar'               ,32    ,''		),
			new rs_col('budget'                     ,'double'                ,''    ,''		),
			new rs_col('num_keywords'               ,'smallint'              ,5     ,''		)
			);
	}
}
?>
