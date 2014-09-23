<?php

class mod_eppctwo_email_tracking extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$uniques = array(array(;''));

		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,10    ,''		),
			new rs_col('email'                      ,'varchar'               ,128   ,''		),
			new rs_col('src'                        ,'varchar'               ,128   ,''		),
			new rs_col('unsubed'                    ,'tinyint'               ,1     ,''		),
			new rs_col('unsub_date'                 ,'datetime'              ,''    ,''		),
			new rs_col('created'                    ,'datetime'              ,''    ,''		)
			);
	}
}
?>
