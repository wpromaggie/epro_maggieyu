<?php

class mod_eppctwo_clients_aff extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('client');
		self::$cols = self::init_cols(
			new rs_col('company'                    ,'int'                   ,11    ,''		),
			new rs_col('client'                     ,'varchar'               ,32    ,''		),
			new rs_col('manager'                    ,'varchar'               ,64    ,''		),
			new rs_col('status'                     ,'char'                  ,16    ,''		),
			new rs_col('url'                        ,'varchar'               ,128   ,''		)
			);
	}
}
?>
