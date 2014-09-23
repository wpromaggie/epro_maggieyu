<?php

class mod_contracts_packages extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'contracts';
		self::$primary_key = array('id');
		self::$uniques = array(array(;'','',''));

		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,10    ,''		),
			new rs_col('name'                       ,'varchar'               ,64    ,''		),
			new rs_col('service'                    ,'char'                  ,16    ,''		),
			new rs_col('deleted'                    ,'tinyint'               ,1     ,''		)
			);
	}
}
?>
