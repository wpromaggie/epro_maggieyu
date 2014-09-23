<?php

class mod_eppctwo_sb_keywords extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'bigint'                ,20    ,''		),
			new rs_col('ad_id'                      ,'bigint'                ,20    ,''		),
			new rs_col('group_id'                   ,'char'                  ,16    ,''		),
			new rs_col('text'                       ,'varchar'               ,200   ,''		)
			);
	}
}
?>
