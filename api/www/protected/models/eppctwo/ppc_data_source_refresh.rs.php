<?php

class mod_eppctwo_ppc_data_source_refresh extends rs_object
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'          ,'int'    ,null,null  ,rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT),
			new rs_col('account_id'  ,'char'   ,32  ,''    ,rs::NOT_NULL),
			new rs_col('refresh_type','enum'   ,null,''    ,rs::NOT_NULL, array('', 'local', 'remote', 'convs')),
			new rs_col('do_force'    ,'bool'   ,null,0     ,rs::NOT_NULL),
			new rs_col('market'      ,'varchar',4   ,''    ,rs::NOT_NULL),
			new rs_col('start_date'  ,'date'   ,null,rs::DD,rs::NOT_NULL),
			new rs_col('end_date'    ,'date'   ,null,rs::DD,rs::NOT_NULL)
		);
	}
}
?>
