<?php

class mod_eppctwo_clients_ql extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('client');
		self::$cols = self::init_cols(
			new rs_col('company'                    ,'int'                   ,11    ,''		),
			new rs_col('client'                     ,'varchar'               ,32    ,''		),
			new rs_col('manager'                    ,'varchar'               ,64    ,''		),
			new rs_col('status'                     ,'char'                  ,16    ,''		),
			new rs_col('wpro_cl_id'                 ,'bigint'                ,20    ,''		),
			new rs_col('wpro_url_id'                ,'bigint'                ,20    ,''		),
			new rs_col('url'                        ,'varchar'               ,128   ,''		),
			new rs_col('plan'                       ,'varchar'               ,32    ,''		),
			new rs_col('oid'                        ,'varchar'               ,32    ,''		),
			new rs_col('pay_period'                 ,'varchar'               ,12    ,''		),
			new rs_col('signup_date'                ,'date'                  ,''    ,''		),
			new rs_col('cancel_date'                ,'date'                  ,''    ,''		),
			new rs_col('de_activation_date'         ,'date'                  ,''    ,''		),
			new rs_col('submit_day'                 ,'tinyint'               ,3     ,''		),
			new rs_col('first_submit_date'          ,'date'                  ,''    ,''		),
			new rs_col('last_submit_date'           ,'date'                  ,''    ,''		),
			new rs_col('country_origin'             ,'varchar'               ,4     ,''		)
			);
	}
}
?>
