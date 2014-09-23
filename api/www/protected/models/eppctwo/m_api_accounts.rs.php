<?php

class mod_eppctwo_m_api_accounts extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('mcc_user','mcc_pass','access_key');
		self::$cols = self::init_cols(
			new rs_col('company'                    ,'int'                   ,11    ,''		),
			new rs_col('mcc_user'                   ,'varchar'               ,64    ,''		),
			new rs_col('mcc_pass'                   ,'varchar'               ,64    ,''		),
			new rs_col('access_key'                 ,'varchar'               ,32    ,''		),
			new rs_col('client_id'                  ,'text'                  ,''    ,''		),
			new rs_col('client_secret'              ,'text'                  ,''    ,''		),
			new rs_col('redirect_uri'               ,'varchar'               ,255   ,''		)
			);
	}
}
?>
