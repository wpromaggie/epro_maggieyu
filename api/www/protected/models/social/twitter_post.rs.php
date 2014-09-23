<?php

class mod_social_twitter_post extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'social';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'         ,'char'    ,16 ,''      ,rs::READ_ONLY)
		);
	}

	public function process_response($api, $r)
	{
		if ($r !== false) {
			$r['id'] = $r['id_str'];
		}
		return parent::process_response($api, $r);
	}
	
	public function can_retry_on_error()
	{
		switch ($this->error) {
			case ('Over capacity.'):
				return true;

			default:
				return false;
		}
	}
}
?>
