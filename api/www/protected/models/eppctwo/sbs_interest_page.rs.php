<?php

class mod_eppctwo_sbs_interest_page extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,10    ,''		),
			new rs_col('user_id'                    ,'int'                   ,10    ,''		),
			new rs_col('ql_package'                 ,'varchar'               ,16    ,''		),
			new rs_col('ql_cost'                    ,'double'                ,''    ,''		),
			new rs_col('ql_setup_fee'               ,'double'                ,''    ,''		),
			new rs_col('sb_package'                 ,'varchar'               ,16    ,''		),
			new rs_col('sb_cost'                    ,'double'                ,''    ,''		),
			new rs_col('sb_setup_fee'               ,'double'                ,''    ,''		),
			new rs_col('sb_fanpage'                 ,'tinyint'               ,1     ,''		),
			new rs_col('gs_package'                 ,'varchar'               ,16    ,''		),
			new rs_col('gs_cost'                    ,'double'                ,''    ,''		),
			new rs_col('gs_setup_fee'               ,'double'                ,''    ,''		),
			new rs_col('details'                    ,'text'                  ,''    ,''		),
			new rs_col('start_date'                 ,'date'                  ,''    ,''		),
			new rs_col('other_date'                 ,'date'                  ,''    ,''		),
			new rs_col('contract_length'            ,'smallint'              ,2     ,''		)
			);
	}
}
?>
