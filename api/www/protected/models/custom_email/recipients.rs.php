<?php

class recipients extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'custom_email';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'varchar'               ,36    ,''		),
			new rs_col('email'                      ,'varchar'               ,100   ,''		),
			new rs_col('first_name'                 ,'varchar'               ,60    ,''		),
			new rs_col('last_name'                  ,'varchar'               ,60    ,''		),
			new rs_col('title'                      ,'varchar'               ,10    ,''		)
			);
	}
}
?>
