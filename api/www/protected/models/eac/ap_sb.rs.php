<?php

class mod_eac_ap_sb extends mod_eac_product
{
	public static $db, $cols, $primary_key;
	
	const FAN_PAGE_AMOUNT = 100;
	
	public static $plan_options = array('Express', 'Starter', 'Core', 'Premier', 'silver', 'gold', 'platinum');
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'      ,'char',16  ,''    ,rs::READ_ONLY),
			new rs_col('has_ads' ,'bool',null,0 ,rs::NOT_NULL),
			new rs_col('has_soci','bool',null,0 ,rs::NOT_NULL)
		);
	}
	
	public static function get_activation_billing_keys()
	{
		$keys = parent::get_activation_billing_keys();
		$keys['fan_page'] = 'Fan Page';
		return $keys;
	}
	
	public static function calc_order_billing_set_dept_data(&$alldata, &$data, $num)
	{
		$keys = array('is_fan_page');
		foreach ($keys as $keybase)
		{
			$key = $keybase.((is_numeric($num)) ? '_'.$num : '');
			$data[$keybase] = $alldata[$key];
		}
	}
	
	public static function calc_order_billing_dept(&$billing, &$data)
	{
		if ($data['is_fan_page'])
		{
			$billing['fan_page'] = self::FAN_PAGE_AMOUNT;
			$billing['today'] += self::FAN_PAGE_AMOUNT;
		}
		else
		{
			$billing['fan_page'] = 0;
		}
	}
	
	public function load_from_post($prod_num)
	{
		parent::load_from_post($prod_num, array('is_fan_page'));
	}
}
?>
