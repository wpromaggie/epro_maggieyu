<?php

class mod_eppctwo_client_media_use extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	public static $use_options = array('PPC Report Logo');

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('client_media_id')
		);
		self::$cols = self::init_cols(
			new rs_col('id'             ,'char'      ,10,''     ,rs::READ_ONLY),
			new rs_col('client_media_id','char'      ,8 ,''     ,rs::READ_ONLY),
			new rs_col('use'            ,'char'      ,64,''     ,rs::READ_ONLY)
		);
	}
	
	protected function uprimary_key($i)
	{
		$sha = sha1(mt_rand());
		return strtoupper(substr($sha, 0, 10));
	}
}
?>
