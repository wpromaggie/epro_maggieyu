<?php

class mod_eppctwo_ql_spend extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('account_id');
		self::$cols = self::init_cols(
			new rs_col('account_id'                 ,'char'                  ,16    ,''		),
			new rs_col('days_to_date'               ,'tinyint'               ,3     ,''		),
			new rs_col('days_remaining'             ,'tinyint'               ,3     ,''		),
			new rs_col('days_in_month'              ,'tinyint'               ,3     ,''		),
			new rs_col('imps_to_date'               ,'int'                   ,10    ,''		),
			new rs_col('spend_to_date'              ,'double'                ,''    ,''		),
			new rs_col('spend_remaining'            ,'double'                ,''    ,''		),
			new rs_col('spend_prev_month'           ,'double'                ,''    ,''		),
			new rs_col('daily_to_date'              ,'double'                ,''    ,''		),
			new rs_col('daily_remaining'            ,'double'                ,''    ,''		),
			new rs_col('imps'                       ,'int'                   ,10    ,''		),
			new rs_col('clicks'                     ,'int'                   ,10    ,''		),
			new rs_col('cost'                       ,'double unsigned'       ,''    ,''		),
			new rs_col('g_imps'                     ,'int'                   ,10    ,''		),
			new rs_col('g_clicks'                   ,'int'                   ,10    ,''		),
			new rs_col('g_cost'                     ,'double unsigned'       ,''    ,''		),
			new rs_col('m_imps'                     ,'int'                   ,10    ,''		),
			new rs_col('m_clicks'                   ,'int'                   ,10    ,''		),
			new rs_col('m_cost'                     ,'double unsigned'       ,''    ,''		)
			);
	}
}
?>
