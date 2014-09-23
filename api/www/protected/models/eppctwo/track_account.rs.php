<?php

class mod_eppctwo_track_account extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('client_id');
		self::$cols = self::init_cols(
			new rs_col('client_id'           ,'varchar',32  ,''    , rs::NOT_NULL),
			new rs_col('external_id'         ,'varchar',32  ,''    , rs::NOT_NULL),
			new rs_col('default_url'         ,'varchar',128 ,''    , rs::NOT_NULL),
			new rs_col('g_start_date'        ,'date'   ,null,rs::DD, rs::NOT_NULL),
			new rs_col('y_start_date'        ,'date'   ,null,rs::DD, rs::NOT_NULL),
			new rs_col('m_start_date'        ,'date'   ,null,rs::DD, rs::NOT_NULL),
			new rs_col('f_start_date'        ,'date'   ,null,rs::DD, rs::NOT_NULL),
			new rs_col('analytics_type'      ,'enum'   ,null,''    , rs::NOT_NULL, array('', 'Simple')),
			new rs_col('track_non_wpro_convs','bool'   ,null,'TRUE', rs::NOT_NULL)
		);
	}
}
?>
