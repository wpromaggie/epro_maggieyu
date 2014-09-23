<?php

class payment_error extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'log';
		self::$cols = self::init_cols(
			new rs_col('id'        ,'bigint' ,null,null  ,rs::AUTO_INCREMENT | rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('d'         ,'date'   ,null,rs::DD,rs::NOT_NULL),
			new rs_col('t'         ,'time'   ,null,rs::DT,rs::NOT_NULL),
			new rs_col('user'      ,'int'    ,null,0     ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('dept'      ,'char'   ,16  ,''    ,rs::NOT_NULL),
			new rs_col('client_id' ,'char'   ,16  ,''    ,rs::NOT_NULL),
			new rs_col('account_id','char'   ,16  ,''    ,rs::NOT_NULL),
			new rs_col('msg'       ,'varchar',512 ,''    ,rs::NOT_NULL)
		);
		self::$primary_key = array('id');
	}
	
	public static function create($data)
	{
		if (!$data['d']) {
			$data['d'] = date(util::DATE);
		}
		if (!$data['t']) {
			$data['t'] = date(util::TIME);
		}
		return parent::create($data);
	}
}

?>