<?php

class mod_surveys_client_files extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'surveys';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,10    ,''		),
			new rs_col('client_survey_id'           ,'int'                   ,10    ,''		),
			new rs_col('filename'                   ,'varchar'               ,256   ,''		),
			new rs_col('type'                       ,'varchar'               ,64    ,''		)
			);
	}
}
?>
