<?php

define('Y_API_VERSION', 'V6');
define('Y_API_LOCATION', 'https://global.marketing.ews.yahooapis.com/services');

/*
 * you cannot set the language of reports in yahoo, so we can't inspect columns to determine what is going on
 * hence, these numbers are defined
 */
define('Y_REPORT_START_ROW', 10);
define('Y_REPORT_SEARCH_WITH_CONVS_COLS', 27);
define('Y_REPORT_CONTENT_WITH_CONVS_COLS', 25);
define('Y_REPORT_SEARCH_NO_CONVS_COLS', 21);
define('Y_REPORT_CONTENT_NO_CONVS_COLS', 19);

class y_api extends base_market
{
	private $units, $operations;
	
	// yahoo specific
	protected $master_account_id, $master_user, $master_password;
	protected $api_loc;

	public function __construct($company, $ac_id = '')
	{
		parent::__construct('y', $company);
		$this->set_account($ac_id);
		$this->set_version(Y_API_VERSION);
		$this->units = $this->operations = 0;
	}
	
	public function set_account($ac_id = '')
	{
		$this->ac_id = $ac_id;
		if (!empty($ac_id))
		{
			$d = db::select_row("select master_account from y_accounts where id='$ac_id'");
			if ($d && $d[0]) $this->set_master($d[0]);
		}
	}
	
	public function set_master($master_account_id, $api_loc = '')
	{
		$this->master_account_id = $master_account_id;
		$d = db::select_row("select url_prefix, external_user, external_pass from y_master_accounts where id='$master_account_id'");
		if ($d && $d[0]) list($this->api_loc, $this->master_user, $this->master_password) = $d;
		else if ($api_loc) $this->api_loc = $api_loc;
	}
	
	public function set_tokens(&$api_info)
	{
		$this->set_key('license', $api_info['license_key']);
	}
	
	public function get_units()
	{
		return ($this->units);
	}
	
	public function get_operations()
	{
		return ($this->operations);
	}
	
	public function whoami()
	{
		echo "
			------
			whoami
			------
			yahoo (".$this->get_version().")
			account=$this->ac_id
			license=".$this->get_key('license')."
			master account=$this->master_account_id
			master user=$this->master_user
			api loc=$this->api_loc
		";
	}
	
	protected function get_header()
	{
		if (empty($this->master_user))
			$ml_on_behalf_of = '';
		else
			$ml_on_behalf_of = '
				<onBehalfOfUsername>'.$this->master_user.'</onBehalfOfUsername>
				<onBehalfOfPassword>'.$this->master_password.'</onBehalfOfPassword>
			';
		
		$ml_ac_id = (empty($this->ac_id)) ? '' : '<accountID>'.$this->ac_id.'</accountID>';
		
		$headers = '
			<wsse:Security>
				<UsernameToken>
					<Username>'.$this->api_user.'</Username>
					<Password>'.$this->api_pass.'</Password>
				</UsernameToken>
			</wsse:Security>
			<masterAccountID>'.$this->master_account_id.'</masterAccountID>
			<license>'.$this->get_key('license').'</license>
			'.$ml_on_behalf_of.'
			'.$ml_ac_id.'
		';
			 
		return ($headers);
	}
	
	protected function get_endpoint()
	{
		$url_suffix = '/'.$this->version.'/'.$this->service;
		$url_base = ($this->service == 'LocationService') ? Y_API_LOCATION : $this->api_loc;
		$url = $url_base.$url_suffix;
		
		return ($url);
	}
	
	protected function get_envolope_attrs()
	{
		return 'xmlns:wsse="http://schemas.xmlsoap.org/ws/2002/07/secext" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://marketing.ews.yahooapis.com/'.Y_API_VERSION.'"';
	}
	
	protected function get_soap_action()
	{
		return '';
	}
	
	public function is_error(&$doc)
	{
		if (is_object($doc))
		{
			$body = $doc->get('Body');
			if (@array_key_exists('Fault', $body))
			{
				$this->error = $body['Fault']['faultstring'];
				return true;
			}
			$op_success = $doc->get('operationSucceeded');
			if ($op_success == 'false')
			{
				$errors = $doc->get('errors');
				$this->error = $errors['Error']['message'];
				return true;
			}
		}
		return false;
	}
	
	public function add_ad_group(&$ag)
	{
		$this->service = 'AdGroupService';
		
		$xml = $this->obj_to_xml($ag, 'ad_group');
		$xml .= '<contentMatchON>'.(($ag->max_content_cpc) ? 'true' : 'false').'</contentMatchON>';
		$xml .= ($ag->status) ? '' : '<status>On</status>';
		
		$response = $this->get_request('
			<addAdGroup>
				<adGroup>
					'.$xml.'
					<adAutoOptimizationON>false</adAutoOptimizationON>
					<advancedMatchON>false</advancedMatchON>
					<campaignOptimizationON>false</campaignOptimizationON>
				</adGroup>
			</addAdGroup>'
			, 'adGroup', 'ad_group');
		
		if ($response === false)
		{
			return false;
		}
		else
		{
			$ag->id = $response['id'];
			return true;
		}
	}
	
	public function add_ad(&$ad)
	{
		$this->service = 'AdService';
		
		$xml = $this->obj_to_xml($ad, 'ad');
		$xml .= '<name>'.strings::xml_encode(($ad->y_name) ? $ad->y_name : ($ad->text.'-'.time())).'</name>';
		$response = $this->get_request('
			<addAd>
				<ad>
					'.$xml.'
				</ad>
			</addAd>
		', 'ad', 'ad');
		
		if ($response === false)
		{
			return false;
		}
		else
		{
			$ad->id = $response['id'];
			return true;
		}
	}
	
	public function add_keywords(&$kws)
	{
		$this->service = 'KeywordService';
		
		$xml = '';
		foreach ($kws as &$kw)
		{
			$kw_xml = $this->obj_to_xml($kw, 'keyword');
			if (empty($kw->status)) $kw_xml .= '<status>On</status>';
			$xml .= '
				<Keyword>
					'.$kw_xml.'
				</Keyword>
			';
		}
		
		$response = $this->get_request('
			<addKeywords>
				<keywords>
					'.$xml.'
				</keywords>
			</addKeywords>
		', 'KeywordResponse', 'keyword', WPRO_SOAP_REQUEST_EXPECT_ARRAY);
		
		if ($response === false)
		{
			return false;
		}
		else
		{
			$kws = array();
			foreach ($response as &$response_data)
			{
				$y_kw = api_translate::standardize_keyword('y', $response_data['keyword']);
				$kw = api_factory::new_keyword(null, $y_kw['id'], $y_kw['text'], $y_kw['match_type'], $y_kw['max_cpc'], $y_kw['dest_url'], $y_kw['status']);
				$kws[] = $kw;
			}
			return true;
			
/*
			 [editorialReasons] => 
            [errors] => 
            [keyword] => Array
                (
                    [ID] => 410780277012
                    [accountID] => 23817196025
                    [adGroupID] => 18226807400
                    [advancedMatchON] => true
                    [alternateText] => 
                    [canonicalSearchText] => california coin
                    [createTimestamp] => 2010-05-05T15:20:32.286-07:00
                    [deleteTimestamp] => 
                    [editorialStatus] => Approved
                    [lastUpdateTimestamp] => 2010-05-05T15:20:32.286-07:00
                    [participatesInMarketplace] => true
                    [phraseSearchText] => california coin
                    [sponsoredSearchBidStatus] => Active
                    [sponsoredSearchMaxBid] => 
                    [sponsoredSearchMaxBidTimestamp] => 
                    [sponsoredSearchMinBid] => 0.1
                    [status] => On
                    [text] => california coins
                    [update] => false
                    [url] => 
                    [watchON] => false
                )

            [operationSucceeded] => true
            [warnings] => 
*/
		}
	}
	
	public function add_campaign($d)
	{
		$this->service = 'CampaignService';
		
		$content = ($d['content'] == 'On') ? 'true' : 'false';
		$ca = $this->get_request('
			<addCampaign>
				<campaign>
					<accountID>'.$d['ac_id'].'</accountID>
					<advancedMatchON>false</advancedMatchON>
					<campaignOptimizationON>false</campaignOptimizationON>
					<contentMatchON>'.$content.'</contentMatchON>
					<name>'.$d['text'].'</name>
					<startDate>'.$d['date'].'T00:00:00-08:00</startDate>
					<status>On</status>
				</campaign>
			</addCampaign>'
			, 'campaign', 'campaign');
		
		if ($ca && array_key_exists('budget', $d))
			$this->update_campaign_daily_spend_limit($ca, $d);
		
		return $ca;
	}
	
	public function update_campaign_daily_spend_limit(&$ca, $d)
	{
		$this->service = 'BudgetingService';
		
		$this->get_request('
			<updateCampaignDailySpendLimit>
				<campaignID>'.$ca['id'].'</campaignID>
				<limit>
					<limit>'.$d['budget'].'</limit>
				</limit>
			</updateCampaignDailySpendLimit>'
			, '', '');
			
		$ca['budget'] = $d['budget'];
	}
	
	public function get_ad_group($id)
	{
		return ($this->get_request('AdGroupService', "<getAdGroup>
		<adGroupId>{$id}</adGroupId>
	</getAdGroup>"));
	}
	
	public function get_ad_groups($ca_id, $include_deleted = 'false')
	{
		$this->service = 'AdGroupService';
		
		$response = $this->get_request(
			'<getAdGroupsByCampaignID>
				<campaignID>'.$ca_id.'</campaignID>
				<includeDeleted>'.$include_deleted.'</includeDeleted>
				<startElement>0</startElement>
				<numElements>0</numElements>
			</getAdGroupsByCampaignID>'
			, 'AdGroup', 'ad_groups', WPRO_SOAP_REQUEST_EXPECT_ARRAY);
		
		if ($response === false) return false;
		
		$ags = array();
		foreach ($response as &$d)
		{
			$ags[] = api_factory::new_ad_group($ca_id, $d['id'], $d['text'], $d['max_cpc'], $d['max_content_cpc'], $d['status']);
		}
		return $ags;
	}

	public function get_keywords($ag_id)
	{
		$this->service = 'KeywordService';
		
		$response =  $this->get_request(
			'<getKeywordsByAdGroupID>
				<adGroupID>'.$ag_id.'</adGroupID>
				<includeDeleted>false</includeDeleted>
				<startElement>0</startElement>
				<numElements>0</numElements>
			</getKeywordsByAdGroupID>'
			, 'Keyword', 'keywords', WPRO_SOAP_REQUEST_EXPECT_ARRAY);
		
		if ($response === false) return false;
		
		$kws = array();
		foreach ($response as &$d)
		{
			$kw = api_factory::new_keyword($ag_id, $d['id'], $d['text'], null, $d['max_cpc'], $d['dest_url'], $d['status']);
			$kw->y_sponsoredSearchMinBid = $d['sponsoredSearchMinBid'];
			$kw->y_advancedMatchON = $d['advancedMatchON'];
			$kws[] = $kw;
		}
		return $kws;
	}
	
	public function update_ad_group(&$ag)
	{
		if ($ag->status == 'Deleted')
		{
			return $this->delete_ad_group($ag->id);
		}
		$this->service = 'AdGroupService';
		
		$xml = $this->obj_to_xml($ag, 'ad_group');
		
		$response = $this->get_request(
			'<updateAdGroup>
				<adGroup>
					'.$xml.'
				</adGroup>
				<updateAll>false</updateAll>
			</updateAdGroup>'
			, 'adGroup', 'ad_group');
		
		return ($response != false);
	}
	
	public function update_keywords($keywords, $update_all = 'false')
	{
		$this->service = 'KeywordService';
		
		$xml = '';
		foreach ($keywords as $kw)
		{
			$ml_kw = $this->obj_to_xml($kw, 'keyword');
			$xml .= '<Keyword>'.$ml_kw.'</Keyword>';
		}
/*
		echo $xml;
		return;
*/
		return ($this->get_request(
			'<updateKeywords>
				<keywords>
					'.$xml.'
				</keywords>
				<updateAll>'.$update_all.'</updateAll>
			</updateKeywords>'
			, 'KeywordResponse', 'keywords', WPRO_SOAP_REQUEST_EXPECT_ARRAY));
	}
	
	public function get_all_criteria($ag_id)
	{
		return ($this->get_request('KeywordService',
			'<getKeywordsByAdGroupID>
				<adGroupID>'.$ag_id.'</adGroupID>
				<includeDeleted>false</includeDeleted>
				<startElement>0</startElement>
				<numElements>0</numElements>
			</getKeywordsByAdGroupID>'
			, 'Keyword', 'keywords', true
		));
	}
	
	public function get_ads($ag_id)
	{
		$this->service = 'AdService';
		
		$response = $this->get_request(
			'<getAdsByAdGroupID>
				<adGroupID>'.$ag_id.'</adGroupID>
				<includeDeleted>false</includeDeleted>
			</getAdsByAdGroupID>'
		, 'Ad', 'ads', WPRO_SOAP_REQUEST_EXPECT_ARRAY);
		
		$ads = array();
		foreach ($response as &$d)
		{
			$ads[] = api_factory::new_ad($ag_id, $d['id'], $d['text'], $d['desc_1'], $d['desc_2'], $d['disp_url'], $d['dest_url'], $d['status']);
		}
		
		return $ads;
	}
	
	public function get_ads_by_editorial_status($ag_id, $status)
	{
		$this->service = 'AdService';
		
		$response = $this->get_request(
			'<getAdsByAdGroupIDByEditorialStatus>
				<adGroupID>'.$ag_id.'</adGroupID>
				<update>false</update>
				<status>'.$status.'</status>
				<includeDeleted>true</includeDeleted>
			</getAdsByAdGroupIDByEditorialStatus>'
		, null, null);
		
		print_r($response);
	}
	
	public function get_ad_editorial_reason($ad_id)
	{
		$this->service = 'AdService';
		
		$response = $this->get_request(
			'<getEditorialReasonsForAd>
				<adID>'.$ad_id.'</adID>
			</getEditorialReasonsForAd>'
		, 'out', null);
		
		if (!is_array($response)) return false;
		
		$id_to_text = db::select("
			select id, reason
			from eppctwo.y_ad_editorial_reason_text
		", 'NUM', 0);
		
		$reasons = array();
		foreach ($response as $k => $v)
		{
			if ((strpos($k, 'Reason') !== false) && is_array($v))
			{
				foreach ($v as $reason_id)
				{
					$reasons[] = $id_to_text[$reason_id];
				}
			}
		}
		return $reasons;
	}
	
	public function get_ad_editorial_reason_text($reason_id)
	{
		
	}
	
	
	public function update_ad(&$ad)
	{
		$this->service = 'AdService';
		$xml = $this->obj_to_xml($ad, 'ad');
		
		$response = $this->get_request(
			'<updateAd>
				<ad>
					'.$xml.'
				</ad>
				<updateAll>false</updateAll>
			</updateAd>'
			, null, null
		);
		return ($response !== false);
	}
	
	public function update_ads(&$ads)
	{
		$this->service = 'AdService';
		
		$xml = '';
		foreach ($ads as $ad)
		{
			$xml .= '<Ad>'.$this->obj_to_xml($ad, 'ad').'</Ad>';
		}
		
		$response = $this->get_request(
			'<updateAds>
				<ads>
					'.$xml.'
				</ads>
				<updateAll>false</updateAll>
			</updateAds>'
			, null, null
		);
		return ($response !== false);
	}
	
/*
		$this->service = 'KeywordService';
		
		$xml = '';
		foreach ($keywords as $kw)
		{
			$xml .= '<Keyword>'.$this->obj_to_xml($kw, 'keyword').'</Keyword>';
		}
		
		return ($this->get_request(
			'<updateKeywords>
				<keywords>
					'.$xml.'
				</keywords>
				<updateAll>false</updateAll>
			</updateKeywords>'
			, 'KeywordResponse', 'keywords', WPRO_SOAP_REQUEST_EXPECT_ARRAY));
*/
	public function get_master_account_location()
	{
		$this->service = 'LocationService';
		
		return ($this->get_request(
			'<getMasterAccountLocation />'
			, null, null
		));
	}
	
	public function get_accounts()
	{
		$this->service = 'AccountService';
		
		return ($this->get_request('<getAccounts />', 'Account', 'accounts', WPRO_SOAP_REQUEST_EXPECT_ARRAY));
	}
	
	public function delete_ad_group($ag_id)
	{
		$this->service = 'AdGroupService';
		
		$response = $this->get_request('
			<deleteAdGroup>
				<adGroupID>'.$ag_id.'</adGroupID>
			</deleteAdGroup>
		', 'out', null);
		
		return ($response !== false);
	}
	
	public function get_campaigns()
	{
		$this->service = 'CampaignService';
		
		$response = $this->get_request(
			'<getCampaignsByAccountID>
				<accountID>'.$this->ac_id.'</accountID>
				<includeDeleted>false</includeDeleted>
			</getCampaignsByAccountID>'
			, 'Campaign', 'campaigns', WPRO_SOAP_REQUEST_EXPECT_ARRAY
		);
		if ($response === false) return false;
		
		$cas = array();
		foreach ($response as &$d)
		{
			if (@empty($d['id'])) continue;
			$cas[] = api_factory::new_campaign(null, $d['id'], $d['text'], null, $d['status']);
		}
		return $cas;
	}
	
	public function delete_keywords(&$kws)
	{
		$this->service = 'KeywordService';
		
		$xml = '';
		foreach ($kws as &$kw)
		{
			$xml .= '<long>'.$kw->id.'</long>';
		}
		
		return $this->get_request('
			<deleteKeywords>
				<keywordIDs>
					'.$xml.'
				</keywordIDs>
			</deleteKeywords>
		', 'out', null);
	}
	
	private function schedule_master_report(&$request)
	{
		return ($this->get_request('
			<addReportRequest>
				<accountID xsi:nil="true" />
				<reportRequest>
					<reportName>'.$request['name'].'</reportName>
					<reportType>'.$request['type'].'</reportType>
					<startDate>'.$request['start_date'].'</startDate>
					<endDate>'.$request['end_date'].'</endDate>
				</reportRequest>
				<fileOutputFormat>
					<zipped>true</zipped>
					<fileOutputType>TSV</fileOutputType>
				</fileOutputFormat>
			</addReportRequest>'
			, 'out', null));
	}
	
	private function schedule_account_report(&$request)
	{
		return ($this->get_request('
			<addReportRequest>
				<accountID>'.$request['account'].'</accountID>
				<reportRequest>
					<reportName>'.$request['name'].'</reportName>
					<reportType>'.$request['type'].'</reportType>
					<startDate>'.$request['start_date'].'</startDate>
					<endDate>'.$request['end_date'].'</endDate>
				</reportRequest>
				<fileOutputFormat>
					<zipped>true</zipped>
					<fileOutputType>TSV</fileOutputType>
				</fileOutputFormat>
			</addReportRequest>'
			, 'out', null));
	}
	
	public function schedule_report($request)
	{
		$this->service = 'BasicReportService';
		
		// if there's no account, run a master report
		if (empty($request['account']))
			return $this->schedule_master_report($request);
		else
			return $this->schedule_account_report($request);
	}
	
	public function get_report_status($report_id, $status_or_url = 'status')
	{
		$this->service = 'BasicReportService';
		
		$data = $this->get_request('
			<getReportDownloadUrl>
				<reportID>'.$report_id.'</reportID>
			</getReportDownloadUrl>'
			, 'out', null);
		
		return @$data['reportStatus'];
	}
	
	public function get_report_url($report_id)
	{
		$this->service = 'BasicReportService';
		
		$data = $this->get_request('
			<getReportDownloadUrl>
				<reportID>'.$report_id.'</reportID>
			</getReportDownloadUrl>'
			, 'out', null);
		
		return @$data['downloadUrl'];
	}
	
	protected function run_report_wrapper($scope, $start_date, $end_date, $flags)
	{
		// run content report
		$request = $this->build_report_request($scope, $start_date, $end_date, 'Content');
		$content_report = $this->run_report($request, $flags);
		
		// run keyword report for search
		$request = $this->build_report_request($scope, $start_date, $end_date, 'Keyword');
		$search_report = $this->run_report($request, $flags);
		
/*
		$content_report = \epro\REPORTS_PATH.'y/'.'y_account_22774889636_AdSummaryByDay_2012-01-22.dat';
		$search_report = \epro\REPORTS_PATH.'y/'.'y_account_22774889636_AdKeywordSummaryByDay_2012-01-22.dat';
*/
		
		// we have to loop over and manipulate the report data to concatenate it
		// may as well standardize it while we're at it
		// concatenate content data to end of keyword data
		return $this->concatenate_and_standardize_report($content_report, $search_report);
	}
	
	public function run_account_report($eac_id, $start_date, $end_date = '', $flags = null)
	{
		$this->eac_id = $eac_id;
		return $this->run_report_wrapper('account', $start_date, $end_date, $flags);
	}
	
	public function run_master_report($start_date, $end_date = '', $flags = null)
	{
		return $this->run_report_wrapper('master', $start_date, $end_date, $flags);
	}
	
	public function run_company_report($date)
	{
		// get distinct active yahoo accounts
		$master_accounts = db::select("
			select distinct ma.id, ma.name
			from eppctwo.clients_ppc p, eppctwo.data_sources ds, eppctwo.y_accounts y, eppctwo.y_master_accounts ma
			where
				p.status = 'On' &&
				ds.market = 'y' &&
				p.client = ds.client &&
				ds.account = y.id &&
				y.master_account = ma.id
		");
		
		// set report date so building the report path below works
		$this->report_start_date = $date;
		$company_report_path = $this->init_company_report($date);
		$report_flags = REPORT_SHOW_STATUS | REPORT_NO_HEADERS | REPORT_DELETE_ZIP;
		for ($i = 0, $count = count($master_accounts); list($ac_id, $ac_name) = $master_accounts[$i]; ++$i)
		{
			echo "master account ".($i+1)." of $count ($ac_id, $ac_name)\n";
			$this->update_report_status($this->market, $date, "Running ".($i+1)." of $count ($ac_id, ".date('H:i:s').")");
			
			// run the report
			$this->set_master($ac_id);
			$report_path = $this->get_report_path().$this->get_report_name('e2').'.dat';
			if (!file_exists($report_path)) $report_path = $this->run_master_report($date, $date, $report_flags);
			
			// check for errors
			if ($report_path === false)
			{
				echo "ERROR, no report path\n";
				continue;
			}
			
			// append it to the company report
			exec('cat '.$report_path.' >> '.$company_report_path);
		}
		// make sure there is a newline at the end
		exec('echo >> '.$company_report_path);
		
		return $company_report_path;
	}
	
	private function define_report_constants()
	{
		if (defined('Y_AC_ID')) return;
		
		define('Y_AC_ID', 1);
	}																																																																																																																																																																																																																									
	
	private function set_content_data(&$data, $report_path)
	{
		// this is the only place we should ever need to use jajoo's "tactic id"
		// seems a waste to define() these at the top when this is the only time they'll ever be used, so we just set a local var here
		$tactic_content_id = 24;
		$tactic_col = 4;
		
		$data = array();
		$cols = null;
		$lines = file($report_path);
		
		for ($i = Y_REPORT_START_ROW, $num_lines = count($lines); $i < $num_lines; ++$i)
		{
			$line = rtrim($lines[$i]);
			if (empty($line)) continue;
			
			$d = explode("\t", $line);
			if (empty($cols))
			{
				$with_convs = ((count($d) > Y_REPORT_CONTENT_NO_CONVS_COLS) ? 'with' : 'no').'_convs';
				$cols = api_translate::define_report_columns($this->market, 'content', $with_convs);
			}
			if ($d[$tactic_col] == $tactic_content_id)
			{
				$data[] = api_translate::standardize_report_data($this->market, $d, $cols, 'content');
			}
		}
	}
	
	private function set_search_data(&$data, $report_path)
	{
		$data = array();
		$cols = null;
		$lines = file($report_path);
		
		for ($i = Y_REPORT_START_ROW, $num_lines = count($lines); $i < $num_lines; ++$i)
		{
			$line = rtrim($lines[$i]);
			if (empty($line)) continue;
			
			$d = explode("\t", $line);
			if (empty($cols))
			{
				$with_convs = ((count($d) > Y_REPORT_SEARCH_NO_CONVS_COLS) ? 'with' : 'no').'_convs';
				$cols = api_translate::define_report_columns($this->market, 'search', $with_convs);
			}
			$data[] = api_translate::standardize_report_data($this->market, $d, $cols, 'search');
		}
	}
	
/*
y content with convs
0Campaign Id
1Ad Group Id
2Ad Id
3Url Id
4Tactic Id
5Date
6Account
7Account Id
8Campaign
9Ad Group
10Ad
11Url
12Quality Index
13Impressions
14Clicks
15CTR
16CPC
17Conversions
18Click Conv Rate
19Cost per Conv
20Revenue
21ROAS
22Cost
23Assists
24Average Position

y search with convs
0Campaign Id
1Ad Group Id
2Keyword Id
3Ad Id
4Url Id
5Date
6Tactic Id
7Account
8Account Id
9Campaign
10Ad Group
11Keyword
12Ad
13Url
14Quality Index
15Impressions
16Clicks
17CTR
18CPC
19Conversions
20Click Conv Rate
21Cost per Conv
22Revenue
23ROAS
24Cost
25Assists
26Average Position

y search no convs
0Campaign Id
1Ad Group Id
2Keyword Id
3Ad Id
4Url Id
5Date
6Tactic Id
7Account
8Account Id
9Campaign
10Ad Group
11Keyword
12Ad
13Url
14Quality Index
15Impressions
16Clicks
17CTR
18CPC
19Cost
20Average Position

y content no convs
0Campaign Id
1Ad Group Id
2Ad Id
3Url Id
4Tactic Id
5Date
6Account
7Account Id
8Campaign
9Ad Group
10Ad
11Url
12Quality Index
13Impressions
14Clicks
15CTR
16CPC
17Cost
18Average Position
*/
	
	public function concatenate_and_standardize_report($content_report, $search_report, $flags = null)
	{
		$this->set_content_data($content_data, $content_report);
		$this->set_search_data($search_data, $search_report);
		// unlink content and search reports?
		
		$headers = ($flags & REPORT_NO_HEADERS) ? '' : api_translate::get_report_columns_string();
		$data = array_merge($content_data, $search_data);
		
		$file_path = $this->get_report_path().$this->get_report_name('e2').'.dat';
		file_put_contents($file_path, $headers."\n".implode("\n", $data));
		
		return $file_path;
	}
	
	public function standardize_report($report_path)
	{
	}
}

?>