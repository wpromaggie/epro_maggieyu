<?php

class mod_eppctwo_email_log_entry extends rs_object{
	public static $db, $cols, $primary_key;
	public static $department_options;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$department_options = array_merge(sbs_lib::$departments, array('combined'));
		self::$primary_key = array('id');
		self::$indexes = array(
			array('department', 'account_id'),
			array('created')
		);

		self::$uniques = array(array(;'','',''));

		self::$cols = self::init_cols(
			new rs_col('id'                         ,'bigint'                ,20    ,''		),
			new rs_col('department'                 ,'char'                  ,2     ,''		),
			new rs_col('account_id'                 ,'char'                  ,16    ,''		),
			new rs_col('created'                    ,'datetime'              ,''    ,''		),
			new rs_col('sent_success'               ,'tinyint'               ,1     ,''		),
			new rs_col('sent_details'               ,'varchar'               ,128   ,''		),
			new rs_col('type'                       ,'varchar'               ,32    ,''		),
			new rs_col('from'                       ,'varchar'               ,128   ,''		),
			new rs_col('to'                         ,'varchar'               ,128   ,''		),
			new rs_col('subject'                    ,'varchar'               ,128   ,''		),
			new rs_col('headers'                    ,'varchar'               ,512   ,''		),
			new rs_col('body'                       ,'text'                  ,''    ,''		)
			);
	}
}
?>
