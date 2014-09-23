<?php

class mod_eppctwo_sb_ad_location extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$uniques = array(array(;'','',''));

		self::$cols = self::init_cols(
			new rs_col('id'                         ,'bigint'                ,20    ,''		),
			new rs_col('ad_id'                      ,'bigint'                ,20    ,''		),
			new rs_col('city'                       ,'varchar'               ,128   ,''		),
			new rs_col('state'                      ,'varchar'               ,64    ,''		),
			new rs_col('zip'                        ,'varchar'               ,500   ,''		)
			);
	}
}
?>
