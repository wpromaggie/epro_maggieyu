<?php

class mod_eppctwo_m_data_error_log extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,10    ,''		),
			new rs_col('d'                          ,'date'                  ,''    ,''		),
			new rs_col('account_id'                 ,'char'                  ,16    ,''		),
			new rs_col('details'                    ,'char'                  ,255   ,''		),
			new rs_col('first_attempt'              ,'datetime'              ,''    ,''		),
			new rs_col('last_attempt'               ,'datetime'              ,''    ,''		),
			new rs_col('num_attempts'               ,'tinyint'               ,3     ,''		),
			new rs_col('success'                    ,'tinyint'               ,1     ,''		)
			);
	}
}
?>
