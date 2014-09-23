<?php

class mod_eppctwo_market_data_status extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('market','d');
		self::$cols = self::init_cols(
			new rs_col('market'                     ,'char'                  ,2     ,''		),
			new rs_col('d'                          ,'date'                  ,''    ,''		),
			new rs_col('t'                          ,'time'                  ,''    ,''		),
			new rs_col('status'                     ,'varchar'               ,64    ,''		)
			);
	}
}
?>
