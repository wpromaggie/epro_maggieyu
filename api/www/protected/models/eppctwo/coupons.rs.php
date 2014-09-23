<?php

class mod_eppctwo_coupons extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
        
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'             ,'int'      ,null    , null  ,rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT),
			new rs_col('code'           ,'varchar'  ,16      ,''   ,0     ,rs::NOT_NULL),
			new rs_col('type'           ,'enum'     ,null    ,null   ,0     ,array('setup fee','first month')),
			new rs_col('value'          ,'double'   ,null    ,0      ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('value_type'     ,'enum'     ,null    ,null   ,0     ,array('percent','dollars')),
            new rs_col('contract_length','int'      ,2       ,0      ,rs::NOT_NULL),
			new rs_col('description'    ,'varchar'  ,256     ,''     ,rs::NOT_NULL),
            new rs_col('status'         ,'enum'     ,null    ,null   ,0     ,array('active','expired'))
		);
	}
}
?>
