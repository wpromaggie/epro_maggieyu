<?php

class mod_eppctwo_ql_url_note extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(array('ql_url_id'));
		self::$cols = self::init_cols(
			new rs_col('id'       ,'int'     ,null,null   ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY | rs::AUTO_INCREMENT),
			new rs_col('ql_url_id','int'     ,null,0      ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('users_id' ,'int'     ,null,0      ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('dt'       ,'datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('note'     ,'varchar' ,512 ,''     ,rs::NOT_NULL)
		);
	}
}
?>
