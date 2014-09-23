<?php



class cgi_info extends rs_object
{
	public static $db, $cols, $primary_key, $belongs_to;
	
	public static function set_table_definition()
	{
		self::$db = 'log';
		self::$cols = self::init_cols(
			new rs_col('entry_id' ,'bigint'  ,null,null,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('user'     ,'int'     ,null,0   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('url'      ,'varchar' ,256 ,''  ,rs::NOT_NULL)
		);
		self::$primary_key = array('entry_id');
		self::$belongs_to = array('entry');
	}
}
?>