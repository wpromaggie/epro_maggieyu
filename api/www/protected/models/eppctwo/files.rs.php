<?php

class mod_eppctwo_files extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,8     ,''		),
			new rs_col('name'                       ,'varchar'               ,32    ,''		),
			new rs_col('description'                ,'varchar'               ,128   ,''		),
			new rs_col('file_name'                  ,'varchar'               ,64    ,''		),
			new rs_col('date'                       ,'date'                  ,''    ,''		)
			);
	}
}
?>
