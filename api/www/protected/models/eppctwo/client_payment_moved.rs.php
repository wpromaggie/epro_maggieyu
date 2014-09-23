<?php

class mod_eppctwo_client_payment_moved extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(array('client_id'));

		self::$uniques = array(array(;''));

		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,10    ,''		),
			new rs_col('nid'                        ,'char'                  ,16    ,''		),
			new rs_col('client_id'                  ,'bigint'                ,20    ,''		),
			new rs_col('user_id'                    ,'int'                   ,10    ,''		),
			new rs_col('pay_id'                     ,'bigint'                ,20    ,''		),
			new rs_col('pay_method'                 ,'char'                  ,8     ,''		),
			new rs_col('fid'                        ,'varchar'               ,64    ,''		),
			new rs_col('date_received'              ,'date'                  ,''    ,''		),
			new rs_col('date_attributed'            ,'date'                  ,''    ,''		),
			new rs_col('amount'                     ,'double'                ,''    ,''		),
			new rs_col('notes'                      ,'varchar'               ,256   ,''		),
			new rs_col('sales_notes'                ,'varchar'               ,256   ,''		)
			);
	}
}
?>
