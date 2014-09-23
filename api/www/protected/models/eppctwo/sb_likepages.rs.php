<?php

class mod_eppctwo_sb_likepages extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'bigint'                ,20    ,''		),
			new rs_col('group_id'                   ,'char'                  ,16    ,''		),
			new rs_col('url'                        ,'varchar'               ,256   ,''		),
			new rs_col('template'                   ,'varchar'               ,128   ,''		),
			new rs_col('business_type'              ,'varchar'               ,128   ,''		),
			new rs_col('address'                    ,'varchar'               ,128   ,''		),
			new rs_col('city'                       ,'varchar'               ,128   ,''		),
			new rs_col('zip'                        ,'varchar'               ,16    ,''		),
			new rs_col('phone'                      ,'varchar'               ,16    ,''		),
			new rs_col('hour_open'                  ,'time'                  ,''    ,''		),
			new rs_col('hour_closed'                ,'time'                  ,''    ,''		),
			new rs_col('details'                    ,'text'                  ,''    ,''		)
			);
	}
}
?>
