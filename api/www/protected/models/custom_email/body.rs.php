<?php

class body extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'custom_email';
		self::$primary_key = array('email_id');
		self::$cols = self::init_cols(
			new rs_col('email_id'                   ,'varchar'               ,36    ,''		),
			new rs_col('body'                       ,'text'                  ,''    ,''		),
			new rs_col('created'                    ,'timestamp'             ,''    ,''		),
			new rs_col('last_updated'               ,'datetime'              ,''    ,''		)
			);
	}
}
?>
