<?php

class mod_billing_checks extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'billing';
		self::$primary_key = array('id');
		self::$uniques = array(array(;'',''));

		self::$cols = self::init_cols(
			new rs_col('id'                         ,'bigint'                ,20    ,null,rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT),
			new rs_col('foreign_table'              ,'varchar'               ,64    ,''		),
			new rs_col('foreign_id'                 ,'varchar'               ,64    ,''		),
			new rs_col('name'                       ,'varchar'               ,128   ,''		),
			new rs_col('phone'                      ,'varchar'               ,32    ,''		),
			new rs_col('account_type'               ,'varchar'               ,16    ,''		),
			new rs_col('account_number'             ,'blob'                  ,''    ,''		),
			new rs_col('routing_number'             ,'blob'                  ,''    ,''		),
			new rs_col('check_number'               ,'varchar'               ,16    ,null	),
			new rs_col('drivers_license'            ,'varchar'               ,16    ,''		),
			new rs_col('drivers_license_state'      ,'varchar'               ,4     ,''		),
			new rs_col('amount'                     ,'int'                   ,10    ,''		)
			);
	}
}
?>
