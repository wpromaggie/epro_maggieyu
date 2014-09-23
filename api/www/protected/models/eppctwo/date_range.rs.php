<?php

// companion: jquery.wpro.date_range.js
class mod_eppctwo_date_range extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static $tmp_id_counter = 0;

	// id for javascript to use, not actually tied to db
	public $js_id;

	public $start_key, $end_key, $defined_key;

	public $default_start, $default_end, $default_defined, $end_cap;

	public $range_type;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'     ,'bigint',20  ,null  ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('defined','char'  ,64  ,''    ,rs::NOT_NULL),
			new rs_col('start'  ,'date'  ,null,rs::DD,rs::NOT_NULL),
			new rs_col('end'    ,'date'  ,null,rs::DD,rs::NOT_NULL)
		);
	}

	public static function init($opts = array(), $data = false)
	{
		$obj = new date_range();
		return $obj->_init($opts, $data);
	}

	private function _init($opts, $data)
	{
		foreach ($opts as $k => $v) {
			$this->$k = $v;
		}

		// for field key
		$this->_init_keys(array('start', 'end', 'defined'));

		if (isset($this->id)) {
			$this->get();
		}
		else {
			// get temp id
			$this->id = 'tmp_'.(self::$tmp_id_counter++);
		}
		$this->range_type = 'custom';
		if (!is_array($data)) {
			$data = $_REQUEST;
		}
		// check data (should this overwrite get()?)
		if (isset($data[$this->start_key])) {
			$this->start = $data[$this->start_key];
			$this->end = $data[$this->end_key];
			// when a defined range is submitted, the start and end dates will
			// also be submitted, so we don't need to call set_dates_from_defined_range
			// however we do need to note that a defined range was the source fo the dates
			if (!empty($data[$this->defined_key])) {
				$this->range_type = 'defined';
				$this->{$this->defined_key} = $data[$this->defined_key];
			}
		}
		// if still have nothing, check defaults
		if (!isset($this->start)) {
			$this->set_default_dates();
		}
		return $this;
	}

	private function _init_keys($keys) {
		$this->base_keys = array();
		for ($i = 0, $ci = count($keys); $i < $ci; ++$i) {
			$key_base = $keys[$i];
			$this->base_keys[] = $key_base;
			$default = "{$key_base}_date";
			$key = "{$key_base}_key";
			if (!isset($this->$key)) {
				$this->$key = $default;
			}
			if ($this->key_prefix) {
				$this->$key = "{$this->key_prefix}_{$this->$key}";
			}
			if ($this->key_suffix) {
				$this->$key .= "_{$this->key_suffix}";
			}
		}
	}

	public function is_valid()
	{
		return util::is_valid_date_range($this->start, $this->end);
	}

	// we need this here as well as js so that we can calculate defaults
	public function set_dates_from_defined($range)
	{
		$now = time();
		$today = date(util::DATE, $now);
		$is_valid = true;
		switch ($range) {
			case ('Last 7'):
				$this->end = date(util::DATE, $now - 86400);
				$this->start = date(util::DATE, $now - 604800);
				break;
				
			case ('Last 14'):
				$this->end = date(util::DATE, $now - 86400);
				$this->start = date(util::DATE, $now - 1209600);
				break;
				
			case ('Last 30'):
				$this->end = date(util::DATE, $now - 86400);
				$this->start = date(util::DATE, $now - 2592000);
				break;
				
			case ('Last 90'):
				$this->end = date(util::DATE, $now - 86400);
				$this->start = date(util::DATE, $now - 7776000);
				break;
				
			case ('This Quarter'):
				$this->end = date(util::DATE, $now - 86400);
				$this->start = util::get_date_time_period('quarterly', $today).'-01';
				break;
				
			case ('Previous Quarter'):
				$etmp = util::get_date_time_period('quarterly', $today).'-01';
				$this->end = date(util::DATE, strtotime($etmp) - 86400);
				$this->start = util::get_date_time_period('quarterly', $this->end).'-01';
				break;
				
			case ('This Month'):
				$this->end = date(util::DATE, $now - 86400);
				$stmp = $today;
				$stmp = substr($stmp, 0, 7).'-01';
				$this->start = $stmp;
				break;
				
			case ('Previous Month'):
				$etmp = $today;
				$etmp = substr($etmp, 0, 7).'-01';
				$etmp = date(util::DATE, strtotime($etmp) - 86400);
				$stmp = substr($etmp, 0, 7).'-01';
				$this->end = $etmp;
				$this->start = $stmp;
				break;
				
			case ('This Client Month'):
				$this->end = date(util::DATE, strtotime($this->rollover_date) - 86400);
				$this->start = util::delta_month($this->rollover_date, -1);
				break;
				
			case ('Previous Client Month'):
				$prev_start = util::delta_month($this->rollover_date, -1);
				$this->end = date(util::DATE, strtotime($prev_start) - 86400);
				$this->start = util::delta_month($prev_start, -1);
				break;

			default:
				$is_valid = false;
		}
		if ($is_valid) {
			$this->{$this->defined_key} = $range;
		}
	}

	public function set_default_dates()
	{
		if (isset($this->default_defined)) {
			$this->set_dates_from_defined($this->default_defined);
			$this->range_type = 'defined';
		}
		else {
			$this->range_type = 'custom';
			if (isset($this->default_start)) {
				$this->start = $this->default_start;
				$this->end = $this->default_end;
			}
		}
		if ($this->end_cap && $this->end > $this->end_cap) {
			$this->end = $this->end_cap;
		}
	}

	public function ml()
	{
		$this->output_js();
		return '<tr class="date_range_placeholder" drid="'.(($this->js_id) ? $this->js_id : $this->id).'"></tr>';
	}

	public function output_js()
	{
		cgi::add_js_var('date_range_'.(($this->js_id) ? $this->js_id : $this->id), $this);
	}
}

?>