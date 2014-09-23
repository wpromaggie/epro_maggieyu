<?php

class mod_eppctwo_sb_settings extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,3     ,''		),
			new rs_col('name'                       ,'varchar'               ,32    ,''		),
			new rs_col('max_bid'                    ,'decimal'               ,3,2   ,''		),
			new rs_col('starter_daily_budget'       ,'decimal'               ,7,2   ,''		),
			new rs_col('core_daily_budget'          ,'decimal'               ,7,2   ,''		),
			new rs_col('premier_daily_budget'       ,'decimal'               ,7,2   ,''		)
			);
	}
}
?>
