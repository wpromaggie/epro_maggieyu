<?php

class mod_eppctwo_ad_refresh_log extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'          ,'int'     ,null,null   ,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('client_id'   ,'bigint'  ,null,0      ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('user_id'     ,'int'     ,null,0      ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('market'      ,'char'    ,8   ,''     ,rs::NOT_NULL),
			new rs_col('process_dt'  ,'datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('start_date'  ,'date'    ,null,rs::DD ,rs::NOT_NULL),
			new rs_col('end_date'    ,'date'    ,null,rs::DD ,rs::NOT_NULL)
		);
	}
}

?>
