<?php

class mod_contracts_package_vars extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'contracts';
		self::$primary_key = array('prospect_id','package_id','name');
		self::$cols = self::init_cols(
			new rs_col('prospect_id'                ,'int'                   ,10    ,''		),
			new rs_col('package_id'                 ,'int'                   ,10    ,''		),
			new rs_col('name'                       ,'varchar'               ,32    ,''		),
			new rs_col('description'                ,'varchar'               ,512   ,''		),
			new rs_col('note'                       ,'varchar'               ,256   ,''		),
			new rs_col('type'                       ,'char'                  ,16    ,''		),
			new rs_col('payment_part_type'          ,'varchar'               ,128   ,''		),
			new rs_col('value'                      ,'double'                ,''    ,''		),
			new rs_col('required'                   ,'tinyint'               ,1     ,''		),
			new rs_col('charge'                     ,'tinyint'               ,1     ,''		),
			new rs_col('order_table'                ,'tinyint'               ,1     ,''		),
			new rs_col('row_order'                  ,'int'                   ,2     ,''		),
			new rs_col('discount'                   ,'double'                ,''    ,''		)
			);
	}
}
?>
