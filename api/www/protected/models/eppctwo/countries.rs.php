<?php

class mod_eppctwo_countries extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('a2');
		self::$cols = self::init_cols(
			new rs_col('country'                    ,'varchar'               ,64    ,''		),
			new rs_col('a2'                         ,'varchar'               ,2     ,''		),
			new rs_col('a3'                         ,'varchar'               ,4     ,''		),
			new rs_col('currency'                   ,'varchar'               ,32    ,''		),
			new rs_col('code'                       ,'varchar'               ,8     ,''		),
			new rs_col('m_currency'                 ,'varchar'               ,32    ,''		)
			);
	}
}
?>
