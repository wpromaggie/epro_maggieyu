<?php

define('REPORT_SHOW_STATUS', 1);
define('REPORT_DELETE_ZIP', 2);
define('REPORT_NO_HEADERS', 4);
define('REPORT_REFRESH_CONVS', 8);
define('REPORT_DEFAULTS', REPORT_DELETE_ZIP);

define('STATUS_INTERVAL', 16);

define('REPORT_MAX_TIME', 87400);
define('REPORT_READ_LEN', 65536);

abstract class base_market extends base_soap
{	
	public $market, $job, $cl_id, $eac_id;
	
	protected $api_user, $api_pass, $api_authentication_token;
	protected $company, $name, $ac_id;
	protected $service, $action;
	protected $report_start_date, $report_end_date, $report_type;
	
	public function __construct($market, $company)
	{
		$api_info = @db::select_row("select * from {$market}_api_accounts where company=".$company, 'ASSOC');

		$this->market = $market;
		$this->company = $company;
		@$this->set_api_user($api_info['mcc_user'], $api_info['mcc_pass']);
		@$this->set_tokens($api_info);
		
		if (class_exists('dbg') && method_exists('dbg', 'is_on') && dbg::is_on())
		{
			$this->debug();
		}
	}
	
	public static function get_api($market, $ac_id = '', $company = 1)
	{
		$class_name = $market.'_api';
		$api = new $class_name($company, $ac_id);
		return $api;
	}
	
	public function set_api_user($api_user, $api_pass, $access_token = NULL)
	{
		$this->api_user = $api_user;
		$this->api_pass = $api_pass;
		if(isset($access_token))
			$this->access_token = $access_token;
	}
	
	public function set_account($ac_id = '')
	{
		$this->ac_id = $ac_id;
	}	
	
	public function get_error()
	{
		return $this->error;
	}
	
	public function delete_report_files($date)
	{
		$dir = opendir(\epro\REPORTS_PATH.$this->market);
		$files = array();
		while (false !== ($file = readdir($dir)))
		{
			if (preg_match("/_{$date}/", $file))
				$files[] = $file;
		}
		closedir($dir);
		
		foreach ($files as $file)
			unlink(\epro\REPORTS_PATH.$this->market.'/'.$file);
	}
	
	protected function build_report_request($scope, $start_date, $end_date, $type = 'Complete')
	{
		if (empty($end_date)) $end_date = $start_date;
		$this->report_start_date = $start_date;
		$this->report_end_date = $end_date;
		$this->report_type = $type;
		return array(
			'name'          => 'api_'.$type.'_'.date('Y-m-d_H:i:s'),
			'account'       => api_translate::marketize_report_account($this->market, $this->ac_id, $scope),
			'start_date'    => api_translate::marketize_report_date($this->market, $start_date),
			'end_date'      => api_translate::marketize_report_date($this->market, $end_date),
			'type'          => api_translate::marketize_report_type($this->market, $type),
			'time_interval' => api_translate::marketize_report_interval($this->market, $end_date),
			'columns'       => api_translate::marketize_report_columns($this->market)
		);
	}
	
	// never give up!
	protected function run_report_give_up($time)
	{
		return false;
	}
	
	// always try again
	protected function run_report_try_again($num_attempts)
	{
		return true;
	}

	// set data sources, exchange rates
	protected function set_client_account_info()
	{
		$this->set_client_account_data_sources();
		$this->set_client_account_exchange_rates();
	}

	protected function set_client_account_data_sources()
	{
		$tmp = db::select("
			select ds.campaign, ds.ad_group
			from eppctwo.data_sources ds, eac.as_ppc ppc
			where
				ds.market = :market &&
				ds.account = :ac_id &&
				ppc.id = :aid &&
				ppc.id = ds.account_id
			order by market asc
		", array(
			'market' => $this->market,
			'ac_id' => $this->ac_id,
			'aid' => $this->eac_id
		));
		$this->cl_ac_data_sources = array();
		for ($i = 0, $ci = count($tmp); $i < $ci; ++$i) {
			list($ca_id, $ag_id) = $tmp[$i];
			if ($ag_id) {
				$this->cl_ac_data_sources['ad_groups'][$ag_id] = 1;
			}
			// only set campaign if ad group is not also set
			else if ($ca_id) {
				$this->cl_ac_data_sources['campaigns'][$ca_id] = 1;
			}
			// else client must own entire account
		}
	}

	protected function set_client_account_exchange_rates()
	{
		$this->ex_rates = false;
		$currency = db::select_one("
			select currency
			from eppctwo.{$this->market}_accounts
			where id = :aid
		", array('aid' => $this->ac_id));
		if (!(empty($currency) || $currency == 'USD')) {
			// get map for date -> exchange rate
			$this->ex_rates = db::select("
				select d, rate
				from eppctwo.exchange_rates
				where
					currency = :currency &&
					d between :start_date and :end_date
			", array(
				'currency' => $currency,
				'start_date' => $this->report_start_date,
				'end_date' => $this->report_end_date
			), 'NUM', 0);
		}
	}

	protected function is_ql_account()
	{
		return (ppc_lib::is_ql_account($this->eac_id));
	}

	protected function does_data_belong_to_client($ca_id, $ag_id = false)
	{
		return (
			 // ql refresh, we want all data
			$this->is_ql_account() ||
			// client owns entire account
			empty($this->cl_ac_data_sources) ||
			// campaign id or ad group id match
			isset($this->cl_ac_data_sources['campaigns'][$ca_id]) ||
			($ag_id && isset($this->cl_ac_data_sources['ad_groups'][$ag_id]))
		);
	}
	
	protected function clear_old_conv_type_data($start_date, $end_date)
	{
		conv_type_count::delete_all(array("where" => "
			aid = '{$this->eac_id}' &&
			d between '$start_date' and '$end_date' &&
			market = '{$this->market}' &&
			account_id = '{$this->ac_id}'
		"));
	}

	// cmp can be and or or
	// if anything else, type map is returned
	protected function account_type($types, $cmp = 'or')
	{
		if (!isset($this->ac_type_maps[$this->ac_id])) {
			$this->ac_type_maps[$this->ac_id] = db::select_row("
				select ".implode(",", $types)."
				from eac.as_ppc ppc, eppctwo.data_sources ds
				where
					ds.market = '{$this->market}' &&
					ds.account = '".$this->ac_id."' &&
					ds.account_id = ppc.id
			", 'ASSOC');
		}
		$type_map = $this->ac_type_maps[$this->ac_id];

		switch (strtolower($cmp)) {
			case ('or'):
				for ($i = 0, $ci = count($types); $i < $ci; ++$i) {
					if ($type_map[$types[$i]]) {
						return true;
					}
				}
				return false;

			case ('and'):
				for ($i = 0, $ci = count($types); $i < $ci; ++$i) {
					if (!$type_map[$types[$i]]) {
						return false;
					}
				}
				return true;

			case (false):
			default:
				return $type_map;
		}
	}

	// we pass in the start date since it doesn't have any of the market formatting
	protected function run_report($request, $flags = REPORT_DEFAULTS, $num_attempts = 1)
	{
		// init report
		$report_id = $this->schedule_report($request);
		if ($report_id === false)
		{
			if ($flags & REPORT_SHOW_STATUS) echo "Error creating report\n";
			return false;
		}
		if ($flags & REPORT_SHOW_STATUS) echo $this->report_start_date.',Report ID='.$report_id."\n";
		
		for ($time = 0; $time < REPORT_MAX_TIME; $time += STATUS_INTERVAL)
		{
			if ($this->run_report_give_up($time))
			{
				if ($this->run_report_try_again($num_attempts))
				{
					if ($flags & REPORT_SHOW_STATUS) echo $this->report_start_date.", let's try this again\n";
					return $this->run_report($request, $flags, $num_attempts + 1);
				}
				else
				{
					if ($flags & REPORT_SHOW_STATUS) echo $this->report_start_date.", out of time, out of attempts\n";
					return false;
				}
			}
			$status = $this->get_report_status($report_id);
			
			util::update_job_details($this->job, 'Running market report ('.$request['type'].'): '.$status);
			
			if ($flags & REPORT_SHOW_STATUS) echo $this->report_start_date.',Status='.$status .'('.date('H:i:s').")\n";
			if ($status == 'Completed')
			{
				util::update_job_details($this->job, 'Running market report ('.$request['type'].'): Downloading');
				
				$report_url = $this->get_report_url($report_id);
				
				$base_name = $this->get_report_name($request['type'], $request['time_interval'], $flags);
				
				$zipped_path = $this->get_report_path().$base_name.'.gz';
				if ($this->download_report($report_url, $zipped_path, ($flags & REPORT_SHOW_STATUS)) === false)
				{
					if ($flags & REPORT_SHOW_STATUS) echo "Error: ".$this->get_error()."\n";
					return false;
				}
				if (!file_exists($zipped_path))
				{
					$this->error = "could not download to {$zipped_path}";
					if ($flags & REPORT_SHOW_STATUS) echo "Error: ".$this->get_error()."\n";
					return false;
				}
				
				// gunzip it, redirect to temp file
				
				for (
					$fcount = 0, $base_unzipped_path = $this->get_report_path().$base_name, $test_unzipped_path = $base_unzipped_path.'.csv';
					file_exists($test_unzipped_path);
					$test_unzipped_path = $base_unzipped_path.'_'.$fcount.'.csv', $fcount++
				);
				// $unzipped_path = $this->get_report_path().$base_name.'.csv';
				$unzipped_path = $test_unzipped_path;
				$cmd = 'gzip -cd '.$zipped_path.' > '.$unzipped_path;
				if ($flags & REPORT_SHOW_STATUS) echo $cmd.ENDL;
				exec($cmd);
				
				// optionally delete zip file
				if ($flags & REPORT_DELETE_ZIP) unlink($zipped_path);
				
				// return name to unzipped file
				return $unzipped_path;
			}
			else if ($status == 'Error' || $status == 'Deleted' || $status == 'Failed')
			{
				if ($flags & REPORT_SHOW_STATUS) echo "Error: report failed\n";
				return false;
			}
			
			// valid stati, sleep and continue
			sleep(STATUS_INTERVAL);
		}
		// should never get here
		return false;
	}
	
	public function download_report($report_url, $path, $do_show_status)
	{
		$cmd = 'wget --no-check-certificate -q '.$report_url.' -O '.$path;
		if ($do_show_status) echo $cmd.ENDL;
		exec($cmd);
		return true;
	}
	
	protected function init_company_report($date)
	{
		$report_name = $this->get_company_report_name($date);
		$report_path = $this->get_report_path().$report_name;
		
		#exec('echo '.api_translate::get_report_columns_string().' > '.$report_path);
		// no headers in company report, just create the file
		exec('echo -n > '.$report_path);
		
		return $report_path;
	}
	
	public function init_sandbox()
	{
	}
	
	public function get_report_path()
	{
		return (\epro\REPORTS_PATH.$this->market.'/');
	}
	
	protected function get_report_path_from_cli($path)
	{
		if (file_exists($path)) {
			return $path;
		}
		$path = $this->get_report_path().$path;
		if (file_exists($path)) {
			return $path;
		}
		return false;
	}

	public function get_company_report_name($date)
	{
		return ($this->market.'_'.$this->company.'_'.$date.'.csv');
	}
	
	public function get_report_name($report_type, $interval = '', $flags = util::NO_FLAGS)
	{
		if (empty($this->ac_id))
		{
			$ac_txt = (empty($this->master_account_id)) ? 'master' : ('master_'.$this->master_account_id);
		}
		else
		{
			$ac_txt = 'account_'.$this->ac_id;
		}
		
		if ($flags & REPORT_REFRESH_CONVS) $report_type .= '_Refresh';
		
		if (!empty($interval)) $report_type .= '_'.$interval;
		
		$rep_date = (empty($this->report_start_date)) ? date(util::DATE) : $this->report_start_date;
		return ($this->market.'_'.$ac_txt.'_'.$report_type.'_'.$rep_date);
	}
	
	public function run_account_report($eac_id, $start_date, $end_date = '', $flags = null)
	{
		$this->set_eac_id($eac_id);
		$request = $this->build_report_request('account', $start_date, $end_date);
		$market_report_path = $this->run_report($request, $flags);
		if ($market_report_path === false) return false;
		return $this->standardize_report($market_report_path, $flags);
	}

	protected function get_rep_delimiter()
	{
		return ',';
	}

	protected function process_campaign_report($report_path, $ca_rev = false)
	{
		$h = fopen($report_path, 'rb');
		if ($h === false) {
			$this->error = 'Could not open campaign report ('.$report_path.')';
			return false;
		}
		// build array of data by date so we know what we need to update for all_data report
		$new_ca_data = array();
		$data_table = "{$this->market}_objects.campaign_data_{$this->eac_id}";
		$line_count = $this->file_get_line_count($h);
		$delimiter = $this->get_rep_delimiter();
		for ($i = 0; ($data = fgetcsv($h, 0, $delimiter)) !== FALSE; ++$i) {
			if ($i % 1000 == 0) util::update_job_details($this->job, 'Processing campaign data '.$i.' / '.$line_count);
			// order from defining rep columns
			//mpc_convs - many per click conversions
			//vt_convs - view through conversions
			list($date, $ca_id, $imps, $clicks, $convs, $cost, $ave_pos, $rev, $mpc_convs, $vt_convs) = $data;
			$dtest = strtotime($date);
			if (is_numeric($ca_id) && $dtest !== false) {

				if (!$this->does_data_belong_to_client($ca_id)) {
					continue;
				}
				// set default values
				if (!is_numeric($mpc_convs)) {
					$mpc_convs = $rev = $vt_convs = 0;
				}
				// bing dates are a different format, standardize
				$date = date(util::DATE, $dtest);
				// get big rev from array passed in
				if ($ca_rev !== false) {
					$rev = (isset($ca_rev[$date][$ca_id])) ? $ca_rev[$date][$ca_id] : 0;
				}
				// check ex rates
				if ($this->ex_rates && isset($this->ex_rates[$date])) {
					$cost *= $this->ex_rates[$date];
				}
				if (!isset($new_ca_data[$date])) {
					$new_ca_data[$date] = array('cost' => 0, 'convs' => 0, 'mpc_convs' => 0);
				}
				$new_ca_data[$date]['cost'] += $cost;
				$new_ca_data[$date]['convs'] += $convs;
				$new_ca_data[$date]['mpc_convs'] += $mpc_convs;

				db::insert_update($data_table, array('data_date', 'campaign_id'), array(
					'job_id' => $this->job->id,
					'account_id' => $this->ac_id,
					'campaign_id' => $ca_id,
					'data_date' => $date,
					'imps' => $imps,
					'clicks' => $clicks,
					'convs' => $convs,
					'cost' => str_replace(',', '', $cost),
					'pos_sum' => round($imps * $ave_pos),
					'revenue' => str_replace(',', '', $rev),
					'mpc_convs' => $mpc_convs,
					'vt_convs' => $vt_convs
				));
			}
		}
		// round costs
		foreach ($new_ca_data as &$data) {
			$data['cost'] = round($data['cost'], 2);
		}
		$this->post_data_report('campaign', $data_table);
		return $new_ca_data;
	}

	protected function get_cached_ca_data($start_date, $end_date)
	{
		// we can force by returning empty array
		// if cached data is not set at a given date, that date will be processed
		if (1 || $this->do_force_refresh) {
			return array();
		}
		else {
			return db::select("
				select data_date, round(sum(cost), 2) cost, sum(convs) convs, sum(mpc_convs) mpc_convs
				from {$this->market}_objects.campaign_data_{$this->eac_id}
				where
					data_date between :start_date and :end_date &&
					account_id = :aid
				group by data_date
			", array(
				'start_date' => $this->report_start_date,
				'end_date' => $this->report_end_date,
				'aid' => $this->ac_id
			), 'ASSOC', 'data_date');
		}
	}

	protected function pre_data_report(&$cached_ca_data, &$new_ca_data)
	{
		// loop over cached dates, see if diff from new ca data
		$refresh_dates = array();
		foreach ($new_ca_data as $date => &$data) {
			if (
				!isset($cached_ca_data[$date]) ||
				// todo: don't need this once we've got all the data
				($date >= '2013-11-26' && $date <= '2013-12-02') ||
				$cached_ca_data[$date]['cost'] != $new_ca_data[$date]['cost'] ||
				$cached_ca_data[$date]['convs'] != $new_ca_data[$date]['convs'] ||
				$cached_ca_data[$date]['mpc_convs'] != $new_ca_data[$date]['mpc_convs']
			) {
				$refresh_dates[$date] = 1;
			}
		}

		// call all_data hook
		all_data::pre_import("{$this->market}_objects.all_data_{$this->eac_id}", $refresh_dates);

		return $refresh_dates;
	}

	protected function get_all_data_report_dates($refresh_dates, $start_date, $end_date)
	{
		// we might not need to run report over entire range if nothing changes for dates at beginning and end
		$dates_array = array_keys($refresh_dates);
		sort($dates_array);
		$start_date = $dates_array[0];
		$end_date = $dates_array[count($dates_array) - 1];
		
		// for all data, only get at most most recent x days
		$all_data_cutoff = date(util::DATE, \epro\NOW - (ppc_lib::MAX_ALL_DATA_DAYS * 86400));
		if ($start_date < $all_data_cutoff) {
			$start_date = $all_data_cutoff;
			if ($start_date > $end_date) {
				return array(false, false);
			}
		}
		return array($start_date, $end_date);
	}

	protected function post_data_report($rep_type, $data_table)
	{
		if (
			// always run delete for campaign data
			$rep_type == 'campaign' ||
			// if not rep type campaign, check test key,
			// delete unless test type is clobber
			// as with clobber we will have already cleared out all data
			all_data::$test_key != 'auto_inc_with_clobber'
		) {
			util::update_job_details($this->job, 'Deleting old data');
			db::delete(
				$data_table,
				"
					data_date between :start_date and :end_date &&
					account_id = :aid &&
					job_id <> :job_id
				",
				array(
					'start_date' => $this->report_start_date,
					'end_date' => $this->report_end_date,
					'aid' => $this->ac_id,
					'job_id' => $this->job->id
				)
			);
		}
	}
	
	// default for campaign report is to run account reports
	// overridden by google
	public function run_campaign_report($campaigns, $start_date, $end_date, $data_id)
	{
		// get parent accounts of campaigns
		$accounts = db::select("
			select distinct account
			from {$this->market}_info.campaigns_{$data_id}
			where campaign in ('".implode("','", $campaigns)."')
		");
		
		// init report name and the file
		$report_name = $this->market.'_ca_'.$campaigns[0].'_'.$start_date.'.csv';
		$campaign_report_path = $this->get_report_path().$report_name;
		exec('touch '.$campaign_report_path);	
		
		// loop over accounts, run report for each
		$report_flags = REPORT_NO_HEADERS | REPORT_DELETE_ZIP;
		foreach ($accounts as $ac_id)
		{
			// run the report
			$this->set_account($ac_id);
			$report_path = $this->run_account_report($start_date, $end_date, $report_flags);
			
			// check for errors
			if ($report_path === false) continue;
			
			// append it to the company report
			exec('cat '.$report_path.' >> '.$campaign_report_path);
		}
		// make sure there is a newline at the end
		exec('echo >> '.$campaign_report_path);
		
		return $campaign_report_path;
	}
	
	protected function post_process_report($dates)
	{
	}
	
	public function update_all_client_data($start_date, $opts = array())
	{
		util::set_opt_defaults($opts, array(
			'verbose' => false,
			'end_date' => $start_date,
			'do_run_cdl' => false,
			'do_update_status' => false
		));
		
		util::set_active_market_clients($clients, $this->market);
		$i = 0;
		$count = count($clients);
		foreach ($clients as $cl_id)
		{
			if (($i++ % 10) == 0)
			{
				if ($opts['verbose'])
				{
					echo "client $i of $count\n";
				}
				if ($opts['do_update_status'])
				{
					$this->update_report_status($this->market, $start_date, 'Updating PPC Client '.($i + 1).' of '.$count);
				}
			}
			util::refresh_client_data($cl_id, $this->market, $start_date, $opts['end_date'], util::REFRESH_CLIENT_DATA_DO_RESET_DATA);
			if ($opts['do_run_cdl'])
			{
				ppc_lib::calculate_cdl_vals($cl_id);
			}
		}
	}
	
	public function refresh_campaigns($cl_id)
	{
		$cas = $this->get_campaigns();
		$mod_date = date(util::DATE);
		
		foreach ($cas as $ca)
		{
			db::exec("
				insert into {$this->market}_campaigns
					(cl_id, ac_id, id, mod_date, text, status)
				values
					(
						'$cl_id',
						'$this->ac_id',
						'".$ca['id']."',
						'$mod_date',
						'".addslashes($ca['text'])."',
						'".$ca['status']."'
					)
				on duplicate key update
					cl_id='$cl_id',
					ac_id='$this->ac_id',
					mod_date='$mod_date',
					text='".addslashes($ca['text'])."',
					status='".$ca['status']."'
			");
		}
	}
	
	public function refresh_ad_groups($cl_id, $ca_id)
	{
		$ags = $this->get_ad_groups($ca_id);
		$mod_date = date(util::DATE);
		
		foreach ($ags as $ag)
		{
			$r = db::exec("
				insert into {$this->market}_ad_groups
					(cl_id, ac_id, ca_id, id, mod_date, text, max_cpc, status)
				values
					(
						'$cl_id',
						'$this->ac_id',
						'$ca_id',
						'".$ag['id']."',
						'$mod_date',
						'".addslashes($ag['text'])."',
						'".$ag['max_cpc']."',
						'".$ag['status']."'
					)
				on duplicate key update
					cl_id='$cl_id',
					ac_id='$this->ac_id',
					ca_id='$ca_id',
					mod_date='$mod_date',
					text='".addslashes($ag['text'])."',
					max_cpc='".$ag['max_cpc']."',
					status='".$ag['status']."'
			");
		}
	}
	
	public function refresh_ads($cl_id, $ca_id, $ag_id)
	{
		$ads = $this->get_ads($ag_id);
		$mod_date = date(util::DATE);
		
		foreach ($ads as $ad)
		{
			$r = db::exec("
				insert into {$this->market}_ads
					(cl_id, ac_id, ca_id, ag_id, id, mod_date, text, desc_1, desc_2, disp_url, dest_url, status)
				values
					(
						'$cl_id',
						'$this->ac_id',
						'$ca_id',
						'$ag_id',
						'".$ad['id']."',
						'$mod_date',
						'".addslashes($ad['text'])."',
						'".addslashes($ad['description_1'])."',
						'".addslashes($ad['description_2'])."',
						'".$ad['display_url']."',
						'".$ad['destination_url']."',
						'".$ad['status']."'
					)
				on duplicate key update
					cl_id='$cl_id',
					ac_id='$this->ac_id',
					ca_id='$ca_id',
					mod_date='$mod_date',
					text='".addslashes($ad['text'])."',
					desc_1='".addslashes($ad['description_1'])."',
					desc_2='".addslashes($ad['description_2'])."',
					disp_url='".$ad['display_url']."',
					dest_url='".$ad['destination_url']."',
					status='".$ad['status']."'
			");
		}
	}
	
	public function run_structure_report($job, $cl_id = null, $entity_type = null, $entity_ids = null)
	{
		require_once(\epro\COMMON_PATH.'data_cache.php');
		
		//data_cache::dbg();
		$this->new_cas = array();
		$this->new_ags = array();
		
		$this->cl_id = $cl_id;
		$this->data_id = db::select_one("select data_id from clients where id = '$cl_id'");
		
		$data_source_campaigns = db::select("
			select distinct campaign
			from eppctwo.data_sources
			where client = '{$this->cl_id}' && market = '{$this->market}' && account = '{$this->ac_id}' && campaign <> ''
		");
		
		if ($data_source_campaigns)
		{
			$cas = $data_source_campaigns;
		}
		else
		{
			data_cache::update_campaigns($cl_id, $this->market, $this->ac_id, array(
				'force_update' => true,
				'callback_func' => 'run_structure_report_data_cache_callback',
				'callback_obj' => &$this
			));
			
			$cas = db::select("
				select campaign
				from {$this->market}_info.campaigns_{$this->data_id}
				where client = '$cl_id'
			");
		}
		
		$total_ags = 0;
		$processed_ags = 0;
		for ($i = 0, $num_cas = count($cas); $i < $num_cas; ++$i)
		{
			if ($job) $job->update_details('Campaign '.($i + 1).' / '.$num_cas);
			$ca_id = $cas[$i];
			if ($entity_type == 'Campaign' && !array_key_exists($ca_id, $entity_ids))
			{
				continue;
			}
			
			$data_source_ad_groups = db::select("
				select distinct ad_group
				from eppctwo.data_sources
				where client = '{$this->cl_id}' && market = '{$this->market}' && account = '{$this->ac_id}' && campaign = '$ca_id' && ad_group <> ''
			");
			
			if ($data_source_ad_groups)
			{
				$ags = $data_source_ad_groups;
			}
			else
			{
				data_cache::update_ad_groups($cl_id, $this->market, $ca_id, array(
					'force_update' => true,
					'callback_func' => 'run_structure_report_data_cache_callback',
					'callback_obj' => &$this
				));
				
				$ags = db::select("
					select ad_group
					from {$this->market}_info.ad_groups_{$this->data_id}
					where campaign = '$ca_id'
				");
			}
			$num_ags = count($ags);
			$total_ags += $num_ags;
			$cache_ad_and_kw_opts = array(
				'force_update' => true,
				'callback_func' => 'run_structure_report_data_cache_callback',
				'callback_obj' => &$this,
				'callback_data' => array('campaign' => $ca_id)
			);
			for ($j = 0; $j < $num_ags; ++$j)
			{
				++$processed_ags;
				if ($job) $job->update_details('Campaign '.($i + 1).' / '.$num_cas.', Ad Group '.$processed_ags.' / '.$total_ags);
				$ag_id = $ags[$j];
				if ($entity_type == 'Ad Group' && !array_key_exists($ag_id, $entity_ids))
				{
					continue;
				}
				#if ($ag_id != '5814713900' && $ag_id != '5814727400' && $ag_id != '5814730400') continue;
				data_cache::update_ads($cl_id, $this->market, $ag_id, $cache_ad_and_kw_opts);
				data_cache::update_keywords($cl_id, $this->market, $ag_id, $cache_ad_and_kw_opts);
			}
		}
	}
	
	public function is_not_wpropath_entity($entity_type, $entity_id)
	{
		$is_wpropathed = db::select_one("
			select is_wpropathed
			from {$this->market}_info.{$entity_type}s_{$this->data_id}
			where {$entity_type} = '{$entity_id}'"
		);
		return (empty($is_wpropathed));
	}
	
	public function run_structure_report_data_cache_callback($event, $data_type, $old_data, $new_data, $callback_data = null)
	{
/*
		echo "run_structure_report_data_cache_callback: $event, $data_type\n";
		var_dump($new_data);
		var_dump($old_data);
		var_dump($callback_data);
*/
		if ($data_type == 'Campaign')
		{
			if ($event == 'insert' || $this->is_not_wpropath_entity('campaign', $new_data->id))
			{
				$this->new_cas[$new_data->id] = 1;
				db::insert("eppctwo.track_sync_entities", array(
					'client' => $this->cl_id,
					'market' => $this->market,
					'entity_type' => $data_type,
					'entity_id0' => $new_data->id,
					'sync_type' => 'New'
				));
			}
		}
		else if ($data_type == 'Ad Group')
		{
			if (!array_key_exists($new_data->campaign_id, $this->new_cas))
			{
				if ($event == 'insert' || $this->is_not_wpropath_entity('ad_group', $new_data->id))
				{
					$this->new_ags[$new_data->id] = 1;
					db::insert("eppctwo.track_sync_entities", array(
						'client' => $this->cl_id,
						'market' => $this->market,
						'entity_type' => $data_type,
						'entity_id0' => $new_data->id,
						'sync_type' => 'New'
					));
				}
			}
		}
		// ad or keyword
		else
		{
			if (!array_key_exists($callback_data['campaign'], $this->new_cas) && !array_key_exists($new_data->ad_group_id, $this->new_ags))
			{
				$sync_type = null;
				if ($event == 'insert')
				{
					$sync_type = 'New';
				}
				else if ($event == 'update' && !util::is_tracker_url($new_data->dest_url) && $old_data['dest_url'] != $new_data->dest_url)
				{
					$sync_type = 'Update';
				}
				
				if ($sync_type)
				{
					db::insert("eppctwo.track_sync_entities", array(
						'client' => $this->cl_id,
						'market' => $this->market,
						'entity_type' => $data_type,
						'entity_id0' => $new_data->ad_group_id,
						'entity_id1' => $new_data->id,
						'sync_type' => $sync_type
					));
				}
			}
		}
	}
	
	protected function obj_to_xml(&$obj, $obj_type, $ordered_keys = null)
	{
		$xml = '';
		$map_func = 'set_'.$obj_type.'_conv_map';
		api_translate::$map_func($map);
		
		if ($ordered_keys)
		{
			$data = array();
			foreach ($ordered_keys as $ordered_key)
			{
				foreach ($obj as $obj_key => $v)
				{
					$market_key = @$map[$obj_key][$this->market];
					if ($market_key == $ordered_key)
					{
						$data[$obj_key] = $v;
						break;
					}
				}
			}
		}
		else
		{
			$data = $obj;
		}
		
		foreach ($data as $obj_key => $v)
		{
			if (
				(!is_null($v))
				&&
				(
					(array_key_exists($obj_key, $map) && @array_key_exists($this->market, $map[$obj_key]))
					||
					(strpos($obj_key, $this->market.'_') === 0)
				)
			)
			{
				$market_key = (strpos($obj_key, $this->market.'_') === 0) ? substr($obj_key, 2) : $map[$obj_key][$this->market];
				if (empty($market_key)) continue;
				
				$data_func = 'marketize_'.$this->market.'_'.$obj_type.'_'.$obj_key;
				if (method_exists('api_translate', $data_func)) $v = api_translate::$data_func($v);
				
				if ($v == '')
				{
					$xml .= '<'.$market_key.' xsi:nil="true" />';
				}
				else
				{
					$xml .= "<$market_key>".strings::xml_encode($v)."</$market_key>";
				}
			}
		}
		return $xml;
	}
	
	protected function obj_to_array(&$obj, $obj_type)
	{
		$a = array();
		$map_func = 'set_'.$obj_type.'_conv_map';
		api_translate::$map_func($map);
		
		foreach ($obj as $obj_key => $v)
		{
			if (
				(!is_null($v))
				&&
				(
					(array_key_exists($obj_key, $map) && is_array($map[$obj_key]) && array_key_exists($this->market, $map[$obj_key]))
					||
					(strpos($obj_key, $this->market.'_') === 0)
				)
			)
			{
				$market_key = (strpos($obj_key, $this->market.'_') === 0) ? substr($obj_key, 2) : $map[$obj_key][$this->market];
				if (empty($market_key)) continue;
				
				$data_func = 'marketize_'.$this->market.'_'.$obj_type.'_'.$obj_key;
				if (method_exists('api_translate', $data_func)) $v = api_translate::$data_func($v);
				
				$a[$market_key] = $v;
			}
		}
		return $a;
	}
	
	public function update_report_status($market, $date, $status)
	{
		db::exec("
			update eppctwo.market_data_status
			set status = '$status', t = '".date('H:i:s')."'
			where market = '$market' && d = '$date'
		");
	}
	
	public function run_ad_structure_report($aid, $opts = array(), $flags = null)
	{
		util::set_opt_defaults($opts, array(
			'start_date' => date('Y-m-d', time() - (86400 * 31)),
			'end_date' => date('Y-m-d', time() - 86400)
		));
		$this->set_eac_id($aid);
		$request = $this->build_report_request('Account', $opts['start_date'], $opts['end_date'], 'Ad_Structure');
		if (class_exists('cli') && cli::$args['p']) {
			$report = $this->get_report_path_from_cli(cli::$args['p']);
		}
		else {
			$report = $this->run_report($request, $flags);
			// error should be set by run report
			if ($report === false) {
				return false;
			}
		}
		// don't need to standardize
		// just process, ie update info tables
		return $this->process_pseudo_ad_structure_report($report, $opts, $flags);
	}
	
	protected function process_pseudo_ad_structure_report($report_path, $opts, $flags)
	{
		$this->set_client_account_data_sources();
		
		$h = fopen($report_path, 'rb');
		if ($h === false)
		{
			$this->error = 'Could not open ad report ('.$report.') for processing';
			return false;
		}
		
		// not a real structure report :(
		// ads can show up multiple times
		// track ads so we don't repeatedly try to insert/update the same row
		$ads = array();
		list($read_len, $read_delimiter) = $this->get_ad_structure_parse_file_vars();
		
		$status_func = 'standardize_'.$this->market.'_ad_status';
		$today = date(util::DATE);
		for ($i = 0; ($data = fgetcsv($h, $read_len, $read_delimiter)) !== FALSE; ++$i) {
			list($imps, $ca_id, $ag_id, $ad_id, $headline, $desc_1, $desc_2, $disp_url, $dest_url, $status) = $this->get_ad_structure_data_vars($data, $this->eac_id);
			
			if (is_numeric($ag_id) && is_numeric($ad_id)) {
				if ($this->does_data_belong_to_client($ca_id, $ag_id)) {
					if (!array_key_exists($ag_id, $ads) || !array_key_exists($ad_id, $ads[$ag_id])) {
						// mark as processed
						$ads[$ag_id][$ad_id] = 1;
						
						db::insert_update("{$this->market}_objects.ad_{$this->eac_id}", array('ad_group', 'ad'), array(
							'account_id' => $this->ac_id,
							'campaign_id' => $ca_id,
							'ad_group_id' => $ag_id,
							'id' => $ad_id,
							'mod_date' => $today,
							'status' => api_translate::$status_func($status),
							'text' => $headline,
							'desc_1' => $desc_1,
							'desc_2' => $desc_2,
							'disp_url' => $disp_url,
							'dest_url' => $dest_url
						));
					}
				}
			}
		}
		fclose($h);
		return true;
	}

	public function set_eac_id($eac_id)
	{
		if ($eac_id) {
			$this->eac_id = $eac_id;
			$this->data_table = "{$this->market}_objects.all_data_{$this->eac_id}";
			ppc_lib::create_market_object_tables($this->market, $eac_id);
		}
	}

	protected function file_get_line_count($h)
	{
		for ($c = 0; !feof($h); ++$c) {
			fgets($h);
		}
		rewind($h);
		return $c;
	}

	public static function device_map($market, $device)
	{
		$switch_device = strtolower($device);
		switch ($market) {
			case ('g'):
				switch ($switch_device) {
					case ('computers'):                         return 'Computer';
					case ('mobile devices with full browsers'): return 'Smartphone';
					case ('tablets with full browsers'):        return 'Tablet';
					case ('other'):                             return 'Other';
					default: return self::device_map_unknown($market, $device);
				}

			case ('m'):
				switch ($switch_device) {
					case ('computer'):        return 'Computer';
					case ('smartphone'):      return 'Smartphone';
					case ('nonsmartphone'):   return 'Cellphone';
					case ('non-smart phone'): return 'Cellphone';
					case ('tablet'):          return 'Tablet';
					default: return self::device_map_unknown($market, $device);	
				}
		}
	}

	private static $unknown_devices = false;

	private static function device_map_unknown($market, $device)
	{
		if (!is_array(self::$unknown_devices)) {
			self::$unknown_devices = array();
		}
		if (empty(self::$unknown_devices[$device])) {
			self::$unknown_devices[$device] = 1;
			util::mail('alerts@wpromote.com', 'chimdi@wpromote.com', 'Unknown Device', "Market = {$market}\nDevice = {$device}");
		}
		return $device;
	}
}

?>