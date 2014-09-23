<?php

class mod_eppctwo_time_temp extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(array('user_id','date'));

		self::$uniques = array(array(;'',''));

		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,10    ,''		),
			new rs_col('user_id'                    ,'int'                   ,11    ,''		),
			new rs_col('date'                       ,'date'                  ,''    ,''		),
			new rs_col('total'                      ,'int'                   ,11    ,''		),
			new rs_col('clock_in'                   ,'datetime'              ,''    ,''		),
			new rs_col('clock_out'                  ,'datetime'              ,''    ,''		),
			new rs_col('type'                       ,'char'                  ,16    ,''		),
			new rs_col('note'                       ,'varchar'               ,500   ,''		)
			);
	}
}
?>
