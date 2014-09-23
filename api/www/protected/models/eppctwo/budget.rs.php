<?php

class mod_eppctwo_budget extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(array('client'));

		self::$uniques = array(array(;''));

		self::$cols = self::init_cols(
			new rs_col('id'                         ,'bigint'                ,20    ,''		),
			new rs_col('payment_id'                 ,'bigint'                ,20    ,''		),
			new rs_col('client'                     ,'varchar'               ,32    ,''		),
			new rs_col('client_type'                ,'varchar'               ,32    ,''		),
			new rs_col('budget_type'                ,'varchar'               ,32    ,''		),
			new rs_col('amount'                     ,'double'                ,''    ,''		)
			);
	}
}
?>
