<?php

// "event" is pretty generic and of course has more general significance programatically
// so we'll call this class "wpro_event"
class wpro_event extends rs_object
{
	public static $db, $table, $cols, $primary_key, $indexes;

	public static $type_options = array('Holiday', 'Party', 'Charity');
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('date')
		);
		self::$cols = self::init_cols(
			new rs_col('id'        ,'char',8   ,''    ,rs::READ_ONLY | rs::NOT_NULL),
			new rs_col('date'      ,'date',null,rs::DD,rs::NOT_NULL),
			new rs_col('all_day'   ,'bool',null,1     ,rs::NOT_NULL),
			new rs_col('start_time','time',null,rs::DT,rs::NOT_NULL),
			new rs_col('end_time'  ,'time',null,rs::DT,rs::NOT_NULL),
			new rs_col('type'      ,'enum',32  ,''    ,rs::NOT_NULL),
			new rs_col('name'      ,'char',120 ,''    ,rs::NOT_NULL)
		);
	}

	// 8 hex digits
	protected function uprimary_key($i)
	{
		return strtoupper(substr(sha1(mt_rand()), 1, 8));
	}

	public static function new_from_post()
	{
		$data = $_POST;
		// don't want start and end time if all day event
		if ($data['wpro_event_all_day']) {
			unset($data['wpro_event_start_time']);
			unset($data['wpro_event_end_time']);
		}
		return parent::new_from_post($data);
	}
}

?>