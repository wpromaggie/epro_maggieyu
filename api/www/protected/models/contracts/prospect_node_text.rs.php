<?php

class mod_contracts_prospect_node_text extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'contracts';
		self::$primary_key = array('prospect_node_id');
		self::$cols = self::init_cols(
			new rs_col('prospect_node_id'           ,'int'                   ,10    ,''		),
			new rs_col('text'                       ,'text'                  ,''    ,''		)
			);
	}
}
?>
