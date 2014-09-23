<?php

class util
{
	const DATA_START_DATE = '2008-01-01';
	const DATA_TABLE_COUNT = 16;
	
	const TRACKER_DOMAIN = 'wpropath.appspot.com';
	const TRACKER_REDIRECT_URL = 'http://wpropath.appspot.com/r';
	
	// the number of tables we use for data
	const CDL_NUM_DAYS = 365;

	// constants for raw data tables
	const RAW_DATA_COUNT = 8; // imps, clicks, convs, cost, pos, rev, mpc convs, vt convs
	const RAW_DATA_START_INDEX = 5;
	const RAW_DATA_COST_INDEX = 3;
	const RAW_DATA_CONV_INDEX = 2;
	const RAW_DATA_REV_INDEX = 5;

	// some date/time constants
	const DATE = 'Y-m-d';
	const TIME = 'H:i:s';
	const DATE_TIME = 'Y-m-d H:i:s';
	const US_DATE = 'n/j/y';

	// e2 php built in default flags
	//const e2_EXTR_DEFAULTS = EXTR_OVERWRITE | EXTR_IF_EXISTS;

	// misc
	const NO_FLAGS = 0;

	// google, yahoo, msn cache expire time.. 12 hours
	const DATA_CACHE_EXPIRE = 43200;
	
	// payment processing
	const MERCHANT_TEST_KEY = '00HSH4jTn4WVvujFd30zv7umyz0Dp126';
	const MERCHANT_PROD_KEY = 'gEs7Qv2O8B1icMVmfDQJ6M5EpSA5IwTk';

	const G_CONTENT_ID = '3000000';
	const Y_CONTENT_ID = '!CONTENT!';

	const E2_CRYPT_KEY = 'ngblkjgsajj';
	const E2_CRYPT_SALT = 'ajsio;ejfcP*$tho489HL$*th4vTA$(49tha0404tPH$vph4vt8';

	const REFRESH_CLIENT_DATA_DO_RESET_DATA = 0x01;
	const REFRESH_CLIENT_DATA_PROGRESS_MONTHLY = 0x02;
	const REFRESH_CLIENT_DATA_PROGRESS_DAILY = 0x04;

	public static function get_ppc_markets($format = 'SIMPLE', $account = null)
	{
		$markets = self::get_base_ppc_markets($format);
		if ($account) {
			switch ($format) {
				case ('ASSOC'):
					if ($account->facebook) {
						$markets['f'] = 'Facebook';
					}
					break;
					
				case ('NUM'):
					if ($account->facebook) {
						$markets[] = array('f', 'Facebook');
					}
					break;
					
				case ('SELECT'):
					if ($account->facebook) {
						$markets[] = array('value' => 'f', 'caption' => 'Facebook');
					}
					break;
					
				case ('SIMPLE'):
				default:
					if ($account->facebook) {
						$markets[] = 'f';
					}
					break;
			}
		}
		return $markets;
	}
	
	private static function get_base_ppc_markets($format)
	{
		switch ($format)
		{
			case ('ASSOC'):
				return array(
					'g' => 'Google',
					'm' => 'MSN'
				);
			case ('NUM'):
				return array(
					array('g', 'Google'),
					array('m', 'MSN')
				);
			case ('SELECT'):
				return array(
					array('value' => 'g', 'caption' => 'Google'),
					array('value' => 'm', 'caption' => 'MSN')
				);
			case ('SIMPLE'):
			default:
				return array(
					'g',
					'm'
				);
		}
	}

	public static function get_ql_markets($format = 'SIMPLE')
	{
		switch ($format)
		{
			case ('ASSOC'):
				return array(
					'g' => 'Google',
					'y' => 'Yahoo'
				);
			case ('NUM'):
				return array(
					array('g', 'Google'),
					array('y', 'Yahoo')
				);
			case ('SELECT'):
				return array(
					array('value' => 'g', 'caption' => 'Google'),
					array('value' => 'y', 'caption' => 'Yahoo')
				);
			case ('SIMPLE'):
			default:
				return array(
					'g',
					'y'
				);
		}
	}

	public static function get_weekdays()
	{
		return array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
	}

	public static function list_assoc(&$a)
	{
		$argv = func_get_args();
		$b = array();
		for ($i = 1, $loop_end = count($argv); $i < $loop_end; ++$i) {
			$key = $argv[$i];
			$b[] = (array_key_exists($key, $a)) ? $a[$key] : false;
		}
		return $b;
	}

	public static function empty_date($d)
	{
		return (empty($d) || !preg_match("/^\d\d\d\d-\d\d-\d\d$/", $d) || $d == '0000-00-00');
	}

	public static function empty_date_time($dt)
	{
		return (empty($dt) || !preg_match("/^\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d$/", $dt) || $dt == '0000-00-00 00:00:00');
	}
	
	public static function is_valid_date_range($start_date, $end_date)
	{
		return (!util::empty_date($start_date) && !util::empty_date($end_date) && $start_date <= $end_date);
	}
	
	public static function get_date_time_period($time_period, $date, $opts = array())
	{
		util::set_opt_defaults($opts, array(
			// weekly and daily always include day of month
			'include_day_of_month' => false,
			// usually this function is called when aggregating data over a date range
			// can pass in start_date to round time periods to start date
			'start_date' => null,
			// default to starting months on the first
			'month_start' => 1,
			// default to starting weeks Monday (date('w'), 0 = Sunday
			'week_start' => 1,
			// custom date buckets
			'custom_dates' => false
		));
		
		switch (strtolower($time_period))
		{
			case ('all'):
			case ('summary'):
			case ('*'):
				return 'Summary';
			
			case ('year'):
			case ('yearly'):
				return substr($date, 0, 4);
			
			case ('quarter'):
			case ('quarterly'):
				$year = substr($date, 0, 4);
				$month = preg_replace("/^0/", '', substr($date, 5, 2));
				$quarter = ceil($month / 3);
				$quarter_start_month = str_pad((($quarter - 1) * 3) + 1, 2, '0', STR_PAD_LEFT);
				return ($year.'-'.$quarter_start_month);
		
			case ('month'):
			case ('monthly'):
				
				$month_start = ($opts['month_start']) ? str_pad($opts['month_start'], 2, '0', STR_PAD_LEFT) : '01';
		
				// get substring of month and year
				$data_month = substr($date, 0, 7);
				
				// append user's start of month day
				$data_month_with_day = $data_month.'-'.$month_start;
				
				// if resulting date is after the data date, use previous month
				if ($data_month_with_day > $date)
				{
					// get substring of month and year for previous month
					$data_month = date('Y-m', strtotime($data_month.'-15 -1 month'));
					
					// append user's start of month day
					$data_month_with_day = $data_month.'-'.$month_start;
				}
				// if it's before out start date, use start date
				if ($opts['start_date'] && $opts['include_day_of_month'] && $data_month_with_day < $opts['start_date'])
				{
					$data_month_with_day = $opts['start_date'];
				}
				return ($opts['include_day_of_month'] ? $data_month_with_day : substr($data_month_with_day, 0, 7));
			
			case ('week'):
			case ('weekly'):
				$week_start = ($opts['week_start']) ? str_pad($opts['week_start'], 2, '0', STR_PAD_LEFT) : 0;
				
				// convert to time
				$data_time = strtotime($date.' 12:00:00');
				
				// see what day of the week it is
				$day_of_week = date('w', $data_time);
				
				// find out how many days it is to the day user picked as the start of the week
				// we have to add 7 because of how php handles modulus of negative numbers
				$days_to_start_of_week = (7 + ($day_of_week - $week_start)) % 7;
				
				// subtract that amount of time to get the date of the start of the week
				$data_week = date('Y-m-d', $data_time - ($days_to_start_of_week * 86400));
				
				// if it's before out start date, use start date
				if ($opts['start_date'] && $data_week < $opts['start_date'])
				{
					$data_week = $opts['start_date'];
				}
				return $data_week;

			case ('custom'):
				if (empty($opts['custom_dates'])) {
					return false;
				}
				foreach ($opts['custom_dates'] as $dates) {
					if ($date >= $dates['start'] && $date <= $dates['end']) {
						return date(util::US_DATE, strtotime($dates['start'])).' - '.date(util::US_DATE, strtotime($dates['end']));
					}
				}
				return false;
			
			case ('day'):
			case ('daily'):
			default:
				return $date;
		}
	}
	
	public static function unempty()
	{
		$argv = func_get_args();
		foreach ($argv as $v)
			if (!empty($v)) return $v;
		return null;
	}

	public static function unnull()
	{
		$argv = func_get_args();
		foreach ($argv as $v)
		{
			if (!is_null($v)) return $v;
		}
		return null;
	}

	public static function safe_div($x, $y)
	{
		return (($y > 0) ? ($x / $y) : 0);
	}

	public static function escape_single_quotes(&$x)
	{
		$x = str_replace("'", "\\'", $x);
	}

	public static function simple_text($text)
	{
		$simple = preg_replace("/[^\w -]/", '', $text);
		$simple = str_replace(array(' ', '-'), '_', $simple);
		return strtolower($simple);
	}

	public static function display_text($text)
	{
		return ucwords(str_replace('_', ' ', $text));
	}

	public static function n0($x) { return number_format($x, 0); }
	public static function n1($x) { return number_format($x, 1); }
	public static function n2($x) { return number_format($x, 2); }
	public static function n2_blank($x) { return number_format($x, 2, '.', ''); }

	public static function format_dollars($x)
	{
		//if(is_string($x)) $x = floatval($x);
		$x = number_format($x, 2);
		return ($x < 0) ? "-$".substr($x,1) : "$$x";
	}

	public static function format_percent($x)
	{
		return number_format($x, 2)."%";
	}

	public static function format_status($x)
	{
		return '<img src="/img/'.$x.'.jpg" />';
	}
	
	public static function xml_data($x)
	{
		return '<![CDATA['.$x.']]>';
	}
	
	public static function micro_to_double($x)
	{
		return (number_format((double) ($x / 1000000.0), 2, '.', ''));
	}

	public static function double_to_micro($x)
	{
		return (number_format($x * 1000000, 0, '.', ''));
	}

	public static function set_client_external_id($cl_id)
	{
		for ($i = 0; $i < 1000; ++$i)
		{
			$external_id = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT).str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
			if (!db::select_one("select count(*) from eppctwo.clients where external_id = '$external_id'"))
			{
				db::exec("
					update eppctwo.clients
					set external_id = '$external_id'
					where id = '$cl_id'
				");
				return $external_id;
			}
		}
	}

	public static function delta_month($start_date, $delta, $target_day = null)
	{
		if ($delta == 0 || !is_numeric($delta) || strpos($delta, '.') !== false)
		{
			return $start_date;
		}
		list($start_year, $start_month, $start_day) = explode('-', $start_date);
		$start_month = ltrim($start_month, '0');
		
		if ($delta > 0)
		{
			$end_month = str_pad((($start_month + $delta - 1) % 12) + 1, 2, '0', STR_PAD_LEFT);
		}
		// meh.. php negative moduli?
		else
		{
			$end_month = (($start_month + $delta) % 12);
			if ($end_month < 1)
			{
				$end_month += 12;
			}
			$end_month = str_pad($end_month, 2, '0', STR_PAD_LEFT);
		}
		$end_year = $start_year + floor(($start_month + $delta - 1) / 12);
		$end_date = "$end_year-$end_month-".(($target_day) ? str_pad($target_day, 2, '0', STR_PAD_LEFT) : $start_day);
		
		// we could end up at a nonexistent date, eg feb 31st.. this checks for that
		$test_date = date('Y-m-d', strtotime($end_date));
		if ($end_date != $test_date)
		{
			$end_date = date('Y-m-d', strtotime(substr($test_date, 0, 7)."-01 -1 day"));
		}
		return $end_date;
	}
	
	public static function set_data_date_options(&$options)
	{
		list($bill_day, $prev, $next) = db::select_row("select bill_day, prev_bill_date, next_bill_date from eppctwo.clients_ppc where client='".g::$client_id."'");
		
		$options = array(
			array($prev.','.$next, 'Current Client Month'),
			array(util::delta_month($prev, -1, $bill_day).','.$prev, 'Previous Client Month'),
			array('last_7' , 'Last 7 Days'),
			array('last_30', 'Last 30 Days'),
		);
	}

	private static function init_detail_cols($opts)
	{
		global $g_detail_cols;
		
		// not empty, already init'd, don't need to do anything
		if (!empty($g_detail_cols)) return;
		
		$g_detail_cols = array(
			'market'    => array('group' => 'detail', 'display' => 'Market'),
			'campaign'  => array('group' => 'detail', 'display' => 'Campaign'),
			'ad_group'  => array('group' => 'detail', 'display' => 'Ad Group'),
			'ad'        => array('group' => 'detail', 'display' => 'Ad', 'is_leaf' => 1),
			'device'    => array('group' => 'detail', 'display' => 'Device', 'is_leaf' => 1),
			'keyword'   => array('group' => 'detail', 'display' => 'Keyword', 'is_leaf' => 1)
		);
		
		if ($opts['do_include_extensions']) {
			$g_detail_cols['extension'] = array('group' => 'detail', 'display' => 'Extension', 'is_leaf' => 1);
		}
	}

	public static function init_report_cols($aid = null, $opts = array())
	{
		global $g_detail_cols, $g_report_cols;

		// not empty, already init'd, don't need to do anything
		if (!empty($g_report_cols)) return;

		util::set_opt_defaults($opts, array(
			'do_include_extensions' => false
		));
		
		util::init_detail_cols($opts);
		$g_report_cols = array_merge($g_detail_cols, array(
			'headline'      => array('group' => 'ad', 'display' => 'Headline'),
			'description'   => array('group' => 'ad', 'display' => 'Description'),
			'disp_url'      => array('group' => 'ad', 'display' => 'Display URL'),
			'dest_url'      => array('group' => 'ad', 'display' => 'Destination URL'),
			
			'pos'           => array('group' => 'performance', 'display' => 'Ave Pos', 'align' => 'r', 'format' => 'n1', 'less_is_good' => 1),
			'clicks'        => array('group' => 'performance', 'display' => 'Clicks', 'align' => 'r', 'format_excel' => 'n0'),
			'imps'          => array('group' => 'performance', 'display' => 'Imps', 'align' => 'r', 'format_excel' => 'n0'),
			'ctr'           => array('group' => 'performance', 'display' => 'CTR', 'align' => 'r', 'format' => 'format_percent'),
			'cpc'           => array('group' => 'performance', 'display' => 'CPC', 'align' => 'r', 'format' => 'format_dollars', 'less_is_good' => 1),
			'cost'          => array('group' => 'performance', 'display' => 'Cost', 'align' => 'r', 'format' => 'format_dollars', 'less_is_good' => 1),
			
			'convs'         => array('group' => 'conversions', 'display' => 'Convs', 'align' => 'r', 'format_excel' => 'n0'),
			'conv_rate'     => array('group' => 'conversions', 'display' => 'Conv Rate', 'align' => 'r', 'format' => 'format_percent'),
			'cost_conv'     => array('group' => 'conversions', 'display' => 'Cost/Conv', 'align' => 'r', 'format' => 'format_dollars', 'less_is_good' => 1),
/*
			'mpc_convs'     => array('group' => 'mpc_conversions', 'display' => 'MPC Convs', 'align' => 'r', 'format_excel' => 'n0'),
			'mpc_conv_rate' => array('group' => 'mpc_conversions', 'display' => 'MPC Conv Rate', 'align' => 'r', 'format' => 'format_percent'),
			'mpc_cost_conv' => array('group' => 'mpc_conversions', 'display' => 'MPC Cost/Conv', 'align' => 'r', 'format' => 'format_dollars'),
*/
			'revenue'       => array('group' => 'revenue', 'display' => 'Rev', 'align' => 'r', 'format' => 'format_dollars'),
			'rev_click'     => array('group' => 'revenue', 'display' => 'Rev/Click', 'align' => 'r', 'format' => 'format_dollars'),
			'rev_ave'       => array('group' => 'revenue', 'display' => 'Rev Ave', 'align' => 'r', 'format' => 'format_dollars'),
			'roas'          => array('group' => 'revenue', 'display' => 'ROAS', 'align' => 'r', 'format' => 'n2'),
		
			'num_days'      => array('group' => 'daily_avg', 'display' => 'No. Days', 'align' => 'r', 'format' => 'n0'),
			'conv_day'     	=> array('group' => 'daily_avg', 'display' => 'Conv/Day', 'align' => 'r', 'format' => 'n2'),
			'rev_day'       => array('group' => 'daily_avg', 'display' => 'Rev/Day', 'align' => 'r', 'format' => 'format_dollars'),
			'click_day'     => array('group' => 'daily_avg', 'display' => 'Click/Day', 'align' => 'r', 'format' => 'n2'),

		));
		
		if ($aid) {
			util::load_rs('ppc');

			// get named conversions
			$conv_types = conv_type_count::get_account_conv_types($aid);
			if (count($conv_types) > 0) {
				foreach ($conv_types as $conv_type) {
					$g_report_cols[$conv_type]         = array('group' => $conv_type, 'display' => ucwords($conv_type)        , 'align' => 'r', 'format_excel' => 'n0'       , 'is_conv_type' => 1);
					$g_report_cols[$conv_type.'_rate'] = array('group' => $conv_type, 'display' => ucwords($conv_type).' Rate', 'align' => 'r', 'format' => 'format_percent');
					$g_report_cols['cost_'.$conv_type] = array('group' => $conv_type, 'display' => 'Cost/'.ucwords($conv_type), 'align' => 'r', 'format' => 'format_dollars', 'less_is_good' => 1);
				}
			}
		}
	}

	// data cols are same as report cols except there are a few more
	public static function init_data_cols($client_id = null)
	{
		global $g_report_cols, $g_data_cols;
		
		// not empty, already init'd, don't need to do anything
		if (!empty($g_data_cols)) return;
		
		util::init_report_cols($client_id);
		$g_data_cols = array_merge($g_report_cols, array(
			'status' => array('group' => 'info', 'display' => 'Status', 'align' => 'c', 'format' => 'format_status'),
			'bid'    => array('group' => 'info', 'display' => 'Bid', 'align' => 'r', 'format' => 'format_dollars'),
			'cbid'   => array('group' => 'info', 'display' => 'C Bid', 'align' => 'r', 'format' => 'format_dollars'),
		));
	}

	public static function ob_stop(&$contents)
	{
		$contents = ob_get_contents();
		ob_end_clean();
	}
	
	public static function var_dump_str($var)
	{
		ob_start();
		var_dump($var);
		util::ob_stop($str);
		return $str;
	}
	
	public static function get_content_query($market, $true_or_false)
	{
		$operator = ($true_or_false) ? '=' : '<>';
		
		switch ($market)
		{
			case ('g'): return "keyword $operator '".util::G_CONTENT_ID."'";
			case ('y'): return "keyword $operator '".util::Y_CONTENT_ID."'";
			case ('m'): return "keyword ".(($true_or_false) ? '' : 'not')." like '".util::G_CONTENT_ID."'";
		}
	}

	public static function is_dev()
	{
		return (\epro\ENV === 'DEV');
	}

	public static function is_nix()
	{
		return (strpos(\epro\ROOT_PATH, '/') === 0);
	}

	public static function array_to_query(&$a, $url_encode = true)
	{
		$str = '';
		foreach ($a as $k => $v)
		{
			if (!empty($str)) $str .= '&';
			$str .= $k.'='.(($url_encode) ? urlencode($v) : $v);
		}
		return $str;
	}

	public static function get_client_types()
	{
		$cl_types = db::select("
			select type_short, type
			from eppctwo.client_type_defs
			order by type asc
		");
		return $cl_types;
	}

	public static function set_active_market_clients(&$active_market_clients, $market)
	{
		$active_market_clients = db::select("
			select distinct c.id
			from eppctwo.clients c, eppctwo.data_sources ds
			where
				status='On' &&
				ds.market = '$market' &&
				c.id = ds.client
		");
	}

	public static function set_active_data_sources(&$data_sources, $market, $target_ac_id = null)
	{
		$clients = db::select("
			select client, 1
			from eppctwo.clients_ppc
			where status='On'
		", 'NUM', 0);
		
		$tmp = db::select("
			select client, account, campaign, ad_group
			from eppctwo.data_sources
			where
				market='$market'
				".(($target_ac_id) ? "&& account = '$target_ac_id'" : "")."
		");
		
		$data_sources = array(
			'acs' => array(),
			'cas' => array(),
			'ags' => array()
		);
		for ($i = 0; list($cl_id, $ac_id, $ca_id, $ag_id) = $tmp[$i]; ++$i)
		{
			if (array_key_exists($cl_id, $clients))
			{
				if (!empty($ag_id)) $data_sources['ags'][$ag_id] = $cl_id;
				else if (!empty($ca_id)) $data_sources['cas'][$ca_id] = $cl_id;
				else $data_sources['acs'][$ac_id] = $cl_id;
			}
		}
	}
	
	public static function get_active_ql_data_sources($market, $target_ac_id = null)
	{
		$tmp = db::select("
			select ds.account_id, ds.account, ds.campaign, ds.ad_group
			from eac.ap_ql q, eac.account a, eppctwo.ql_data_source ds
			where
				a.status in ('Active', 'NonRenewing') &&
				ds.market = '$market' &&
				".(($target_ac_id) ? "ds.account = '$target_ac_id' &&" : "")."
				q.id = a.id &&
				q.id = ds.account_id
		");
		$data_sources = array(
			'acs' => array(),
			'cas' => array(),
			'ags' => array()
		);
		for ($i = 0; list($cl_id, $ac_id, $ca_id, $ag_id) = $tmp[$i]; ++$i)
		{
			if (!empty($ag_id)) $data_sources['ags'][$ag_id] = $cl_id;
			else if (!empty($ca_id)) $data_sources['cas'][$ca_id] = $cl_id;
			else $data_sources['acs'][$ac_id] = $cl_id;
		}
		return $data_sources;
	}
	
	public static function set_client_data_id($cl_id)
	{
		$sum = db::select_one("select sum(speed) from eppctwo.data_speeds");
		$data_speeds = db::select("
			select id, speed
			from eppctwo.data_speeds
		");
		
		// we want the smaller speeds to be most likely to be selected
		// if we subtract speed from sum, and then divide that by sum * (count - 1), we get.. something useful?
		$reverse_sum = $sum * (util::DATA_TABLE_COUNT - 1);
		$rand = (mt_rand() / mt_getrandmax()) * $reverse_sum;
		$reverse_aggregate = 0;
		for ($i = 0; list($data_id, $speed) = $data_speeds[$i]; ++$i)
		{
			$reverse = $sum - $speed;
			$reverse_aggregate += $reverse;
			if ($reverse_aggregate > $rand)
			{
				db::exec("
					update eppctwo.clients
					set data_id = '$data_id'
					where id = '$cl_id'
				");
				return $data_id;
			}
		}
		return false;
	}
	
	public static function encrypt($x)
	{
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		return mcrypt_encrypt(MCRYPT_RIJNDAEL_256, \epro\CRYPT_KEY, $x.\epro\CRYPT_SALT, MCRYPT_MODE_ECB, $iv);
	}

	public static function decrypt($x)
	{
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$tmp = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, \epro\CRYPT_KEY, $x, MCRYPT_MODE_ECB, $iv), "\0");
		return substr($tmp, 0, strlen($tmp) - strlen(\epro\CRYPT_SALT));
	}

	public static function e2_encrypt($x)
	{
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		return mcrypt_encrypt(MCRYPT_RIJNDAEL_256, util::E2_CRYPT_KEY, $x.util::E2_CRYPT_SALT, MCRYPT_MODE_ECB, $iv);
	}

	public static function e2_decrypt($x)
	{
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$tmp = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, util::E2_CRYPT_KEY, $x, MCRYPT_MODE_ECB, $iv), "\0");
		return substr($tmp, 0, strlen($tmp) - strlen(util::E2_CRYPT_SALT));
	}

	public static function get_client_from_data_ids(&$data_sources, $ac_id, $ca_id = '', $ag_id = '')
	{
		if (array_key_exists($ag_id, $data_sources['ags'])) return $data_sources['ags'][$ag_id];
		if (array_key_exists($ca_id, $data_sources['cas'])) return $data_sources['cas'][$ca_id];
		if (array_key_exists($ac_id, $data_sources['acs'])) return $data_sources['acs'][$ac_id];
		return false;
	}

	public static function set_client_ex_rates(&$ex_rates, $cl_id, $market, $date = null)
	{
		$ex_rates = array();
		
		$ac_ids = db::select("select distinct account from eppctwo.data_sources where client='$cl_id'");
		
		// get currency for each account and ex rate if not USD
		foreach ($ac_ids as $ac_id)
		{
			$currency = db::select_one("select currency from eppctwo.{$market}_accounts where id='$ac_id' && currency<>'USD'");

			if ($date == null)
			{
				$acnt_ex_rates = db::select("
					select d, rate
					from eppctwo.exchange_rates
					where currency='$currency'
				", 'NUM', 0);
				$ex_rates[$ac_id] = $acnt_ex_rates;
			}
			else
			{
				$ex_rates[$ac_id] = db::select_one("select rate from eppctwo.exchange_rates where currency='$currency' && d='$date'");
			}
		}
	}

	public static function m_currency_to_standard_currency($c)
	{
		$standard = db::select_one("
			select code
			from eppctwo.countries
			where m_currency = '".db::escape($c)."'
		");
		return (($standard) ? $standard :$c);
	}

	public static function set_client_data_sources(&$data_sources, $market, $cl_id)
	{
		$data_sources = db::select("
			select account, campaign, ad_group
			from eppctwo.data_sources
			where client='$cl_id' && market='$market'
		");
		return (!empty($data_sources));
	}

	public static function set_client_data_query(&$data_query, $market, $cl_id, $data_sources = null)
	{
		if (empty($data_sources) && !util::set_client_data_sources($data_sources, $market, $cl_id)) return false;
		
		$data_query = '';
		for ($i = 0; list($ac_id, $ca_id, $ag_id) = $data_sources[$i]; ++$i)
		{
			if (!empty($data_query)) $data_query .= ' || ';
			
			if (!empty($ag_id))      $data_query .= "ad_group_id = '$ag_id'";
			else if (!empty($ca_id)) $data_query .= "campaign_id = '$ca_id'";
			else                     $data_query .= "account_id = '$ac_id'";
		}
		return (!empty($data_query));
	}

	public static function update_client_info_tables($market, $cl_id, $data_id = '', $data_query = '', $show_progress = false)
	{
		if (empty($data_id))
		{
			$data_id = db::select_one("select data_id from eppctwo.clients where id='$cl_id'");
			if ($data_id == -1) return false;
		}
		
		if (empty($data_query) && !util::set_client_data_query($data_query, $market, $cl_id)) return;
		
		$info_tables = array('campaigns', 'ad_groups', 'ads', 'keywords');
		
		foreach ($info_tables as $table_base)
		{
			if ($show_progress) echo "Refresh Info: $market, $cl_id, $table_base\n";
			
			// release any tables currently associated with this client
			db::exec("
				update {$market}_info.{$table_base}_{$data_id}
				set client=''
				where client='$cl_id'
			");
			
			// get any info in unassigned tables that may be theirs
			db::exec("
				insert into {$market}_info.{$table_base}_{$data_id}
				select * from {$market}_info.unassigned_{$table_base} where $data_query
			");
			
			// assign client to info
			db::exec("
				update {$market}_info.{$table_base}_{$data_id}
				set client='$cl_id'
				where $data_query
			");
			
			// delete from unassigned table
			db::exec("
				delete from {$market}_info.unassigned_{$table_base}
				where $data_query
			");
		}
	}

	public static function is_standard_pay_period($pay_period)
	{
		return (empty($pay_period) || $pay_period == 'standard');
	}

	public static function is_tracker_url($url)
	{
		return (strpos($url, util::TRACKER_DOMAIN) !== false);
	}
	
	public static function get_keyword_display($text, $type)
	{
		switch ($type)
		{
			case ('Exact'):  return '['.$text.']';
			case ('Phrase'): return '"'.$text.'"';
			default:         return $text;
		}
	}
	
	public static function get_keyword_text_and_match_type($text)
	{
		$len = strlen($text);
		
		if      ($len < 2)                                  return array($text, 'Broad');
		if      ($text[0] == '"' && $text[$len - 1] == '"') return array(substr($text, 1, $len - 2), 'Phrase');
		else if ($text[0] == '[' && $text[$len - 1] == ']') return array(substr($text, 1, $len - 2), 'Exact');
		else                                                return array($text, 'Broad');
	}
	
	public static function update_active_market_accounts()
	{
		$markets = util::get_ppc_markets();
		
		// get active clients
		$active_cl_ids = db::select("select id, 1 from eppctwo.clients where status='On'", 'NUM', 0);
		
		// get all accounts ids, set active account ids
		$ac_ids = db::select("
			select client, market, account
			from eppctwo.data_sources
		");
		$active_ac_ids = array();
		for ($i = 0; list($cl_id, $market, $ac_id) = $ac_ids[$i]; ++$i)
		{
			if (array_key_exists($cl_id, $active_cl_ids))
				$active_ac_ids[$market][$ac_id] = 1;
		}
		
		// get all accounts for each market and set status
		foreach ($markets as $market)
		{
			$market_ac_ids = db::select("select id from eppctwo.{$market}_accounts");
			
			foreach ($market_ac_ids as $market_ac_id)
			{
				$status = (array_key_exists($market_ac_id, $active_ac_ids[$market])) ? 'On' : 'Off';
				db::update(
					"eppctwo.{$market}_accounts",
					array('status' => $status),
					"id='$market_ac_id'"
				);
			}
		}
		
		// yahoo master accounts: get all child accounts, if any are On, master account is On
		$y_master_ac_ids = db::select("select id from eppctwo.y_master_accounts");
		foreach ($y_master_ac_ids as $master_ac_id)
		{
			$num_active = db::select_one("select count(*) from eppctwo.y_accounts where master_account='$master_ac_id' && status='On'");
			$status = ($num_active > 0) ? 'On' : 'Off';
			echo "$master_ac_id:$num_active:$status\n";
			db::exec("
				update eppctwo.y_master_accounts
				set status='$status'
				where id='$master_ac_id'
			");
		}
	}
	
	public static function passhash($password, $user)
	{
		return (md5($user.'-'.$password));	
	}

	
	public static function verify_dates(&$start_date, &$end_date)
	{
		$start_time = strtotime($start_date);
		$end_time = strtotime($end_date);
		
		// incorrectly formatted dates, default to yesterday
		if (empty($start_time) || empty($end_time))
		{
			$end_date = date(util::DATE, time() - 86400);
			$start_date = $end_date;
		}
		// start date after end date, just use end date
		else if ($start_date > $end_date)
		{
			$start_date = $end_date;
		}
	}
	
	public static function is_cgi()
	{
		return (class_exists('cgi'));
	}
	
	public static function is_cli()
	{
		return (class_exists('cli'));
	}

	public static function is_api()
	{
		return (class_exists('api'));
	}

	public static function create_file_from_db($filename, $data, $format = "\t"){
		$head = true;
		$fp = fopen($filename,"w");

		foreach($data as $content){
			if($head){
				$head = false;
				fputcsv($fp,array_keys($content),$format);
			}
			fputcsv($fp,$content,$format);
		}
		fclose($fp);
	}

	public static function get_phpmailer(){
		require_once(epro\COMMON_PATH.'phpmailer.php');
		$email = new PHPMailer();
		$email->Host = 'smtp.emailsrvr.com';
		$email->SMTPAuth = true;
		$email->Username = 'techsupport@wpromote.com';
		$email->Password = 'Tl5#PPB580WiEpLRH';
		$email->SMTPSecure = 'tls';		
		$email->From = 'techsupport@wpromote.com';
		$email->FromName = 'Hermes @ Wpromote';
		$email->addReplyTo('techsupport@wpromote.com');
		return $email;
	}


	
	/**
	 * deprecated changed from load_rs to load_rs_legacy 2014-05-02
	 */
	public static function load_rs_legacy()
	{
		// add most specific first
		$paths = array();
		$paths[] = \epro\COMMON_PATH.'tables/';
		$rs_names = func_get_args();
		
		foreach ($rs_names as $rs_name) {
			foreach ($paths as $path) {
				foreach(glob($path.$rs_name.".*rs.php") as $filename){
					if (file_exists($filename)) {
						require_once($filename);
					}
				}
			}
		}
	}

	public static function load_rs()
	{
		// add most specific first
		$paths = array();
		//$paths[] = \epro\COMMON_PATH.'tables/';
		//$dir = array_unique(scandir(\epro\COMMON_PATH.'tables/'));
		$dir = array_diff(scandir(\epro\COMMON_PATH.'tables/'),array('..'));
		foreach($dir as $path){
			if(is_dir(\epro\COMMON_PATH.'tables/'.$path)){
				$paths[] = \epro\COMMON_PATH.'tables/'.$path.'/';
			}
		}
		$rs_names = func_get_args();
		foreach ($rs_names as $rs_name) {
			foreach ($paths as $path) {
				foreach(glob($path.$rs_name.".*rs.php") as $filename){
					if (file_exists($filename)) {
						//include file
						require_once($filename);
					}
				}
			}
		}
	}
	
	public static function load_lib()
	{
		$args = func_get_args();
		foreach ($args as $lib)
		{
			$lib_base = \epro\COMMON_PATH.$lib;
			
			// of the form common_path/lib_name.php
			$lib_path = $lib_base.'.php';
			if (file_exists($lib_path))
			{
				require_once($lib_path);
			}
			else
			{
				// of the form common_path/lib_name/lib_name.php
				$lib_path = $lib_base.'/'.$lib.'.php';
				if (file_exists($lib_path))
				{
					require_once($lib_path);
				}
			}
			
			// try to load rs file
			util::load_rs($lib);
			
			$mod_lib_path = \epro\COMMON_PATH.'modules/'.$lib.'_lib.php';
			if (file_exists($mod_lib_path))
			{
				require_once($mod_lib_path);
			}
		}
	}

	// in util instead of cli:
	// 1. other "load" functions are here
	// 2. may want to load workers from cgi for testing?
	// arg: can be string of worker type or job
	public static function load_worker($mixed)
	{
		$job_type = (is_string($mixed)) ? $mixed : $mixed->type;
		$worker_class = 'worker_'.util::simple_text($job_type);
		$worker_path = \epro\CLI_PATH."workers/{$worker_class}.php";
		if (file_exists($worker_path)) {
			require_once(\epro\CLI_PATH.'worker.php');
			require_once($worker_path);

			return new $worker_class($mixed);	
		}
		else {
			return false;
		}
	}
	
	public static function refresh_accounts($market, $user = null, $pass = null, $customer_id = null, $opts = array())
	{
		require_once(\epro\WPROPHP_PATH.'apis/apis.php');
		
		util::set_opt_defaults($opts, array(
			'update_existing' => false
		));
		$api = base_market::get_api($market);
		// $api->debug(); db::dbg();
		
		if ($market == 'm')
		{
			/*
			 * make array of m user accounts
			 */
			// client passed in user
			if ($user)
			{
				$m_users = array(array($user, $pass, $customer_id));
			}
			// get users from db
			else
			{
				$m_users = db::select("
					select distinct user, pass, customer_id
					from eppctwo.m_accounts
					where user <> '' && status = 'On'
				");
			}
			
			// get all current accounts
			$new_acs = array();
			$cur_acs = db::select("select distinct id, 1 from eppctwo.m_accounts", 'NUM', 0);
			for ($i = 0, $ci = count($m_users); $i < $ci && list($user, $pass, $customer_id) = $m_users[$i]; ++$i) {
				$user_acs = util::m_refresh_mcc($api, $cur_acs, $user, $pass, $customer_id, $opts);
				$new_acs = array_merge($new_acs, $user_acs);
			}
		}
		else if ($market == 'y')
		{
			/*
			 * make array of y user accounts
			 */
			// client passed in user
			if ($user)
			{
				$y_users = array(array($user, $pass, $customer_id));
			}
			// get users from db
			else
			{
				$y_users = db::select("
					select distinct external_user, external_pass, id
					from eppctwo.y_master_accounts
					where status = 'On'
				");
			}
			
			// get all current accounts
			$cur_acs = db::select("select distinct id, 1 from eppctwo.y_accounts", 'NUM', 0);
			for ($i = 0; list($user, $pass, $master_id) = $y_users[$i]; ++$i)
			{
				if ($master_id == '378953')
				{
					util::y_refresh_mcc($api, $cur_acs, $user, $pass, $master_id, $opts);
				}
			}
		}
		else if ($market == 'g')
		{
			// get all current accounts
			$cur_acs = db::select("select id, text from eppctwo.g_accounts", 'NUM', 0);
			$new_acs = util::g_refresh_mcc('', $api, $cur_acs, $opts);
			if (class_exists('feedback'))
			{
				if ($new_acs === false)
				{
					feedback::add_error_msg('Error: '.$api->get_error());
				}
				else
				{
					if ($new_acs)
					{
						for ($i = 0; list($ac_id, $ac_text) = $new_acs[$i]; ++$i)
						{
							feedback::add_success_msg('New account '.($i + 1).': '.(($ac_text) ? $ac_text : 'empty').' ('.$ac_id.')');
						}
					}
					else
					{
						feedback::add_success_msg('No new accounts found');
					}
				}
			}
		}
		return $new_acs;
	}
	
	
	
	public static function y_refresh_mcc(&$api, &$cur_acs, $user, $pass, $master_id)
	{
		$api->set_master($master_id);
		$accounts = $api->get_accounts();
		
		if ($accounts === false)
		{
			if (class_exists('feedback'))
			{
				feedback::add_error_msg('Error refresing accounts for '.$user.': '.$api->get_error());
			}
			return false;
		}
		
		for ($i = 0, $ci = count($accounts); $i < $ci; ++$i)
		{
			$ac = $accounts[$i];
			if (!array_key_exists($ac['id'], $cur_acs))
			{
				switch ($ac['marketID'])
				{
					case ('AU'): $currency = 'AUD'; break;
					case ('HK'): $currency = 'HKD';	break;
					case ('UK'): $currency = 'GBP'; break;
					case ('JP'): $currency = 'JPY'; break;
					case ('KR'): $currency = 'KRW'; break;
					case ('BR'): $currency = 'BRR'; break;
					
					case ('DE'):
					case ('FR'):
					case ('ES'):
					case ('NL'):
					case ('IT'):
						$currency = 'EUR';
						break;
					
					case ('US'):
					default:
						$currency = 'USD';
						break;
				}
				// id	company	master_account	text	ca_info_mod_time	status	market	currency
				db::insert_update("eppctwo.y_accounts", array('id'), array(
					'id' => $ac['id'],
					'company' => 1,
					'master_account' => $ac['masterAccountID'],
					'text' => $ac['text'],
					'market' => $ac['marketID'],
					'currency' => $currency
				), false);
				if (class_exists('feedback'))
				{
					feedback::add_success_msg('Successfully added account '.$ac['text'].' ('.$ac['id'].')');
				}
			}
		}
	}
	
	public static function m_refresh_mcc(&$api, &$cur_acs, $user, $pass, $customer_id, $opts = array())
	{
		$api->set_api_user($user, $pass);
		$accounts = $api->get_accounts($customer_id);
		if ($accounts === false) {
			if (class_exists('feedback')) {
				feedback::add_error_msg('Error refresing accounts for '.$user.': '.$api->get_error());
			}
			return false;
		}

		$new_acs = array();
		for ($i = 0, $ci = count($accounts); $i < $ci; ++$i) {
			$ac_id = $accounts[$i]['Id'];
			if (!empty($ac_id) && (!empty($opts['update_existing']) || !array_key_exists($ac_id, $cur_acs))) {
				$account = $api->get_account($ac_id);
				if ($account === false) {
					if (class_exists('feedback')) {
						feedback::add_error_msg('Error getting account '.$ac_id.': '.$api->get_error());
					}
				}
				else {
					$account['Status'] = $account['AccountLifeCycleStatus'];
					if ($account['Status'] == 'Active') {
						$account['Status'] = 'On';
					}
					$ac_data = array(
						'id' => $account['Id'],
						'company' => 1,
						'num' => $account['Number'],
						'customer_id' => $account['ParentCustomerId'],
						'text' => $account['Name'],
						'status' => $account['Status'],
						'currency' => util::m_currency_to_standard_currency($account['CurrencyType']),
						'user' => $user,
						'pass' => $pass
					);
					db::insert_update("eppctwo.m_accounts", array('id'), $ac_data, false);
					if (!array_key_exists($ac_id, $cur_acs)) {
						$new_acs[] = $ac_data;
					}
					if (class_exists('feedback')) {
						feedback::add_success_msg('Successfully added account '.$account['Name'].' ('.$account['Id'].')');
					}
				}
			}
		}
		return $new_acs;
	}
	
	public static function g_refresh_mcc($mcc_id, $api = null, &$cur_acs = null, $opts)
	{
		require_once(\epro\WPROPHP_PATH.'apis/apis.php');
		
		if (!$api)
		{
			$api = new g_api(1);
		}
		if (!$cur_acs)
		{
			$cur_acs = db::select("select id, text from eppctwo.g_accounts", 'NUM', 0);
		}
		
		$g_acs = $api->get_accounts($mcc_id);
		$new_accounts = array();
		foreach ($g_acs as $ac)
		{
			// if this email isn't in our list of current clients, they're either new or have changed their email
			$ac_data = array(
				'parent_id' => $ac->parentId,
				'text' => $ac->name,
				'email' => $ac->login,
				'currency' => $ac->currencyCode,
				'is_mcc' => $ac->canManageClients
			);
			if (!array_key_exists($ac->customerId, $cur_acs))
			{
				$ac_data['id'] = $ac->customerId;
				$ac_data['company'] = 1;
				db::insert("eppctwo.g_accounts", $ac_data);
				$new_accounts[] = array($ac->customerId, $ac->name);
			}
			else
			{
				db::update("eppctwo.g_accounts", $ac_data, "id = '{$ac->customerId}'");
			}
		}
		return $new_accounts;
	}
	
	const RE_EMAIL_ADDRESS = "[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}";
	
	public static function is_email_address($x)
	{
		return preg_match("/^".self::RE_EMAIL_ADDRESS."$/i", $x);
	}
	
	public static function mail($from, $to, $subject, $body, $additional_headers = array(), $opts = array())
	{
		util::set_opt_defaults($opts, array(
			'silent' => false,
			'dbg' => false
		));
		$email_sender = (preg_match("/\b(".self::RE_EMAIL_ADDRESS.")\b/i", $from, $matches)) ? $matches[1] : 'postmaster@wpromote.com';
		
		$additional_headers['From'] = $from;
		$headers = '';
		foreach ($additional_headers as $k => $v)
		{
			$headers .= $k.': '.$v."\n";
		}
		
		util::mail_format($body, $headers, $additional_headers);
		
		if (util::is_dev() || $opts['dbg'])
		{
			if (!$opts['silent'])
			{
				echo "
					<h2>Localhost email dump</h2>
					<p><b>From</b>: $from (sender = $email_sender)</p>
					<p><b>To</b>: $to</p>
					<p><b>Other Headers</b>: <pre>$headers</pre></p>
					<p><b>Subject</b>: $subject</p>
					<p><b>Message</b>: <pre>$body</pre></p>
				";
			}
			return true;
		}
		else
		{
			return mail($to, $subject, $body, $headers, "-f{$email_sender}");
		}
	}
	
	private static function mail_format(&$body, &$headers, &$additional_headers)
	{
		if (is_array($body))
		{
			$text_plain = $body['plain'];
			$text_html = $body['html'];
		}
		else
		{
			$text_plain = $body;
			$text_html = false;
		}
		if (array_key_exists('attachments', $additional_headers))
		{
			$attachments = $additional_headers['attachments'];
		}
		else
		{
			$attachments = false;
		}
		
		$boundary = md5(time());
		if ($attachments)
		{
			$mixed_boundary = 'm'.$boundary;
			$headers .= 'Content-Type: multipart/mixed; boundary='.$mixed_boundary."\n";
			
			if ($text_html)
			{
				$alt_boundary = 'a'.$boundary;
				$body  = "--{$mixed_boundary}\n";
				$body .= "Content-Type: multipart/alternative; boundary={$alt_boundary}\n";
				$body .= "\n";
				$body .= util::mail_format_multipart_alternative($alt_boundary, $text_plain, $text_html);
				$body .= util::mail_format_encode_attachments($mixed_boundary, $attachments)."--{$mixed_boundary}--\n";
			}
			else
			{
				$body  = "--{$mixed_boundary}\n";
				$body .= "Content-Type: text/plain; charset=ISO-8859-1\n";
				$body .= "\n";
				$body .= "{$text_plain}\n";
				$body .= util::mail_format_encode_attachments($mixed_boundary, $attachments)."--{$mixed_boundary}--\n";
			}
		}
		else
		{
			if ($text_html)
			{
				$headers .= 'Content-Type: multipart/alternative; boundary='.$boundary."\n";
				$body = util::mail_format_multipart_alternative($boundary, $text_plain, $text_html);
			}
			else
			{
				$headers .= 'Content-Type: text/plain; charset=ISO-8859-1'."\n";
				$body = $text_plain."\n";
			}
		}
	}
	
	private function mail_format_multipart_alternative($boundary, $text_plain, $text_html)
	{
		return
			"--{$boundary}\n".
			"Content-Type: text/plain; charset=ISO-8859-1\n".
			"\n".
			"{$text_plain}\n".
			"\n".
			"--{$boundary}\n".
			"Content-Type: text/html; charset=ISO-8859-1\n".
			"\n".
			"{$text_html}\n".
			"\n".
			"--{$boundary}--\n"
		;
	}
	
	private function mail_format_encode_attachments($boundary, $attachments)
	{
		$encoded = '';
		for ($i = 0, $ci = count($attachments); $i < $ci; ++$i)
		{
			$attachment = $attachments[$i];
			if (array_key_exists('path', $attachment))
			{
				$path =  $attachment['path'];
				if (array_key_exists('name', $attachment))
				{
					$name = $attachment['name'];
				}
				else
				{
					$pathinfo = pathinfo($path);
					$name = $pathinfo['basename'];
				}
				$file_data = file_get_contents($path);
			}
			else if (array_key_exists('data', $attachment) && array_key_exists('name', $attachment))
			{
				$file_data = $attachment['data'];
				$name = $attachment['name'];
			}
			else
			{
				continue;
			}
			$encoded .= "\n--{$boundary}\n";
			$encoded .= "Content-Type: application/octet-stream; name=\"{$name}\"\n";
			$encoded .= "Content-Disposition: attachment; filename=\"{$name}\"\n";
			$encoded .= "Content-Transfer-Encoding: base64\n";
			$encoded .= "\n";
			$encoded .= chunk_split(base64_encode($file_data), 76, "\n");
		}
		return $encoded;
	}
	
	public static function ordinal($x)
	{
		$x = (string) $x;
		$len = strlen($x);
		if (!$len)
		{
			return $x;
		}
		else if ($len > 1 && $x[$len - 2] == '1')
		{
			return $x.'th';
		}
		else
		{
			switch ($x[$len - 1])
			{
				case (1): return $x.'st';
				case (2): return $x.'nd';
				case (3): return $x.'rd';
				default:  return $x.'th';
			}
		}
	}
	
	public static function get_sap_url($url_key)
	{
		$url = 'http'.((util::is_dev()) ? '' : 's').'://'.\epro\SAP_DOMAIN;
		return $url.'/'.$url_key;
	}

	public static function get_client_sap_urls($cl_ids)
	{
		if (!is_array($cl_ids)) {
			$cl_ids = array($cl_ids);
		}
		$new_url_keys = db::select("
			SELECT url_key
			FROM contracts.prospects
			WHERE client_id in ('".implode("','", $cl_ids)."')
		");
		
		//check old saps
		$old_url_keys = db::select("
			SELECT url_key
			FROM eppctwo.prospects
			WHERE client_id in ('".implode("','", $cl_ids)."')
		");
		
		$url_keys = array_merge($new_url_keys, $old_url_keys);
		
		if(!empty($url_keys)){
			$sap_urls = array();
			foreach($url_keys as $url_key){
				$sap_urls[] = util::get_sap_url($url_key);
			}
			return $sap_urls;
		}
		
		return false;
	}

	public static function update_job_details($job, $msg, $lvl = 0)
	{
		if ($job && is_object($job) && method_exists($job, 'update_details')) {
			$job->update_details($msg, $lvl);
		}
	}
	
	// 20130913, this should be deletable once accouns are normalized to eac
	// 20140123, keeping around, can use with object, array, string
	public static function get_account_object_key($mixed)
	{
		if (is_string($mixed) && !empty($mixed)) {
			return $mixed;
		}
		else if (is_array($mixed) && isset($mixed['id'])) {
			return $mixed['id'];
		}
		else if (is_object($mixed) && isset($mixed->id)) {
			return $mixed->id;
		}
		else {
			return false;
		}
	}

	public static function get_url_contents($url)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		$r = curl_exec($curl);
		curl_close($curl);
		return $r;
	}
	
	// dirs: ASC, DESC
	// types: STR, NUM
	public static function sort2d(&$a, $index, $dir = 'ASC', $type = false)
	{
		if (!$type) {
			$type = (is_numeric($a[0][$index])) ? 'NUM' : 'STR';
		}
		$vars = (strtoupper($dir) == 'ASC') ? '$a, $b' : '$b, $a';
		$func = (strtoupper($type) == 'STR') ?
			create_function($vars, 'return strcmp($a["'.$index.'"], $b["'.$index.'"]);')
				:
			create_function($vars, 'return ($a["'.$index.'"] < $b["'.$index.'"]) ? 0 : 1;')
		;
		usort($a, $func);
	}
	
	public static function set_opt_defaults(&$opts, $defaults)
	{
		if (!is_array($opts))
		{
			$opts = array();
		}
		foreach ($defaults as $k => $v)
		{
			if (!array_key_exists($k, $opts))
			{
				$opts[$k] = $v;
			}
		}
	}

	public static function wpro_post($wpro_url_path, $wpro_func, $data, $dbg = null)
	{
		if (is_null($dbg) && class_exists('dbg') && dbg::is_on()) {
			$dbg = true;
		}
		return self::sts_post('http://'.\epro\WPRO_DOMAIN.'/'.$wpro_url_path.'/sts_'.$wpro_func, $data, $dbg);
	}

	private static function sts_post($url, $data, $dbg = false)
	{
		if (is_string($data))
		{
			$post_str = $data;
		}
		else
		{
			// prepare post data
			$post_str = '';
			foreach ($data as $k => $v)
			{
				if (!empty($post_str))
				{
					$post_str .= '&';
				}
				if (is_scalar($v))
				{
					$post_str .= urlencode($k).'='.urlencode($v);
				}
			}
		}
		
		$url_info = parse_url($url);
		$path = $url_info['path'];
		if (!empty($url_info['query'])) $path .= '?'.$url_info['query'];
		
		$headers = array(
			'POST '.$url_info['path'].' HTTP/1.1',
			'Host: '.$url_info['host'],
			'Connection: close',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: '.strlen($post_str)
		);
		if ($dbg === true)
		{
			echo "u=$url".ENDL;
			echo "post=$post_str".ENDL;
			print_r($headers);
		}
		
		$curl = curl_init($url);
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => $post_str
		));
		if (strpos($url, 'https') !== false) curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

		$response = curl_exec($curl);
		@curl_close($curl);
		
		if ($dbg === true) var_dump($response);
		
		$unserialized = @unserialize($response);
		return (($unserialized) ? $unserialized : $response);
	}

	/**
	 * mt_rand_uuid() a simple unique user id generator
	 * @return string (36 char HEX) '8-4-4-4-12'
	 */
	public static function mt_rand_uuid(){
		$str = sha1(mt_rand());
		return sprintf("%s-%s-%s-%s-%s%d",
						substr($str,0,8),
						substr($str,8,4),
						substr($str,12,4),
						substr($str,16,4),
						substr($str,20,2),
						time());
	}

	public static function get_current_user_id(){
		return $_SESSION["id"];
	}


    public static function wget($url){
    	$cmd = "wget -q -O - $url";
    	return shell_exec($cmd);
    }

    public static function HttpGET($url){
    	$c = curl_init();
    	$opt = array(
    			CURLOPT_URL=>$url,
    			CURLOPT_USERAGENT=>$_SERVER['HTTP_USER_AGENT'],
    			CURLOPT_RETURNTRANSFER=>true,
    			CURLOPT_FOLLOWLOCATION=>true,
    	);
    	curl_setopt_array($c,$opt);
    	return curl_exec($c);
    }

}

// e for echo!
function e()
{
	$args = func_get_args();
	foreach ($args as $i => &$arg) {
		echo "(";
		if ($arg) {
			if (is_scalar($arg)) {
				echo $arg;
			}
			else {
				if (util::is_cgi()) {
					echo '<pre>'.print_r($arg, true)."</pre>\n";
				}
				else {
					print_r($arg);
				}
			}
		}
		else {
			var_dump($arg);
			if (util::is_cgi()) {
				echo "<br />\n";
			}
		}
		echo ")";
	}
	echo ENDL;
}

?>