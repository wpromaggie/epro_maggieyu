<?php

class mod_eppctwo_sb_client_files extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array);
		self::$cols = self::init_cols(
			new rs_col('client_id'                  ,'char'                  ,16    ,''		),
			new rs_col('file_id'                    ,'int'                   ,2     ,''		)
			);
	}
}
?>
