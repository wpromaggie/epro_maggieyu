<?php

class mod_eppctwo_exchange_rates extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('d','currency');
		self::$cols = self::init_cols(
			new rs_col('d'                          ,'date'                  ,''    ,''		),
			new rs_col('currency'                   ,'varchar'               ,8     ,''		),
			new rs_col('rate'                       ,'double'                ,''    ,''		)
			);
	}
}
?>
