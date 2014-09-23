<?php

class mod_eppctwo_sbs_contacts extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'bigint'                ,20    ,''		),
			new rs_col('interest_page_id'           ,'int'                   ,10    ,''		),
			new rs_col('name'                       ,'varchar'               ,64    ,''		),
			new rs_col('email'                      ,'varchar'               ,128   ,''		),
			new rs_col('phone'                      ,'varchar'               ,16    ,''		),
			new rs_col('company'                    ,'varchar'               ,64    ,''		),
			new rs_col('url'                        ,'text'                  ,''    ,''		),
			new rs_col('budget'                     ,'double'                ,''    ,''		),
			new rs_col('interests'                  ,'varchar'               ,64    ,''		),
			new rs_col('source'                     ,'varchar'               ,32    ,''		),
			new rs_col('status'                     ,'varchar'               ,32    ,''		),
			new rs_col('created'                    ,'datetime'              ,''    ,''		),
			new rs_col('notes'                      ,'text'                  ,''    ,''		),
			new rs_col('referer'                    ,'varchar'               ,64    ,''		),
			new rs_col('referring_url'              ,'text'                  ,''    ,''		),
			new rs_col('cat'                        ,'varchar'               ,32    ,''		),
			new rs_col('subid'                      ,'varchar'               ,32    ,''		),
			new rs_col('ip'                         ,'varchar'               ,40    ,''		),
			new rs_col('browser'                    ,'varchar'               ,200   ,''		),
			new rs_col('lphid'                      ,'int'                   ,11    ,''		)
			);
	}
}
?>
