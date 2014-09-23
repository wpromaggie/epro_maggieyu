<?php

class mod_social_facebook_album extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'social';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'char'                  ,32    ,''		),
			new rs_col('account_id'                 ,'char'                  ,16    ,''		),
			new rs_col('page_id'                    ,'char'                  ,32    ,''		),
			new rs_col('name'                       ,'char'                  ,200   ,''		)
			);
	}
}
?>
