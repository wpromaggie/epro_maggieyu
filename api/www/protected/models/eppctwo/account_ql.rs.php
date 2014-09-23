<?php

class mod_eppctwo_account_ql extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'bigint'                ,20    ,''		),
			new rs_col('is_3_day_done'              ,'tinyint'               ,1     ,''		),
			new rs_col('is_7_day_done'              ,'tinyint'               ,1     ,''		),
			new rs_col('alt_num_keywords'           ,'smallint'              ,5     ,''		),
			new rs_col('last_report_review_date'    ,'date'                  ,''    ,''		),
			new rs_col('do_report'                  ,'tinyint'               ,1     ,''		),
			new rs_col('is_billing_paused'          ,'tinyint'               ,1     ,''		),
			new rs_col('is_billing_failure'         ,'tinyint'               ,1     ,''		)
			);
	}
}
?>
