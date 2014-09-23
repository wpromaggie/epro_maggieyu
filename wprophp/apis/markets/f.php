<?php

/*
 * facebook!
 */

define('F_API_VERSION', '1');

class f_api extends base_market
{
	protected $data_id;
	
	// set the min and max dates when importing data
	// so we know what date range to refresh client data for
	protected $min_import_date, $max_import_date;
	
	const REPORT_COL_INDEX_AD_TEXT = 3;
	
	public function __construct($company, $ac_id = '')
	{
		parent::__construct('f', $company);
		$this->set_account($ac_id);
		$this->set_version(F_API_VERSION);
		$this->units = $this->operations = 0;
	}
	
	public function set_account($ac_id)
	{
		parent::set_account($ac_id);
		$this->data_id = db::select_one("select data_id from eppctwo.clients where id = '$ac_id'");
	}
	
	protected function set_tokens($api_info)
	{
		return true;
	}
	
	public function run_company_report($date)
	{
		return true;
	}
	
	/*
	 * called by worker_tracker_refresh_account to set track_sync_entities
	 */
	public function run_structure_report($job = null, $cl_id = null, $entity_type = null, $entity_ids = null)
	{
		// at this point in facebook's life we have already uploaded the
		// new data, so the best we can do is just set all ad groups
		// as sync entities.. yay!
		
		// get f ad groups for this client
		$data_id = db::select_one("select data_id from eppctwo.clients where id = $cl_id");
		$ags = db::select("
			select campaign, ad_group
			from f_info.ad_groups_{$data_id}
			where client = '$cl_id'
		");
		
		// add them to track sync entities db
		for ($i = 0; list($ca_id, $ag_id) = $ags[$i]; ++$i)
		{
			if (
				($entity_type == 'Account') ||
				($entity_type == 'Campaign' && array_key_exists($ca_id, $entity_ids)) ||
				($entity_type == 'Ad Group' && array_key_exists($ag_id, $entity_ids))
			) {
				db::insert("eppctwo.track_sync_entities", array(
					'client' => $cl_id,
					'market' => 'f',
					'entity_type' => 'Ad Group',
					'entity_id0' => $ag_id,
					'sync_type' => 'New'
				));
			}
		}
		
		// make them all non-wpropathed
		db::update("f_info.ad_groups_{$data_id}", array('is_wpropathed' => 0), "client = '$cl_id'");
	}
	
	// we want to refresh last 31 days for wpropath stuff
	public function update_all_client_data($start_date, $opts = array())
	{
		$end_date = $start_date;
		$start_date = date(util::DATE, strtotime("$end_date -31 days"));
		$opts['end_date'] = $end_date;
		return parent::update_all_client_data($start_date, $opts);
	}
	
	public function get_campaigns()
	{
	}
	
	public function get_ad_groups()
	{
	}
	
	public function get_ads()
	{
	}
	
	public function get_keywords()
	{
	}
	
	protected function get_header()
	{
		return '';
	}
	
	protected function get_endpoint()
	{
		return '';
	}
	
	protected function get_soap_action()
	{
		return '';
	}
	
	protected function is_error(&$doc)
	{
		return false;
	}
	
	public function get_import_dates()
	{
		return (($this->min_import_date) ? array($this->min_import_date, $this->max_import_date) : false);
	}
	
	public function import_report($report_path, $flags = 0)
	{
		require_once(\epro\WPROPHP_PATH.'file_iterator.php');
		
		$standardized_path = $this->standardize_report($report_path);
		if ($standardized_path === false)
		{
			return false;
		}
		$this->process_report($standardized_path);
		if ($this->ac_id)
		{
			util::refresh_client_data($this->ac_id, 'f', $this->min_import_date, $this->max_import_date);
		}
		return true;
	}
	
	private function standardize_report_bad_headers($headers)
	{
		$this->set_error('Unrecognized upload headers, please tell your friendly neighborhood programmer ('.implode(',', $headers).')');
		return false;
	}
	
	public function standardize_report($report_path, $flags = 0, $separator = ',')
	{
		$cols = api_translate::define_report_columns($this->market);
		
		$h = fopen($report_path, 'rb');
		if ($h === false)
		{
			$this->set_error('Could not read upload data');
			return false;
		}
		
		$headers = fgetcsv($h, 0, $separator);
		$expected_headers = array(
			'Date', 'Campaign', 'Campaign ID', 'Ad Name', 'Ad ID', 'Impressions', 'Social Impressions', 'Social %', 'Clicks', 'Social Clicks', 'CTR',
			'Social CTR', 'CPC', 'CPM', 'Spent', 'Reach', 'Frequency', 'Social Reach', 'Actions', 'Page Likes', 'App Installs', 'Event Responses', 'Unique Clicks', 'Unique CTR'
		);
		if (count($headers) != count($expected_headers))
		{
			return $this->standardize_report_bad_headers($headers);
		}
		foreach ($headers as $i => $header)
		{
			if ($header != $expected_headers[$i])
			{
				return $this->standardize_report_bad_headers($headers);
			}
		}
		$data = array();
		$ad_ids = array();
		$today = date(util::DATE);
		$this->min_import_date = '9999-99-99';
		$this->max_import_date = '0000-00-00';
		for ($i = 0; (($d = fgetcsv($h, 0, $separator)) !== false); ++$i)
		{
			// campaign id is 3rd column [2], check numeric to see if we are looking at valid row
			if (is_numeric($d[2]))
			{
				// add "account id" to end
				$d[] = $this->ac_id;
				
				$date = api_translate::standardize_f_report_date($d[api_translate::$report_cols['Date']['f']]);
				if ($date > $this->max_import_date) $this->max_import_date = $date;
				if ($date < $this->min_import_date) $this->min_import_date = $date;
				
				// process_account_report does not expect any ad text, but we want that so let's put it in ourselves
				$ad_id = $d[api_translate::$report_cols['Ad ID']['f']];
				if ($this->data_id != -1 && !array_key_exists($ad_id, $ad_ids))
				{
					// because it is not expected, column is indexed by class constant instead of generic report_cols array
					$ad_text = $d[self::REPORT_COL_INDEX_AD_TEXT];
					$ad_ids[$ad_id] = 1;
					db::insert("f_info.ads_{$this->data_id}", array(
						'client' => $this->ac_id,
						'account' => $this->ac_id,
						'campaign' => $d[api_translate::$report_cols['Campaign ID']['f']],
						'ad_group' => $d[api_translate::$report_cols['Ad Group ID']['f']],
						'ad' => $ad_id,
						'mod_date' => $today,
						'text' => $ad_text,
						'status' => 'On'
					));
				}
				
				$data[] = api_translate::standardize_report_data($this->market, $d, $cols);
			}
		}
		fclose($h);
		$headers = ($flags & REPORT_NO_HEADERS) ? '' : api_translate::get_report_columns_string();
		
		$file_path = $this->get_report_path().$this->get_report_name('e2').'.dat';
		file_put_contents($file_path, $headers."\n".implode("\n", $data));
		
		return $file_path;
	}
}

?>