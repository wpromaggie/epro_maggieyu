<?php

class mod_eppctwo_track_browse_keyword extends rs_object
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$cols = self::init_cols(
			new rs_col('ad_group_id', 'varchar', 32 , '', rs::NOT_NULL),
			new rs_col('keyword_id' , 'varchar', 32 , '', rs::NOT_NULL),
			new rs_col('dest_url'   , 'varchar', 500, '', rs::NOT_NULL)
		);
		self::$primary_key = array('ad_group_id', 'keyword_id');
	}
}
?>
