<?php

class mod_surveys_layouts extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'surveys';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,10    ,''		),
			new rs_col('ac_type'                    ,'varchar'               ,16    ,''		),
			new rs_col('user_id'                    ,'int'                   ,10    ,''		),
			new rs_col('title'                      ,'varchar'               ,128   ,''		),
			new rs_col('dt'                         ,'datetime'              ,''    ,''		),
			new rs_col('status'                     ,'varchar'               ,16    ,''		)
			);
	}
}
?>
