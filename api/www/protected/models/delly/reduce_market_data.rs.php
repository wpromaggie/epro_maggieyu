<?php

class mod_delly_reduced_market_data extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	public static function set_table_definition()
	{
		self::$db = 'delly';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('job_id')
		);
		self::$cols = self::init_cols(
			new rs_col('id'    ,'int' ,null        ,null,rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('job_id','char',job::$id_len,''  ,rs::NOT_NULL),
			new rs_col('i'     ,'int' ,null        ,0   ,rs::UNSIGNED),
			new rs_col('data'  ,'text',null        ,null)
		);
	}
}

?>