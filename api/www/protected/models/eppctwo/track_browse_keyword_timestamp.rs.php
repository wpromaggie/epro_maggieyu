<?php

class mod_eppctwo_track_browse_keyword_timestamp extends rs_object
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$cols = self::init_cols(
			new rs_col('ad_group_id', 'varchar' , 32  , ''                   , rs::NOT_NULL),
			new rs_col('t'          , 'datetime', null, '0000-00-00 00:00:00', rs::NOT_NULL)
		);
		self::$primary_key = array('ad_group_id');
	}
}
?>
