<?php

class mod_eppctwo_gae_convs extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('gae_conv_id');
		self::$indexes = array(array('click_date','client'));

		self::$uniques = array(array(;'',''));

		self::$cols = self::init_cols(
			new rs_col('gae_conv_id'                ,'varchar'               ,64    ,''		),
			new rs_col('gae_redirect_id'            ,'varchar'               ,64    ,''		),
			new rs_col('market'                     ,'varchar'               ,4     ,''		),
			new rs_col('client'                     ,'varchar'               ,32    ,''		),
			new rs_col('account'                    ,'varchar'               ,32    ,''		),
			new rs_col('campaign'                   ,'varchar'               ,32    ,''		),
			new rs_col('ad_group'                   ,'varchar'               ,32    ,''		),
			new rs_col('ad'                         ,'varchar'               ,32    ,''		),
			new rs_col('keyword'                    ,'varchar'               ,32    ,''		),
			new rs_col('click_date'                 ,'date'                  ,''    ,''		),
			new rs_col('click_time'                 ,'time'                  ,''    ,''		),
			new rs_col('conv_date'                  ,'date'                  ,''    ,''		),
			new rs_col('conv_time'                  ,'time'                  ,''    ,''		),
			new rs_col('oid'                        ,'varchar'               ,64    ,''		),
			new rs_col('name1'                      ,'varchar'               ,64    ,''		),
			new rs_col('name2'                      ,'varchar'               ,64    ,''		),
			new rs_col('name3'                      ,'varchar'               ,64    ,''		),
			new rs_col('value'                      ,'double'                ,''    ,''		),
			new rs_col('is_dup'                     ,'tinyint'               ,4     ,''		),
			new rs_col('is_test'                    ,'tinyint'               ,4     ,''		)
			);
	}
}
?>
