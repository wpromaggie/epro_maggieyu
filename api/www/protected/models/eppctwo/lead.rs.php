<?php

class mod_eppctwo_lead extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('url');
		self::$indexes = array(array('lead_upload_id'));

		self::$uniques = array(array(;''));

		self::$cols = self::init_cols(
			new rs_col('url'                        ,'varchar'               ,128   ,''		),
			new rs_col('lead_upload_id'             ,'int'                   ,10    ,''		),
			new rs_col('name'                       ,'varchar'               ,80    ,''		),
			new rs_col('email'                      ,'varchar'               ,80    ,''		),
			new rs_col('phone'                      ,'varchar'               ,256   ,''		)
			);
	}
}
?>
