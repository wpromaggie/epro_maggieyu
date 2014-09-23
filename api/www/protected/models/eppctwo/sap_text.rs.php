<?php

class mod_eppctwo_sap_text extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(array('client_id'));

		self::$uniques = array(array(;'','','','','',''));

		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,11    ,''		),
			new rs_col('s1'                         ,'varchar'               ,32    ,''		),
			new rs_col('s2'                         ,'varchar'               ,32    ,''		),
			new rs_col('s3'                         ,'varchar'               ,32    ,''		),
			new rs_col('type'                       ,'varchar'               ,32    ,''		),
			new rs_col('list_order'                 ,'int'                   ,11    ,''		),
			new rs_col('text'                       ,'text'                  ,''    ,''		),
			new rs_col('client_id'                  ,'int'                   ,11    ,''		)
			);
	}
}
?>
