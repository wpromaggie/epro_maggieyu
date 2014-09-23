<?php


class mod_log_delta_kv extends rs_object
{	
	public static $db, $cols, $primary_key, $has_one, $has_many;

	public static function set_table_definition(){
		self::$db = 'log';
		self::$cols = self::init_cols(
			new rs_col('id'			,'char'		,36		,'' 		,rs::NOT_NULL),
			new rs_col('meta_id'	,'char'		,36		,NULL 		,rs::NOT_NULL),
			new rs_col('field'		,'varchar'	,64 	,rs::DDT 	,rs::NOT_NULL),
			new rs_col('value'		,'varchar'	,255	,NULL 		,rs::NOT_NULL)
		);
		self::$primary_key = array('id');	
	}

	protected function uprimary_key($i){
		return util::mt_rand_uuid();
	}
}
?>