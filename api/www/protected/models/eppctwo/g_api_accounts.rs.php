<?php

class mod_eppctwo_g_api_accounts extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('mcc_user','mcc_pass','dev_token','app_token');
		self::$cols = self::init_cols(
			new rs_col('company'                    ,'int'                   ,11    ,''		),
			new rs_col('mcc_user'                   ,'varchar'               ,64    ,''		),
			new rs_col('mcc_pass'                   ,'varchar'               ,64    ,''		),
			new rs_col('dev_token'                  ,'varchar'               ,32    ,''		),
			new rs_col('app_token'                  ,'varchar'               ,32    ,''		)
			);
	}
}
?>
