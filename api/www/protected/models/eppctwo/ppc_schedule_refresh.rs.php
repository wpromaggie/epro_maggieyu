<?php
class mod_eppctwo_ppc_schedule_refresh extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('client_id')
		);
		self::$cols = self::init_cols(
			new rs_col('id'          ,'int'    ,null,null    ,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('client_id'   ,'bigint' ,null,0       ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('user_id'     ,'int'    ,null,0       ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('frequency'   ,'enum'   ,null,'Weekly',rs::NOT_NULL, array('Weekly','Monthly')),
			new rs_col('day_of_week' ,'enum'   ,null,null    ,0           , array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')),
			new rs_col('day_of_month','tinyint',null,null    ,rs::UNSIGNED),
			new rs_col('time'        ,'time'   ,null,rs::DT  ,rs::NOT_NULL),
			new rs_col('num_days'    ,'tinyint',null,null    ,rs::NOT_NULL)
		);
	}
	
	public static function day_of_month_form_input($table, $col, $val)
	{
		return cgi::html_select($table.'_'.$col->name, range(1, 31), $val);
	}
	
	public static function num_days_form_input($table, $col, $val)
	{
		return cgi::html_select($table.'_'.$col->name, range(1, 60), $val);
	}
	
	public function get_frequency_details_string()
	{
		switch ($this->frequency)
		{
			case ('Weekly'): return $this->day_of_week;
			case ('Monthly'): return 'The '.util::ordinal($this->day_of_month);
		}
	}
	
	public function put($opts = array())
	{
		if ($this->frequency == 'Weekly')
		{
			unset($this->day_of_month);
		}
		if ($this->frequency == 'Monthly')
		{
			unset($this->day_of_week);
		}
		return parent::put($opts);
	}
}
?>
