<?php

class mod_eppctwo_guild_roles extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('guild_id','role');
		self::$cols = self::init_cols(
			new rs_col('guild_id'                   ,'varchar'               ,32    ,''		),
			new rs_col('role'                       ,'varchar'               ,64    ,''		)
			);
	}
}
?>
