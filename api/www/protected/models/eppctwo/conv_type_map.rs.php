<?php

class mod_eppctwo_conv_type_map extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(array('client'));

		self::$uniques = array(array(;''));

		self::$cols = self::init_cols(
			new rs_col('id'                         ,'char'                  ,8     ,''		),
			new rs_col('client'                     ,'char'                  ,16    ,''		),
			new rs_col('market'                     ,'char'                  ,2     ,''		),
			new rs_col('canonical'                  ,'char'                  ,64    ,''		),
			new rs_col('market_name'                ,'char'                  ,64    ,''		)
			);
	}
}
?>
