<?php

class mod_eppctwo_sb_ad_relationship extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('ad_id');
		self::$cols = self::init_cols(
			new rs_col('ad_id'                      ,'bigint'                ,20    ,''		),
			new rs_col('single'                     ,'tinyint'               ,1     ,''		),
			new rs_col('relationship'               ,'tinyint'               ,1     ,''		),
			new rs_col('engaged'                    ,'tinyint'               ,1     ,''		),
			new rs_col('married'                    ,'tinyint'               ,1     ,''		)
			);
	}
}
?>
