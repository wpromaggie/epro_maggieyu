<?php

class mod_social_post_media extends rs_object
{
	public static $db, $cols, $primary_key;

	public static $base_dir;

	// 2^24, 16 MBs
	const MAX_SIZE = 16777216;

	public static function set_table_definition()
	{
		self::$db = 'social';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'     ,'char'      ,16  ,''     ,rs::READ_ONLY),
			new rs_col('post_id','char'      ,16  ,''     ,rs::READ_ONLY),
			new rs_col('type'   ,'char'      ,16  ,''),
			new rs_col('name'   ,'char'      ,200 ,''),
			new rs_col('path'   ,'char'      ,255 ,''),
			new rs_col('data'   ,'mediumblob',null)
		);
	}

	public static function get_base_dir()
	{
		return \epro\CGI_PATH.'img/u/smo/swapp';
	}

	// 16 hex chars
	protected function uprimary_key($i)
	{
		$sha = sha1(mt_rand());
		return strtoupper(substr($sha, 0, 16));
	}

	public function delete()
	{
		if (!empty($this->path) && file_exists($this->path)) {
			unlink($this->path);
		}
		return parent::delete();
	}
}
?>
