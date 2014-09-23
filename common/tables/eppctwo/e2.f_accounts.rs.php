<?php

class f_accounts extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static $status_options = array('On','Off');
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'              ,'bigint'  ,20  ,null   ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('text'            ,'varchar' ,64  ,''     ,rs::NOT_NULL),
			new rs_col('status'          ,'enum'    ,4  ,'On'    ,rs::NOT_NULL),
			new rs_col('ca_info_mod_time','datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('currency'        ,'varchar' ,4   ,'USD'  ,rs::NOT_NULL)
		);
	}
}

?>