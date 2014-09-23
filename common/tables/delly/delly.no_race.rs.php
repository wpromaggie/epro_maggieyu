<?php


// if only one machine should be doing something, can create a
// no_race record, if success, you are the only one
class no_race extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'delly';
		self::$primary_key = array('baton');
		self::$cols = self::init_cols(
			new rs_col('baton'   ,'char'    ,128 ,''     ),
			new rs_col('dt'      ,'datetime',null,rs::DDT),
			new rs_col('hostname','char'    ,32  ,''     )
		);
	}
}
?>