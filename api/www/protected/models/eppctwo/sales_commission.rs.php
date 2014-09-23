<?php

class mod_eppctwo_sales_commission extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('dept', 'sale_type');
		self::$cols = self::init_cols(
			new rs_col('dept'    ,'varchar',8   ,''  ,rs::NOT_NULL),
			new rs_col('com_type','enum'   ,null,null,rs::NO_ATTRS,sales_client_info::$types),
			new rs_col('percent' ,'double' ,null,0.0 ,rs::UNSIGNED | rs::NOT_NULL)
		);
	}
}
?>
