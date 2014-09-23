<?php

class mod_eppctwo_ppc_cdl extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('account_id');
		self::$cols = self::init_cols(
			new rs_col('account_id'    ,'char'   ,16  ,'',rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('mo_spend'      ,'double' ,null,0 ,rs::NOT_NULL),
			new rs_col('yd_spend'      ,'double' ,null,0 ,rs::NOT_NULL),
			new rs_col('days_to_date'  ,'tinyint',null,0 ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('days_remaining','tinyint',null,0 ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('days_in_month' ,'tinyint',null,0 ,rs::UNSIGNED | rs::NOT_NULL)
		);
	}
}
?>
