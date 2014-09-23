<?php

class mod_eppctwo_client_type_defs extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('company','type');
		self::$uniques = array(array(;'',''));

		self::$cols = self::init_cols(
			new rs_col('company'                    ,'int'                   ,11    ,''		),
			new rs_col('type'                       ,'varchar'               ,32    ,''		),
			new rs_col('type_short'                 ,'varchar'               ,32    ,''		)
			);
	}
}
?>
