<?php

class mod_eppctwo_data_sources extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$cols = self::init_cols(
			new rs_col('account_id','char'   ,16,'',rs::NOT_NULL),
			new rs_col('market'    ,'varchar',4 ,'',rs::NOT_NULL),
			new rs_col('account'   ,'varchar',32,'',rs::NOT_NULL),
			new rs_col('campaign'  ,'varchar',32,'',rs::NOT_NULL),
			new rs_col('ad_group'  ,'varchar',32,'',rs::NOT_NULL)
		);
		self::$primary_key = array('market','account','campaign','ad_group');
	}
}
?>
