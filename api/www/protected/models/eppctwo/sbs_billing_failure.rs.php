<?php

class mod_eppctwo_sbs_billing_failure extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('department', 'account_id');
		self::$cols = self::init_cols(
			new rs_col('department'  ,'enum'   ,null,null  ,0            ,sbs_lib::$departments),
			new rs_col('account_id'  ,'bigint' ,null,0     ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('details'     ,'varchar',256 ,''    ,rs::NOT_NULL),
			new rs_col('first_fail'  ,'date'   ,null,rs::DD,rs::NOT_NULL),
			new rs_col('last_fail'   ,'date'   ,null,rs::DD,rs::NOT_NULL),
			new rs_col('num_fails'   ,'tinyint',null,0     ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('last_contact','date'   ,null,rs::DD,rs::NOT_NULL),
			new rs_col('num_contacts','tinyint',null,0     ,rs::UNSIGNED | rs::NOT_NULL)
		);
	}
}
?>
