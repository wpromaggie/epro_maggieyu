<?php

class mod_eppctwo_account_sbs extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'bigint'                ,20    ,''		),
			new rs_col('oid'                        ,'varchar'               ,32    ,''		),
			new rs_col('sales_rep'                  ,'int'                   ,11    ,''		),
			new rs_col('partner'                    ,'varchar'               ,64    ,''		),
			new rs_col('source'                     ,'varchar'               ,64    ,''		),
			new rs_col('partner_data'               ,'varchar'               ,128   ,''		),
			new rs_col('multi_month_bill_date'      ,'date'                  ,''    ,''		),
			new rs_col('alt_recur_amount'           ,'double unsigned'       ,''    ,''		)
			);
	}
}
?>
