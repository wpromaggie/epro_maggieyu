<?php
class mod_log_request extends rs_object
{
	public static $db, $cols, $primary_key, $has_one, $has_many;

	public static $interface_options = array('cgi', 'cli');
	public static $context_options = array('browser', 'ajax', 'api', 'cron', 'delly', 'tty');

	public static function set_table_definition()
	{
		self::$db = 'log';
		self::$cols = self::init_cols(
			new rs_col('id'        ,'bigint' ,null,null,rs::AUTO_INCREMENT | rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('interface' ,'enum'   ,8   ,''  ,rs::NOT_NULL),
			new rs_col('user'      ,'char'   ,32  ,''  ,rs::NOT_NULL),
			new rs_col('context'   ,'enum'   ,16  ,''  ,rs::NOT_NULL),
			new rs_col('start_time','double'  ,null,0  ,rs::NOT_NULL),
			new rs_col('end_time'  ,'double' ,null,0   ,rs::NOT_NULL),
			new rs_col('elapsed'   ,'double' ,null,0   ,rs::NOT_NULL),
			new rs_col('hostname'  ,'char'   ,32  ,''  ,rs::NOT_NULL),
			new rs_col('max_memory','bigint' ,null,0   ,rs::NOT_NULL),
			new rs_col('is_error'  ,'tinyint',null,0   ,rs::NOT_NULL),
			// cgi only
			// todo: create sub class
			new rs_col('url'       ,'varchar',1024,''   ,rs::NOT_NULL),
			new rs_col('ip'        ,'char'   ,16  ,''   ,rs::NOT_NULL),
			// cli only, see todo above
			new rs_col('path'      ,'char'   ,200 ,''   ,rs::NOT_NULL),
			new rs_col('args'      ,'char'   ,200 ,''   ,rs::NOT_NULL)
		);
		self::$primary_key = array('id');
	}
}
?>