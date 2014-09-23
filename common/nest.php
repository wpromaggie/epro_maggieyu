<?php

class nest
{
	// sha1 length
	const NEST_ID_LEN = 40;

	public static $num_levels = 1;
	public static $chars_per_level = 2;

	// return full path to file
	public static function put($base_dir, $file_path, $file_name = false)
	{
		if (!$file_name) {
			$file_name = basename($file_path);
		}
		$base_dir = self::normalize_dir($base_dir);
		$nest_path = self::get_unique_nest_path($base_dir, $file_name);
		// create nest dirs
		$nest_dir = dirname($nest_path);
		if (!is_dir($nest_dir)) {
			mkdir($nest_dir, 0775, true);
		}
		// mv file path to nest path
		rename($file_path, $nest_path);
		chmod($nest_path, 0644);
		// return nest path
		// todo? do not return full path, just inner nest path
		return $nest_path;
	}

	public function set_levels($num_levels, $chars_per_level = 2)
	{
		self::$num_levels = $num_levels;
		self::$chars_per_level = $chars_per_level;
	}

	private static function normalize_dir($dir)
	{
		return $dir.(($dir[strlen($dir) - 1] !== DIRECTORY_SEPARATOR) ? DIRECTORY_SEPARATOR : '');
	}

	private static function get_unique_nest_path($base_dir, $file_name)
	{
		while (1) {
			$nest_id = self::get_nest_id($file_name);
			$nest_dirs = self::get_nest_dirs($nest_id);
			// test if we already have used this nest id
			$nest_path_prefix = $base_dir.$nest_dirs.DIRECTORY_SEPARATOR.$nest_id.'.';
			$test_files = glob("{$nest_path_prefix}*");
			if (empty($test_files)) {
				break;
			}
		}
		return $nest_path_prefix.$file_name;
	}

	private static function get_nest_id($file_name)
	{
		return sha1($file_name.mt_rand());
	}

	private static function get_nest_dirs($nest_id)
	{
		$nest_dirs = substr($nest_id, 0, self::$chars_per_level);
		for ($i = 1; $i < self::$num_levels; ++$i) {
			$nest_dirs .= DIRECTORY_SEPARATOR.substr($nest_id, $i * self::$chars_per_level, self::$chars_per_level);
		}
		return $nest_dirs;
	}
}

?>