<?php

class mod_surveys_categories extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'surveys';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,10    ,''		),
			new rs_col('layout_id'                  ,'int'                   ,5     ,''		),
			new rs_col('name'                       ,'varchar'               ,64    ,''		),
			new rs_col('order'                      ,'int'                   ,10    ,''		)
			);
	}
}
?>
