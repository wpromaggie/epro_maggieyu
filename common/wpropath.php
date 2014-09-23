<?php
require_once(\epro\WPROPHP_PATH.'gae/gae.php');

class wpropath
{
	const ADMIN_EMAIL = 'chimdi@wpromote.com';
	
	public static function setting($k, $v = null)
	{
		$post_data = array('k' => $k);
		if (!is_null($v)) $post_data['v'] = $v;
		
		return gae::post('setting', $post_data);
	}
	
	public static function set_account($ac_id, $dest_url, $analytics_type, $track_non_wpro_convs)
	{
		$post_data = array(
			'a' => $ac_id,
			'default_dest_url' => $dest_url,
			'analytics_type' => $analytics_type,
			'track_non_wpro_convs' => $track_non_wpro_convs
		);
		return gae::post('set_account', $post_data);
	}
	
	public static function set_dest_urls($type, $ac_id, $market, $ag_id, $entities, $allow_empty = false)
	{
		$post_data = array(
			'a' => $ac_id,
			'm' => $market,
			'type' => $type,
			'g' => $ag_id
		);
		$i = 0;
		foreach ($entities as &$entity)
		{
			$dest_url = $entity['destination_url'];
			if (empty($dest_url) && !$allow_empty) continue;
			
			if (strpos($dest_url, util::TRACKER_REDIRECT_URL) !== false) continue;
			
			$post_data['id_'.$i] = $entity['id'];
			$post_data['dest_url_'.$i] = $dest_url;
			++$i;
		}
		if ($i == 0)
		{
			return;
		}
		return gae::post('set_dest_urls', $post_data);
	}
	
	public static function get_convs($start_time, $end_time, $ac_id = null, $do_get_all_convs = false)
	{
		$post_data = array(
			's' => $start_time,
			'e' => $end_time
		);
		if (!is_null($ac_id))
		{
			$post_data['a'] = $ac_id;
		}
		if ($do_get_all_convs)
		{
			$post_data['get_all_convs'] = 1;
		}
		return gae::post('get_convs', $post_data);
	}
	
	public static function get_snapshot($start_time, $end_time, $ac_id = null)
	{
		$post_data = array(
			's' => $start_time,
			'e' => $end_time
		);
		if (!is_null($ac_id))
		{
			$post_data['a'] = $ac_id;
		}
		return gae::post('get_snapshot', $post_data);
	}
	
	public static function delete_dest_urls($type, $ac_id, $market, $ag_id)
	{
		$post_data = array(
			'a' => $ac_id,
			'm' => $market,
			'type' => $type,
			'g' => $ag_id
		);
		return gae::post('delete_dest_urls', $post_data);
	}
	
	public static function get_dest_urls($ac_id, $market, $ag_id)
	{
		return gae::post('get_dest_urls', array(
			'a' => $ac_id,
			'm' => $market,
			'g' => $ag_id
		));
	}
	
	public static function get_clicks($ac_id, $start_date, $end_date, $market = null, $ag_id = null)
	{
		$post_data = array(
			'a' => $ac_id,
			's' => strtotime($start_date),
			'e' => strtotime($end_date) + 86399
		);
		if (!is_null($market))
		{
			$post_data['m'] = $market;
		}
		if (!is_null($ag_id))
		{
			$post_data['g'] = $ag_id;
		}
		return gae::post('get_clicks', $post_data);
	}
	
	
	public static function get_raw_redirects($start_dt, $end_dt)
	{
		return gae::post('get_raw_redirects', array(
			's' => strtotime($start_dt),
			'e' => strtotime($end_dt)
		));
	}
	
	public static function delete_redirects($start_dt, $end_dt)
	{
		return gae::post('delete_redirects', array(
			's' => strtotime($start_dt),
			'e' => strtotime($end_dt)
		));
	}
	
	public static function set_conv_data($cl_id, $data_id, $market, $start_date, $end_date)
	{
		$convs = db::select("
			select click_date, account, campaign, ad_group, ad, keyword, value
			from gae_convs
			where
				client = '$cl_id' &&
				market = '$market' &&
				click_date >= '$start_date' &&
				click_date <= '$end_date' &&
				!is_dup &&
				!is_test
		");
		if (empty($convs))
		{
			return;
		}
		// clear out previous conversion data
/*
		db::update("update {$market}_data.clients_{$data_id}   set convs=0, revenue=0 where client='$cl_id' && data_date >= '$start_date' && data_date <= '$end_date'");
		db::update("update {$market}_data.campaigns_{$data_id} set convs=0, revenue=0 where client='$cl_id' && data_date >= '$start_date' && data_date <= '$end_date'");
		db::update("update {$market}_data.ad_groups_{$data_id} set convs=0, revenue=0 where client='$cl_id' && data_date >= '$start_date' && data_date <= '$end_date'");
		db::update("update {$market}_data.ads_{$data_id}       set convs=0, revenue=0 where client='$cl_id' && data_date >= '$start_date' && data_date <= '$end_date'");
		db::update("update {$market}_data.keywords_{$data_id}  set convs=0, revenue=0 where client='$cl_id' && data_date >= '$start_date' && data_date <= '$end_date'");
*/
		$by_date = array();
		for ($i = 0; list($date, $ac_id, $ca_id, $ag_id, $ad_id, $kw_id, $value) = $convs[$i]; ++$i)
		{
			// what is wrong with you google?
			// and what do we do with these "conversions"?
			if ($market == 'g')
			{
				$click_sanity_check = db::select_one("
					select count(*)
					from {$market}_data.keywords_{$data_id}
					where ad_group = '$ag_id' && keyword = '$kw_id'
				");
				if (!$click_sanity_check)
				{
					continue;
				}
			}
			$by_date[$date]['convs'] += 1;
			$by_date[$date]['value'] += $value;
			
			$by_date[$date]['acs'][$ac_id]['cas'][$ca_id]['convs'] += 1;
			$by_date[$date]['acs'][$ac_id]['cas'][$ca_id]['value'] += $value;
			
			$by_date[$date]['acs'][$ac_id]['cas'][$ca_id]['ags'][$ag_id]['convs'] += 1;
			$by_date[$date]['acs'][$ac_id]['cas'][$ca_id]['ags'][$ag_id]['value'] += $value;
			
			$by_date[$date]['acs'][$ac_id]['cas'][$ca_id]['ags'][$ag_id]['ads'][$ad_id]['convs'] += 1;
			$by_date[$date]['acs'][$ac_id]['cas'][$ca_id]['ags'][$ag_id]['ads'][$ad_id]['value'] += $value;
			
			$by_date[$date]['acs'][$ac_id]['cas'][$ca_id]['ags'][$ag_id]['kws'][$kw_id]['convs'] += 1;
			$by_date[$date]['acs'][$ac_id]['cas'][$ca_id]['ags'][$ag_id]['kws'][$kw_id]['value'] += $value;
		}
		
		foreach ($by_date as $date => &$cl_data)
		{
			db::insert_update("{$market}_data.clients_{$data_id}", array('client', 'data_date'), array(
				'client' => $cl_id,
				'data_date' => $date,
				'convs' => $cl_data['convs'],
				'revenue' => $cl_data['value']
			));
			
			$acs = &$cl_data['acs'];
			foreach ($acs as $ac_id => &$ac_data)
			{
				$cas = &$ac_data['cas'];
				foreach ($cas as $ca_id => &$ca_data)
				{
					db::insert_update("{$market}_data.campaigns_{$data_id}", array('campaign', 'data_date'), array(
						'client' => $cl_id,
						'account' => $ac_id,
						'campaign' => $ca_id,
						'data_date' => $date,
						'convs' => $ca_data['convs'],
						'revenue' => $ca_data['value']
					));
					
					$ags = &$ca_data['ags'];
					foreach ($ags as $ag_id => &$ag_data)
					{
						db::insert_update("{$market}_data.ad_groups_{$data_id}", array('ad_group', 'data_date'), array(
							'client' => $cl_id,
							'account' => $ac_id,
							'campaign' => $ca_id,
							'ad_group' => $ag_id,
							'data_date' => $date,
							'convs' => $ag_data['convs'],
							'revenue' => $ag_data['value']
						));
						
						$ads = &$ag_data['ads'];
						foreach ($ads as $ad_id => &$ad_data)
						{
							db::insert_update("{$market}_data.ads_{$data_id}", array('ad_group', 'ad', 'data_date'), array(
								'client' => $cl_id,
								'account' => $ac_id,
								'campaign' => $ca_id,
								'ad_group' => $ag_id,
								'ad' => $ad_id,
								'data_date' => $date,
								'convs' => $ad_data['convs'],
								'revenue' => $ad_data['value']
							));
						}
						
						$kws = &$ag_data['kws'];
						foreach ($kws as $kw_id => &$kw_data)
						{
							db::insert_update("{$market}_data.keywords_{$data_id}", array('ad_group', 'keyword', 'data_date'), array(
								'client' => $cl_id,
								'account' => $ac_id,
								'campaign' => $ca_id,
								'ad_group' => $ag_id,
								'keyword' => $kw_id,
								'data_date' => $date,
								'convs' => $kw_data['convs'],
								'revenue' => $kw_data['value']
							));
						}
					}
				}
			}
		}
	}
}

?>