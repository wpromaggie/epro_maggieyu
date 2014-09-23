<?php

class mod_eppctwo_conv_type_count extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('aid', 'd')
		);
		self::$cols = self::init_cols(
			new rs_col('id'         ,'char'    ,16  ,''    ,rs::READ_ONLY),
			new rs_col('aid'        ,'char'    ,16  ,''    ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('d'          ,'date'    ,null,rs::DD,rs::NOT_NULL),
			new rs_col('market'     ,'char'    ,2   ,''    ,rs::NOT_NULL),
			new rs_col('account_id' ,'char'    ,16  ,''    ,rs::NOT_NULL),
			new rs_col('campaign_id','char'    ,16  ,''    ,rs::NOT_NULL),
			new rs_col('ad_group_id','char'    ,32  ,''    ,rs::NOT_NULL),
			new rs_col('ad_id'      ,'char'    ,32  ,''    ,rs::NOT_NULL),
			new rs_col('keyword_id' ,'char'    ,32  ,''    ,rs::NOT_NULL),
			new rs_col('device'     ,'char'    ,32  ,''    ,rs::NOT_NULL),
			new rs_col('purpose'    ,'char'    ,64  ,''    ,rs::NOT_NULL),
			new rs_col('name'       ,'char'    ,64  ,''    ,rs::NOT_NULL),
			new rs_col('amount'     ,'smallint',null,0     ,rs::NOT_NULL)
		);
	}

	// 16 hex chars
	protected function uprimary_key($i)
	{
		$sha = sha1(mt_rand());
		return strtoupper(substr($sha, 0, 16));
	}

	public static function get_account_conv_types($aid, $opts = array())
	{
		util::set_opt_defaults($opts, array(
			'include_market' => false
		));
		$tmp_conv_types = conv_type_count::get_all(array(
			'select' => "distinct name, market",
			'where' => "aid = :aid && name <> ''",
			'data' => array("aid" => $aid),
			'order_by' => "name asc"
		));
		$map = conv_type::get_client_market_map($aid);
		$conv_types = array();
		foreach ($tmp_conv_types as $conv_type) {
			if ($opts['include_market']) {
				$conv_types[$conv_type->market][] = $conv_type->name;
			}
			else {
				$ct_name = (isset($map[$conv_type->market][$conv_type->name])) ?
					$map[$conv_type->market][$conv_type->name] :
					$conv_type->name
				;
				if (!in_array($ct_name, $conv_types)) {
					$conv_types[] = $ct_name;
				}
			}
		}
		return $conv_types;
	}
}
?>
