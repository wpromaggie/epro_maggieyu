<?php

class mod_eppctwo_sbs_manual_order extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'     ,'bigint'  ,null,null   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('created','datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('mo_key' ,'varchar' ,32  ,''     ,rs::NOT_NULL)
			);
	}
}
?>
