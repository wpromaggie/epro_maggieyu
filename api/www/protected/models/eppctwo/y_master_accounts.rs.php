<?php

class mod_eppctwo_y_master_accounts extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id','url_prefix');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'varchar'               ,64    ,''		),
			new rs_col('company'                    ,'int'                   ,11    ,''		),
			new rs_col('url_prefix'                 ,'varchar'               ,255   ,''		),
			new rs_col('name'                       ,'varchar'               ,128   ,''		),
			new rs_col('status'                     ,'varchar'               ,16    ,''		),
			new rs_col('is_external'                ,'tinyint'               ,4     ,''		),
			new rs_col('external_user'              ,'varchar'               ,64    ,''		),
			new rs_col('external_pass'              ,'varchar'               ,64    ,''		)
			);
	}
}
?>
