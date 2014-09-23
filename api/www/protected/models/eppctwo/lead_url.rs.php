<?php

class mod_eppctwo_lead_url extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(array('lead_upload_id'));

		self::$uniques = array(array(;''));

		self::$cols = self::init_cols(
			new rs_col('id'                         ,'bigint'                ,20    ,''		),
			new rs_col('lead_upload_id'             ,'int'                   ,10    ,''		),
			new rs_col('url'                        ,'varchar'               ,128   ,''		),
			new rs_col('is_new'                     ,'tinyint'               ,1     ,''		)
			);
	}
}
?>
