<?php

class sb_lib
{
	public static $markets = array('f');
	
	public static $relationship_map;
	
	// map from fb to e2
	public static function get_relationship_map()
	{
		if (!self::$relationship_map)
		{
			self::$relationship_map = array(
				'Single' => 'single',
				'In a Relationship' => 'relationship',
				'Engaged' => 'engaged',
				'Married' => 'married'
			);
		}
		return self::$relationship_map;
	}
	
	public static function get_ad_geotargeting($ad)
	{
		$geo = array(
			'cities' => '',
			'regions' => '',
			'zips' => ''
		);
		
		// location
		if ($ad['location_type'] == 'city')
		{
			$tmp_cities = db::select("
				select concat(city, ', ', state)
				from eppctwo.sb_ad_location
				where ad_id = '{$ad['id']}'
			");
			$geo['cities'] = implode('; ', $tmp_cities);
		}
		else if ($ad['location_type'] == 'state')
		{
			$geo['regions'] = self::ad_get_many($ad['id'], 'sb_ad_location', 'state');
		}
		else if ($ad['location_type'] == 'zip')
		{
			$geo['zips'] = self::ad_get_many($ad['id'], 'sb_ad_location', 'zip');
		}
		return $geo;
	}
	
	public static function ad_get_many($ad_id, $table, $col, $separator = ', ')
	{
		$tmp = db::select("
			select {$col}
			from eppctwo.{$table}
			where ad_id = '{$ad_id}'
		");
		return implode($separator, $tmp);
	}
	
	public static function get_ad_relationships($ad_id)
	{
		// relationship
		$map = array_flip(self::get_relationship_map());
		$relationships = array();
		$e2_relationships = db::select_row("
			select ".implode(', ', array_keys($map))."
			from eppctwo.sb_ad_relationship
			where ad_id = {$ad_id}
		", 'ASSOC');
		if ($e2_relationships)
		{
			foreach ($e2_relationships as $e2_val => $is_on)
			{
				if ($is_on)
				{
					$relationships[] = $map[$e2_val];
				}
			}
		}
		return $relationships;
	}
	
	public static function update_url_data($url, $market, $start_date, $end_date = null)
	{
		return false;
	}
}

?>