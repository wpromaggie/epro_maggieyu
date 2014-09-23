<?php

class mod_eppctwo_sbs_account_rep extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('users_id');
		self::$cols = self::init_cols(
			new rs_col('users_id'                   ,'int'                   ,11    ,''		),
			new rs_col('name'                       ,'char'                  ,64    ,''		),
			new rs_col('email'                      ,'char'                  ,64    ,''		),
			new rs_col('phone'                      ,'char'                  ,64    ,''		)
			);
	}
}
?>
