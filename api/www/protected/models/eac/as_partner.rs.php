<?php

class mod_eac_as_partner extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'char'                  ,16    ,''		),
			new rs_col('ppc_fee'                    ,'double'                ,''    ,''		),
			new rs_col('ppc_budget'                 ,'double'                ,''    ,''		),
			new rs_col('smo_fee'                    ,'double'                ,''    ,''		),
			new rs_col('seo_fee'                    ,'double'                ,''    ,''		)
			);
	}
}
?>
