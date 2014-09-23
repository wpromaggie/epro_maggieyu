<?php

class mod_surveys_client_surveys extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'surveys';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,10    ,''		),
			new rs_col('urlkey'                     ,'varchar'               ,64    ,''		),
			new rs_col('layout_id'                  ,'int'                   ,5     ,''		),
			new rs_col('status'                     ,'varchar'               ,32    ,''		),
			new rs_col('client_id'                  ,'int'                   ,10    ,''		),
			new rs_col('last_mod'                   ,'datetime'              ,''    ,''		),
			new rs_col('user_id'                    ,'int'                   ,10    ,''		)
			);
	}
}
?>
