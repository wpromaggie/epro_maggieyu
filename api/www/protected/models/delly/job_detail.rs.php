<?php

class mod_delly_job_detail extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	
	public static function set_table_definition()
	{
		self::$db = 'delly';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('job_id')
		);
		self::$cols = self::init_cols(
			new rs_col('id'     ,'int'     ,null        ,null   ,rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('job_id' ,'char'    ,job::$id_len,''     ),
			new rs_col('ts'     ,'datetime',null        ,rs::DDT),
			new rs_col('level'  ,'tinyint' ,16          ,0      ,rs::UNSIGNED),
			new rs_col('message','char'    ,250         ,''     )
		);
	}
}
?>