<?php


class client_payment extends rs_object
{
	public static $db, $cols, $primary_key, $indexes, $has_many;
	
	public static $pay_method_options;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('client_id')
		);
		self::$has_many = array('client_payment_part');
		self::$pay_method_options = array('cc','check','wire');
		self::$cols = self::init_cols(
			new rs_col('id'             ,'int'    ,null,null  ,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('client_id'      ,'bigint' ,null,0     ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('user_id'        ,'int'    ,null,0     ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('pay_id'         ,'bigint' ,null,0     ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('pay_method'     ,'enum'   ,8   ,''    ,rs::NOT_NULL),
			new rs_col('fid'            ,'varchar',64  ,''    ,rs::NOT_NULL),
			new rs_col('date_received'  ,'date'   ,null,rs::DD,rs::NOT_NULL),
			new rs_col('date_attributed','date'   ,null,rs::DD,rs::NOT_NULL),
			new rs_col('amount'         ,'double' ,null,0     ,rs::NOT_NULL),
			new rs_col('notes'          ,'varchar',256 ,''    ,rs::NOT_NULL),
			new rs_col('sales_notes'    ,'varchar',256 ,''    ,rs::NOT_NULL)
		);
	}
}


class secondary_manager extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	
	public static $pay_method_options;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('account_id', 'user_id');
		self::$indexes = array(
			array('user_id')
		);
		self::$cols = self::init_cols(
			new rs_col('client_id' ,'bigint',null,0 ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('dept'      ,'char'  ,16  ,'',rs::NOT_NULL),
			new rs_col('account_id','char'  ,16  ,'',rs::NOT_NULL),
			new rs_col('user_id'   ,'int'   ,null,0 ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY)
		);
	}
}

class ad_refresh_log extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'          ,'int'     ,null,null   ,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('client_id'   ,'bigint'  ,null,0      ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('user_id'     ,'int'     ,null,0      ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('market'      ,'char'    ,8   ,''     ,rs::NOT_NULL),
			new rs_col('process_dt'  ,'datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('start_date'  ,'date'    ,null,rs::DD ,rs::NOT_NULL),
			new rs_col('end_date'    ,'date'    ,null,rs::DD ,rs::NOT_NULL)
		);
	}
}

class client_media extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	// 2^24, 16 MBs
	const MAX_SIZE = 16777216;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('account_id')
		);
		self::$cols = self::init_cols(
			new rs_col('id'       ,'char'      ,8   ,''     ,rs::READ_ONLY),
			new rs_col('account_id','char'      ,16  ,''     ,rs::READ_ONLY),
			new rs_col('user_id'  ,'int'       ,null,0      ,rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('ts'       ,'datetime'  ,null,rs::DDT,rs::READ_ONLY),
			new rs_col('type'     ,'char'      ,16  ,''     ,rs::READ_ONLY),
			new rs_col('name'     ,'char'      ,96  ,''     ,rs::READ_ONLY),
			new rs_col('data'     ,'mediumblob',null,null   ,rs::READ_ONLY),
			new rs_col('w'        ,'int'       ,null,0      ,rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('h'        ,'int'       ,null,0      ,rs::UNSIGNED | rs::READ_ONLY)
		);
	}
	
	protected function uprimary_key($i)
	{
		$sha = sha1(mt_rand());
		return strtoupper(substr($sha, 0, 8));
	}
}

class client_media_use extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	public static $use_options = array('PPC Report Logo');

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('client_media_id')
		);
		self::$cols = self::init_cols(
			new rs_col('id'             ,'char'      ,10,''     ,rs::READ_ONLY),
			new rs_col('client_media_id','char'      ,8 ,''     ,rs::READ_ONLY),
			new rs_col('use'            ,'char'      ,64,''     ,rs::READ_ONLY)
		);
	}
	
	protected function uprimary_key($i)
	{
		$sha = sha1(mt_rand());
		return strtoupper(substr($sha, 0, 10));
	}
}

?>