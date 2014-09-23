<?php
// for some errors, we can retry a post
// if the retry is successful, error would be over written
// so we keep a log for historical purposes, to go with
// the error field in the network post table, which represents
// the error of the most recent post (or lack thereof)
class mod_social_network_post_error extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'social';
		self::$primary_key = array('id');
		self::$indexes = array(array('network_post_id'));

		self::$uniques = array(array(;''));

		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,10    ,''		),
			new rs_col('network_post_id'            ,'char'                  ,16    ,''		),
			new rs_col('posted_at'                  ,'datetime'              ,''    ,''		),
			new rs_col('error'                      ,'char'                  ,200   ,''		)
			);
	}
}
?>
