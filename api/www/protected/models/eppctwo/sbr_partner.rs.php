<?php

class mod_eppctwo_sbr_partner extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$has_many = array('sbr_source');
		self::$cols = self::init_cols(
			new rs_col('id'     ,'varchar',64  ,''  ,rs::NOT_NULL),
			new rs_col('status' ,'enum'   ,null,'On',rs::NOT_NULL,array('On', 'Off'))
		);
	}
}
?>
