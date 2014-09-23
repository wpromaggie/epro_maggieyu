<?php

class mod_eppctwo_ql_zurvita_api_log extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,10    ,''		),
			new rs_col('ip'                         ,'varchar'               ,32    ,''		),
			new rs_col('d'                          ,'date'                  ,''    ,''		),
			new rs_col('t'                          ,'time'                  ,''    ,''		),
			new rs_col('url'                        ,'varchar'               ,256   ,''		),
			new rs_col('request'                    ,'text'                  ,''    ,''		),
			new rs_col('is_processed'               ,'tinyint'               ,4     ,''		)
			);
	}
}
?>
