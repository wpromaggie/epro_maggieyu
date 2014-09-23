<?php

class mod_eppctwo_m_accounts extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'varchar'               ,32    ,''		),
			new rs_col('company'                    ,'int'                   ,11    ,''		),
			new rs_col('num'                        ,'varchar'               ,32    ,''		),
			new rs_col('customer_id'                ,'varchar'               ,32    ,''		),
			new rs_col('text'                       ,'varchar'               ,128   ,''		),
			new rs_col('ca_info_mod_time'           ,'datetime'              ,''    ,''		),
			new rs_col('status'                     ,'varchar'               ,16    ,''		),
			new rs_col('currency'                   ,'varchar'               ,32    ,''		),
			new rs_col('user'                       ,'varchar'               ,32    ,''		),
			new rs_col('pass'                       ,'varchar'               ,32    ,''		),
			new rs_col('j_auth'                     ,'text'                  ,''    ,''		),
			new rs_col('last_updated'               ,'datetime'              ,''    ,''		)
			);
	}
}
?>
