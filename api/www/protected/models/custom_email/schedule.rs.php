<?php

class schedule extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'custom_email';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,11    ,''		),
			new rs_col('type'                       ,'varchar'               ,60    ,''		),
			new rs_col('next_runtime'               ,'datetime'              ,''    ,''		),
			new rs_col('frequency'                  ,'int'                   ,11    ,''		)
			);
	}
}
?>
