<?php

class mod_eppctwo_gae_snapshots extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('client','market');
		self::$cols = self::init_cols(
			new rs_col('client'                     ,'varchar'               ,32    ,''		),
			new rs_col('market'                     ,'varchar'               ,4     ,''		),
			new rs_col('last_1'                     ,'int'                   ,10    ,''		),
			new rs_col('last_2'                     ,'int'                   ,10    ,''		),
			new rs_col('last_4'                     ,'int'                   ,10    ,''		),
			new rs_col('last_8'                     ,'int'                   ,10    ,''		),
			new rs_col('last_16'                    ,'int'                   ,10    ,''		),
			new rs_col('last_32'                    ,'int'                   ,10    ,''		),
			new rs_col('last_64'                    ,'int'                   ,10    ,''		),
			new rs_col('last_128'                   ,'int'                   ,10    ,''		)
			);
	}
}
?>
