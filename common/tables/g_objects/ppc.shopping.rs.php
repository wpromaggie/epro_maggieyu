<?php
util::load_lib('data_cache');
class shopping extends rs_object
{
	public static $db, $cols, $primary_key, $indexes, $unique;
	public static $object_key, $test_key;

	public static function set_table_definition(){
		//self::$db = 'g_objects';
		self::$indexes = array(
			array('campaign_id','ad_group_id','data_date')
		);
		self::$cols = self::init_cols(
			new rs_col('job_id'				,'char'			,24			,''				,rs::NOT_NULL),
			new rs_col('account_id'			,'char'			,32			,''				,rs::NOT_NULL),
			new rs_col('campaign_id'		,'char'			,32			,''				,rs::NOT_NULL),
			new rs_col('ad_group_id'		,'char'			,32			,''				,rs::NOT_NULL),
			new rs_col('data_date'  		,'date'     	,null 		,rs::DD 		,rs::NOT_NULL),
			new rs_col('type'				,'varchar'		,100 		,''				),
			new rs_col('brand'				,'varchar' 		,100		,''				,rs::NOT_NULL),
			new rs_col('imps'       		,'int'      	,10  		,'0'   			,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('clicks'     		,'mediumint'	,8   		,'0'   			,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('convs'      		,'smallint' 	,5   		,'0'   			,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('mpc_convs'     		,'smallint' 	,5   		,'0'   			,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('cost'       		,'double'  		,null 		,'0'   			,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('revenue'    		,'double'   	,null 		,'0'   			,rs::NOT_NULL)
		);		
	}

}

?>