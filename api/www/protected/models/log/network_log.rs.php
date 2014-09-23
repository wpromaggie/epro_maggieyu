<?php

class mod_log_network_log extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'log';
		self::$cols = self::init_cols(
			new rs_col('id'     ,'bigint'  ,null,null   ,rs::AUTO_INCREMENT | rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('post_id','char'    ,16  ,''     ,rs::READ_ONLY),
			new rs_col('dt'     ,'datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('network','char'    ,16  ,''     ,rs::NOT_NULL),
			new rs_col('app_id' ,'char'    ,32  ,''     ,rs::NOT_NULL),
			new rs_col('message','text'    ,null,''     ,rs::NOT_NULL)
		);
		self::$primary_key = array('id');
	}

	public static function new_entry($network, $app_id, $log_str, $other_data = array())
	{
		$data = array(
			'dt' => date(util::DATE_TIME),
			'network' => $network,
			'app_id' => $app_id,
			'message' => $log_str
		);
		if (isset($other_data['post_id'])) {
			$data['post_id'] = $other_data['post_id'];
		}
		return self::create($data);
	}
}
?>