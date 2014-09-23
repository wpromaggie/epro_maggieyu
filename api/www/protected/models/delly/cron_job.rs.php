<?php
class mod_delly_cron_job extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static $status_options = array('Active', 'Paused', 'Deleted');
	public static function set_table_definition()
	{
		self::$db = 'delly';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'          ,'char',8  ,'' ,rs::READ_ONLY),
			new rs_col('minute'      ,'char',128,'*'),
			new rs_col('hour'        ,'char',128,'*'),
			new rs_col('day_of_month','char',128,'*'),
			new rs_col('month'       ,'char',128,'*'),
			new rs_col('day_of_week' ,'char',128,'*'),
			new rs_col('status'      ,'enum',16 ,'' ),
			new rs_col('worker'      ,'enum',64 ,'' ),
			new rs_col('args'        ,'char',200,'' ),
			new rs_col('comments'    ,'char',250,'' )
		);
	}

	// 16 hex chars
	protected function uprimary_key($i)
	{
		$sha = sha1(mt_rand());
		return strtoupper(substr($sha, 0, 8));
	}

	public static function get_worker_options()
	{
		$workers = array();
		foreach (glob(\epro\CLI_PATH.'/workers/worker_*') as $path) {
			if (preg_match("/\/worker_(.*?)\.php$/", $path, $matches)) {
				$workers[] = strtoupper(str_replace('_', ' ', $matches[1]));
			}
		}
		return $workers;
	}

	private static $time_fields = false;

	public static function get_time_fields()
	{
		if (!self::$time_fields) {
			self::$time_fields = array('minute', 'hour', 'day_of_month', 'month', 'day_of_week');
		}
		return self::$time_fields;
	}

	public static function get_time_field_min_max($time_field)
	{
		switch ($time_field) {
			case ('minute'):       return array(0, 59);
			case ('hour'):         return array(0, 23);
			case ('day_of_month'): return array(1, 31);
			case ('month'):        return array(1, 12);
			case ('day_of_week'):  return array(0, 6);
		}
	}

	// get array of values from string representing one time field
	// for example, if we are looking at minutes and string is 2,33-36
	// return array(2, 33, 34, 35, 36)
	public static function get_time_parts($time_field, $cron_time)
	{
		list($field_min, $field_max) = self::get_time_field_min_max($time_field);
		$parts = array();
		$cron_times = array_map('trim', explode(',', $cron_time));
		foreach ($cron_times as $t) {
			if (preg_match("/^(\d+)($|-(\d+)($|\/(\d+)))$/", $t, $matches)) {
				$match_count = count($matches);
				// single number
				if ($match_count === 3) {
					list($ph1, $part_min) = $matches;
					$part_max = $part_min;
					$step = 1;
				}
				// range
				else if ($match_count === 5) {
					list($ph1, $part_min, $ph2, $part_max) = $matches;
					$step = 1;
				}
				// range with step
				else if ($match_count === 6) {
					list($ph1, $part_min, $ph2, $part_max, $ph3, $step) = $matches;
				}
			}
			// wildcard with step
			else if (preg_match("/^\*\/(\d+)$/", $t, $matches)) {
				$part_min = $field_min;
				$part_max = $field_max;
				$step = $matches[1];
			}
			// unrecognized pattern
			else {
				return false;
			}
			// invalid range
			if ($part_min > $part_max) {
				return false;
			}
			$parts = array_merge($parts, range($part_min, $part_max, $step));
		}
		// trim any leading zeros,
		// make sure we are in range
		foreach ($parts as $i => $part) {
			if ($part != '0') {
				$part = ltrim($part, '0');
				$parts[$i] = $part;
			}
			if ($part < $field_min || $part > $field_max) {
				return false;
			}
		}
		return $parts;
	}
}
?>