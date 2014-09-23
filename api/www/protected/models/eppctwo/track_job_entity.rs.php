<?php

class track_job_entity extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$cols = array(
			'track_job_id' => new rs_col('track_job_id', 'int', 10, null, rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT),
			'entity_id' => new rs_col('entity_id', 'varchar', 32, '', rs::NOT_NULL)
		);
		self::$primary_key = array('track_job_id','entity_id');
	}
}
?>
