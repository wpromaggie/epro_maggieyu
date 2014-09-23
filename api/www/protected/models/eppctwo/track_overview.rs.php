<?php

class mod_eppctwo_track_overview extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('market','level','level_id');
		self::$indexes = array(array('client'));

		self::$uniques = array(array(;''));

		self::$cols = self::init_cols(
			new rs_col('client'                     ,'varchar'               ,32    ,''		),
			new rs_col('market'                     ,'char'                  ,4     ,''		),
			new rs_col('level'                      ,'char'                  ,8     ,''		),
			new rs_col('level_id'                   ,'varchar'               ,32    ,''		)
			);
	}
}
?>
