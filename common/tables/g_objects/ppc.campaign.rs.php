<?php
class campaign extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('account_id'      ,'char'    ,32  ,''                   ,rs::NOT_NULL),
			new rs_col('id'              ,'char'    ,32  ,''                   ,rs::NOT_NULL),
			new rs_col('mod_date'        ,'date'    ,null,'0000-00-00'         ,rs::NOT_NULL),
			new rs_col('status'          ,'char'    ,16  ,null                 ,rs::NOT_NULL),
			new rs_col('ag_info_mod_time','datetime',null,'0000-00-00 00:00:00',rs::NOT_NULL),
			new rs_col('text'            ,'char'    ,128 ,''                   ,rs::NOT_NULL)
		);
	}
}
?>