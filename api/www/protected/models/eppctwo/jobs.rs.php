<?php

class mod_eppctwo_jobs extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'bigint'                ,20    ,''		),
			new rs_col('foreign_id'                 ,'varchar'               ,32    ,''		),
			new rs_col('type'                       ,'varchar'               ,32    ,''		),
			new rs_col('pid'                        ,'int'                   ,11    ,''		),
			new rs_col('user'                       ,'int'                   ,11    ,''		),
			new rs_col('client'                     ,'bigint'                ,20    ,''		),
			new rs_col('status'                     ,'varchar'               ,64    ,''		),
			new rs_col('details'                    ,'varchar'               ,512   ,''		),
			new rs_col('minutia'                    ,'varchar'               ,512   ,''		),
			new rs_col('create_date'                ,'datetime'              ,''    ,''		),
			new rs_col('processing_start'           ,'datetime'              ,''    ,''		),
			new rs_col('processing_end'             ,'datetime'              ,''    ,''		)
			);
	}
}
?>
