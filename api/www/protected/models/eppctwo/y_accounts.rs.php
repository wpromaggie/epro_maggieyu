<?php

class mod_eppctwo_y_accounts extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'varchar'               ,32    ,''		),
			new rs_col('company'                    ,'int'                   ,11    ,''		),
			new rs_col('master_account'             ,'varchar'               ,32    ,''		),
			new rs_col('text'                       ,'varchar'               ,255   ,''		),
			new rs_col('ca_info_mod_time'           ,'datetime'              ,''    ,''		),
			new rs_col('status'                     ,'varchar'               ,16    ,''		),
			new rs_col('market'                     ,'varchar'               ,8     ,''		),
			new rs_col('currency'                   ,'varchar'               ,8     ,''		)
			);
	}
}
?>
