<?php

class mod_eppctwo_client_payment_part_moved extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(array('client_id'));

		self::$uniques = array(array(;''));

		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,10    ,''		),
			new rs_col('nid'                        ,'int'                   ,10    ,''		),
			new rs_col('client_payment_id'          ,'int'                   ,11    ,''		),
			new rs_col('client_id'                  ,'bigint'                ,20    ,''		),
			new rs_col('type'                       ,'char'                  ,32    ,''		),
			new rs_col('amount'                     ,'double'                ,''    ,''		),
			new rs_col('rep_pay_num'                ,'tinyint'               ,4     ,''		)
			);
	}
}
?>
