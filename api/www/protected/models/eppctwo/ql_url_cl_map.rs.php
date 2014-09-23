<?php

class mod_eppctwo_ql_url_cl_map extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('client','url');
		self::$cols = self::init_cols(
			new rs_col('client'                     ,'varchar'               ,32    ,''		),
			new rs_col('url'                        ,'varchar'               ,32    ,''		)
			);
	}
}
?>
