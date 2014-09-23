<?php

class mod_eppctwo_companies extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,11    ,''		),
			new rs_col('name'                       ,'varchar'               ,255   ,''		),
			new rs_col('domain'                     ,'varchar'               ,128   ,''		),
			new rs_col('utc_offset'                 ,'tinyint'               ,4     ,''		)
			);
	}
}
?>
