<?php

class mod_surveys_questions extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'surveys';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,10    ,''		),
			new rs_col('parent_id'                  ,'int'                   ,10    ,''		),
			new rs_col('category_id'                ,'int'                   ,10    ,''		),
			new rs_col('order'                      ,'int'                   ,10    ,''		),
			new rs_col('type'                       ,'varchar'               ,32    ,''		),
			new rs_col('text'                       ,'text'                  ,''    ,''		)
			);
	}
}
?>
