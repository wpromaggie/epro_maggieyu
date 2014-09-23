<?php

class mod_eppctwo_client_media extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	// 2^24, 16 MBs
	const MAX_SIZE = 16777216;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('account_id')
		);
		self::$cols = self::init_cols(
			new rs_col('id'       ,'char'      ,8   ,''     ,rs::READ_ONLY),
			new rs_col('account_id','char'      ,16  ,''     ,rs::READ_ONLY),
			new rs_col('user_id'  ,'int'       ,null,0      ,rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('ts'       ,'datetime'  ,null,rs::DDT,rs::READ_ONLY),
			new rs_col('type'     ,'char'      ,16  ,''     ,rs::READ_ONLY),
			new rs_col('name'     ,'char'      ,96  ,''     ,rs::READ_ONLY),
			new rs_col('data'     ,'mediumblob',null,null   ,rs::READ_ONLY),
			new rs_col('w'        ,'int'       ,null,0      ,rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('h'        ,'int'       ,null,0      ,rs::UNSIGNED | rs::READ_ONLY)
		);
	}
	
	protected function uprimary_key($i)
	{
		$sha = sha1(mt_rand());
		return strtoupper(substr($sha, 0, 8));
	}
}

?>
