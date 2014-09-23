<?php

class mod_eppctwo_track_refresh_ids extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array);
		self::$indexes = array(array('track_refresh_id'));

		self::$uniques = array(array(;''));

		self::$cols = self::init_cols(
			new rs_col('track_refresh_id'           ,'int'                   ,10    ,''		),
			new rs_col('id'                         ,'varchar'               ,32    ,''		)
			);
	}
}
?>
