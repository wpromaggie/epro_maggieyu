<?php

class mod_eppctwo_f_ad_group extends rs_object
{
	public static $db, $cols, $primary_key, $uniques;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$uniques = array(
			array('campaign_id', 'text')
		);
		self::$cols = self::init_cols(
			new rs_col('id'         ,'int'    ,null,null,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('campaign_id','int'    ,null,null,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('text'       ,'varchar',128 ,''  ,rs::NOT_NULL)
		);
	}
}

?>
