<?php

class mod_eppctwo_user_guilds extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('user_id','guild_id');
		self::$cols = self::init_cols(
			new rs_col('user_id'                    ,'int'                   ,10    ,''		),
			new rs_col('guild_id'                   ,'varchar'               ,32    ,''		),
			new rs_col('role'                       ,'varchar'               ,64    ,''		)
			);
	}
}
?>
