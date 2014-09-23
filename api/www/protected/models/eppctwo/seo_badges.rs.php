<?php

class mod_eppctwo_seo_badges extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,5     ,''		),
			new rs_col('filename'                   ,'varchar'               ,256   ,''		),
			new rs_col('alt_text'                   ,'varchar'               ,256   ,''		),
			new rs_col('url'                        ,'varchar'               ,256   ,''		),
			new rs_col('title'                      ,'varchar'               ,256   ,''		)
			);
	}
}
?>
