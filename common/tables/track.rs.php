<?php

class track_job extends rs_object
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

class track_sync_entities extends rs_object
{
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

class track_account extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('client_id');
		self::$cols = self::init_cols(
			new rs_col('client_id'           ,'varchar',32  ,''    , rs::NOT_NULL),
			new rs_col('external_id'         ,'varchar',32  ,''    , rs::NOT_NULL),
			new rs_col('default_url'         ,'varchar',128 ,''    , rs::NOT_NULL),
			new rs_col('g_start_date'        ,'date'   ,null,rs::DD, rs::NOT_NULL),
			new rs_col('y_start_date'        ,'date'   ,null,rs::DD, rs::NOT_NULL),
			new rs_col('m_start_date'        ,'date'   ,null,rs::DD, rs::NOT_NULL),
			new rs_col('f_start_date'        ,'date'   ,null,rs::DD, rs::NOT_NULL),
			new rs_col('analytics_type'      ,'enum'   ,null,''    , rs::NOT_NULL, array('', 'Simple')),
			new rs_col('track_non_wpro_convs','bool'   ,null,'TRUE', rs::NOT_NULL)
		);
	}
}

class track_conv_type extends rs_object
{
	public static $db, $cols, $primary_key, $uniques;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$cols = array(
			'id' => new rs_col('id', 'int', 10, null, rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT),
			'client_id' => new rs_col('client_id', 'varchar', 32, '', rs::NOT_NULL),
			'conv_type' => new rs_col('conv_type', 'varchar', 64, '', rs::NOT_NULL)
		);
		self::$primary_key = array('id');
		self::$uniques = array(
			array('client_id', 'conv_type')
		);
	}
}

?>