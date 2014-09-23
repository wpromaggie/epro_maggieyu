<?php

class mod_contracts_prospect_nodes extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'contracts';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,10    ,''		),
			new rs_col('parent_id'                  ,'int'                   ,10    ,''		),
			new rs_col('child_order'                ,'smallint'              ,5     ,''		),
			new rs_col('node_struct'                ,'char'                  ,8     ,''		),
			new rs_col('node_type'                  ,'varchar'               ,16    ,''		),
			new rs_col('prospect_id'                ,'int'                   ,10    ,''		),
			new rs_col('default_node_id'            ,'int'                   ,10    ,''		),
			new rs_col('edited_text'                ,'tinyint'               ,1     ,''		)
			);
	}
}
?>
