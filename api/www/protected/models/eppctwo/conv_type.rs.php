<?php

// conv types might have differently spelled names in different markets but actually
// represent the same action
class mod_eppctwo_conv_type extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('aid')
		);
		self::$cols = self::init_cols(
			new rs_col('id'       ,'char',8,'',rs::READ_ONLY),
			new rs_col('aid'      ,'char',16,'',rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('canonical','char',64,'',rs::NOT_NULL)
		);
	}

	// 16 hex chars
	protected function uprimary_key($i)
	{
		$sha = sha1(mt_rand());
		return strtoupper(substr($sha, 0, 8));
	}

	public static function get_client_market_map($aid, $market = false)
	{
		$qwhere = array("aid = :aid");
		$qdata = array("aid" => $aid);
		if ($market) {
			$qwhere[] = "market = :market";
			$qdata["market"] = $market;
		}
		$tmp_map = conv_type::get_all(array(
			"select" => array(
				"conv_type" => array("id", "canonical"),
				"conv_type_market" => array("conv_type_id", "market", "market_name")
			),
			"where" => $qwhere,
			"data" => $qdata,
			"join_many" => array(
				"conv_type_market" => "conv_type.id = conv_type_market.conv_type_id"
			)
		));
		$map = array();
		foreach ($tmp_map as $mapping) {
			foreach ($mapping->conv_type_market as $ct_market) {
				if ($market) {
					$map[$ct_market->market] = $mapping->canonical;
				}
				else {
					$map[$ct_market->market][$ct_market->market_name] = $mapping->canonical;
				}
			}
		}
		return $map;
	}
}
?>
