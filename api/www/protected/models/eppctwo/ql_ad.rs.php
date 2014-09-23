<?php

class mod_eppctwo_ql_ad extends rs_object
{
	public static $db, $cols, $primary_key, $indexes, $uniques;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$uniques = array(
			array('market', 'ad_group_id', 'ad_id')
		);
		self::$indexes = array(
			array('account_id')
		);
		self::$cols = self::init_cols(
			new rs_col('id'         ,'int'    ,null,null,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY | rs::AUTO_INCREMENT),
			new rs_col('market'     ,'char'   ,2   ,''  ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('ad_group_id','char'   ,32  ,''  ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('ad_id'      ,'char'   ,32  ,''  ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('account_id' ,'bigint' ,null,0   ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('is_su'      ,'tinyint',null,0   ,rs::NOT_NULL | rs::UNSIGNED)
		);
	}
}
?>
