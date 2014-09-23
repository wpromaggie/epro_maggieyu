<?php

class mod_eppctwo_tmp extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array);
		self::$cols = self::init_cols(
			new rs_col('thing'                      ,'varchar'               ,8     ,''		),
			new rs_col('dt'                         ,'datetime'              ,''    ,''		),
			new rs_col('a'                          ,'int'                   ,11    ,''		),
			new rs_col('b'                          ,'int'                   ,11    ,''		)
			);
	}
}
?>
