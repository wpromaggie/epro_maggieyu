<?php
// todo: this is for errors, change name to reflect that
//  add foreign key to request table
class mod_log_entry extends rs_object
{
	public static $db, $cols, $primary_key, $has_one, $has_many;
	
	public static function set_table_definition()
	{
		self::$db = 'log';
		self::$cols = self::init_cols(
			new rs_col('id'       ,'bigint'  ,null,null   ,rs::AUTO_INCREMENT | rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('dt'       ,'datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('type0'    ,'enum'    ,null,'note' ,rs::NOT_NULL,array('note', 'error')),
			new rs_col('type1'    ,'varchar' ,32  ,''     ,rs::NOT_NULL),
			new rs_col('interface','enum'    ,null,'cgi'  ,rs::NOT_NULL,array('cgi', 'cli')),
			new rs_col('message'  ,'text'    ,null,''     ,rs::NOT_NULL)
		);
		self::$primary_key = array('id');
		self::$has_one = array('cgi_info');
		self::$has_many = array('frame');
	}
}
?>