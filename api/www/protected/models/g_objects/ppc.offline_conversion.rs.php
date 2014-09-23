<?php


//util::load_lib('data_cache');

class offline_conversion extends rs_object
{
	public static $db, $cols, $primary_key, $indexes,$unique;
	public static $object_key, $test_key;

	public static function set_table_definition(){
		//self::$db = 'g_objects';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('gclid','conversion_name')
		);
		self::$cols = self::init_cols(
			new rs_col('id'					,'char'			,36			,''			,rs::NOT_NULL),
			new rs_col('gclid'				,'varchar'		,120		,''			,rs::NOT_NULL),
			new rs_col('utm'				,'text'			,NULL 		),
			new rs_col('action'				,'char'			,10			,'ADD'		,rs::NOT_NULL),
			new rs_col('conversion_name'	,'varchar'		,100		,''			),
			new rs_col('conversion_value'	,'int'			,11			,0			,rs::NOT_NULL),
			new rs_col('conversion_time'	,'varchar'		,120		,0			,rs::NOT_NULL),
			new rs_col('sent'				,'datetime'		,NULL		,rs::DDT	,rs::NOT_NULL),
			new rs_col('success'			,'tinyint'		,1			,0			,rs::NOT_NULL),
			new rs_col('user_defined'		,'text'			,NULL		),
			new rs_col('error_reason'		,'varchar'		,100 		,''			)
		);		
	}

}

?>