<?php

class mod_eppctwo_ql_client_update_engine extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,10    ,''		),
			new rs_col('dt'                         ,'datetime'              ,''    ,''		),
			new rs_col('processed_dt'               ,'datetime'              ,''    ,''		),
			new rs_col('wpro_url_id'                ,'bigint'                ,20    ,''		),
			new rs_col('url'                        ,'varchar'               ,128   ,''		),
			new rs_col('title'                      ,'varchar'               ,64    ,''		),
			new rs_col('desc_1'                     ,'varchar'               ,100   ,''		),
			new rs_col('desc_2'                     ,'varchar'               ,100   ,''		),
			new rs_col('keywords'                   ,'text'                  ,''    ,''		)
			);
	}
}
?>
