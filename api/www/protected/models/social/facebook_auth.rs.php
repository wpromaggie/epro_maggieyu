<?php

class mod_social_facebook_auth extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'social';
		self::$primary_key = array('account_id');
		self::$cols = self::init_cols(
			new rs_col('account_id'  ,'char'    ,16  ,''     ),
			new rs_col('app_id'      ,'char'    ,32  ,''     ),
			new rs_col('app_secret'  ,'char'    ,64  ,''     ),
			new rs_col('access_token','char'    ,200 ,''     ),
			new rs_col('expires'     ,'int'     ,null,0      ,rs::UNSIGNED),
			new rs_col('expires_at'  ,'datetime',null,rs::DDT),
			new rs_col('page_id'     ,'char'    ,32  ,''     ),
			new rs_col('page_token'  ,'char'    ,200 ,''     ),
			new rs_col('album_id'    ,'char'    ,32  ,''     )
			);
	}
}
?>
