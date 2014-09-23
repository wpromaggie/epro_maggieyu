<?php

class mod_eppctwo_user_session extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'char'                  ,128   ,''		),
			new rs_col('data'                       ,'text'                  ,''    ,''		),
			new rs_col('created'                    ,'datetime'              ,''    ,''		)
			);
	}
}
?>
