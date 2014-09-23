<?php

define('M_API_VERSION', 'V8');
define('M_API_LOCATION', 'https://adcenterapi.microsoft.com/');

/*
 * note on msn keywords:
 * msn will attribute data to keywords which do not actually exist
 * if a more restrictive match type would match a keyword that does exist
 * for example, if we have the broad match keyword 'red shoe', and a search
 * for 'red shoe' triggers our add, msn will attribute this to 'red shoe' extact match,
 * even if 'red shoe' exact match does not exist. it it therefore impossible to know
 * from an msn report what keywords exist and which do not. when using msn data, we
 * attempt to show data only for keywords that actually do exist
 */

class m_api extends base_market
{
	private $units, $operations;
	
	private $client;
	
	
	public static $match_types = array(
		'B' => 'BroadMatchBid',
		'P' => 'PhraseMatchBid',
		'E' => 'ExactMatchBid',
		'C' => 'ContentMatchBid'
	);

	public function __construct($company, $ac_id = '')
	{
		parent::__construct('m', $company);
		$this->set_account($ac_id);
		$this->set_version(M_API_VERSION);
		$this->units = $this->operations = 0;
	}

	public function set_account($ac_id = '')
	{
		$this->ac_id = $ac_id;
		if (!empty($ac_id))
		{
			$d = db::select_row("select user, pass, j_auth, last_updated from m_accounts where id='$ac_id'");
			
			// wpromote's accounts do not have a user/pass, they are kept with the api account
			// only set api user from above query if we have the info
			if ($d && !empty($d[0]))
			{
				list($this->api_user, $this->api_pass,$this->j_auth,$this->auth_last_updated) = $d;
			}
		}
	}

	public function set_tokens(&$api_info)
	{
		$this->set_key('access_key', $api_info['access_key']);
	}

	public function get_units()
	{
		return ($this->units);
	}

	public function get_operations()
	{
		return ($this->operations);
	}

	public function get_error()
	{
		if ($this->client)
		{
			$e = $this->client->GetErrorMsg();
			if ($e)
			{
				return ((is_string($e)) ? $e : print_r($e, true));
			}
			else
			{
				return 'Error getting error';
			}
		}
		else
		{
			return parent::get_error();
		}
	}
	
	protected function get_header()
	{
		// zOMG msn uses different header formats for different services
		if ($this->service == 'CustomerManagement')
		{
			$headers = '
				<ApiUserAuthHeader xmlns="http://adcenter.microsoft.com/syncapis">
					<UserName>'.$this->api_user.'</UserName>
					<Password>'.$this->api_pass.'</Password>
					<UserAccessKey>'.$this->get_key('access_key').'</UserAccessKey>
				</ApiUserAuthHeader>
			';
		}
		else
		{
			$headers = '
				<ApplicationToken></ApplicationToken>
				<DeveloperToken>'.$this->get_key('access_key').'</DeveloperToken>
				<UserName>'.$this->api_user.'</UserName>
				<Password>'.$this->api_pass.'</Password>
				<CustomerAccountId>'.$this->ac_id.'</CustomerAccountId>
			';
		}
		return ($headers);
	}

	protected function get_endpoint()
	{
		if ($this->service == 'CustomerManagement')
		{
			#$endpoint = M_API_LOCATION.M_API_VERSION.'/'.$this->service.'/'.$this->service.'.asmx';
			$endpoint = M_API_LOCATION.'Api/Advertiser/'.strtoupper(M_API_VERSION).'/'.$this->service.'/'.$this->service.'.asmx?wsdl';
		}
		else
		{
			$endpoint = M_API_LOCATION.'Api/Advertiser/'.strtoupper(M_API_VERSION).'/'.$this->service.'/'.$this->service.'Service.svc';
		}
		return $endpoint;
	}
	
	protected function get_envolope_attrs()
	{
		return 'xmlns="https://adcenter.microsoft.com/'.strtolower(substr(M_API_VERSION, 0, 2)).'" xmlns:array="http://schemas.microsoft.com/2003/10/Serialization/Arrays" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"';
	}

	protected function get_soap_action()
	{
		if ($this->service == 'CustomerManagement')
		{
			return ('http://adcenter.microsoft.com/syncapis/'.$this->action);
		}
		else
		{
			return $this->action;
		}
	}
	
	protected function is_error(&$doc)
	{
		if (is_object($doc))
		{
			$body = $doc->get('Body');
			if (@array_key_exists('Fault', $body))
			{
				$message = $doc->get('Message');
				if ($message)
				{
					$this->error = $message;
				}
				else
				{
					$this->error = print_r($body['Fault'], true);
				}
				return true;
			}
		}
		return false;
	}
	
	public function update_ads($ads, $ag_id = '')
	{
		$a = array();
		foreach ($ads as &$ad)
		{
			$ad->id = (float) $ad->id;
			$a[] = new SoapVar($this->obj_to_array($ad, 'ad'), SOAP_ENC_OBJECT, 'TextAd', 'https://adcenter.microsoft.com/'.strtolower(M_API_VERSION));
		}
		
		// client returns empty array on success
		$this->init_client();
		return ($this->client->UpdateAds($ag_id, $a) !== false);
	}
	
	public function update_ad($ad, $ag_id = '')
	{
		return $this->update_ads(array($ad), $ag_id);
	}

	public function add_ads(&$ads, $ag_id = '')
	{
		$a = array();
		foreach ($ads as &$ad)
		{
			$ad->id = null;
			$a[] = new SoapVar($this->obj_to_array($ad, 'ad'), SOAP_ENC_OBJECT, 'TextAd', 'https://adcenter.microsoft.com/'.strtolower(M_API_VERSION));
		}
		
		$this->init_client();
		$r = $this->client->AddAds($ag_id, $a);
		if ($r === false)
		{
			return false;
		}
		// success, set ad ids
		else
		{
			for ($i = 0, $ci = count($ads); $i < $ci; ++$i)
			{
				$ads[$i]->id = $r[$i];
			}
			return true;
		}
	}
	
	public function add_ad(&$ad, $ag_id = '')
	{
		// create local var, 1st param is passed by ref
		$tmp = array(&$ad);
		return $this->add_ads($tmp, $ag_id);
	}

	public function get_accounts($mcc_id)
	{
		$this->init_client();
		return $this->client->GetAccountsInfo($mcc_id);
	}
	
	public function get_account($ac_id = '')
	{
		$this->init_client();
		return $this->client->GetAccount(($ac_id) ? $ac_id : $this->ac_id);
	}
	
	public function get_campaigns()
	{
		$this->init_client();
		$r = $this->client->GetCampaignsByAccountId($this->ac_id);
		if ($r === false) return false;
		
		$cas = array();
		foreach ($r as &$d)
		{
			// 28.09.2011: why was this here? do we still need it?
			if (@empty($d['Id'])) continue;
			$cas[] = api_factory::new_campaign($this->ac_id, $d['Id'], $d['Name'], $d['DailyBudget'], $d['Status']);
		}
		return $cas;
	}
	
	public function get_ad_groups($ca_id)
	{
		$this->init_client();
		$r = $this->client->GetAdGroupsByCampaignId($ca_id);
		if ($r === false) return false;
		
		$ags = array();
		foreach ($r as &$d)
		{
			if (@empty($d['Id'])) continue;
			$ags[] = $this->standardize_ad_group($d, $ca_id);
		}
		return $ags;
	}
	
	public function get_ad_group($ca_id, $ag_id)
	{
		$this->init_client();
		$r = $this->client->GetAdGroupsByIds($ca_id, array($ag_id));
		return (($r === false) ? false : $this->standardize_ad_group($r[0]));
	}
	
	private function standardize_ad_group($d, $ca_id = '')
	{
		$bid = 0;
		foreach (self::$match_types as $match_type)
		{
			if (!empty($d[$match_type]['Amount']))
			{
				$bid = $d[$match_type]['Amount'];
				break;
			}
		}
		$d['Status'] = api_translate::standardize_m_ad_group_status($d['Status']);
		return api_factory::new_ad_group($ca_id, $d['Id'], $d['Name'], $bid, $d['ContentMatchBid']['Amount'], $d['Status']);
	}
	
	public function get_ads($ag_id)
	{
		$this->init_client();
		$r = $this->client->GetAdsByAdGroupId($ag_id);
		if ($r === false) return false;
		
		$ads = array();
		foreach ($r as &$d)
		{
			$d['Status'] = api_translate::standardize_m_ad_status($d['Status']);
			$ads[] = api_factory::new_ad($ag_id, $d['Id'], $d['Title'], $d['Text'], '', $d['DisplayUrl'], $d['DestinationUrl'], $d['Status']);
		}
		return $ads;
	}
	
	public function get_keywords($ag_id)
	{
		$this->init_client();
		$r = $this->client->GetKeywordsByAdGroupId($ag_id);
		if ($r === false) return false;
		
		$match_types = $this->get_match_types();
		
		$kws = array();
		foreach ($r as &$d) {
			if (empty($d['Id']) || empty($d['Text'])) continue;
			$d['Status'] = api_translate::standardize_m_keyword_status($d['Status']);
			// whichever match type has the amount key is the active match type
			// it may be empty, which means use the ad group bid
			$bid = false;
			foreach ($match_types as $match_type) {
				if (is_array($d[$match_type.'MatchBid'])) {
					$bid = $d[$match_type.'MatchBid']['Amount'];
					break;
				}
			}
			// if there are no bids for this keyword, guess broad match
			// 2013-02-11, kw: still needed?
			if ($bid === false) {
				$bid = null;
				$match_type = 'Broad';
			}
			$kws[] = api_factory::new_keyword($ag_id, $match_type[0].$d['Id'], $d['Text'], $match_type, $bid, '', $d['Status']);
		}
		return $kws;
	}
	
	private function get_match_types()
	{
		return array('Broad', 'Phrase', 'Exact', 'Content');
	}
	
	public function update_keywords(&$kws, $ag_id)
	{
		// group our normal keywords back into stupid msn keywords
		$bid_updates = array();
		$pause_ids = array();
		$resume_ids = array();
		foreach ($kws as &$kw) {
			$match_type = $kw->id[0];
			$m_id = (float) substr($kw->id, 1);
			if (isset($kw->status)) {
				if ($kw->status == 'On') {
					$resume_ids[] = $m_id;
				}
				else {
					$pause_ids[] = $m_id;
				}
			}
			// set max_cpc to 0 (we then set to null here in object to send to m) to signify no bid (ie, use ad group bid)
			if (isset($kw->max_cpc)) {
				$bid_updates[] = array(
					'Id' => $m_id,
					self::$match_types[$match_type] => array('Amount' => empty($kw->max_cpc) ? null : $kw->max_cpc)
				);
			}
		}
		
		if ($bid_updates) {
			$this->init_client();
			if ($this->client->UpdateKeywords($ag_id, $bid_updates) === false) {
				return false;
			}
		}
		
		// then check for deleted keywords and run that
		if ($pause_ids || $resume_ids) {
			$rpause = ($pause_ids) ? $this->pause_keywords($pause_ids, $ag_id) : true;
			$rresume = ($resume_ids) ? $this->resume_keywords($resume_ids, $ag_id) : true;
			return ($rpause && $rresume);
		}
		else {
			return true;
		}
	}
	
	public function delete_keywords($kw_ids, $ag_id)
	{
		$m_kw_ids = array();
		foreach ($kw_ids as $kw_id)
		{
			$kw_id = (float) ((is_numeric(substr($kw_id, 0, 1))) ? $kw_id : substr($kw_id, 1));
			if (!in_array($kw_id, $m_kw_ids))
			{
				$m_kw_ids[] = $kw_id;
			}
		}
		$this->init_client();
		return $this->client->DeleteKeywords($ag_id, $m_kw_ids);
	}
	
	public function pause_keywords($kw_ids, $ag_id)
	{
		$m_kw_ids = array();
		foreach ($kw_ids as $kw_id)
		{
			$kw_id = (float) ((is_numeric(substr($kw_id, 0, 1))) ? $kw_id : substr($kw_id, 1));
			if (!in_array($kw_id, $m_kw_ids))
			{
				$m_kw_ids[] = $kw_id;
			}
		}
		$this->init_client();
		return $this->client->PauseKeywords($ag_id, $m_kw_ids);
	}
	
	public function resume_keywords($kw_ids, $ag_id)
	{
		$m_kw_ids = array();
		foreach ($kw_ids as $kw_id)
		{
			$kw_id = (float) ((is_numeric(substr($kw_id, 0, 1))) ? $kw_id : substr($kw_id, 1));
			if (!in_array($kw_id, $m_kw_ids))
			{
				$m_kw_ids[] = $kw_id;
			}
		}
		$this->init_client();
		return $this->client->ResumeKeywords($ag_id, $m_kw_ids);
	}
	
	private function init_client()
	{
		if (!$this->client)
		{
			require_once(\epro\WPROPHP_PATH.'apis/phpadcenter/src/AdCenterClient.php');
			
			$log_filename = ((util::is_cgi()) ? 'cgi' : 'cli').'-'.((class_exists('user') && user::$id) ? user::$id.'-' : '').'acc.log';
			$headers = array(
				'DeveloperToken' => $this->get_key('access_key'),
				'UserName' => $this->api_user,
				'Password' => $this->api_pass,
				'CustomerAccountId' => $this->ac_id
			);


			//This provision is made for OAuth 2.0 authentication. When used with username and password
			//The access_token takes precidence and the username/password are ignored	
			$this->get_m_refresh_token();
			
			if(isset($this->access_token))
				$headers['AuthenticationToken'] = $this->access_token;

			
			
			$opts = array(
				'log' => true,
				'log_file' => \epro\WPROPHP_PATH.'logs/apis/m/'.$log_filename
			);
			$this->client = new AdCenterClient($headers, $opts);
		}
		else
		{
			if ($this->api_user != $this->prev_user)
			{
				$this->client->SetHeaders(array(
					'UserName' => $this->api_user,
					'Password' => $this->api_pass
				));
			}
			if ($this->ac_id != $this->prev_ac_id)
			{
				$this->client->SetHeaders(array(
					'CustomerAccountId' => $this->ac_id
				));
			}

			//This provision is made for OAuth 2.0 authentication. When used with username and password
			//The access_token takes precidence and the username/password are ignored	
			$this->get_m_refresh_token();
			
			if(isset($this->access_token))
				$headers['AuthenticationToken'] = $this->access_token;

		}
		$this->prev_user = $this->api_user;
		$this->prev_ac_id = $this->ac_id;
	}

	/**
	 * request_access_token(string code)
	 * @param string code
	 */
	public static function request_access_token($code,$user,$pass){
		
		$r = db::select_row("SELECT client_id, client_secret, redirect_uri 
							 FROM eppctwo.m_api_accounts
							 WHERE mcc_user = :mcc_user",
							 array('mcc_user'=>'technology@wpromote.com'),'ASSOC');
							 //array('mcc_user'=>'technology@wpromote.com'),'ASSOC');

		$params = array(
					'client_id'=>$r['client_id'],
					'client_secret'=>$r['client_secret'],
					'code'=>$code,
					'grant_type'=>'authorization_code',
					'redirect_uri'=>$r['redirect_uri'],
		);
		$query = NULL;
		foreach($params as $k=>$v){
			if(strlen($query) == 0)
				$query = "$k=$v";
			else
				$query .= "&$k=$v";
		}

		$uri = sprintf("https://login.live.com/oauth20_token.srf?%s",$query);
		$resp = util::HttpGET($uri);
		
		if(!$resp){
			return array(false,'HTTP RESPONSE ERROR');
		}

		$j = json_decode($resp);
		if(!$j){
			return array(false,'HTTP RESPONSE IS NON-JSON OBJECT');
		}

		if(!empty($j))
			$access_token = $j->access_token;
		else
			return array(false, 'JSON OBJECT DOES NOT CONTAIN `access_token`');

		if(isset($j->error)){
			return array(false,$resp);
		}

		$api = base_market::get_api('m');
		$api->set_api_user($user,$pass,$access_token);	
		$accounts = $api->get_accounts(NULL);


		foreach($accounts as $k => $v){
			$account = $api->get_account($v['Id']);
			$currency = ($account['CurrencyType'] == 'USDollar')? 'USD' : $account['CurrencyType'];
			$r = db::insert_update("eppctwo.m_accounts",array('id'),
							array(
							'id'=>$v['Id'],
							'company'=>1,
							'num'=>$v['Number'],
							'customer_id'=>$account['BillToCustomerId'],
							'text'=>$v['Name'],
							'status'=>'On',
							'currency'=>$currency,
							'user'=>$user,
							'pass'=>$pass,
							'j_auth'=>$resp,
							'last_updated'=>date('Y-m-d H:i:s'),	
			));	
		}

		return array(true,'Account added successfully!');
	}


	protected function get_m_refresh_token(){
		//$this->api_user, $this->api_pass,$this->j_auth,$this->auth_last_updated
		$j = json_decode($this->j_auth);

		//return & do not set access_token if j does not exits as json object
		if(!$j)
			return false;

		//todo get the last-updated value
		if((time() - strtotime($this->auth_last_updated)) < 3600){
			if(isset($j->access_token)){
				$this->access_token = $j->access_token;
				return true;
			}
		}


		//https://login.live.com/oauth20_token.srf?client_id=CLIENT_ID&client_secret=CLIENT_SECRET&grant_type=refresh_token&redirect_uri=REDIRECTURI&refresh_token=REFRESH_TOKEN
		$d = db::select_row("SELECT client_id, client_secret, redirect_uri FROM m_api_accounts WHERE mcc_user = :mcc_user",
							array('mcc_user'=>'technology@wpromote.com'));
	
		list($client_id,$client_secret,$redirect_uri) = $d;			
	
		$query = http_build_query(array(
					'client_id'=>$client_id,
					'client_secret'=>$client_secret,
					'grant_type'=>'refresh_token',
					'redirect_uri'=>$redirect_uri,
					'refresh_token'=>$j->refresh_token,
		));

		//https://login.live.com/oauth20_token.srf?client_id=CLIENT_ID&client_secret=CLIENT_SECRET&grant_type=refresh_token&redirect_uri=REDIRECTURI&refresh_token=REFRESH_TOKEN
	
		$uri = sprintf("https://login.live.com/oauth20_token.srf?%s",$query);
		$resp = util::HttpGET($uri);
		if(!$resp){
			return false;
		}

		//Get error response
		$j = json_decode($resp);
		if(isset($j->error)){
			var_dump($j);
			return false;
		}

		$ru = db::update("eppctwo.m_accounts",
					array('j_auth'=>$resp,'last_updated'=>date('Y-m-d H:i:s')),
					"id = :id",
					array('id'=>$this->ac_id));

		$this->access_token =  $j->access_token;
		return true;
	}

	public function run_account_report($eac_id, $start_date, $end_date = '', $flags = null)
	{
		$this->set_eac_id($eac_id);
		$this->set_client_account_info();

		// first get campaign data
		// is there any report yet with both revenue and average position?????

		// get ca rev
		$request = $this->build_report_request('Account', $start_date, $end_date, 'CampaignRevenue');
		$campaign_rev_report = (class_exists('cli') && !empty(cli::$args['v'])) ? $this->get_report_path_from_cli(cli::$args['v']) : $this->run_report($request, $flags);
		if ($campaign_rev_report === false) {
			return false;
		}
		$ca_rev = $this->process_campaign_revenue_report($campaign_rev_report);
		// get the rest of ca data
		$request = $this->build_report_request('Account', $start_date, $end_date, 'Campaign');
		$campaign_report = (class_exists('cli') && !empty(cli::$args['c'])) ? $this->get_report_path_from_cli(cli::$args['c']) : $this->run_report($request, $flags);
		if ($campaign_report === false) {
			return false;
		}
		// get old data before processing new data
		$cached_ca_data = $this->get_cached_ca_data($start_date, $end_date);
		$new_ca_data = $this->process_campaign_report($campaign_report, $ca_rev);
		if ($new_ca_data === false) {
			return false;
		}

		$refresh_dates = $this->pre_data_report($cached_ca_data, $new_ca_data);
		// nothing has changed, no need to run other reports
		if (empty($refresh_dates)) {
			return true;
		}

		list($start_date, $end_date) = $this->get_all_data_report_dates($refresh_dates, $start_date, $end_date);
		if ($start_date === false) {
			// maybe not what user wanted, but we ran campaign data report above
			return true;
		}
		
		// run goals report if we track conversions for this account
		$do_run_goals_report = $this->account_type(array('revenue_tracking', 'conversion_types'), 'or');
		if ($do_run_goals_report) {
			$request = $this->build_report_request('account', $start_date, $end_date, 'Revenue');
			$revenue_report = (class_exists('cli') && !empty(cli::$args['z'])) ? $this->get_report_path_from_cli(cli::$args['z']) : $this->run_report($request, $flags);
			if ($revenue_report === false) return false;
		}
		
		// run keyword report
		$request = $this->build_report_request('account', $start_date, $end_date, 'Keyword');
		$keyword_report = (class_exists('cli') && !empty(cli::$args['k'])) ? $this->get_report_path_from_cli(cli::$args['k']) : $this->run_report($request, $flags);
		if ($keyword_report === false) return false;

		// process reports
		if ($do_run_goals_report) {
			return $this->merge_goals_and_keyword_report($revenue_report, $keyword_report, $refresh_dates);
		}
		else {
			return $this->process_report($keyword_report, $refresh_dates);
		}
	}

	protected function process_campaign_revenue_report($report_path)
	{
		$h = fopen($report_path, 'rb');
		if ($h === false) {
			$this->error = 'Could not open campaign revenue report ('.$report_path.')';
			return false;
		}
		// build array of data by date so we know what we need to update for all_data report
		$ca_rev = array();
		$line_count = $this->file_get_line_count($h);
		$delimiter = $this->get_rep_delimiter();
		for ($i = 0; ($data = fgetcsv($h, 0, $delimiter)) !== FALSE; ++$i) {
			if ($i % 1000 == 0) util::update_job_details($this->job, 'Processing campaign revenue data '.$i.' / '.$line_count);
			// order from defining rep columns
			list($date, $ca_id, $rev) = $data;
			$dtest = strtotime($date);
			if (is_numeric($ca_id) && $dtest !== false) {

				if (!$this->does_data_belong_to_client($ca_id)) {
					continue;
				}
				// bing dates are a different format, standardize
				$date = date(util::DATE, $dtest);

				$ca_rev[$date][$ca_id] = $rev;
			}
		}
		return $ca_rev;
	}

	/*
	 * msn revenue reports do not have match type
	 * as such, revenue cannot be properly attributed to keyword match types
	 * best we can do, is evenly distribute it between each match type based on
	 * the number of conversions
	 */
	// todo: does this still matter??
	protected function post_process_report($dates)
	{
		$table_full = "{$this->market}_objects.all_data_{$this->eac_id}";
		foreach ($dates as $date => $ph) {
			$convs = db::select("
				select substring(keyword, 2), ad_group, ad, keyword, convs, revenue
				from {$table_full}
				where data_date = '$date' && (convs > 0 || revenue > 0)
			", 'NUM', array(0));
			
			foreach ($convs as $kw_base_id => $kw_info) {
				for ($i = $rev_sum = $convs_sum = 0; list($ag_id, $ad_id, $kw_id, $convs, $rev) = $kw_info[$i]; ++$i) {
					$convs_sum += $convs;
					$rev_sum += $rev;
				}
				$rev_per_conv = ($convs_sum > 0) ? ($rev_sum / $convs_sum) : 0;
				$rounded_sum = 0;
				for ($i = 0; list($ag_id, $ad_id, $kw_id, $convs, $rev) = $kw_info[$i]; ++$i) {
					$estimated_rev = number_format($convs * $rev_per_conv, 2, '.', '');
					$rounded_sum += $estimated_rev;
					$row_id = "data_date = '$date' && ad_group = '$ag_id' && ad = '$ad_id' && keyword = '$kw_id'";
					if ($estimated_rev != '0.00') {
						$round_fix_row = $row_id;
					}
					db::update($table_full, array('revenue' => $estimated_rev), $row_id);
				}
				$diff = round($rev_sum - $rounded_sum, 2);
				if ($diff != 0) {
					db::update(
						$table_full,
						array('revenue' => db::literal("revenue ".(($diff < 0) ? "-" : "+")." ".number_format(abs($diff), 2, '.', ''))),
						$round_fix_row
					);
				}
			}
		}
	}

	private function schedule_report_get_columns($type)
	{
		switch ($type)
		{
			case ('GoalsAndFunnelsReportRequest'):
				return array(
					'AccountId',
					'AccountName',
					'CampaignId',
					'CampaignName',
					'AdGroupId',
					'AdGroupName',
					'KeywordId',
					'TimePeriod',
					'Goal',
					'Conversions',
					'Assists',
					'Revenue'
				);
			
			// can't get status :(
			case ('AdPerformanceReportRequest'):
				return array(
					'Impressions',
					'TimePeriod',
					'AdGroupId',
					'AdId',
					'AdTitle',
					'AdDescription',
					'DisplayUrl',
					'DestinationUrl'
				);
			
			case ('ConversionPerformanceReportRequest'):
				return array(
					'TimePeriod',
					'CampaignId',
					'Revenue'
				);

			case ('CampaignPerformanceReportRequest'):
				return array(
					'TimePeriod',
					'CampaignId',
					'Impressions',
					'Clicks',
					'Conversions',
					'Spend',
					'AveragePosition'
				);

			case ('KeywordPerformanceReportRequest'):
				return array(
					'AccountId',
					'AccountName',
					'CampaignId',
					'CampaignName',
					'AdGroupId',
					'AdGroupName',
					'AdId',
					'KeywordId',
					'Keyword',
					'MatchType',
					'CurrentMaxCpc',
					'TimePeriod',
					'Impressions',
					'Clicks',
					'Spend',
					'Conversions',
					'AveragePosition',
					'DeviceType'
				);
		}
	}
	
	public function schedule_report($request)
	{
		$this->init_client();
		
		$start_date = $request['start_date'];
		$end_date = $request['end_date'];
		
		$report_request = array(
			'Format' => 'Tsv',
			'Language' => 'English',
			'ReportName' => $request['name'],
			'ReturnOnlyCompleteData' => 'False',
			'Aggregation' => 'Daily',
			'Columns' => $this->schedule_report_get_columns($request['type']),
			'Filter' => null,
			'Scope' => array(
				'AccountIds' => array($request['account'])
			),
			'Time' => array(
				'CustomDateRangeEnd' => array(
					'Day' => ltrim(substr($end_date, 8), '0'),
					'Month' => ltrim(substr($end_date, 5, 2), '0'),
					'Year' => ltrim(substr($end_date, 0, 4), '0')
				),
				'CustomDateRangeStart' => array(
					'Day' => ltrim(substr($start_date, 8), '0'),
					'Month' => ltrim(substr($start_date, 5, 2), '0'),
					'Year' => ltrim(substr($start_date, 0, 4), '0')
				)
			)
		);
		$r = $this->client->SubmitGenerateReport($report_request, $request['type']);
		return $r;
	}
	
	public function get_report_status($report_id)
	{
		$this->init_client();
		$r = $this->client->PollGenerateReport($report_id);
		return (($r) ? api_translate::standardize_report_status('m', $r['Status']) : false);
	}
	
	public function get_report_url($report_id)
	{
		$this->init_client();
		$r = $this->client->PollGenerateReport($report_id);
		return (($r) ? api_translate::standardize_report_status('m', $r['ReportDownloadUrl']) : false);
	}
	
	// give up after 10 minutes
	protected function run_report_give_up($time)
	{
		return ($time > 600);
	}
	
	// try 3 times
	protected function run_report_try_again($num_attempts)
	{
		return ($num_attempts < 3);
	}
	
	public function run_company_report($date)
	{
		$accounts = db::select("
			select id, text
			from {$this->market}_accounts
			where
				company=".$this->company." &&
				status='On'
		");
		
		$raw_data_table = "{$this->market}_data_tmp.".str_replace('-', '_', $date);
		@$ac_data_counts = db::select("
			select account, count(*)
			from {$raw_data_table}
			group by account
		", 'NUM', 0);
		
		$company_report_path = $this->init_company_report($date);
		$report_flags = REPORT_SHOW_STATUS | REPORT_NO_HEADERS | REPORT_DELETE_ZIP;
		$now = date(util::DATE_TIME);
		for ($i = 0, $count = count($accounts); list($ac_id, $ac_name) = $accounts[$i]; ++$i)
		{
			echo "account ".($i + 1)." of $count ($ac_id, $ac_name)\n";
			$this->update_report_status($this->market, $date, "Running ".($i+1)." of $count (".date('H:i:s').")");
			
			// if we have already run a report for this account for this day, run_company_report is not the place to run it again
			if ($ac_data_counts[$ac_id])
			{
				continue;
			}
			
			// run the report
			$this->set_account($ac_id);
			$report_path = $this->get_report_path().$this->get_report_name('e2').'.csv';
			if (!file_exists($report_path)) $report_path = $this->run_account_report($date, $date, $report_flags);
			
			// check for errors
			if ($report_path === false)
			{
				db::insert("eppctwo.m_data_error_log", array(
					'd' => $date,
					'account_id' => $ac_id,
					'details' => $this->get_error(),
					'first_attempt' => $now,
					'last_attempt' => $now,
					'num_attempts' => 1,
					'success' => 0
				));
				continue;
			}
			
			// append it to the company report
			exec('cat '.$report_path.' >> '.$company_report_path);
		}
		// make sure there is a newline at the end
		exec('echo >> '.$company_report_path);
		
		return $company_report_path;
	}
	
	// no difference between master and account reports in msn
	public function run_master_report($date, $flags = null)
	{
		return $this->run_account_report($date, $date, $flags);
	}

	protected function get_rep_delimiter()
	{
		return "\t";
	}

	public function process_report($report_path, $refresh_dates, $rev_data = array())
	{
		// define which columns are where
		$lines = file($report_path);
		$num_lines = count($lines);

		if (!empty($this->conv_type_counts)) {
			$this->clear_old_conv_type_data($this->report_start_date, $this->report_end_date);
		}
		$campaigns = array();
		$ad_groups = array();
		$ca_rev = array();
		for ($i = 0; $i < $num_lines; ++$i) {
			if ($i % 1000 == 0) util::update_job_details($this->job, 'Processing market report data '.$i.' / '.$num_lines);
			// only replace one quote at the end!
			$line = preg_replace("/\"$/", '', ltrim(trim($lines[$i]), '"'));
			
			// data is enclosed in quotes (ugh) and separated by tabs
			$d = explode("\"\t\"", $line);
			
			// for indexes, see file resetta_stone.php, class api_translate, function set_standard_report_columns
			list($ac_id, $ac_name, $ca_id, $ca_name, $ag_id, $ag_name, $ad_id, $kw_id_m, $kw_text, $kw_type, $kw_max_cpc, $mdate, $imps, $clicks, $cost, $convs, $ave_pos, $device) = $d;
			
			$dt = strtotime($mdate);
			// make sure this is a valid data row
			if ($dt === false || !is_numeric($ag_id) || !is_numeric($kw_id_m)) {
				continue;
			}
			// rev data and conv type data is indexed by msn formatted date, so we have mdate and date
			$date = date(util::DATE, $dt);
			// see if this is a date we need to refresh
			if (!isset($refresh_dates[$date])) {
				continue;
			}
			// check that this data belongs to client
			if (!$this->does_data_belong_to_client($ca_id, $ag_id)) {
				continue;
			}
			// rev data and conv type data does not have match type, so kw_id_m is used here,
			// but we use our id with the match type when updating database
			$kw_id = $kw_type[0].$kw_id_m;
			
			// device is standardized now as we need it for conv type operations
			$device = self::device_map($this->market, $device);
			if ($this->ex_rates && isset($this->ex_rates[$date])) {
				$cost *= $this->ex_rates[$date];
			}
			
			// get ad group and keyword to add rev data
			$rev = 0;
			if ($rev_data) {
				if (isset($rev_data[$mdate][$ag_id][$kw_id_m])) {
					// $d[18] = $rev_data[$date][$ag_id][$kw_id_m];
					$rev = $rev_data[$mdate][$ag_id][$kw_id_m];

					// also group at campaign level
					if (!isset($ca_rev[$date][$ca_id])) {
						$ca_rev[$date][$ca_id] = 0;
					}
					$ca_rev[$date][$ca_id] += $rev;
					
					// multiple rows in report can have same ag/kw if served under multiple ads
					// rev data does not give the ad, so we just arbitrarily pick the first ag/kw match
					// and add rev data to that and then null it out so it is not added to another
					$rev_data[$mdate][$ag_id][$kw_id_m] = null;
				}
			}
			if (isset($this->conv_type_counts[$mdate][$ag_id][$kw_id_m])) {
				foreach ($this->conv_type_counts[$mdate][$ag_id][$kw_id_m] as $conv_type => $ct_amount) {
					conv_type_count::create(array(
						'aid' => $this->eac_id,
						'd' => $date,
						'market' => $this->market,
						'account_id' => $this->ac_id,
						'campaign_id' => $ca_id,
						'ad_group_id' => $ag_id,
						'ad_id' => $ad_id,
						'keyword_id' => $kw_id,
						'device' => $device,
						'purpose' => '',
						'name' => $conv_type,
						'amount' => $ct_amount
					));
				}
				$this->conv_type_counts[$mdate][$ag_id][$kw_id_m] = null;
			}
			$put_data = array(
				'account_id' => $this->ac_id,
				'campaign_id' => $ca_id,
				'ad_group_id' => $ag_id,
				'ad_id' => $ad_id,
				'keyword_id' => $kw_id,
				'device' => $device,
				'data_date' => $date,
				'imps' => $imps,
				'clicks' => $clicks,
				'convs' => $convs,
				'cost' => str_replace(',', '', $cost),
				'pos_sum' => round($imps * $ave_pos),
				'revenue' => str_replace(',', '', $rev),
				'mpc_convs' => 0,
				'vt_convs' => 0
			);
			list($put_key_cols, $test_data) = all_data::get_import_info($this->eac_id, $this->job->id, $date, $ag_id, $ad_id, $kw_id, $device);
			if ($test_data) {
				$put_data = array_merge($test_data, $put_data);
			}

			db::insert_update($this->data_table, $put_key_cols, $put_data);
			
			if (!array_key_exists($ca_id, $campaigns)) {
				db::insert_update("{$this->market}_objects.campaign_{$this->eac_id}", array('id'), array(
					'account_id' => $this->ac_id,
					'id' => $ca_id,
					'mod_date' => $date,
					'text' => $ca_name,
					'status' => 'On'
				));
				$campaigns[$ca_id] = 1;
			}
			if (!array_key_exists($ag_id, $ad_groups)) {
				db::insert_update("{$this->market}_objects.ad_group_{$this->eac_id}", array('id'), array(
					'account_id' => $this->ac_id,
					'campaign_id' => $ca_id,
					'id' => $ag_id,
					'mod_date' => $date,
					'text' => $ag_name,
					'status' => 'On'
				));
				$ad_groups[$ag_id] = 1;
			}
			db::insert_update("{$this->market}_objects.keyword_{$this->eac_id}", array('ad_group_id', 'id'), array(
				'account_id' => $this->ac_id,
				'campaign_id' => $ca_id,
				'ad_group_id' => $ag_id,
				'id' => $kw_id,
				'mod_date' => \epro\TODAY,
				'text' => $kw_text,
				'type' => $kw_type,
				'max_cpc' => $kw_max_cpc,
				'first_page_cpc' => 0,
				'quality_score' => 0,
				'status' => 'On'
			));
		}
		// bubble any rev data up to campaigns
		foreach ($ca_rev as $date => $date_data) {
			foreach ($date_data as $ca_id => $rev) {
				db::update(
					"{$this->market}_objects.campaign_data_{$this->eac_id}",
					array("revenue" => $rev),
					"data_date = :date && campaign_id = :ca_id",
					array("date" => $date, "ca_id" => $ca_id)
				);
			}
		}
		$this->post_data_report('all', $this->data_table);
		return true;
	}
	
	private function merge_goals_and_keyword_report($revenue_report, $keyword_report, $refresh_dates)
	{
		// make associative array from rev report, send to standardize
		$rev_data = array();
		$lines = file($revenue_report);
		$this->conv_type_counts = array();
		for ($i = 0, $ci = count($lines); $i < $ci; ++$i) {
			list($ac_id, $ac_name, $ca_id, $ca_name, $ag_id, $ag_name, $kw_id, $date, $goal, $convs, $assists, $rev) = explode("\"\t\"", trim(trim($lines[$i]), '"'));
			$rev = (double) str_replace(',', '', $rev);
			if (is_numeric($ac_id) && strtotime($date) !== false) {
				if (is_numeric($rev) && $rev > 0) {
					$rev_data[$date][$ag_id][$kw_id] += $rev;
				}

				if ($convs > 0) {
					$this->conv_type_counts[$date][$ag_id][$kw_id][$goal] += $convs;
					$goal_sums[$goal] += $convs;
				}
			}
		}
		return $this->process_report($keyword_report, $refresh_dates, $rev_data);
	}
	
	public function update_ad_groups(&$ags, $ca_id = '')
	{
		$a = array();
		foreach ($ags as &$ag)
		{
			if (!$ca_id && $ag->campaign_id)
			{
				$ca_id = $ag->campaign_id;
			}
			$tmp = $this->obj_to_array($ag, 'ad_group');
			
			// use resume and pause to update status, not this
			if ($tmp['Status'])
			{
				unset($tmp['Status']);
			}
			
			$bid = $ag->max_cpc;
			// bids need their weird wrapper
			// don't set content, set all the others
			foreach (self::$match_types as $match_type)
			{
				if (strpos($match_type, 'Content') === 0)
				{
					$tmp[$match_type] = array('Amount' => null);
				}
				else
				{
					$tmp[$match_type] = array('Amount' => $bid);
				}
			}
			
			$a[] = $tmp;
		}
		
		// client returns empty array on success
		$this->init_client();
		return ($this->client->UpdateAdGroups($ca_id, $a) !== false);
	}
	
	public function pause_ad_group(&$ag, $ca_id = '')
	{
		return ($this->pause_ad_groups(array($ag->id), ($ca_id) ? $ca_id : $ag->campaign_id));
	}
	
	public function pause_ad_groups($ag_ids, $ca_id)
	{
		$this->init_client();
		return ($this->client->PauseAdGroups($ca_id, $ag_ids) !== false);
	}
	
	public function resume_ad_group(&$ag, $ca_id = '')
	{
		return ($this->resume_ad_groups(array($ag->id), ($ca_id) ? $ca_id : $ag->campaign_id) !== false);
	}
	
	public function resume_ad_groups($ag_ids, $ca_id)
	{
		$this->init_client();
		return ($this->client->ResumeAdGroups($ca_id, $ag_ids) !== false);
	}
	
	public function update_ad_group($ag, $ca_id = '')
	{
		$tmp = array(&$ag);
		return $this->update_ad_groups($tmp, $ca_id);
	}
	
	public function add_campaign($d)
	{
		$this->service = 'CampaignManagement';
		$this->action = 'AddCampaigns';
		
		$xml = '
			<'.$this->action.'Request>
				<AccountId>'.$this->ac_id.'</AccountId>
				<Campaigns>
					<Campaign>
						<BudgetType>DailyBudgetWithMaximumMonthlySpend</BudgetType>
						<ConversionTrackingEnabled>false</ConversionTrackingEnabled>
						<DailyBudget>'.$d['budget'].'</DailyBudget>
						<DaylightSaving>true</DaylightSaving>
						<Description>'.$d['text'].'</Description>
						<MonthlyBudget>'.($d['budget'] * 30).'</MonthlyBudget>
						<Name>'.$d['text'].'</Name>
						<TimeZone>PacificTimeUSCanadaTijuana</TimeZone>
					</Campaign>
				</Campaigns>
			</'.$this->action.'Request>
		';

		$response = $this->get_request($xml);
		if ($response === false)
		{
			return false;
		}
		else
		{
			$d['id'] = $response['Body']['AddCampaignsResponse']['CampaignIds']['long'];
			return $d;
		}
	}
	
	public function add_ad_group($ag)
	{
		$this->service = 'CampaignManagement';
		$this->action = 'AddAdGroups';
		
		$xml = '
			<'.$this->action.'Request>
				<CampaignId>'.$ag->campaign_id.'</CampaignId>
				<AdGroups>
					<AdGroup>
						<AdDistribution>Search</AdDistribution>
						<BiddingModel>Keyword</BiddingModel>
						<BroadMatchBid><Amount>'.$ag->max_cpc.'</Amount></BroadMatchBid>
						<LanguageAndRegion>EnglishUnitedStates</LanguageAndRegion>
						<Name>'.$ag->text.'</Name>
						<PricingModel>Cpc</PricingModel>
					</AdGroup>
				</AdGroups>
			</'.$this->action.'Request>
		';

		$response = $this->get_request($xml);
		if ($response === false)
		{
			return false;
		}
		else
		{
			$ag->id = $response['Body'][$this->action.'Response']['AdGroupIds']['long'];
			return true;
		}
	}
	
	public function add_keywords(&$kws, $ag_id)
	{
		$m_kws = array();
		foreach ($kws as $kw) {
			$m_kws[] = array(
				'Text' => $kw->text,
				self::$match_types[$kw->match_type[0]] => array('Amount' => empty($kw->max_cpc) ? null : $kw->max_cpc)
			);
		}
		$this->init_client();
		$r = $this->client->AddKeywords($ag_id, $m_kws);

		if ($r === false) {
			return false;
		}
		else {
			foreach ($kws as $i => &$kw) {
				$kw->id = (($kw->match_type) ? $kw->match_type[0] : 'B').$r[$i];
			}
			return true;
		}

		// old school.. good times
		
		$keywords_by_text = db::select("
			select text, substring(keyword, 2)
			from {$this->market}_info.keywords_{$data_id}
			where ad_group = '$ag_id' && status = 'On'
			group by text
		", 'NUM', 0);
		
		// group by match type to make stupid msn keywords
		// and check our keyword counts for already existing keywords
		$kws_for_update = array();
		$msn_kws = array();
		$kw_index_to_msn_index = array();
		foreach ($kws as $i => &$kw)
		{
			if (array_key_exists($kw->text, $keywords_by_text))
			{
				$kw->id = $kw->match_type[0].$keywords_by_text[$kw->text];
				$kw->status = 'On';
				$kws_for_update[] = $kw;
			}
			else
			{
				$msn_kws[$kw->text][$kw->match_type] = $kw->max_cpc;
				$kw_index_to_msn_index[$i] = array_search($kw->text, array_keys($msn_kws));
			}
		}
		
		if ($kws_for_update)
		{
			$response = $this->update_keywords($kws_for_update, $ag_id);
			if ($response === false)
			{
				return false;
			}
		}
		
		if ($msn_kws)
		{
			$match_types = $this->get_match_types();
			$kws_for_add = array();
			foreach ($msn_kws as $kw_text => $kw_match_types)
			{
				$kw_for_add = array('Text' => $kw_text);
				foreach ($match_types as $match_type)
				{
					if (array_key_exists($match_type, $kw_match_types))
					{
						$bid = $kw_match_types[$match_type];
						// take default ad group bid
						if ($bid == '0' || !is_numeric($bid))
						{
							$kw_for_add[$match_type.'MatchBid'] = null;
							//$bid_xml .= '<'.$match_type.'MatchBid xsi:nil="true"/>';
						}
						// specific bid
						else
						{
							$kw_for_add[$match_type.'MatchBid']['Amount'] = $bid;
							//$bid_xml .= '<'.$match_type.'MatchBid><Amount>'.$bid.'</Amount></'.$match_type.'MatchBid>';
						}
					}
					// not using this match type
					else
					{
						#$kw_for_add[$match_type.'MatchBid']['Amount'] = 0;
						//$bid_xml .= '<'.$match_type.'MatchBid><Amount>0</Amount></'.$match_type.'MatchBid>';
					}
				}
				$kws_for_add[] = $kw_for_add;
			}
			$this->init_client();
			$r = $this->client->AddKeywords($ag_id, $kws_for_add);

			if ($r === false)
			{
				return false;
			}
			else
			{
				// looks like this may work now?
				// see crazy code below for previous 
				foreach ($kws as $i => &$kw)
				{
					$kw->id = (($kw->match_type) ? $kw->match_type[0] : 'B').$r[$i];
				}
				return true;
				$ids = $r['Envelope']['Body']['AddKeywordsResponse']['KeywordIds']['long'];
				if (!is_array($ids))
				{
					$ids = array($ids);
				}
				// so... lafkjalefjeal;jfieaifeji
				// it does not seem possible to add a keyword *without* exact match being one of the match types no matter what you do or say
				// see if we actually wanted it, and if not, delete it
				$exact_kws = array();
				$non_exact_kws = array();
				foreach ($kws as $i => &$kw)
				{
					$msn_index = $kw_index_to_msn_index[$i];
					$kw->id = $kw->match_type[0].$ids[$msn_index];
					$kw->status = 'On';
					
					if ($kw->match_type == 'Exact')
					{
						$exact_kws[$kw->text] = 1;
					}
					else
					{
						$non_exact_kws[$kw->text] = $ids[$msn_index];
					}
				}
				
				$delete_kws = array();
				foreach ($non_exact_kws as $kw_text => $msn_id)
				{
					if (!array_key_exists($kw_text, $exact_kws))
					{
						$delete_kws[] = 'E'.$msn_id;
					}
				}
				if ($delete_kws)
				{
					$this->delete_keywords($delete_kws, $ag_id, false);
				}
			}
		}
		return true;
	}
	
	protected function get_ad_structure_parse_file_vars()
	{
		return array(0, "\t");
	}
	
	// adcenter ad reports: no campaign id and no status
	// we should be able to get campaign id from our own cache
	// no status = not really anything we can do
	protected function get_ad_structure_data_vars(&$data, $eac_id)
	{
		list($imps, $date, $ag_id, $ad_id, $headline, $desc_1, $disp_url, $dest_url) = $data;
		
		if (!$this->ad_structure_ag_to_ca) {
			$this->ad_structure_ag_to_ca = db::select("
				select id, campaign_id
				from {$this->market}_objects.ad_group_{$eac_id}
			", 'NUM', 0);
		}
		$ca_id = $this->ad_structure_ag_to_ca[$ag_id];
		
		// just assume On for status
		$status = 'On';
		
		// no desc_2 for msn
		$desc_2 = '';
		
		return array($imps, $ca_id, $ag_id, $ad_id, $headline, $desc_1, $desc_2, $disp_url, $dest_url, $status);
	}
}
?>