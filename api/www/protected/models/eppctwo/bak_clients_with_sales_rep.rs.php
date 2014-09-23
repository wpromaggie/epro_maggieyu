<?php

class mod_eppctwo_bak_clients_with_sales_rep extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array);
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'bigint'                ,20    ,''		),
			new rs_col('company'                    ,'int'                   ,11    ,''		),
			new rs_col('name'                       ,'varchar'               ,255   ,''		),
			new rs_col('status'                     ,'char'                  ,16    ,''		),
			new rs_col('sales_rep'                  ,'int'                   ,10    ,''		),
			new rs_col('data_id'                    ,'varchar'               ,8     ,''		),
			new rs_col('cc_id'                      ,'bigint'                ,20    ,''		),
			new rs_col('old_id'                     ,'bigint'                ,20    ,''		),
			new rs_col('external_id'                ,'varchar'               ,12    ,''		)
			);
	}
}
?>
