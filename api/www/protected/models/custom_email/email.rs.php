<?php

class email extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'custom_email';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'varchar'               ,36    ,''		),
			new rs_col('format_id'                  ,'varchar'               ,100   ,''		),
			new rs_col('subject'                    ,'varchar'               ,60    ,''		),
			new rs_col('body_type'                  ,'varchar'               ,10    ,''		),
			new rs_col('created'                    ,'timestamp'             ,''    ,''		),
			new rs_col('last_updated'               ,'datetime'              ,''    ,''		)
			);
	}
}
?>
