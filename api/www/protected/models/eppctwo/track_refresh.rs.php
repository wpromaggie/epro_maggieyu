<?php

class mod_eppctwo_track_refresh extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(array('client'));

		self::$uniques = array(array(;''));

		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,10    ,''		),
			new rs_col('client'                     ,'varchar'               ,32    ,''		),
			new rs_col('job_id'                     ,'bigint'                ,20    ,''		),
			new rs_col('dt'                         ,'datetime'              ,''    ,''		),
			new rs_col('market'                     ,'char'                  ,4     ,''		),
			new rs_col('entity_type'                ,'char'                  ,8     ,''		)
			);
	}
}
?>
