<?php

class mod_eppctwo_sb_ads extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'bigint'                ,20    ,''		),
			new rs_col('group_id'                   ,'char'                  ,16    ,''		),
			new rs_col('edit_status'                ,'char'                  ,8     ,''		),
			new rs_col('status'                     ,'char'                  ,16    ,''		),
			new rs_col('name'                       ,'varchar'               ,35    ,''		),
			new rs_col('bid_type'                   ,'char'                  ,8     ,''		),
			new rs_col('max_bid'                    ,'decimal'               ,3,2   ,''		),
			new rs_col('title'                      ,'varchar'               ,25    ,''		),
			new rs_col('image'                      ,'varchar'               ,512   ,''		),
			new rs_col('body_text'                  ,'varchar'               ,135   ,''		),
			new rs_col('link'                       ,'varchar'               ,256   ,''		),
			new rs_col('location_type'              ,'char'                  ,8     ,''		),
			new rs_col('country'                    ,'varchar'               ,2     ,''		),
			new rs_col('radius'                     ,'smallint'              ,5     ,''		),
			new rs_col('min_age'                    ,'int'                   ,2     ,''		),
			new rs_col('max_age'                    ,'int'                   ,2     ,''		),
			new rs_col('sex'                        ,'char'                  ,8     ,''		),
			new rs_col('education_status'           ,'char'                  ,16    ,''		),
			new rs_col('college_year_min'           ,'varchar'               ,4     ,''		),
			new rs_col('college_year_max'           ,'varchar'               ,4     ,''		),
			new rs_col('interested_in'              ,'char'                  ,8     ,''		),
			new rs_col('language'                   ,'varchar'               ,5     ,''		),
			new rs_col('birthday'                   ,'char'                  ,8     ,''		),
			new rs_col('client_creates'             ,'int'                   ,1     ,''		),
			new rs_col('create_date'                ,'datetime'              ,''    ,''		),
			new rs_col('fb_id'                      ,'varchar'               ,32    ,''		)
			);
	}
}
?>
