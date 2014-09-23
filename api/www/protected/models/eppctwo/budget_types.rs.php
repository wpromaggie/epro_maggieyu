<?php

class mod_eppctwo_budget_types extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('budget_type');
		self::$cols = self::init_cols(
			new rs_col('client_type'                ,'varchar'               ,32    ,''		),
			new rs_col('budget_type'                ,'varchar'               ,32    ,''		)
			);
	}
}
?>
