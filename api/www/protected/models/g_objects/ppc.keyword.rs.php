<?php

class keyword extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	
	public static function set_table_definition()
	{
		self::$primary_key = array('ad_group_id','id');
		self::$indexes = array(
			'ca_index' => array('campaign_id')
		);
		self::$cols = self::init_cols(
			new rs_col('account_id'    ,'char'   ,32  ,''          ,rs::NOT_NULL),
			new rs_col('campaign_id'   ,'char'   ,32  ,''          ,rs::NOT_NULL),
			new rs_col('ad_group_id'   ,'char'   ,32  ,''          ,rs::NOT_NULL),
			new rs_col('id'            ,'char'   ,32  ,''          ,rs::NOT_NULL),
			new rs_col('mod_date'      ,'date'   ,null,'0000-00-00',rs::NOT_NULL),
			new rs_col('status'        ,'char'   ,16  ,''          ,rs::NOT_NULL),
			new rs_col('text'          ,'char'   ,128 ,''          ,rs::NOT_NULL),
			new rs_col('type'          ,'char'   ,8   ,''          ,rs::NOT_NULL),
			new rs_col('max_cpc'       ,'double' ,null,'0'         ,rs::NOT_NULL),
			new rs_col('dest_url'      ,'varchar',1024,''          ,rs::NOT_NULL),
			new rs_col('first_page_cpc','double' ,null,'0'         ,rs::NOT_NULL),
			new rs_col('quality_score' ,'tinyint',3   ,'0'         ,rs::UNSIGNED | rs::NOT_NULL)
		);
	}
}
?>