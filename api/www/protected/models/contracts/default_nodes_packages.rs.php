<?php

class mod_contracts_default_nodes_packages extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'contracts';
		self::$primary_key = array('default_node_id','package_id');
		self::$cols = self::init_cols(
			new rs_col('default_node_id'            ,'int'                   ,10    ,''		),
			new rs_col('package_id'                 ,'int'                   ,10    ,''		)
			);
	}
}
?>
