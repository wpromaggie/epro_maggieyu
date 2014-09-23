<?php

class mod_eppctwo_f_ad extends rs_object
{
	public static $db, $cols, $primary_key, $uniques;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id', 'ad_group_id');
		self::$uniques = array(
			array('ad_group_id', 'title', 'desc_1', 'dest_url')
		);
		self::$cols = self::init_cols(
			new rs_col('id'         ,'int'    ,null,null,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('ad_group_id','int'    ,null,null,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('title'      ,'varchar',128 ,''  ,rs::NOT_NULL),
			new rs_col('desc_1'     ,'varchar',255 ,''  ,rs::NOT_NULL),
			new rs_col('dest_url'   ,'varchar',512 ,''  ,rs::NOT_NULL)
		);
	}
}
?>
