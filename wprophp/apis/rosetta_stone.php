<?php

/*
 * convert from proprietry market terminology to wpro standard vernacular and back
 * 
 */

class api_translate
{
	// standard name and market name of object type
	public static $standard_type, $market_type;
	
	// the market we are going from/to
	public static $market;
	
	// column map from/to market
	public static $converter;
	
	// default values for sending data to market
	public static $market_defaults = array();
	
	// report columns definition
	public static $report_cols;
	
	
	public static function standardize_array(&$a)
	{
		$standardized = array();
		for ($i = 0, $count = count($a); $i < $count; ++$i)
			$standardized[] = api_translate::standardize_data($a[$i]);
		return ($standardized);
	}
	
	public static function standardize_data(&$d)
	{
		$temp = array();
		if (empty($d)) return $temp;
		foreach ($d as $market_key => $v)
		{
			$standard_key = (array_key_exists($market_key, self::$converter)) ? self::$converter[$market_key] : $market_key;
			
			$conversion_func = 'standardize_'.self::$market.'_'.self::$standard_type.'_'.$standard_key;
			if (method_exists('api_translate', $conversion_func)) $v = api_translate::$conversion_func($v, $d);
			$temp[$standard_key] = $v;
		}
		return ($temp);
	}
	
	public static function marketize_array(&$a)
	{
		$market_xml = '';
		for ($i = 0, $count = count($a); $i < $count; ++$i)
			$market_xml .= '<'.self::$market_type.">\n".self::marketize_data($a[$i]).'</'.self::$market_type.">\n";
		return $market_xml;
	}
	
	public static function marketize_data(&$d)
	{
		// keep track of the market keys so we can check defaults later
		$market_keys = array();
		$xml = '';
		foreach ($d as $standard_key => $v)
		{
			$market_key = (array_key_exists($standard_key, self::$converter)) ? self::$converter[$standard_key] : $standard_key;
			$market_keys[$market_key] = 1;
			
			$conversion_func = 'marketize_'.self::$market.'_'.self::$standard_type.'_'.$standard_key;
			if (is_array($v))
			{
				foreach ($v as $v1)
				{
					if (method_exists('api_translate', $conversion_func)) $v1 = api_translate::$conversion_func($v1, $d);
					$xml .= '<'.$market_key.'>'.strings::xml_encode($v1).'</'.$market_key.">\n";
				}
			}
			else
			{
				if (method_exists('api_translate', $conversion_func)) $v = api_translate::$conversion_func($v, $d);
				// conversion func can return array of the form array(value, key) if it wants to change the market key
				// value must be first because of the implementation of list()
				if (is_array($v)) list($v, $market_key) = $v;
				$xml .= '<'.$market_key.'>'.strings::xml_encode($v).'</'.$market_key.">\n";
			}
		}
		foreach (self::$market_defaults as $market_key => $default_value)
		{
			if (!array_key_exists($market_key, $market_keys))
				$xml .= '<'.$market_key.'>'.$default_value.'</'.$market_key.">\n";
		}
		return ($xml);
	}
	
	public static function standardize_header($market, $header)
	{
		switch ($market)
		{
			case ('g'):
				self::$converter = array(
					'units' => 'quota',
					'responseTime' => 'response_time',
					'requestId' => 'request_id'
				);
				break;
			
			case ('y'):
				self::$converter = array(
					'quotaUsedForThisRequest' => 'quota',
					'timeTakenMillis' => 'response_time',
					'sid' => 'request_id'
				);
				break;
			
			case ('m'):
				self::$converter = array(
					'TrackingId' => 'request_id'
				);
				break;
		}
		
		return (api_translate::standardize_data($header));
	}
	
	public static function marketize_g_report_type($type)
	{
		switch ($type)
		{
			case ('Complete'): return 'Creative';
			default: return $type;
		}
	}
	
	public static function marketize_y_report_type($type)
	{
		switch ($type)
		{
			case ('Complete'): return 'KeywordSummaryByDay';
			default: return $type;
		}
	}

	public static function set_standard_report_columns()
	{
		if (empty(api_translate::$report_cols))
			api_translate::$report_cols = array(
				'Account ID'    => array('g' =>  1, 'g_refresh' =>  1, 'm' =>  0, 'f' => 24, 'y_content_with_convs' =>  7, 'y_content_no_convs' =>  7, 'y_search_with_convs' =>  8, 'y_search_no_convs' =>  8),
				'Campaign ID'   => array('g' =>  2, 'g_refresh' =>  2, 'm' =>  2, 'f' =>  2, 'y_content_with_convs' =>  0, 'y_content_no_convs' =>  0, 'y_search_with_convs' =>  0, 'y_search_no_convs' =>  0),
				'Campaign Text' => array('g' =>  3, 'g_refresh' => -1, 'm' =>  3, 'f' =>  1, 'y_content_with_convs' =>  8, 'y_content_no_convs' =>  8, 'y_search_with_convs' =>  9, 'y_search_no_convs' =>  9),
				'Ad Group ID'   => array('g' =>  4, 'g_refresh' =>  3, 'm' =>  4, 'f' =>  2, 'y_content_with_convs' =>  1, 'y_content_no_convs' =>  1, 'y_search_with_convs' =>  1, 'y_search_no_convs' =>  1),
				'Ad Group Text' => array('g' =>  5, 'g_refresh' => -1, 'm' =>  5, 'f' =>  1, 'y_content_with_convs' =>  9, 'y_content_no_convs' =>  9, 'y_search_with_convs' => 10, 'y_search_no_convs' => 10),
				'Ad ID'         => array('g' =>  9, 'g_refresh' =>  5, 'm' =>  6, 'f' =>  4, 'y_content_with_convs' =>  2, 'y_content_no_convs' =>  2, 'y_search_with_convs' =>  3, 'y_search_no_convs' =>  3),
				'Keyword ID'    => array('g' =>  6, 'g_refresh' =>  4, 'm' =>  7, 'f' => -1, 'y_content_with_convs' => -1, 'y_content_no_convs' => -1, 'y_search_with_convs' =>  2, 'y_search_no_convs' =>  2),
				'Keyword Text'  => array('g' =>  7, 'g_refresh' => -1, 'm' =>  8, 'f' => -1, 'y_content_with_convs' => -1, 'y_content_no_convs' => -1, 'y_search_with_convs' => 11, 'y_search_no_convs' => 11),
				'Keyword Type'  => array('g' =>  8, 'g_refresh' => -1, 'm' =>  9, 'f' => -1, 'y_content_with_convs' => -1, 'y_content_no_convs' => -1, 'y_search_with_convs' => -1, 'y_search_no_convs' => -1),
				'Max CPC'       => array('g' => 10, 'g_refresh' => -1, 'm' => 10, 'f' => -1, 'y_content_with_convs' => -1, 'y_content_no_convs' => -1, 'y_search_with_convs' => -1, 'y_search_no_convs' => -1),
				'Device'        => array('g' => 19, 'g_refresh' => -1, 'm' => 17, 'f' =>  0, 'y_content_with_convs' =>  5, 'y_content_no_convs' =>  5, 'y_search_with_convs' =>  5, 'y_search_no_convs' =>  5),
				'Date'          => array('g' =>  0, 'g_refresh' =>  0, 'm' => 11, 'f' =>  0, 'y_content_with_convs' =>  5, 'y_content_no_convs' =>  5, 'y_search_with_convs' =>  5, 'y_search_no_convs' =>  5),
				'Impressions'   => array('g' => 11, 'g_refresh' => -1, 'm' => 12, 'f' =>  5, 'y_content_with_convs' => 13, 'y_content_no_convs' => 13, 'y_search_with_convs' => 15, 'y_search_no_convs' => 15),
				'Clicks'        => array('g' => 12, 'g_refresh' => -1, 'm' => 13, 'f' =>  8, 'y_content_with_convs' => 14, 'y_content_no_convs' => 14, 'y_search_with_convs' => 16, 'y_search_no_convs' => 16),
				'Cost'          => array('g' => 13, 'g_refresh' => -1, 'm' => 14, 'f' => 14, 'y_content_with_convs' => 22, 'y_content_no_convs' => 17, 'y_search_with_convs' => 24, 'y_search_no_convs' => 19),
				'Conversions'   => array('g' => 15, 'g_refresh' =>  6, 'm' => 15, 'f' => -1, 'y_content_with_convs' => 17, 'y_content_no_convs' => -1, 'y_search_with_convs' => 19, 'y_search_no_convs' => -1),
				'Position'      => array('g' => 14, 'g_refresh' => -1, 'm' => 16, 'f' => -1, 'y_content_with_convs' => 24, 'y_content_no_convs' => 18, 'y_search_with_convs' => 26, 'y_search_no_convs' => 20),
				'Revenue'       => array('g' => 16, 'g_refresh' =>  7, 'm' => 18, 'f' => -1, 'y_content_with_convs' => 22, 'y_content_no_convs' => -1, 'y_search_with_convs' => 22, 'y_search_no_convs' => -1),
				'MPC Convs'     => array('g' => 17, 'g_refresh' =>  8, 'm' => -1, 'f' => -1, 'y_content_with_convs' => -1, 'y_content_no_convs' => -1, 'y_search_with_convs' => -1, 'y_search_no_convs' => -1),
				'VT Convs'      => array('g' => 18, 'g_refresh' =>  9, 'm' => -1, 'f' => -1, 'y_content_with_convs' => -1, 'y_content_no_convs' => -1, 'y_search_with_convs' => -1, 'y_search_no_convs' => -1)
			);
	}

	public static function get_report_columns_string($separator = "\t")
	{
		api_translate::set_standard_report_columns();
		
		$cols = array();
		foreach (api_translate::$report_cols as $col => $col_info)
			$cols[] = $col;
		
		return implode($separator, $cols);
	}
	
	public static function define_report_columns($market, $key1 = '', $key2 = '')
	{
		api_translate::set_standard_report_columns();
		
		$col_key = $market.((empty($key1)) ? '' : '_'.$key1).((empty($key2)) ? '' : '_'.$key2);
		$cols = array();
		foreach (api_translate::$report_cols as $col => $col_info)
		{
			$cols[] = $col_info[$col_key];
		}
		
		return $cols;
	}
	
	public static function standardize_y_report_keyword_id_content($v)
	{
		return '!CONTENT!';
	}
	
	public static function standardize_y_report_date_search($v)  { return standardize_y_report_date($v); }
	public static function standardize_y_report_date_content($v) { return standardize_y_report_date($v); }
	
	public static function standardize_y_report_date($v)
	{
		// us dates
		if (strpos($v, '/') !== false) list($day, $month, $year) = explode('/', $v);
		
		// eu (?) dates
		else list($day, $month, $year) = explode('.', $v);
		return date('Y-m-d', strtotime("$month/$day/$year"));
	}
	
	// convert from us formatted date
	public static function standardize_f_report_date($v)
	{
		return (strpos($v, '/') !== false) ? date(util::DATE, strtotime($v)) : '0000-00-00';
	}
	
	// no ad groups, use campaign name, prepend "AG "
	public static function standardize_f_report_ad_group_text($v)
	{
		return 'AG '.$v;
	}
	
	public static function standardize_report_data($market, &$d, &$cols)
	{
		$num_cols = count($cols);
		$col_names = array_keys(api_translate::$report_cols);
		$data_str = '';
		for ($i = 0; $i < $num_cols; ++$i)
		{
			if ($i != 0) $data_str .= "\t";
			
			$col = $cols[$i];
			$val = $d[$col];
			$col_name = util::simple_text($col_names[$i]);
			$conversion_func = 'standardize_'.$market.'_report_'.$col_name.((empty($report_type)) ? '' : '_'.$report_type);
			if (method_exists('api_translate', $conversion_func)) $val = api_translate::$conversion_func($val);
			
			$data_str .= $val;
		}
		return $data_str;
	}
	
	public static function marketize_report_request($market, &$request)
	{
		// report requests can either have a single date or a start date and an end date
		// but when they are sent to the server they need the latter, so, ya
		if (!array_key_exists('start_date', $request))
		{
			$request['start_date'] = $request['date'];
			$request['end_date'] = $request['date'];
			unset($request['date']);
		}
		
		switch ($market)
		{
			case ('g'):
				self::$converter = array(
					'account' => 'clientEmails',
					'start_date' => 'startDay',
					'end_date' => 'endDay',
					'type' => array('selectedReportType', 'marketize_g_report_type'),
					'time_interval' => 'aggregationTypes',
					'columns' => 'selectedColumns',
					'_marketize_defaults_' => array(
						'crossClient' => 'true',
						'name' => 'api '.date(util::DATE_TIME)
					)
				);
				break;
			
			case ('y'):
				self::$converter = array(
					'account' => 'clientEmails',
					'start_date' => 'startDay',
					'end_date' => 'endDay',
					'type' => array('selectedReportType', 'marketize_g_report_type'),
					'time_interval' => 'aggregationTypes',
					'columns' => 'selectedColumns',
					'_marketize_defaults_' => array(
						'crossClient' => 'true',
						'name' => 'api '.date(util::DATE_TIME)
					)
				);
				break;
			
			case ('m'):
				break;
		}
		
		return (api_translate::marketize_data($request));
	}
	
	public static function marketize_report_account($market, $ac_id, $scope)
	{
		switch ($market)
		{
			case ('g'): return ((empty($ac_id)) ? '' : db::select_one("select email from g_accounts where id='$ac_id'"));
			case ('y'): return (($scope == 'master') ? '' : $ac_id);
			default:    return $ac_id;
		}
	}
	
	public static function marketize_report_date($market, $date)
	{
		switch ($market)
		{
			case ('y'): return "{$date}T00:00:00-08:00";
			default:    return $date;
		}
	}
	
	public static function marketize_report_type($market, $type)
	{
		switch ($market)
		{
			case ('g'):
				switch ($type)
				{
					case ('Campaign'): return 'CAMPAIGN_PERFORMANCE_REPORT';
					case ('Extensions'): return 'PLACEHOLDER_FEED_ITEM_REPORT';
					case ('Ad'): return 'AD_PERFORMANCE_REPORT';
					case ('Ad_Structure'): return 'AD_PERFORMANCE_REPORT';
					case ('Named_Conversions'): return 'AD_PERFORMANCE_REPORT';
					case ('Keyword'): return 'KEYWORDS_PERFORMANCE_REPORT';
					case ('Shopping'): return 'SHOPPING_PERFORMANCE_REPORT';
					default: return 'Creative';
				}
			
			case ('y'):
				switch ($type)
				{
					case ('Keyword'): return 'AdKeywordSummaryByDay';
					case ('Content'): return 'AdSummaryByDay';
				}
			
			case ('m'):
				switch ($type)
				{
					#case ('Revenue'): return 'ConversionPerformanceReportRequest';
					case ('Campaign'): return 'CampaignPerformanceReportRequest';
					case ('CampaignRevenue'): return 'ConversionPerformanceReportRequest';
					case ('Revenue'): return 'GoalsAndFunnelsReportRequest';
					case ('Ad_Structure'): return 'AdPerformanceReportRequest';
					default: return 'KeywordPerformanceReportRequest';
				}
		}
	}
	
	public static function marketize_report_interval($market, $end_date)
	{
		switch ($market)
		{
			case ('g'): return 'Daily';
			case ('y'): return null;
			case ('m'): return 'Daily';
		}
	}
	
	public static function marketize_report_columns($market)
	{
		switch ($market)
		{
			case ('g'):
				return array(
					'ExternalCustomerId',
					'CampaignId',
					'Campaign',
					'AdGroupId',
					'AdGroup',
					'CreativeId',
					'KeywordId',
					'Keyword',
					'KeywordTypeDisplay',
					'MaximumCPC',
					'Impressions',
					'Clicks',
					'Conversions',
					'Cost',
					'AveragePosition'
				);
			
			// can't specify columns in jajoo
			case ('y'):
				return null;
			
			// msn can take care of itself
			case ('m'):
				return null;
		}
	}
	
	public static function marketize_ad_group($market, $ag)
	{
		api_translate::set_converter($market, 'ad_group', 'market');
		return (self::marketize_data($ag));
	}
	
	public static function marketize_keywords($market, $keywords)
	{
		api_translate::set_converter($market, 'keyword', 'market');
		return (self::marketize_array($keywords));
	}
	
	public static function standardize_report_status($market, $status)
	{
		switch ($market)
		{
			case ('g'):
				
			// jajoo report status return URL upon completion, so check for a url
			case ('y'):
				if (strpos($status, 'http') === 0) return 'Completed';
				return 'Pending';
			
			case ('m'):
				if ($status == 'Success') return 'Completed';
				return $status;
		}
	}
	
	// basically a dummy func for yahoo
	public static function standardize_report_url($market, $url)
	{
		return $url;
	}
	
	public static function standardize_accounts($market, &$acs)
	{
		api_translate::set_converter($market, 'account', 'standard');
		return (api_translate::standardize_array($acs));
	}
	
	public static function standardize_account($market, &$ac)
	{
		api_translate::set_converter($market, 'account', 'standard');
		return (api_translate::standardize_data($ac));
	}
	
	public static function standardize_campaigns($market, &$cas)
	{
		api_translate::set_converter($market, 'campaign', 'standard');
		return (api_translate::standardize_array($cas));
	}
	
	public static function standardize_campaign($market, &$ca)
	{
		api_translate::set_converter($market, 'campaign', 'standard');
		return (api_translate::standardize_data($ca));
	}
	
	public static function standardize_ad_groups($market, &$ags)
	{
		
		api_translate::set_converter($market, 'ad_group', 'standard');
		return (api_translate::standardize_array($ags));
	}
	
	public static function standardize_ad_group($market, &$ag)
	{
		api_translate::set_converter($market, 'ad_group', 'standard');
		return (api_translate::standardize_data($ag));
	}
	
	public static function standardize_ads($market, &$ads)
	{
		api_translate::set_converter($market, 'ad', 'standard');
		return (api_translate::standardize_array($ads));
	}
	
	public static function standardize_ad($market, &$ad)
	{
		api_translate::set_converter($market, 'ad', 'standard');
		return (api_translate::standardize_data($ad));
	}
	
	
	# Active - The campaign is currently running.
	# Pending - The campaign's start date has not yet been reached.
	# Ended - The campaign's end date has been reached.
	# Paused - The customer has paused the campaign.
	# Deleted - The customer has deleted the campaign.
	# Suspended - Google has temporarily suspended the campaign. 
	public static function standardize_g_campaign_status($v)
	{
		switch (strtolower($v))
		{
			case ('active'): return 'On';
			case ('paused'): return 'Off';
		}
		return ucfirst($v);
	}
	
	
	# Active - Specifies that the campaign status is Active.
	# BudgetAndManualPaused - Specifies that the campaign status is Paused due to budget restrictions and subsequently paused manually. The manual pause can occur by using either the Microsoft adCenter user interface or the PauseCampaigns service operation.
	# BudgetPaused - Specifies that the campaign status is Paused due to budget restrictions.
	# Deleted - Specifies that the campaign status is Deleted because it has been deleted.
	# Paused - Specifies that the campaign status is Paused because it was paused manually.
	public static function standardize_m_campaign_status($v)
	{
		switch ($v)
		{
			case ('Active'): return 'On';
		}
		return 'Off';
	}
	
	# Active - The status of the ad is Active.
	# Deleted - The status of the ad is Deleted.
	# Inactive - The status of the ad is Inactive.
	# Paused - The status of the ad is Paused.
	public static function standardize_m_ad_status($v)
	{
		switch ($v)
		{
			case ('Active'):
			case ('On'):
				return 'On';
			
			case ('Deleted'):
				return 'Deleted';
		}
		return 'Off';
	}
	
	public static function standardize_m_keyword_status($v)
	{
		switch ($v)
		{
			case ('Active'): return 'On';
			case ('Deleted'): return 'Deleted';
		}
		return 'Off';
	}
	
	# Active - Specifies that the status of the ad group is Active.
	# Deleted - Specifies that the status of the ad group is Deleted.
	# Draft - Specifies that the status of the ad group is Draft.
	# Paused - Specifies that the status of the ad group is Paused.
	public static function standardize_m_ad_group_status($v)
	{
		switch ($v)
		{
			case ('Active'): return 'On';
		}
		return 'Off';
	}
	
	
	# Enabled, Paused, Disabled
	public static function standardize_g_ad_status($v)
	{
		switch (strtolower($v))
		{
			case ('enabled'): return 'On';
			case ('paused'): return 'Off';
			case ('disabled'): return 'Deleted';
		}
	}
	
	public static function standardize_g_keyword_first_page_cpc($v, &$d)
	{
		return ((strpos($v, '.') !== false) ? $v : util::micro_to_double($v));
	}
	
	# Enabled, Paused, Deleted
	public static function marketize_g_ad_group_status($v)
	{
		switch ($v)
		{
			case ('On'): return 'ENABLED';
			case ('Off'): return 'PAUSED';
			case ('Deleted'): return 'DELETED';
		}
		return $v;
	}
	
	public static function marketize_g_ad_status($v)
	{
		switch ($v)
		{
			case ('On'): return 'ENABLED';
			case ('Off'): return 'PAUSED';
			case ('Deleted'): return 'DISABLED';
		}
		return $v;
	}

	public static function marketize_g_keyword_max_cpc($v, &$d)
	{
		return util::double_to_micro($v);
	}
	
	public static function marketize_g_keyword_status($v)
	{
		switch ($v)
		{
			case ('On'): return 'ACTIVE';
			case ('Off'): return 'PAUSED';
			
			// deleting keywords is not done by updating status
		}
	}
	
	public static function standardize_g_keyword_max_cpc($v, &$d)
	{
		return ((strpos($v, '.') !== false) ? $v : util::micro_to_double($v));
	}
	
	public static function standardize_g_keyword_match_type($v)
	{
		return strtolower($v);
	}
	
	public static function standardize_g_ad_group_max_cpc($v, &$d)
	{
		return ((strpos($v, '.') !== false) ? $v : util::micro_to_double($v));
	}
	
	public static function standardize_g_ad_group_content_max_cpc($v, &$d)
	{
		return ((strpos($v, '.') !== false) ? $v : util::micro_to_double($v));
	}
	
	# Enabled, Paused, Deleted
	public static function standardize_g_ad_group_status($v)
	{
		switch (strtolower($v))
		{
			case ('enabled'): return 'On';
			case ('paused'): return 'Off';
		}
		return ucfirst(strtolower($v));
	}
	
	# Active, Disapproved, Deleted
	# "Paused" is another field, check $other_data
	public static function standardize_g_keyword_status($v)
	{
		switch (strtolower($v))
		{
			case ('active'): return 'On';
			case ('paused'): return 'Off';
			case ('deleted'): return 'Deleted';
		}
		return ucfirst(strtolower($v));
	}
	
	public static function standardize_y_keyword_max_cpc($v, &$d)
	{
		return ((empty($v)) ? 0 : $v);
	}
	
	public static function set_account_conv_map(&$map)
	{
		$map = array(
			'id'       => array('g' => 'customerId'      , 'y' => 'ID'        , 'm' => 'AccountId'),
			'text'     => array('g' => 'descriptiveName' , 'y' => 'name'      , 'm' => 'AccountName'),
			'currency' => array('g' => 'currencyCode'    , 'y' => 'fiscalCode', 'm' => 'PreferredCurrencyType')
		);
	}

	public static function set_campaign_conv_map(&$map)
	{
		$map = array(
			'id'     => array('g' => 'id'    , 'y' => 'ID'    , 'm' => 'Id'),
			'text'   => array('g' => 'name'  , 'y' => 'name'  , 'm' => 'Name'),
			'status' => array('g' => 'status', 'y' => 'status', 'm' => 'Status')
		);
	}
	
	public static function set_ad_group_conv_map(&$map)
	{
		$map = array(
			'campaign_id'     => array('g' => 'campaignId'          , 'y' => 'campaignID'           , 'm' => ''),
			'id'              => array('g' => 'id'                  , 'y' => 'ID'                   , 'm' => 'Id'),
			'text'            => array('g' => 'name'                , 'y' => 'name'                 , 'm' => 'Name'),
			'max_cpc'         => array('g' => 'keywordMaxCpc'       , 'y' => 'sponsoredSearchMaxBid', 'm' => 'BroadMatchBid'),
			'max_content_cpc' => array('g' => 'keywordContentMaxCpc', 'y' => 'contentMatchMaxBid'   , 'm' => 'ContentMatchBid'),
			'status'          => array('g' => 'status'              , 'y' => 'status'               , 'm' => 'Status')
		);
	}
	
	public static function set_ad_conv_map(&$map)
	{
		$map = array(
			'ad_group_id' => array('g' => 'id'            , 'y' => 'adGroupID'       , 'm' => ''),
			'id'          => array('g' => 'id'            , 'y' => 'ID'              , 'm' => 'Id'),
			'text'        => array('g' => 'headline'      , 'y' => 'title'           , 'm' => 'Title'),
			'desc_1'      => array('g' => 'description1'  , 'y' => 'shortDescription', 'm' => 'Text'),
			'desc_2'      => array('g' => 'description2'  , 'y' => ''                , 'm' => ''),
			'disp_url'    => array('g' => 'displayUrl'    , 'y' => 'displayUrl'      , 'm' => 'DisplayUrl'),
			'dest_url'    => array('g' => 'destinationUrl', 'y' => 'url'             , 'm' => 'DestinationUrl'),
			'status'      => array('g' => 'status'        , 'y' => 'status'          , 'm' => 'Status')
		);
	}
	
	public static function set_keyword_conv_map(&$map)
	{
		$map = array(
			'ad_group_id'     => array('g' => 'adGroupId'     , 'y' => 'adGroupID'            , 'm' => ''),
			'id'              => array('g' => 'id'            , 'y' => 'ID'                   , 'm' => 'Id'),
			'text'            => array('g' => 'text'          , 'y' => 'text'                 , 'm' => 'Text'),
			'match_type'      => array('g' => 'type'          , 'y' => ''                     , 'm' => ''),
			'max_cpc'         => array('g' => 'maxCpc'        , 'y' => 'sponsoredSearchMaxBid', 'm' => ''),
			'dest_url'        => array('g' => 'destinationUrl', 'y' => 'url'                  , 'm' => 'Param1'),
			'status'          => array('g' => 'status'        , 'y' => 'status'               , 'm' => 'Status')
		);
	}
	
	public static function standardize_keywords($market, &$kws)
	{
		api_translate::set_converter($market, 'keyword', 'standard');
		return (api_translate::standardize_array($kws));
	}
	
	public static function standardize_keyword($market, &$kw)
	{
		api_translate::set_converter($market, 'keyword', 'standard');
		return (api_translate::standardize_data($kw));
	}
	
	public static function set_converter($market, $standard_type, $s_or_m)
	{
		self::$standard_type = $standard_type;
		self::$market = $market;
		
		$conv_map_func = 'set_'.$standard_type.'_conv_map';
		self::$conv_map_func($conv_map);
		
		self::$converter = array();
		foreach ($conv_map as $standard_key => &$market_info)
		{
			if ($s_or_m == 'market') self::$converter[$standard_key] = $market_info[$market];
			else                     self::$converter[$market_info[$market]] = $standard_key;
		}
		
		if ($s_or_m == 'market')
		{
			$market_info_func = 'set_'.$standard_type.'_market_info';
			self::$market_info_func($market);
		}
	}
}

?>