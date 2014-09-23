<?php
// val can be long so type is varchar
// we have an auto inc pk and delete when refreshing data
class extension_data extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	
	public static function set_table_definition()
	{
		self::$primary_key = array('id');
		self::$indexes = array(
			array('data_date', 'type', 'campaign_id')
		);
		self::$cols = self::init_cols(
			new rs_col('id'         ,'bigint'   ,20  ,null  ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY | rs::AUTO_INCREMENT),
			new rs_col('account_id' ,'char'     ,32  ,''    ,rs::NOT_NULL),
			new rs_col('campaign_id','char'     ,32  ,''    ,rs::NOT_NULL),
			new rs_col('type'       ,'char'     ,64  ,''    ,rs::NOT_NULL),
			new rs_col('extension'  ,'varchar'  ,1024,''    ,rs::NOT_NULL),
			new rs_col('data_date'  ,'date'     ,null,rs::DD,rs::NOT_NULL),
			new rs_col('imps'       ,'int'      ,10  ,'0'   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('clicks'     ,'mediumint',8   ,'0'   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('convs'      ,'smallint' ,5   ,'0'   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('cost'       ,'double'   ,null,'0'   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('pos_sum'    ,'int'      ,10  ,'0'   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('revenue'    ,'double'   ,null,'0'   ,rs::NOT_NULL),
			new rs_col('mpc_convs'  ,'smallint' ,5   ,'0'   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('vt_convs'   ,'smallint' ,5   ,'0'   ,rs::UNSIGNED | rs::NOT_NULL)
		);
	}
}
?>