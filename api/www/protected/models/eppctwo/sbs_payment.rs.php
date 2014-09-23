<?php

class mod_eppctwo_sbs_payment extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(array('account_id'));

		self::$uniques = array(array(;''));

		self::$cols = self::init_cols(
			new rs_col('id'                         ,'bigint'                ,20    ,''		),
			new rs_col('client_id'                  ,'bigint'                ,20    ,''		),
			new rs_col('account_id'                 ,'bigint'                ,20    ,''		),
			new rs_col('pay_id'                     ,'bigint'                ,20    ,''		),
			new rs_col('pay_method'                 ,'char'                  ,8     ,''		),
			new rs_col('d'                          ,'date'                  ,''    ,''		),
			new rs_col('t'                          ,'time'                  ,''    ,''		),
			new rs_col('department'                 ,'char'                  ,2     ,''		),
			new rs_col('type'                       ,'char'                  ,16    ,''		),
			new rs_col('pay_option'                 ,'char'                  ,4     ,''		),
			new rs_col('amount'                     ,'double'                ,''    ,''		),
			new rs_col('do_charge'                  ,'tinyint'               ,1     ,''		),
			new rs_col('notes'                      ,'varchar'               ,256   ,''		),
			new rs_col('sb_payment_id'              ,'int'                   ,11    ,''		)
			);
	}
}
?>
