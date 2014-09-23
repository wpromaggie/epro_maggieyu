<?php

class mod_eppctwo_sb_ad_major extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'bigint'                ,20    ,''		),
			new rs_col('ad_id'                      ,'bigint'                ,20    ,''		),
			new rs_col('major'                      ,'varchar'               ,128   ,''		)
			);
	}
}
?>
