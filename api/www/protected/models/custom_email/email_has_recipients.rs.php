<?php

class email_has_recipients extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'custom_email';
		self::$primary_key = array('recipients_id','email_id');
		self::$indexes = array(array('email_id'));

		self::$uniques = array(array(;''));

		self::$cols = self::init_cols(
			new rs_col('recipients_id'              ,'varchar'               ,36    ,''		),
			new rs_col('email_id'                   ,'varchar'               ,36    ,''		),
			new rs_col('role'                       ,'varchar'               ,10    ,''		)
			);
	}
}
?>
