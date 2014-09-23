<?php

class mod_contracts_url_lead_url extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'contracts';
		self::$primary_key = array('id');
		self::$indexes = array(array('url_lead_upload_id'));

		self::$uniques = array(array(;''));

		self::$cols = self::init_cols(
			new rs_col('id'                ,'bigint' ,null,null,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY | rs::AUTO_INCREMENT),
			new rs_col('url_lead_upload_id','int'    ,null,null,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('url'               ,'varchar',128 ,''  ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('is_new'            ,'bool'   ,null,0   ,rs::NOT_NULL | rs::READ_ONLY)
			);
	}
}
?>
