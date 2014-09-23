<?php

class mod_eppctwo_ppc_report_sheet extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('report_id')
		);
		self::$cols = self::init_cols(
			new rs_col('report_id','char'  	 ,36  ,null   ,rs::NOT_NULL),
			new rs_col('id'       ,'char'    ,6   ,''     ,rs::READ_ONLY),
			new rs_col('name'     ,'char'    ,100 ,''     ,rs::READ_ONLY),
			new rs_col('position' ,'int'     ,null,0      ,rs::UNSIGNED)
		);
	}
	
	protected function uprimary_key($i)
	{
		$sha = sha1(mt_rand());
		return strtoupper(substr($sha, 0, 6));
	}
}
?>
