<?php


class mod_log_frame extends rs_object
{
	public static $db, $cols, $primary_key, $belongs_to;
	
	public static function set_table_definition()
	{
		self::$db = 'log';
		self::$cols = self::init_cols(
			new rs_col('entry_id' ,'bigint'  ,null,null,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('i'        ,'smallint',null,0   ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('file'     ,'varchar' ,256 ,''  ,rs::NOT_NULL),
			new rs_col('class'    ,'varchar' ,128 ,''  ,rs::NOT_NULL),
			new rs_col('function' ,'varchar' ,256 , '' ,rs::NOT_NULL),
			new rs_col('line'     ,'int'     ,null, 0  ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('url'      ,'varchar' ,256 , '' ,rs::NOT_NULL)
		);
		self::$primary_key = array('entry_id', 'i');
		self::$belongs_to = array('entry');
	}
}
?>