<?php

class mod_eppctwo_partner_contacts extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,10    ,''		),
			new rs_col('rep'                        ,'varchar'               ,32    ,''		),
			new rs_col('date'                       ,'date'                  ,''    ,''		),
			new rs_col('practice'                   ,'varchar'               ,64    ,''		),
			new rs_col('name'                       ,'varchar'               ,32    ,''		),
			new rs_col('phone'                      ,'varchar'               ,11    ,''		),
			new rs_col('email'                      ,'varchar'               ,64    ,''		),
			new rs_col('url'                        ,'varchar'               ,512   ,''		),
			new rs_col('interests'                  ,'varchar'               ,32    ,''		),
			new rs_col('notes'                      ,'text'                  ,''    ,''		),
			new rs_col('created'                    ,'datetime'              ,''    ,''		)
			);
	}
}
?>
