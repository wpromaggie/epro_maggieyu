<?php
/*
 * url lead stuff
 */
class mod_contracts_url_lead extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	public static function set_table_definition()
	{
		self::$db = 'contracts';
		self::$primary_key = array('url');
		self::$indexes = array(array('lead_upload_id'));
		self::$cols = self::init_cols(
			new rs_col('url'               ,'varchar',128 ,''     ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('url_lead_upload_id','int'    ,null,null,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('name'              ,'varchar',80  ,''     ,rs::NOT_NULL),
			new rs_col('email'             ,'varchar',80  ,''     ,rs::NOT_NULL),
			new rs_col('phone'             ,'varchar',256 ,''     ,rs::NOT_NULL)
		);
	}
}
?>
