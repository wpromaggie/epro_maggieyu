<?php

class mod_social_twitter_auth extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'social';
		self::$primary_key = array('account_id');
		self::$cols = self::init_cols(
			new rs_col('account_id'                 ,'char'                  ,16    ,''		),
			new rs_col('consumer_key'               ,'char'                  ,32    ,''		),
			new rs_col('consumer_secret'            ,'char'                  ,64    ,''		),
			new rs_col('oauth_token'                ,'char'                  ,128   ,''		),
			new rs_col('oauth_token_secret'         ,'char'                  ,128   ,''		),
			new rs_col('user_id'                    ,'char'                  ,32    ,''		),
			new rs_col('screen_name'                ,'char'                  ,32    ,''		)
			);
	}
}
?>
