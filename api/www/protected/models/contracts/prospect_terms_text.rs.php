<?php

class mod_contracts_prospect_terms_text extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'contracts';
		self::$primary_key = array('prospect_id','layout');
		self::$cols = self::init_cols(
			new rs_col('prospect_id'                ,'int'                   ,10    ,''		),
			new rs_col('text'                       ,'text'                  ,''    ,''		),
			new rs_col('layout'                     ,'varchar'               ,32    ,''		)
			);
	}
}
?>
