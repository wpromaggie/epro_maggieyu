<?php

class mod_eac_pe_event extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$uniques = array(array(;''));

		self::$cols = self::init_cols(
			new rs_col('id'                         ,'char'                  ,8     ,''		),
			new rs_col('event'                      ,'char'                  ,32    ,''		)
			);
	}
}
?>
