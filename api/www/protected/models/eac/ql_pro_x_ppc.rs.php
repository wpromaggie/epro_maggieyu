<?php

// temporary table for joining a ql pro product account to a ppc agency account
class mod_eac_ql_pro_x_ppc extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('ql_account_id', 'ppc_client_id');
		self::$cols = self::init_cols(
			new rs_col('ql_account_id','char'  ,16  ,'', rs::READ_ONLY),
			new rs_col('ppc_client_id','bigint',null,0 , rs::READ_ONLY)
		);
	}
}

?>
