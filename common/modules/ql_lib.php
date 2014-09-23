<?php
// quicklist often uses ppc functionality
util::load_lib('ppc');

class ql_lib
{
	// number of days prior to rollover when we send report to accounts which get reporting
	const EMAIL_REPORTING_DAYS = 3;
	
	public static $markets = array('g', 'm');
	
	// lazy loaded
	public static $plans;
	
	public static function get_mcc_accounts($market)
	{
		if ($market == 'm')
		{
			return array(
				array('Wpquick1', 'QL1201moth', '4007572'),
				//array('adultql', 'QL1201moth')
			);
		}
	}
	
	public static function refresh_accounts($market)
	{
		if ($market == 'g') {
			$new_acs = util::refresh_accounts('g');
		}
		else if ($market == 'm') {
			$mcc_accounts = ql_lib::get_mcc_accounts($market);
			$new_acs = array();
			for ($i = 0, $ci = count($mcc_accounts); $i < $ci && list($user, $pass, $customer_id) = $mcc_accounts[$i]; ++$i) {
				$customer_acs = util::refresh_accounts($market, $user, $pass, $customer_id);
				$new_acs = array_merge($new_acs, $customer_acs);
			}
		}
		foreach ($new_acs as $new_ac) {
			if (array_key_exists('id', $new_ac)) {
				list($ac_id, $ac_text) = util::list_assoc($new_ac, 'id', 'text');
			}
			else {
				list($ac_id, $ac_text) = $new_ac;
			}
			if (ql_lib::is_ql_account($ac_text)) {
				ppc_lib::create_market_object_tables($market, "Q{$ac_id}");
			}
		}
	}

	// all QL accounts start with "QL" by definition
	// 02.05.2012: no more lal
	public static function is_ql_account($account_name)
	{
		return (
			(preg_match("/^ql/i", $account_name) || stripos($account_name, 'quick list') !== false)
			&&
			(!preg_match("/\blal\b/i", $account_name))
		);
	}
	
	public static function prepare_keywords($keywords)
	{
		if (!is_array($keywords))
		{
			$keywords = explode("\t", $keywords);
		}
		$keywords = array_values(array_filter(array_unique($keywords)));
		usort($keywords, 'strcasecmp');
		return $keywords;
	}
	
	public static function get_ads($account, $market, $ds)
	{
		if (!method_exists($ds, 'get_entity_query')) {
			return array();
		}
		else {
			$ads = db::select("
				select ads.text, ads.desc_1, ads.desc_2, ads.dest_url, ads.disp_url
				from {$market}_objects.ad_Q{$ds->account} ads, {$market}_objects.ad_group_Q{$ds->account} ags
				where
					".$ds->get_entity_query()." &&
					ads.status = 'On' &&
					ags.status = 'On' &&
					ads.ad_group_id = ags.id
			", 'ASSOC');
			return $ads;
		}
	}

	public static function get_keywords($account, $market, $ds)
	{
		if (!method_exists($ds, 'get_entity_query')) {
			return array();
		}
		else {
			$market_keywords = db::select("
				select distinct kws.text
				from {$market}_objects.keyword_Q{$ds->account} kws, {$market}_objects.ad_group_Q{$ds->account} ags
				where
					".$ds->get_entity_query()." &&
					kws.status = 'On' &&
					ags.status = 'On' &&
					kws.ad_group_id = ags.id
			");
			return $market_keywords;
		}
	}

	public static function get_market_info($account, $data_sources = false)
	{
		if (!$data_sources) {
			$data_sources = $account->get_data_sources();
		}
		$keywords = array();
		$ads = array();
		foreach (self::$markets as $market) {
			if (!$ads) {
				$ads = self::get_ads($account, $market, $data_sources->a[$market]);
			}
			$market_keywords = self::get_keywords($account, $market, $data_sources->a[$market]);
			if (is_array($market_keywords)) {
				$keywords = array_merge($keywords, $market_keywords);
			}
		}
		if ($ads) {
			foreach ($ads as &$ad) {
				if (preg_match("/{keyword:\s*(.*)}/i", $ad['text'], $matches)) {
					$ad['text'] = $matches[1];
				}
				$ad['one_line'] = ($ad['desc_2']) ? $ad['desc_1'].' '.$ad['desc_2'] : $ad['desc_1'];
			}
		}
		$keywords = ql_lib::prepare_keywords($keywords);
		self::set_plans();
		$max_keywords = util::unempty(self::$plans[$account->plan]['num_keywords'], 10);
		if ($account->alt_num_keywords) {
			$max_keywords = $account->alt_num_keywords;
		}
		$num_keywords = count($keywords);
		if ($num_keywords < $max_keywords) {
			$keywords = array_merge($keywords, array_fill(0, $max_keywords - $num_keywords, ''));
		}
		else if ($num_keywords > $max_keywords) {
			$keywords = array_slice($keywords, 0, $max_keywords);
		}
		return array(
			'ads' => $ads,
			'keywords' => $keywords
		);
	}
	
	public static function set_plans()
	{
		// plans have already been loaded
		if (self::$plans)
		{
			return;
		}
		self::$plans = db::select("
			select name, budget, num_keywords
			from eppctwo.ql_plans
		", 'ASSOC', 'name');
	}
	
	public static function update_client_ads($account, $market, $ds)
	{
		$ads = db::select("
			select '{$account->id}' account_id, a.text, a.desc_1, a.desc_2, a.disp_url, qa.id, qa.is_su
			from {$market}_objects.ad_Q{$ds->account} a
			join eppctwo.ql_ad qa on
				qa.market = '{$market}' &&
				a.ad_group_id = qa.ad_group_id &&
				a.id = qa.ad_id
			where
				".$ds->get_entity_query('a.')." &&
				a.status in ('On', 'Active')
		", 'ASSOC');
		
		if ($ads) {
			util::wpro_post('account', 'ql_set_ads', array(
				'aid' => $account->id,
				'ads' => serialize($ads)
			));
			
			if (class_exists('feedback')) {
				feedback::add_success_msg('SU Ads Updated');
			}
		}
	}
	
	public static function update_client_keywords($account, $data_sources = false)
	{
		$market_info = ql_lib::get_market_info($account, $data_sources);
		if ($market_info['keywords']) {
			util::wpro_post('account', 'ql_set_keywords', array(
				'aid' => $account->id,
				'keywords' => implode("\t", $market_info['keywords'])
			));
			if (class_exists('feedback')) {
				feedback::add_success_msg('SU Keywords Updated');
			}
		}
	}
	
	public static function lal_get_full_url($url)
	{
		$url_info = parse_url($url);
		$fp = fsockopen($url_info['host'], 80);

		if ($fp)
		{
			$out  = "HEAD ".$url_info['path']." HTTP/1.1\r\n";
			$out .= "Host: ".$url_info['host']."\r\n";
			$out .= "Connection: Close\r\n\r\n";

			fwrite($fp, $out);
			$response = fread($fp, 8192);
			fclose($fp);

			$lines = explode("\n", $response);
			foreach ($lines as $line)
			{
				$line = trim($line);
				if (stripos($line, 'location') === 0)
				{
					if (preg_match("/^location:\s+(.*\/details\/(\d+\/.*?)\..*)$/i", $line, $matches))
					{
						list($ph, $full_url, $ac_name) = $matches;
						return $full_url;
					}
				}
			}
		}
		return $url;
	}
	
}

?>