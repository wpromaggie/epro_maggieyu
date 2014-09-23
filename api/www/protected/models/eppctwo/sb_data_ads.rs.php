<?php

class mod_eppctwo_sb_data_ads extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('ad_id','d');
		self::$indexes = array(array('client_id'));

		self::$uniques = array(array(;''));

		self::$cols = self::init_cols(
			new rs_col('client_id'                  ,'char'                  ,16    ,''		),
			new rs_col('campaign_id'                ,'varchar'               ,32    ,''		),
			new rs_col('ad_id'                      ,'varchar'               ,32    ,''		),
			new rs_col('d'                          ,'date'                  ,''    ,''		),
			new rs_col('imps'                       ,'int'                   ,10    ,''		),
			new rs_col('clicks'                     ,'mediumint'             ,8     ,''		),
			new rs_col('convs'                      ,'smallint'              ,5     ,''		),
			new rs_col('cost'                       ,'double unsigned'       ,''    ,''		),
			new rs_col('pos_sum'                    ,'int'                   ,10    ,''		),
			new rs_col('revenue'                    ,'double'                ,''    ,''		)
			);
	}
}
?>
