<?php

class mod_eppctwo_g_company_report_log extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,10    ,''		),
			new rs_col('d'                          ,'date'                  ,''    ,''		),
			new rs_col('account_id'                 ,'char'                  ,16    ,''		),
			new rs_col('details'                    ,'text'                  ,''    ,''		)
			);
	}
}
?>
