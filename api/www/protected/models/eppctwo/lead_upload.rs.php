<?php

class mod_eppctwo_lead_upload extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,10    ,''		),
			new rs_col('created'                    ,'datetime'              ,''    ,''		),
			new rs_col('name'                       ,'varchar'               ,128   ,''		)
			);
	}
}
?>
