<?php

class mod_eppctwo_ql_data_source extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('market','campaign','ad_group');
		self::$indexes = array(
			array('account_id')
		);
		self::$cols = self::init_cols(
			new rs_col('account_id','varchar',32,'' ,rs::NOT_NULL),
			new rs_col('market'   ,'varchar',4 ,'' ,rs::NOT_NULL),
			new rs_col('account'  ,'varchar',32,'' ,rs::NOT_NULL),
			new rs_col('campaign' ,'varchar',32,'' ,rs::NOT_NULL),
			new rs_col('ad_group' ,'varchar',32,'' ,rs::NOT_NULL)
		);
	}

	public function get_ad_group_query($prefix = '')
	{
		if ($this->ad_group) {
			return "{$prefix}id = '{$this->ad_group}'";
		}
		else {
			return "{$prefix}campaign_id = '{$this->campaign}'";
		}
	}

	public function get_entity_query($prefix = '')
	{
		if ($this->ad_group) {
			return "{$prefix}ad_group_id = '{$this->ad_group}'";
		}
		else {
			return "{$prefix}campaign_id = '{$this->campaign}'";
		}
	}
}

?>
