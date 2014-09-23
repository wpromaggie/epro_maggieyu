<?php

class mod_eppctwo_time_sheets extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'bigint'                ,20    ,''		),
			new rs_col('user'                       ,'int'                   ,10    ,''		),
			new rs_col('clock_in'                   ,'datetime'              ,''    ,''		),
			new rs_col('clock_out'                  ,'datetime'              ,''    ,''		),
			new rs_col('notes'                      ,'varchar'               ,500   ,''		),
			new rs_col('flex'                       ,'tinyint'               ,3     ,''		)
			);
	}
}
?>
