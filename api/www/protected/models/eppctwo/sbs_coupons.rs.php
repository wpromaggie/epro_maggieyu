<?php

class mod_eppctwo_sbs_coupons extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array);
		self::$uniques = array(array(;'','',''));

		self::$cols = self::init_cols(
			new rs_col('account_id'                 ,'bigint'                ,20    ,''		),
			new rs_col('department'                 ,'char'                  ,2     ,''		),
			new rs_col('coupon_id'                  ,'int'                   ,10    ,''		)
			);
	}
}
?>
