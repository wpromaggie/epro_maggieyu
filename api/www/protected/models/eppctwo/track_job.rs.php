<?php

class mod_eppctwo_track_job extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$cols = self::init_cols(
			new rs_col('id', 'int', 10, null, rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT),
			new rs_col('client', 'varchar', 32, '', rs::NOT_NULL),
			new rs_col('job_sync_id', 'bigint', 20, '0', rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('job_refresh_id', 'bigint', 20, '0', rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('job_trackify_id', 'bigint', 20, '0', rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('job_revert_id', 'bigint', 20, '0', rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('dt', 'datetime', null, '0000-00-00 00:00:00', rs::NOT_NULL),
			new rs_col('market', 'char', 4, '', rs::NOT_NULL),
			new rs_col('entity_type', 'enum', null, '', rs::NOT_NULL, array('', 'Account', 'Campaign', 'Ad Group'))
		);
		self::$primary_key = array('id');
		self::$indexes = array(
			array('client')
		);
	}
}

?>
