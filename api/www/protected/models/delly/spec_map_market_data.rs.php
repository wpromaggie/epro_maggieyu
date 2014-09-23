<?php
class mod_delly_spec_map_market_data extends job_spec
{
	public static $db, $cols, $primary_key, $indexes;

	public static function set_table_definition()
	{
		self::$db = 'delly';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('parent_job_id')
		);
		self::$cols = self::init_cols(
			new rs_col('id'           ,'int'       ,null        ,null,rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('parent_job_id','char'      ,job::$id_len,''  ,rs::NOT_NULL),
			new rs_col('market'       ,'char'      ,8           ,''  ,rs::NOT_NULL),
			new rs_col('ad_group_id'  ,'char'      ,32          ,''  ,rs::NOT_NULL),
			new rs_col('data'         ,'mediumtext',null        ,null)
		);
	}
}
?>