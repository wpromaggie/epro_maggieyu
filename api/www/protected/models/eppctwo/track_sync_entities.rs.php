<?php

class mod_eppctwo_track_sync_entities extends rs_object{
	
	public static $db, $cols, $primary_key, $indexes, $alters;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$cols = array(
			'client' => new rs_col('client', 'varchar', 32, '', rs::NOT_NULL),
			'market' => new rs_col('market', 'char', 4, '', rs::NOT_NULL),
			'entity_type' => new rs_col('entity_type', 'enum', null, 'Campaign', rs::NOT_NULL, array('Campaign', 'Ad Group', 'Ad', 'Keyword')),
			'entity_id0' => new rs_col('entity_id0', 'varchar', 32, '', rs::NOT_NULL),
			'entity_id1' => new rs_col('entity_id1', 'varchar', 32, '', rs::NOT_NULL),
			'sync_type' => new rs_col('sync_type', 'enum', null, 'New', rs::NOT_NULL, array('New', 'Update', 'Not Tracked')),
			'is_processed' => new rs_col('is_processed', 'tinyint', 4, '0', rs::NOT_NULL)
		);
		self::$primary_key = array('market','entity_type','entity_id0','entity_id1');
		self::$indexes = array(
			array('client')
		);
	}
}
?>
