<?php

class mod_eppctwo_y_api_accounts extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('mcc_user','mcc_pass','license_key');
		self::$cols = self::init_cols(
			new rs_col('company'                    ,'int'                   ,11    ,''		),
			new rs_col('mcc_user'                   ,'varchar'               ,64    ,''		),
			new rs_col('mcc_pass'                   ,'varchar'               ,64    ,''		),
			new rs_col('license_key'                ,'varchar'               ,32    ,''		)
			);
	}
}
?>
