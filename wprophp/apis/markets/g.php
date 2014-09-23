<?php

/**
 * 
 */

define('G_API_LOCATION', 'https://adwords.google.com/api/adwords/');
define('G_API_SANDBOX', 'https://sandbox.google.com/api/adwords/');

define('G_API_UTILITY_VERSION', 'v201402');
define('G_ADWORDS_BASE_PATH', \epro\WPROPHP_PATH.'apis/adwords/');
define('G_ADWORDS_SRC_PATH', G_ADWORDS_BASE_PATH.'src/');

define('G_ROOT_ACCOUNT', '4954769138');

// path to google implementation of api
set_include_path(get_include_path() . PATH_SEPARATOR . G_ADWORDS_SRC_PATH);

class g_api extends base_market
{
	
	private $is_cross_client_report;
	
	private $user;
	
	/*
	 * from api docs:
	 * RateExceededError
	 * Recommended handling tips
	 * Wait for about 30 seconds, then retry the request.
	 * see also: http://adwordsapi.blogspot.com/2010/06/better-know-error-rateexceedederror.html
	 * 
	 * I believe we are hitting this when running reports
	 * Should be inspecting the exception
	 */
	const RATE_EXCEED_SLEEP = 45;
	
	// how many times to try a report
	const COMPANY_REPORT_MAX_TRIES = 2;
	
	
	// we still build report in schedule_report
	// for historical reasons (report request info is passed in)
	// even though we don't actually schedule anything anymore
	private $report_def;
	
	public function __construct($company, $ac_id = '')
	{
		parent::__construct('g', $company);
		$this->set_account($ac_id);
		$this->set_version(G_API_UTILITY_VERSION);
		$this->units = $this->operations = 0;
	}
	
	public function set_tokens(&$api_info)
	{
		$this->set_key('developer', $api_info['dev_token']);
		$this->set_key('application', $api_info['app_token']);
	}
	
	public function whoami()
	{
		echo "
			------
			whoami
			------
			google (".$this->get_version().")
			account=$this->ac_id
			app=".$this->get_key('application')."
			mcc=$this->api_user
		";
	}
	
	public function get_units()
	{
		return ($this->units);
	}
	
	public function get_operations()
	{
		return ($this->operations);
	}
	
	protected function get_header()
	{
		if (empty($this->ac_id) || ($this->service == 'ReportService' && $this->is_cross_client_report))
		{
			$ml_account = '';
		}
		else
		{
			// can either use account id or email, if there's an "@", it's an email, otherwise assume it's an id
			if (strpos($this->ac_id, '@') !== false)
			{
				$ml_account = '<clientEmail>'.$this->ac_id.'</clientEmail>';
			}
			else
			{
				$ml_account = '<clientCustomerId>'.$this->ac_id.'</clientCustomerId>';
			}
		}
		
		$dev_token = ($this->sandbox) ? ($this->api_user.'++USD') : $this->get_key('developer');
		$ml_app_token = ($this->sandbox) ? '' : ('<applicationToken>'.$this->get_key('application').'</applicationToken>');
		
		return ('
			<email>'.$this->api_user.'</email>
			<password>'.$this->api_pass.'</password>
			<useragent>WproPHP Google Agent 0.0.1</useragent>
			<developerToken>'.$dev_token.'</developerToken>
			'.$ml_app_token.'
			'.$ml_account.'
		');
	}
	
	protected function get_endpoint()
	{
		$url_base = ($this->sandbox) ? G_API_SANDBOX : G_API_LOCATION;
		return ($url_base.$this->version.'/'.$this->service);
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
		}
		return false;
	}
	
	public function get_accounts($ac_id = null)
	{
		try
		{
			if (!$ac_id)
			{
				$ac_id = G_ROOT_ACCOUNT;
			}
			$this->set_account($ac_id);
			$this->set_adwords_user();

      		$service = $this->user->GetService('ManagedCustomerService', G_API_UTILITY_VERSION);

			// Create selector.
			$selector = new Selector();
			// To get the links paging must be disabled.
			$selector->fields = array('Login', 'CustomerId',  'Name', 'CanManageClients', 'CurrencyCode');
			$selector->enablePaging = FALSE;
			
			// Get serviced account graph.
			$graph = $service->get($selector);
			if (!$graph || !$graph->entries || !$graph->links)
			{
				$this->error = 'No accounts';
				return false;
			}
			$accounts = array();
			foreach ($graph->entries as $account)
			{
				$accounts[$account->customerId] = $account;
			}
			foreach ($graph->links as $link)
			{
				if (array_key_exists($link->clientCustomerId, $accounts))
				{
					$accounts[$link->clientCustomerId]->parentId = $link->managerCustomerId;
				}
			}
			return $accounts;
		}
		catch (Exception $e)
		{
			$this->error = $e->getMessage();
			return false;
		}
	}


	public function set_offline_conversion($convs){
		try{
			$this->set_adwords_user();

			$tracker_service = $this->user->GetService('ConversionTrackerService',G_API_UTILITY_VERSION);
			$conversion_service = $this->user->GetService('OfflineConversionFeedService',G_API_UTILITY_VERSION);

			//$conv = new UploadConversion();
			$batch_ops = array();
			foreach($convs as $conv){
				$feed = new OfflineConversionFeed();
				$feed->conversionName = $conv['name'];
				$feed->conversionTime = $conv['time'];
				$feed->conversionValue = $conv['value'];
				$feed->googleClickId = $conv['gclid'];

				$op = new OfflineConversionFeedOperation();
				$op->operator = 'ADD';
				$op->operand = $feed;
				$batch_ops[] = $op;
			}
			return $conversion_service->mutate($batch_ops);	

			//offline_conv_srvc->mutate(array(new OfflienConversionFeedOperation))
			//get result from offline_conv_srvc


		}catch(Exception $e){
			return $e;
		}
	}




	public function get_report_field_types($report_type){
		try {
			$this->set_adwords_user();
			// Get the service, which loads the required classes.
			$reportDefinitionService = $this->user->GetService('ReportDefinitionService', G_API_UTILITY_VERSION);
			$reportDefinitionFields = $reportDefinitionService->getReportFields($report_type);

			// Display results.
			printf("The report type '%s' contains the following fields:\n", $report_type);
			
			foreach ($reportDefinitionFields as $reportDefinitionField) {
				printf('  %s (%s)', $reportDefinitionField->fieldName,
				$reportDefinitionField->fieldType);
				if (isset($reportDefinitionField->enumValues)) {
					printf(' := [%s]', implode(', ', $reportDefinitionField->enumValues));
				}   
				print "\n";
			}
		} catch (Exception $e) {
			printf("An error has occurred: %s\n", $e->getMessage());
		}
	}
	
	public function get_ad_group($ag_id)
	{
		try
		{
			$this->set_adwords_user();

			$service = $this->user->GetAdGroupService(G_API_UTILITY_VERSION);
			$selector = new Selector();
			$selector->fields = array('Id', 'CampaignId', 'Name', 'KeywordMaxCpc', 'KeywordContentMaxCpc', 'Status');
			$selector->predicates = array(new Predicate('AdGroupId', 'IN', array((float) $ag_id)));
			$page = $service->get($selector);
			
			if (empty($page) || empty($page->entries)) return false;
			
			$ag = api_factory::new_ad_group();
			$this->standardize_ad_group($ag, $page->entries[0]);
			return $ag;
		}
		catch (Exception $e)
		{
			$this->error = $e->getMessage();
			return false;
		}
	}
	
	public function get_ad_groups($ca_id)
	{
		try
		{
			$this->set_adwords_user();

			$service = $this->user->GetAdGroupService(G_API_UTILITY_VERSION);
			
			$selector = new Selector();
			$selector->fields = array('Id', 'CampaignId', 'Name', 'KeywordMaxCpc', 'KeywordContentMaxCpc', 'Status');
			$selector->predicates = array(new Predicate('CampaignId', 'IN', array((float) $ca_id)));
			$page = $service->get($selector);
			
			if (empty($page) || empty($page->entries)) return false;
			
			$ags = array();
			foreach ($page->entries as $i => &$g_ag)
			{
				$ag = api_factory::new_ad_group($ca_id);
				$this->standardize_ad_group($ag, $g_ag);
				$ags[] = $ag;
			}
			return $ags;
		}
		catch (Exception $e)
		{
			$this->error = $e->getMessage();
			return false;
		}
	}
	
	public function get_ads($ag_id)
	{
		try {
			$this->set_adwords_user();
			
			$service = $this->user->GetAdGroupAdService(G_API_UTILITY_VERSION);
			
			$selector = new Selector();
			$selector->fields = array('Id', 'AdGroupId', 'Headline', 'Description1', 'Description2', 'DisplayUrl', 'Url', 'Status', 'AdGroupCreativeApprovalStatus', 'AdGroupAdDisapprovalReasons');
			$selector->predicates = array(new Predicate('AdGroupId', 'IN', array((float) $ag_id)));
			$page = $service->get($selector);

			$page = $service->get($selector);
			if (empty($page) || empty($page->entries)) {
				$this->error = 'error: '.__FUNCTION__;
				return false;
			}
			$ads = array();
			foreach ($page->entries as &$entry) {
				$g_ad = &$entry->ad;
				$ad = api_factory::new_ad($ag_id, $g_ad->id, $g_ad->headline, $g_ad->description1, $g_ad->description2, $g_ad->displayUrl, $g_ad->url);
				$ad->status = api_translate::standardize_g_ad_status($entry->status);
				$ad->g_approvalStatus = $g_ad->approvalStatus;
				$ad->g_disapprovalReasons = $g_ad->disapprovalReasons;
				$ads[] = $ad;
			}
			return $ads;
		}
		catch (Exception $e) {
			$this->error = $e->getMessage();
			return false;
		}
	}
	
	private function standardize_campaign(&$ca, &$g_ca)
	{
		$ca->id = $g_ca->id;
		$ca->text = $g_ca->name;
		$ca->budget = util::micro_to_double($g_ca->budget->amount->microAmount);
		$ca->status = api_translate::standardize_g_campaign_status($g_ca->status);
	}
	
	private function standardize_ad_group(&$ag, &$g_ag)
	{
		if ($g_ag->campaignId) $ag->campaign_id = $g_ag->campaignId;
		$ag->id = (float) $g_ag->id;
		$ag->text = $g_ag->name;
		if (isset($g_ag->biddingStrategyConfiguration->bids[0]->bid->microAmount)) {
			$ag->max_cpc = util::micro_to_double($g_ag->biddingStrategyConfiguration->bids[0]->bid->microAmount);
		}
		else {
			$ag->max_cpc = 0;
		}
		// $ag->max_cpc = util::micro_to_double($g_ag->bids->keywordMaxCpc->amount->microAmount);
		if ($g_kw->bids->keywordContentMaxCpc) $ag->max_content_cpc = util::micro_to_double($g_ag->bids->keywordContentMaxCpc->amount->microAmount);
		$ag->status = api_translate::standardize_g_ad_group_status($g_ag->status);
	}
	
	private function standardize_ad(&$ad)
	{
		
	}
	
	private function standardize_keyword(&$kw, &$g_kw)
	{
		$kw->id = (float) $g_kw->criterion->id;
		$kw->text = $g_kw->criterion->text;
		$kw->match_type = ucfirst(strtolower($g_kw->criterion->matchType));
		if (isset($g_kw->biddingStrategyConfiguration->bids[0]->bid->microAmount)) {
			$kw->max_cpc = util::micro_to_double($g_kw->biddingStrategyConfiguration->bids[0]->bid->microAmount);
		}
		else {
			$kw->max_cpc = 0;
		}
		// $kw->max_cpc = util::micro_to_double($g_kw->bids->maxCpc->amount->microAmount);
		$kw->dest_url = $g_kw->destinationUrl;
		$kw->status = api_translate::standardize_g_keyword_status($g_kw->userStatus);
		$kw->use = $g_kw->criterionUse;
		
		$kw->g_firstPageCpc = util::micro_to_double($g_kw->firstPageCpc->amount->microAmount);
		$kw->g_qualityScore = $g_kw->qualityInfo->qualityScore;
	}
	
	public function get_keywords($ag_id)
	{
		try {
			$this->set_adwords_user();
			
			// Get the AdGroupCriterionService.
			$service = $this->user->GetAdGroupCriterionService(G_API_UTILITY_VERSION);

			$selector = new Selector();
			$selector->fields = array('Id', 'AdGroupId', 'Text', 'KeywordMatchType', 'MaxCpc', 'DestinationUrl', 'Status', 'QualityScore', 'FirstPageCpc');
			$selector->predicates = array(new Predicate('AdGroupId', 'IN', array((float) $ag_id)));
			$page = $service->get($selector);
			
			if (empty($page) || empty($page->entries)) return false;
			
			$kws = array();
			foreach ($page->entries as $i => &$g_kw) {
				if ($g_kw->criterionUse == 'NEGATIVE') {
					continue;
				}
				$kw = api_factory::new_keyword($ag_id);
				$this->standardize_keyword($kw, $g_kw);
				$kws[] = $kw;
			}
			
			return $kws;
		}
		catch (Exception $e) {
			$this->error = $e->getMessage();
			return false;
		}
	}
	
	public function pause_ad_group(&$ag)
	{
		return $this->update_ad_group($ag);
	}
	
	public function resume_ad_group(&$ag)
	{
		return $this->update_ad_group($ag);
	}
	
	public function update_ad_group(&$ag)
	{
		try
		{
			$this->set_adwords_user();
			
			// Get the AdGroupService.
			$adGroupService = $this->user->GetAdGroupService(G_API_UTILITY_VERSION);
			
			// Create ad group with updated status.
			$adGroup = new AdGroup();
			$adGroup->id = (float) $ag->id;
			if ($ag->status) $adGroup->status = api_translate::marketize_g_ad_group_status($ag->status);

			// Create ad group bid.
			if ($ag->max_cpc || $ag->max_content_cpc)
			{
				$adGroupBids = new ManualCPCAdGroupBids();
				if ($ag->max_cpc) $adGroupBids->keywordMaxCpc = new Bid(new Money(util::double_to_micro($ag->max_cpc)));
				if ($ag->max_content_cpc) $adGroupBids->keywordContentMaxCpc = new Bid(new Money(util::double_to_micro($ag->max_content_cpc)));
				$adGroup->bids = $adGroupBids;
			}
			
			// Create operations.
			$operation = new AdGroupOperation();
			$operation->operand = $adGroup;
			$operation->operator = 'SET';

			$operations = array($operation);

			// Update ad group.
			$result = $adGroupService->mutate($operations);
			if (empty($result) || empty($result->value))
			{
				$this->error = 'error: '.__FUNCTION__;
				return false;
			}
			return true;
		}
		catch (Exception $e)
		{
			$this->error = $e->getMessage();
			return false;
		}
	}
	
	public function update_ad(&$ad)
	{
		try
		{
			$this->set_adwords_user();
			
			// Get the AdGroupAdService.
			$adGroupAdService = $this->user->GetAdGroupAdService(G_API_UTILITY_VERSION);

			// Create ad with updated status.
			$g_ad = new Ad();
			$g_ad->id = (float) $ad->id;

			$adGroupAd = new AdGroupAd();
			$adGroupAd->adGroupId = (float) $ad->ad_group_id;
			$adGroupAd->ad = $g_ad;
			$adGroupAd->status = api_translate::marketize_g_ad_status($ad->status);

			// Create operations.
			$operation = new AdGroupAdOperation();
			$operation->operand = $adGroupAd;
			$operation->operator = 'SET';

			$operations = array($operation);

			// Update ad.
			$result = $adGroupAdService->mutate($operations);
			if (empty($result) || empty($result->value))
			{
				$this->error = 'error: '.__FUNCTION__;
				return false;
			}
			return true;
		}
		catch (Exception $e)
		{
			$this->error = $e->getMessage();
			return false;
		}
	}
	
	public function update_keywords(&$kws, $ag_id = null)
	{
		try
		{
			$this->set_adwords_user();
			
			$adGroupCriteriaService = $this->user->GetAdGroupCriterionService(G_API_UTILITY_VERSION);
			
			$operations = array();
			foreach ($kws as &$kw)
			{
				$market_kw = $this->marketize_keyword($kw, $ag_id, 'SET');
				
				$operation = new AdGroupCriterionOperation();
				$operation->operand = $market_kw;
				$operation->operator = 'SET';
				
				$operations[] = $operation;
			}
			// Update ad group criteria.
			$result = $adGroupCriteriaService->mutate($operations);
			if (empty($result) || empty($result->value))
			{
				$this->error = 'error: '.__FUNCTION__;
				return false;
			}
			return true;
		}
		catch (Exception $e)
		{
			$this->error = $e->getMessage();
			return false;
		}
	}
	
	public function delete_keywords($kws)
	{
		try
		{
			$this->set_adwords_user();
			
			$adGroupCriteriaService = $this->user->GetAdGroupCriterionService(G_API_UTILITY_VERSION);
			
			$operations = array();
			foreach ($kws as &$kw)
			{
				$market_kw = $this->marketize_keyword($kw, $ag_id, 'SET');
				
				$operation = new AdGroupCriterionOperation();
				$operation->operand = $market_kw;
				$operation->operator = 'REMOVE';
				
				$operations[] = $operation;
			}
			
			
			// Update ad group criteria.
			$result = $adGroupCriteriaService->mutate($operations);
			
			return $result;
		}
		catch (Exception $e)
		{
			$this->error = $e->getMessage();
			return false;
		}
			
	}
	
	public function add_campaign($d)
	{
		try
		{
			$this->set_adwords_user();

			// Get the CampaignService.
			$campaignService = $this->user->GetCampaignService(G_API_UTILITY_VERSION);

			// Create campaign.
			$campaign = new Campaign();
			$campaign->name = $d['text'];
			if (array_key_exists('status', $d)) $campaign->status = $d['status'];
			$campaign->biddingStrategy = new ManualCPC();

			$budget = new Budget();
			$budget->period = strtoupper(($d['period']) ? $d['period'] : 'DAILY');
			$budget->amount = new Money(util::double_to_micro($d['budget']));
			$budget->deliveryMethod = 'STANDARD';
			$campaign->budget = $budget;

			$networkSetting = new NetworkSetting();
			$networkSetting->targetGoogleSearch = true;
			$networkSetting->targetSearchNetwork = true;
			$networkSetting->targetContentNetwork = false;
			$networkSetting->targetContentContextual = false;
			$networkSetting->targetPartnerSearchNetwork = false;
			$campaign->networkSetting = $networkSetting;
			
			// Create operations.
			$operation = new CampaignOperation();
			$operation->operand = $campaign;
			$operation->operator = 'ADD';

			$operations = array($operation);

			$result = $campaignService->mutate($operations);
			
			if (empty($result) || empty($result->value))
			{
				$this->error = 'error: '.__FUNCTION__;
				return false;
			}
			return api_translate::standardize_campaign('g', $result->value[0]);
		}
		catch (Exception $e)
		{
			$this->error = $e->getMessage();
			return false;
		}
	}
	
	public function add_ad_group(&$ag)
	{
		try
		{
			$this->set_adwords_user();
			
			$adGroupService = $this->user->GetAdGroupService(G_API_UTILITY_VERSION);
			
			// Create ad group.
			$adGroup = new AdGroup();
			$adGroup->name = $ag->text;
			if ($ag->status) $adGroup->status = api_translate::marketize_g_ad_group_status($ag->status);
			$adGroup->campaignId = $ag->campaign_id;

			// Create ad group bid.
			$adGroupBids = new ManualCPCAdGroupBids();
			$adGroupBids->keywordMaxCpc = new Bid(new Money(util::double_to_micro($ag->max_cpc)));
			if ($ag->max_content_cpc) $adGroupBids->keywordContentMaxCpc = new Bid(new Money(util::double_to_micro($ag->max_content_cpc)));
			$adGroup->bids = $adGroupBids;

			// Create operations.
			$operation = new AdGroupOperation();
			$operation->operand = $adGroup;
			$operation->operator = 'ADD';

			$operations = array($operation);

			// Add ad group.
			$result = $adGroupService->mutate($operations);
			if (empty($result) || empty($result->value))
			{
				$this->error = 'error: '.__FUNCTION__;
				return false;
			}
			
			$g_ad_group = &$result->value[0];
			$ag->id = (float) $g_ad_group->id;
			
			return true;
		}
		catch (Exception $e)
		{
			$this->error = $e->getMessage();
			return false;
		}
	}
	
	public function add_ad(&$ad)
	{
		try
		{
			$this->set_adwords_user();
			
			$adGroupAdService = $this->user->GetAdGroupAdService(G_API_UTILITY_VERSION);			
			
			// Create text ad.
			$textAd = new TextAd();
			$textAd->headline = $ad->text;
			$textAd->description1 = $ad->desc_1;
			$textAd->description2 = $ad->desc_2;
			$textAd->displayUrl = $ad->disp_url;
			$textAd->url = $ad->dest_url;
			
			// Create ad group ad.
			$textAdGroupAd = new AdGroupAd();
			$textAdGroupAd->adGroupId = (float) $ad->ad_group_id;
			$textAdGroupAd->ad = $textAd;
			
			// Create operations.
			$textAdGroupAdOperation = new AdGroupAdOperation();
			$textAdGroupAdOperation->operand = $textAdGroupAd;
			$textAdGroupAdOperation->operator = 'ADD';

			$operations = array($textAdGroupAdOperation);

			// Add ads.
			$result = $adGroupAdService->mutate($operations);
			
			if (empty($result) || empty($result->value))
			{
				$this->error = 'error: '.__FUNCTION__;
				return false;
			}
			$g_ad = &$result->value[0];
			$ad->id = (float) $g_ad->ad->id;
			$ad->status = api_translate::standardize_g_ad_status($g_ad->status);
			$ad->g_approvalStatus = $g_ad->ad->approvalStatus;
			$ad->g_disapprovalReasons = $g_ad->ad->disapprovalReasons;
			return true;
		}
		catch (Exception $e)
		{
			$this->error = $e->getMessage();
			return false;
		}
	}
	
	private function marketize_keyword($kw, $ag_id, $add_or_set)
	{
		// Create keyword.
		if ($add_or_set == 'ADD') {
			$keyword = new Keyword();
			$keyword->text = $kw->text;
			if ($kw->match_type) $keyword->matchType = strtoupper($kw->match_type);
		}
		else {
			$keyword = new Criterion((float) $kw->id);
		}
		
		// Create biddable ad group criterion.
		$keywordAdGroupCriterion = new BiddableAdGroupCriterion();
		$keywordAdGroupCriterion->adGroupId = ($ag_id) ? ((float) $ag_id) : ((float) $kw->ad_group_id);
		$keywordAdGroupCriterion->criterion = $keyword;
		
		if (isset($kw->max_cpc) && is_numeric($kw->max_cpc)) {
			// Create bids.
			$bid = new CpcBid();
			$bid->bid = new Money(($kw->max_cpc == 0) ? null : util::double_to_micro($kw->max_cpc));
			$biddingStrategyConfiguration = new BiddingStrategyConfiguration();
			$biddingStrategyConfiguration->bids[] = $bid;
			$keywordAdGroupCriterion->biddingStrategyConfiguration = $biddingStrategyConfiguration;
		}
		if ($kw->dest_url) {
			$keywordAdGroupCriterion->destinationUrl = $kw->dest_url;
		}
		if ($kw->status) {
			$keywordAdGroupCriterion->userStatus = api_translate::marketize_g_keyword_status($kw->status);
		}
		
		return $keywordAdGroupCriterion;
	}
	
	public function add_keywords(&$kws, $ag_id = null)
	{
		try
		{
			$this->set_adwords_user();
			
			// Get the AdGroupCriterionService.
			$adGroupCriteriaService = $this->user->GetAdGroupCriterionService(G_API_UTILITY_VERSION);
			
			$operations = array();
			foreach ($kws as $i => &$kw)
			{
				$g_kw = $this->marketize_keyword($kw, $ag_id, 'ADD');
				
				// Create operations.
				$operation = new AdGroupCriterionOperation();
				$operation->operand = $g_kw;
				$operation->operator = 'ADD';
				
				$operations[] = $operation;
			}
			
			// Add ad group criteria.
			$result = $adGroupCriteriaService->mutate($operations);
			if (empty($result) || empty($result->value))
			{
				$this->error = 'error: '.__FUNCTION__;
				return false;
			}
			
			$kws = array();
			foreach ($result->value as &$val)
			{
				$kw = api_factory::new_keyword();
				$this->standardize_keyword($kw, $val);
				$kws[] = $kw;
			}
			return true;
		}
		catch (Exception $e)
		{
			$this->error = $e->getMessage();
			return false;
		}
	}
	
	private function get_auth_file()
	{
		return (G_ADWORDS_BASE_PATH . 'auth.ini');
	}

	private function get_settings_file()
	{
		return (G_ADWORDS_BASE_PATH . (($this->sandbox) ? 'settings_sandbox.ini' : 'settings_production.ini'));
	}
	
	private function set_adwords_user()
	{
		require_once 'Google/Api/Ads/AdWords/Lib/AdWordsUser.php';
		
		#Logger::$FILE_PREFIX = base_soap::$log_user;
		
		if ($this->sandbox)
		{
			$dev_token = $this->api_user.'++USD';
			$app_token = 'sandbox';
		}
		else
		{
			$dev_token = $this->get_key('developer');
			$app_token = $this->get_key('application');
		}
		
		$this->user = new AdWordsUser($this->get_auth_file(), null, null, null, null, null, $this->ac_id, $this->get_settings_file());
		
		// Log SOAP XML request and response.
		$this->user->LogAll();
	}
	
	public function get_campaigns()
	{
		try
		{
			$this->set_adwords_user();

			$campaignService = $this->user->GetCampaignService(G_API_UTILITY_VERSION);
			$selector = new Selector();
			$selector->fields = array('Id', 'Name', 'Status', 'ServingStatus', 'Period', 'Amount', 'DeliveryMethod', 'Settings');
			$page = $campaignService->get($selector);
			
			if (empty($page) || empty($page->entries)) return false;
			
			$cas = array();
			foreach ($page->entries as $i => &$g_ca)
			{
				$ca = api_factory::new_campaign();
				$this->standardize_campaign($ca, $g_ca);
				$cas[] = $ca;
			}
			return $cas;
		}
		catch (Exception $e)
		{
			$this->error = $e->getMessage();
			return false;
		}
	}
	
	public function init_sandbox()
	{
		return $this->get_campaigns();
	}
	
	/**
	 * schedule_report(array $request)
	 * @todo rename this method to set_report_columns or something more helpful
	 */
	public function schedule_report($request)
	{
		switch ($this->report_type)
		{
			// CAMPAIGN_PERFORMANCE_REPORT
			// todo: conv value and mpc convs order reversed here - change to this order in other reports (easier match with bing)
			// * note, turns out this is not quite a true fact, as bing does not actually have a campaign report with everything we need
			case ('Campaign'):
				$fields = array(
					'Date', 'Id',
					'Impressions', 'Clicks', 'Conversions', 'Cost', 'AveragePosition', 'TotalConvValue', 'ConversionsManyPerClick', 'ViewThroughConversions'
				);
				break;
			
			case ('Extensions'):
			// placeholder feed columns
			// Cannot select a combination of Clicks and ConversionCategory,ConversionCategoryName,ConversionTypeId,ConversionTypeName
			// Cannot select a combination of ClickType and IsSelfAction
			// no view through?
				$fields = array(
					'PlaceholderType', 'FeedId', 'FeedItemId', 'AttributeValues', 'Date', 'CampaignId',
					'Impressions', 'Clicks', 'Conversions', 'Cost', 'AveragePosition', 'ConversionsManyPerClick', 'TotalConvValue'
				);
				break;
			
			// AD_PERFORMANCE_REPORT
			case ('Ad'):
				$fields = array(
					'Date', 'CampaignId', 'CampaignName', 'AdGroupId', 'AdGroupName', 'Id', 'KeywordId', 'Device',
					'Impressions', 'Clicks', 'Conversions', 'Cost', 'AveragePosition', 'ConversionsManyPerClick', 'TotalConvValue', 'ViewThroughConversions'
				);
				break;
			
			// AD_PERFORMANCE_REPORT
			case ('Ad_Structure'):
				$fields = array(
					'Impressions',
					'CampaignId', 'AdGroupId', 'Id',
					'Headline', 'Description1', 'Description2', 'DisplayUrl', 'Url', 'Status'
				);
				break;
			
			// AD_PERFORMANCE_REPORT
			case ('Named_Conversions'):
				$fields  = array(
					'Date', 'CampaignId', 'AdGroupId', 'Id', 'KeywordId', 'Device',
					'Conversions', 'ConversionsManyPerClick', 'ConversionCategoryName', 'ConversionTypeName'
				);
				break;

			// KEYWORDS_PERFORMANCE_REPORT
			case ('Keyword'):
				$fields = array('CampaignId', 'AdGroupId', 'Id', 'KeywordMatchType', 'KeywordText', 'MaxCpc', 'FirstPageCpc', 'QualityScore', 'Impressions');
				break;
				
			//SHOPPING_PERFORMANCE_REPORT
			case ('Shopping'):
				$fields = array('Date','CampaignId', 'AdGroupId', 'Clicks', 'Conversions', 'ConversionsManyPerClick', 'ConversionValue', 'Cost', 'Impressions','ClickType','Brand');
				break;
		}
		
		try
		{
			$this->set_adwords_user();
			$this->user->LoadService('ReportDefinitionService', G_API_UTILITY_VERSION);
			
			// Create report definition.
			$this->report_def = new ReportDefinition();
			
			$selector = new Selector();
			$selector->fields = $fields;
			
			$selector->dateRange = new DateRange(str_replace('-', '', $request['start_date']), str_replace('-', '', $request['end_date']));
			$this->report_def->dateRangeType = 'CUSTOM_DATE';
			
			$this->report_def->selector = $selector;
			$this->report_def->reportName = $request['name'];
			$this->report_def->reportType = $request['type'];
			$this->report_def->downloadFormat = 'GZIPPED_CSV';
			$this->report_def->includeZeroImpressions = FALSE;
			return true;
		}
		catch (Exception $e)
		{
			$this->error = $e->getMessage();
			return false;
		}
	}
	
	public function download_report($report_url, $path, $do_show_status)
	{
		require_once(G_ADWORDS_SRC_PATH.'Google/Api/Ads/AdWords/Util/ReportUtils.php');
		
		try
		{
			$options = array('version' => G_API_UTILITY_VERSION, 'returnMoneyInMicros' => false);

			// Download report.
			return ReportUtils::DownloadReport($this->report_def, $path, $this->user, $options);
		}
		catch (Exception $e)
		{
			$this->error = $e->getMessage();
			$this->exception = $e;
			return false;
		}
	}
	
	public function get_report_status($report_id)
	{
		// do not need to get status of google reports anymore
		// see http://code.google.com/apis/adwords/docs/reportingtopics.html#downloading
		return 'Completed';
	}
	
	public function get_report_url($report_id)
	{
		// do not need to get url of google reports anymore
		// see http://code.google.com/apis/adwords/docs/reportingtopics.html#downloading
		return $report_id;
	}
	
	public function run_account_report($eac_id, $start_date, $end_date = '', $flags = null)
	{
		$this->set_eac_id($eac_id);
		$this->set_client_account_info();

		$this->run_shopping_report($start_date, $end_date);

		// first get campaign data
		$request = $this->build_report_request('Account', $start_date, $end_date, 'Campaign');
		$campaign_report = (class_exists('cli') && !empty(cli::$args['c'])) ? $this->get_report_path_from_cli(cli::$args['c']) : $this->run_report($request, $flags);
		if ($campaign_report === false) {
			return false;
		}
		// get old data before processing new data
		$cached_ca_data = $this->get_cached_ca_data($start_date, $end_date);
		$new_ca_data = $this->process_campaign_report($campaign_report);
		if ($new_ca_data === false) {
			return false;
		}
		// this can be moved below start date check once extension data has been seeded
		// extensions report
		$this->run_extensions_report($start_date, $end_date);

		$refresh_dates = $this->pre_data_report($cached_ca_data, $new_ca_data);
		// nothing has changed, no need to run 
		if (empty($refresh_dates)) {
			return true;
		}

		list($start_date, $end_date) = $this->get_all_data_report_dates($refresh_dates, $start_date, $end_date);
		if ($start_date === false) {
			// maybe not what user wanted, but we ran campaign data report above
			return true;
		}
		// run named conversion report
		if ($this->account_type(array('conversion_types'))) {
			$r = $this->run_conv_types_report($start_date, $end_date, $flags);
			if ($r === false) {
				return false;
			}	
		}
		
		// ad report to get all data
		$request = $this->build_report_request('Account', $start_date, $end_date, 'Ad');
		$ad_report = (class_exists('cli') && !empty(cli::$args['z'])) ? $this->get_report_path_from_cli(cli::$args['z']) : $this->run_report($request, $flags);
		if ($ad_report === false) {
			return false;
		}
		// run keyword report to get keyword text, match types, etc
		$request = $this->build_report_request('Account', $start_date, $end_date, 'Keyword');
		$keyword_report = (class_exists('cli') && !empty(cli::$args['k'])) ? $this->get_report_path_from_cli(cli::$args['k']) : $this->run_report($request, $flags);
		if ($keyword_report === false) {
			return false;
		}
		
		// standardize
		return $this->process_ad_and_keyword_reports($ad_report, $keyword_report, $refresh_dates);
	}
	
	public static function get_extension_type_name($placeholder_type)
	{
		switch ($placeholder_type) {
			case (1): return 'Sitelink';
			case (2): return 'Call';
			case (9): return 'Social';
			// known unknowns
			case (5):
			case (1000005):
				return $placeholder_type;
			// unknown unknowns
			default: return $placeholder_type.'?';
		}
	}

	private function parse_extension_call($x)
	{
		$pos = strpos($x, ';');
		if ($pos !== false) {
			return trim(substr($x, 0, $pos));
		}
		else {
			return $x;
		}
	}

	private function parse_extension_sitelink($x)
	{
		// remove url from sitelinks
		return trim(preg_replace("/;\s+http.*?(;|$)/", '$1', $x));
	}

	private function run_shopping_report($start_date, $end_date){
		$request = $this->build_report_request('Account', $start_date, $end_date, 'Shopping');
		$shopping_report = (class_exists('cli') && !empty(cli::$args['k'])) ? $this->get_report_path_from_cli(cli::$args['k']) : $this->run_report($request);
		if ($ext_report === false) {
			return false;
		}


		$h = fopen($shopping_report,'rb');
		if ($h === false) {
			$this->error = 'Could not open extensions report ('.$ext_report.')';
			return false;
		}
		$data_table = "{$this->market}_objects.shopping_{$this->eac_id}";

		//should i clear out old data or perform insert update?
		$new_data = array();
		$shopping_data = array();
		$line_count = $this->file_get_line_count($h);
		$delimiter = $this->get_rep_delimiter();


		for ($i = 0; ($data = fgetcsv($h, 0, $delimiter)) !== FALSE; ++$i) {
			if ($i % 1000 == 0) util::update_job_details($this->job, 'Processing campaign data '.$i.' / '.$line_count);
			// order from defining rep columns

			//array('Date','CampaignId', 'AdGroupId', 'Clicks', 'Conversions', 'ConversionValue','Cost', 'Impressions','ClickType','Brand');
			list($date, $ca_id, $ad_grp_id, $clicks, $convs, $mpc_convs, $rev, $cost, $imps, $type, $brand) = $data;
			//list($date, $ca_id, $imps, $clicks, $convs, $cost, $ave_pos, $rev, $mpc_convs, $vt_convs) = $data;
			$dtest = strtotime($date);
			if (is_numeric($ca_id) && $dtest !== false) {

				if (!$this->does_data_belong_to_client($ca_id)) {
					continue;
				}

				// bing dates are a different format, standardize
				$date = date(util::DATE, $dtest);

				// check ex rates
				if ($this->ex_rates && isset($this->ex_rates[$date])) {
					$cost *= $this->ex_rates[$date];
				}
				if (!isset($new_data[$date])) {
					$new_data[$date] = array('cost' => 0, 'convs' => 0, 'mpc_convs' => 0);
				}
				$new_data[$date]['cost'] += $cost;
				$new_data[$date]['convs'] += $convs;
				$new_data[$date]['mpc_convs'] += $mpc_convs;

				db::insert_update($data_table, array('data_date', 'campaign_id','type','brand'), array(
					'job_id' => $this->job->id,
					'account_id' => $this->ac_id,
					'campaign_id' => $ca_id,
					'ad_group_id' => $ad_grp_id,
					'data_date' => $date,
					'type'=>$type,
					'brand'=>$brand,
					'imps'=>$imps,
					'clicks'=>$clicks,
					'convs'=>$convs,
					'mpc_convs'=>$mpc_convs,
					'cost'=>str_replace(',', '', $cost),
					'revenue'=>str_replace(',', '', $rev)
				));
			}
		}
	}

	private function run_extensions_report($start_date, $end_date)
	{
		// do not need to run extension reports for ql
		if ($this->is_ql_account()) {
			return true;
		}

		$request = $this->build_report_request('Account', $start_date, $end_date, 'Extensions');
		$ext_report = (class_exists('cli') && !empty(cli::$args['e'])) ? $this->get_report_path_from_cli(cli::$args['e']) : $this->run_report($request);
		if ($ext_report === false) {
			return false;
		}

		// process report
		$h = fopen($ext_report, 'rb');
		if ($h === false) {
			$this->error = 'Could not open extensions report ('.$ext_report.')';
			return false;
		}
		$data_table = "{$this->market}_objects.extension_data_{$this->eac_id}";

		// clear out old data
		db::delete(
			$data_table,
			"
				data_date between :start_date and :end_date &&
				account_id = :aid
			",
			array(
				'start_date' => $this->report_start_date,
				'end_date' => $this->report_end_date,
				'aid' => $this->ac_id
			)
		);

		// call data has some other fields we don't care about, which segments the data
		// we want to group it
		// let's just group all data
		$ext_data = array();
		$data_keys = array('imps', 'clicks', 'convs', 'cost', 'pos_sum', 'mpc_convs', 'revenue', 'vt_convs');
		for ($i = 0; ($data = fgetcsv($h)) !== FALSE; ++$i) {
			// column order from schedule report
			list($placeholder_type, $feed_id, $feed_item_id, $attr_vals, $date, $ca_id, $imps, $clicks, $convs, $cost, $ave_pos, $mpc_convs, $revenue) = $data;

			if (!is_numeric($ca_id) || !$this->does_data_belong_to_client($ca_id)) {
				continue;
			}

			// set/update some values
			$vt_convs = 0;
			$cost = str_replace(',', '', $cost);
			$revenue = str_replace(',', '', $revenue);
			$type = $this->get_extension_type_name($placeholder_type);
			$pos_sum = $imps * $ave_pos;
			if ($this->ex_rates && isset($this->ex_rates[$date])) {
				$cost *= $this->ex_rates[$date];
			}

			// get val depending on type
			$ext_val_func = 'parse_extension_'.strtolower($type);
			if (method_exists($this, $ext_val_func)) {
				$val = $this->$ext_val_func($attr_vals);
			}
			else {
				$val = $attr_vals;
			}
			// group data
			foreach ($data_keys as $data_key) {
				$ext_data[$date][$type][$ca_id][$val][$data_key] += $$data_key;
			}
		}

		foreach ($ext_data as $date => &$date_data) {
			foreach ($date_data as $type => &$type_data) {
				foreach ($type_data as $ca_id => &$ca_data) {
					foreach ($ca_data as $val => &$val_data) {
						db::insert($data_table, array_merge($val_data, array(
							'account_id' => $this->ac_id,
							'campaign_id' => $ca_id,
							'type' => $type,
							'extension' => $val,
							'data_date' => $date,
						)));
					}
				}
			}
		}
		fclose($h);
		return true;
	}

	public function run_conv_types_report($start_date, $end_date = '', $flags = null)
	{
		// run our named conv report
		$request = $this->build_report_request('Account', $start_date, $end_date, 'Named_Conversions');
		$convs_report = (class_exists('cli') && !empty(cli::$args['c'])) ? $this->get_report_path_from_cli(cli::$args['c']) : $this->run_report($request, $flags);
		// error should be set by run report
		if ($convs_report === false) {
			return false;
		}

		// process report
		$h = fopen($convs_report, 'rb');
		if ($h === false) {
			$this->error = 'Could not open named conversions report ('.$convs_report.')';
			return false;
		}
		// successfully ran report, delete old data for this client for this date range
		$this->clear_old_conv_type_data($start_date, $end_date);
		for ($i = 0; ($data = fgetcsv($h)) !== FALSE; ++$i) {
			// column order from schedule report fields
			list($date, $ca_id, $ag_id, $ad_id, $kw_id, $device, $convs, $mpc_convs, $purpose, $name) = $data;
			if (is_numeric($ag_id) && is_numeric($kw_id)) {
				$amount = ($mpc_convs) ? $mpc_convs : $convs;
				$device = self::device_map($this->market, $device);
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
					'purpose' => $purpose,
					'name' => $name,
					'amount' => $amount
				));
			}
		}
		fclose($h);
		return true;
	}

	public function run_company_report($date)
	{
		// get active google account from ppc and ql
		$accounts = db::select("
			# ppc accounts
			(select distinct ds.account, g.text
			from eppctwo.clients_ppc p, eppctwo.data_sources ds, eppctwo.g_accounts g
			where
				p.status = 'On' &&
				ds.market = '{$this->market}' &&
				ds.account <> '' &&
				p.client = ds.client &&
				ds.account = g.id)
			
			union
			
			# ql accounts
			(select distinct ds.account, g.text
			from eac.ap_ql q, eac.account a, eppctwo.ql_data_source ds, eppctwo.g_accounts g
			where
				a.status in ('Active', 'NonRenewing') &&
				ds.market = '{$this->market}' &&
				ds.account <> '' &&
				q.id = a.id &&
				q.id = ds.account_id &&
				ds.account = g.id)
		");
		
		$rep_ids = array();
		$report_flags = REPORT_SHOW_STATUS | REPORT_NO_HEADERS | REPORT_DELETE_ZIP;
		for ($i = 0, $count = count($accounts); list($ac_id, $ac_name) = $accounts[$i]; ++$i)
		{
			// capital C
			if (array_key_exists('C', cli::$args) && $i < cli::$args['C']) {
				continue;
			}
			for ($j = 0; $j < self::COMPANY_REPORT_MAX_TRIES; ++$j)
			{
				echo "account ".($i + 1)." of $count, $attempt ".($j + 1)." ($ac_id, $ac_name)\n";
				$this->update_report_status($this->market, $date, "Running ".($i+1)." of $count (".date('H:i:s').")");
				
				// run the report
				$this->set_account($ac_id);
				$report_id = $this->run_account_report($date, $date, $report_flags);
				
				// check for errors
				if ($report_id !== false)
				{
					$rep_ids[] = $report_id;
					break;
				}
				// no point in sleeping right before we break
				else if (($j + 1) < self::COMPANY_REPORT_MAX_TRIES)
				{
					sleep(self::RATE_EXCEED_SLEEP);
				}
			}
			if ($j == self::COMPANY_REPORT_MAX_TRIES)
			{
				db::insert("eppctwo.g_company_report_log", array(
					'd' => $date,
					'account_id' => $ac_id,
					'details' => util::var_dump_str(array(
						'error' => $this->error,
						'exception' => $this->exception
					))
				));
			}
		}
		return $rep_ids;
	}
	
	public function process_conversion_refresh_report($report_path)
	{
		// info stuff
		util::set_active_data_sources($data_sources, $this->market);
		$data_ids = db::select("
			select id, data_id
			from clients
			where data_id <> -1
		", 'NUM', 0);
		$campaigns = array();
		$ad_groups = array();
		
		$wpropath_start_dates = array();
		
		// process file
		$clients = array();
		for ($fi = new file_iterator($report_path); $fi->next($block); )
		{
			$report_data = explode("\n", $block);
			for ($j = 0, $num_lines = count($report_data); $j < $num_lines; ++$j)
			{
				$line = $report_data[$j];
				
				if (empty($line)) continue;
				
				// is there a "better" way?
				list($ac_id, $ca_id, $ca_text, $ag_id, $ag_text, $ad_id, $kw_id, $kw_text, $kw_type, $max_cpc, $date, $imps, $clicks, $cost, $convs, $pos_ave, $revenue) = explode("\t", $line);
				
				$cl_id = util::get_client_from_data_ids($data_sources, $ac_id, $ca_id, $ag_id);
				
				$raw_data_table = str_replace('-', '_', $date);
				
				db::exec("
					update g_data_tmp.{$raw_data_table}
					set
						convs = '$convs',
						revenue = '$revenue'
					where
						ad_group = '$ag_id' &&
						ad = '$ad_id' &&
						keyword = '$kw_id'
				");
				
				if ($cl_id)
				{
					if (array_key_exists($cl_id, $wpropath_start_dates))
					{
						$wpropath_start_date = $wpropath_start_dates[$cl_id];
					}
					else
					{
						$wpropath_start_date = db::select_one("
							select g_start_date
							from eppctwo.track_account
							where client_id = '$cl_id'
						");
						$wpropath_start_dates[$cl_id] = $wpropath_start_date;
					}
					if (util::empty_date($wpropath_start_date) || $date < $wpropath_start_date)
					{
						$this->add_client_conv_data($clients, $date, $cl_id, $convs, $revenue);
						$this->add_client_conv_data($clients[$cl_id]['cas'], $date, $ca_id, $convs, $revenue);
						$this->add_client_conv_data($clients[$cl_id]['cas'][$ca_id]['ags'], $date, $ag_id, $convs, $revenue);
						$this->add_client_conv_data($clients[$cl_id]['cas'][$ca_id]['ags'][$ag_id]['ads'], $date, $ad_id, $convs, $revenue);
						$this->add_client_conv_data($clients[$cl_id]['cas'][$ca_id]['ags'][$ag_id]['kws'], $date, $kw_id, $convs, $revenue);
					}
				}
			}
		}
		
		$cl_names = db::select("
			select id, name
			from clients
		", 'NUM', 0);
		$num_clients = count($clients);
		$sum_new = 0;
		foreach ($clients as $cl_id => &$cl_info)
		{
			$data_id = $data_ids[$cl_id];
			if ($data_id == -1) continue;
			$cl_data = &$cl_info['data'];
			
			foreach ($cl_data as $date => &$d)
			{
				$num_new = db::exec("update g_data.clients_{$data_id} set convs='".$d['convs']."',revenue='".$d['revenue']."' where client='$cl_id' && data_date='$date'");
				$sum_new += $num_new;
				//if ($num_new > 0) echo $cl_names[$cl_id].', '.$date.': '.$num_new." Delayed Convs\n";
			}
				
			$cas = &$cl_info['cas'];
			foreach ($cas as $ca_id => &$ca_info)
			{
				$ca_data = &$ca_info['data'];
				foreach ($ca_data as $date => &$d)
					db::exec("update g_data.campaigns_{$data_id} set convs='".$d['convs']."',revenue='".$d['revenue']."' where campaign='$ca_id' && data_date='$date'");
			
				$ags = &$ca_info['ags'];
				foreach ($ags as $ag_id => &$ag_info)
				{
					$ag_data = &$ag_info['data'];
					foreach ($ag_data as $date => &$d)
						db::exec("update g_data.ad_groups_{$data_id} set convs='".$d['convs']."',revenue='".$d['revenue']."' where ad_group='$ag_id' && data_date='$date'");
					
					$ads = &$ag_info['ads'];
					foreach ($ads as $ad_id => &$ad_info)
					{
						$ad_data = &$ad_info['data'];
						foreach ($ad_data as $date => &$d)
							db::exec("update g_data.ads_{$data_id} set convs='".$d['convs']."',revenue='".$d['revenue']."' where ad_group='$ag_id' && ad='$ad_id' && data_date='$date'");
					}
					
					$kws = &$ag_info['kws'];
					foreach ($kws as $kw_id => &$kw_info)
					{
						$kw_data = &$kw_info['data'];
						foreach ($kw_data as $date => &$d)
							db::exec("update g_data.keywords_{$data_id} set convs='".$d['convs']."',revenue='".$d['revenue']."' where ad_group='$ag_id' && keyword='$kw_id' && data_date='$date'");
					}
				}
			}
		}
		return $sum_new;
	}
	
	private function add_client_conv_data(&$a, $date, $id, $convs, $revenue)
	{
		$a[$id]['data'][$date]['convs'] += $convs;
		$a[$id]['data'][$date]['revenue'] += $revenue;
	}
	
	private function set_report_data_values(&$node, &$vals)
	{
		$vals = array();

		// get the tag name
		preg_match("/<(\S*|\/\S*)(\s|>)/", $node, $tag_matches);
		$tag = $tag_matches[1];

		$node = substr($node, strlen($tag) + 2);
		while (true)
		{
			$index = strpos($node, "=");
			if ($index === false) break;
			$node = substr($node, $index + 1);
			$end_val = $node[0];
			if ($end_val == '"')
			{
				$node = substr($node, 1);
				for ($j = 0; $j < strlen($node); $j++)
				{
					$char = $node[$j];
					if ($char == "\\") $is_escaped = true;
					else if ($char == '"' && !$is_escaped) break;
					else $is_escaped = false;
				}
				// add key and get rid of any escapes
				$vals[] = stripslashes(substr($node, 0, $j));

				// add one to j so we start looking for next attribute after "
				$j++;
			}
			else
			{
				for ($j = 0; $j < strlen($node); $j++)
				{
					$char = $node[$j];
					if ($char == " " || $char == ">") break;
				}
				$vals[] = substr($node, 0, $j);
			}
			$all_done = false;
			for ($j = $j; $j < strlen($node); $j++)
			{
				$char = $node[$j];
				if ($char != " ") break;
				else if ($char == ">")
				{
					$all_done = true;
					break;
				}
			}
			if ($all_done) break;
			$node = substr($node, $j);
		}
	}

	private function process_ad_and_keyword_reports($ad_report, $keyword_report, &$refresh_dates)
	{
		$h = fopen($ad_report, 'rb');
		if ($h === false) {
			$this->error = 'Could not open ad report ('.$ad_report.') for standardization';
			return false;
		}

		// track which campaigns and ad group we have updated
		$campaigns = array();
		$ad_groups = array();
		$line_count = $this->file_get_line_count($h);
		for ($i = 0; ($data = fgetcsv($h)) !== FALSE; ++$i) {
			if ($i % 1000 == 0) util::update_job_details($this->job, 'Processing keyword data '.$i.' / '.$line_count);
			// column order from schedule report fields
			list($date, $ca_id, $ca_name, $ag_id, $ag_name, $ad_id, $kw_id, $device, $imps, $clicks, $convs, $cost, $ave_pos, $mpc_convs, $rev, $vt_convs) = $data;
			if (is_numeric($ag_id) && is_numeric($kw_id)) {
				// see if this is a date we need to refresh
				if (!isset($refresh_dates[$date])) {
					continue;
				}
				if (!$this->does_data_belong_to_client($ca_id, $ag_id)) {
					continue;
				}
				// get device
				$device = self::device_map($this->market, $device);
				// check ex rates
				if ($this->ex_rates && isset($this->ex_rates[$date])) {
					$cost *= $this->ex_rates[$date];
				}
				// rarely, google will report > 0 clicks with zero impressions
				if ($imps == 0 && ($clicks > 0 || $convs > 0)) {
					$imps = max($clicks, 1);
					$ave_pos = 1;
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
					'mpc_convs' => $mpc_convs,
					'vt_convs' => $vt_convs
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
			}
		}
		fclose($h);
		
		$h = fopen($keyword_report, 'rb');
		if ($h === false) {
			$this->error = 'Could not open keyword report ('.$keyword_report.') for standardization';
			return false;
		}
		$line_count = $this->file_get_line_count($h);
		for ($i = 0; ($data = fgetcsv($h)) !== FALSE; ++$i) {
			if ($i % 1000 == 0) util::update_job_details($this->job, 'Updating keyword info '.$i.' / '.$line_count);
			// column order from schedule report fields
			list($ca_id, $ag_id, $kw_id, $match_type, $kw_text, $bid, $fp_cpc, $qs, $imps) = $data;
			if (is_numeric($ag_id) && is_numeric($kw_id)) {
				if (!$this->does_data_belong_to_client($ca_id, $ag_id)) {
					continue;
				}
				// see if we actually had to update this data
				if (!isset($ad_groups[$ag_id])) {
					continue;
				}
				// for content
				if (!is_numeric($bid)) {
					$bid = 0;
				}
				db::insert_update("{$this->market}_objects.keyword_{$this->eac_id}", array('ad_group_id', 'id'), array(
					'account_id' => $this->ac_id,
					'campaign_id' => $ca_id,
					'ad_group_id' => $ag_id,
					'id' => $kw_id,
					'mod_date' => \epro\TODAY,
					'text' => $kw_text,
					'type' => $match_type,
					'max_cpc' => $bid,
					'first_page_cpc' => $fp_cpc,
					'quality_score' => $qs,
					'status' => 'On'
				));
			}
		}
		fclose($h);

		$this->post_data_report('all', $this->data_table);
		return true;
	}
	
	public function process_ad_structure_report($report_path, $target_cl_id, &$ag_info, &$new_cas, &$new_ags, $entity_type = null, $entity_ids = null)
	{
		util::set_active_data_sources($data_sources, $this->market);
		$data_ids = db::select("
			select id, data_id
			from clients
			where data_id <> -1
		", 'NUM', 0);
		
		$cas = array();
		$ags = array();
		
		$new_cas = array();
		$new_ags = array();
		
		// get info for kw report processing
		$ag_info = array();
		
		$today = date(util::DATE);
		for ($fi = new file_iterator($report_path, REPORT_READ_LEN, '>'); $fi->next($block); )
		{
			$report_data = explode("\n", str_replace("><", ">\n<", $block));
			$data_out = '';
			foreach ($report_data as $line)
			{
				if (strpos($line, '<row ') !== 0) continue;
				$this->set_report_data_values($line, $values);
				if (empty($values)) continue;
				
				list($ca_id, $ca_text, $ag_id, $ag_text, $ad_title, $ad_desc1, $ad_desc2, $ad_disp_url, $ad_id, $ad_type, $ad_status, $ca_budget, $ag_max_cpc, $ag_content_max_cpc, $ag_status, $ad_dest_url, $ca_status) = $values;
				
				if ($entity_type == 'Campaign' && !array_key_exists($ca_id, $entity_ids))
				{
					continue;
				}
				if ($entity_type == 'Ad Group' && !array_key_exists($ag_id, $entity_ids))
				{
					continue;
				}
				$cl_id = util::get_client_from_data_ids($data_sources, $this->ac_id, $ca_id, $ag_id);
				if ($cl_id != $target_cl_id) continue;
				$data_id = ($cl_id) ? @$data_ids[$cl_id] : -1;
				
				// just ignore inactive stuff for structure reports
				if (is_null($data_id) || $data_id == -1) continue;
				
				if (!array_key_exists($ca_id, $cas))
				{
					$cas[$ca_id] = 1;
					list($exists, $is_wpropathed) = db::select_row("
						select 1, is_wpropathed
						from g_info.campaigns_{$data_id}
						where campaign = '$ca_id'
					");
					
					$ca_db_data = array(
						'client' => $cl_id,
						'account' => $this->ac_id,
						'mod_date' => $today,
						'text' => $ca_text,
						'status' => api_translate::standardize_g_campaign_status($ca_status, $ph)
					);
					if ($exists)
					{
						// mark as a new campaign if not wpropathed
						if (empty($is_wpropathed))
						{
							$new_cas[$ca_id] = 1;
						}
						db::update("g_info.campaigns_{$data_id}", $ca_db_data, "campaign = '$ca_id'");
					}
					else
					{
						$new_cas[$ca_id] = 1;
						$ca_db_data['campaign'] = $ca_id;
						db::insert("g_info.campaigns_{$data_id}", $ca_db_data);
					}
					if (array_key_exists($ca_id, $new_cas))
					{
						db::insert("eppctwo.track_sync_entities", array(
							'client' => $cl_id,
							'market' => 'g',
							'entity_type' => 'Campaign',
							'entity_id0' => $ca_id,
							'sync_type' => 'New'
						));
					}
				}
				
				if (!array_key_exists($ag_id, $ags))
				{
					$ags[$ag_id] = 1;
					$ag_info[$ag_id] = array($cl_id, $data_id, $ca_id);
					$ag_db_data = array(
						'client' => $cl_id,
						'account' => $this->ac_id,
						'campaign' => $ca_id,
						'mod_date' => $today,
						'text' => $ag_text,
						'max_cpc' => api_translate::standardize_g_ad_group_max_cpc($ag_max_cpc, $ph),
						'max_content_cpc' => api_translate::standardize_g_ad_group_content_max_cpc($ag_content_max_cpc, $ph),
						'status' => api_translate::standardize_g_ad_group_status($ag_status, $ph)
					);
					list($exists, $is_wpropathed) = db::select_row("
						select 1, is_wpropathed
						from g_info.ad_groups_{$data_id}
						where ad_group = '$ag_id'
					");
					if ($exists)
					{
						// mark as a new ad group if not wpropathed
						if (empty($is_wpropathed))
						{
							$new_ags[$ag_id] = 1;
						}
						db::update("g_info.ad_groups_{$data_id}", $ag_db_data, "where ad_group = '$ag_id'");
					}
					else
					{
						$new_ags[$ag_id] = 1;
						$ag_db_data['ad_group'] = $ag_id;
						db::insert("g_info.ad_groups_{$data_id}", $ag_db_data);
					}
					if (array_key_exists($ag_id, $new_ags) && !array_key_exists($ca_id, $new_cas))
					{
						db::insert("eppctwo.track_sync_entities", array(
							'client' => $cl_id,
							'market' => 'g',
							'entity_type' => 'Ad Group',
							'entity_id0' => $ag_id,
							'sync_type' => 'New'
						));
					}
				}
				
				$ad_dest_url = strings::xml_decode($ad_dest_url);
				$ad_db_data = array(
					'client' => $cl_id,
					'account' => $this->ac_id,
					'campaign' => $ca_id,
					'mod_date' => $today,
					'text' => strings::xml_decode($ad_title),
					'desc_1' => strings::xml_decode($ad_desc1),
					'desc_2' => strings::xml_decode($ad_desc2),
					'disp_url' => strings::xml_decode($ad_disp_url),
					'status' => $ad_status
				);
				// don't overwrite dest urls with tracker urls -> only update if this is *not* a tracker url
				if (!util::is_tracker_url($ad_dest_url))
				{
					$ad_db_data['dest_url'] = $ad_dest_url;
				}
				
				list($exists, $cur_dest_url) = db::select_row("
					select 1, dest_url
					from g_info.ads_{$data_id}
					where ad_group = '$ag_id' && ad = '$ad_id'
				");
				$sync_type = null;
				if ($exists)
				{
					db::update("g_info.ads_{$data_id}", $ad_db_data, "ad_group = '$ag_id' && ad = '$ad_id'");
					if (!util::is_tracker_url($ad_dest_url) && $ad_dest_url != $cur_dest_url && !array_key_exists($ca_id, $new_cas) && !array_key_exists($ag_id, $new_ags))
					{
						$sync_type = 'Update';
					}
				}
				else
				{
					$ad_db_data['ad_group'] = $ag_id;
					$ad_db_data['ad'] = $ad_id;
					db::insert("g_info.ads_{$data_id}", $ad_db_data);
					if (!array_key_exists($ca_id, $new_cas) && !array_key_exists($ag_id, $new_ags))
					{
						$sync_type = 'New';
					}
				}
				if ($sync_type)
				{
					db::insert("eppctwo.track_sync_entities", array(
						'client' => $cl_id,
						'market' => 'g',
						'entity_type' => 'Ad',
						'entity_id0' => $ag_id,
						'entity_id1' => $ad_id,
						'sync_type' => $sync_type
					));
				}
			}
		}
	}
	
	public function process_kw_structure_report($report_path, $target_cl_id, &$ag_info, &$new_cas, &$new_ags, $entity_type = null, $entity_ids = null)
	{
		$today = date(util::DATE);
		#db::dbg_off();
		$all_count = $cl_count = 0;
		for ($fi = new file_iterator($report_path, REPORT_READ_LEN, '>'); $fi->next($block); )
		{
			$report_data = explode("\n", str_replace("><", ">\n<", $block));
			$data_out = '';
			foreach ($report_data as $line)
			{
				if (strpos($line, '<row ') !== 0) continue;
				$this->set_report_data_values($line, $values);
				if (empty($values)) continue;
				
				/*
					adgroupid
					keywordid
					keyword
					kwType
					kwisnegative
					kwStatus
					maxCpc
					maxContentCpc
					kwDestUrl
				*/

				list($ag_id, $kw_id, $kw_text, $kw_type, $kw_is_negative, $kw_status, $kw_max_cpc, $kw_max_content_cpc, $kw_dest_url) = $values;
				list($cl_id, $data_id, $ca_id) = $ag_info[$ag_id];
				if ($cl_id != $target_cl_id) continue;
				
				// ignore inactive stuff
				++$all_count;
				if (empty($cl_id))
				{
					continue;
				}
				if ($entity_type == 'Campaign' && !array_key_exists($ca_id, $entity_ids))
				{
					continue;
				}
				if ($entity_type == 'Ad Group' && !array_key_exists($ag_id, $entity_ids))
				{
					continue;
				}
				
				++$cl_count;
				#echo "$ag_id, $kw_id, $kw_text, $kw_type, $kw_is_negative, $kw_status, $kw_max_cpc\n";
				
				// weird.. why do you do this google?
				if ($kw_dest_url == 'default URL') $kw_dest_url = '';
				
				$kw_db_data = array(
					'client' => $cl_id,
					'account' => $this->ac_id,
					'campaign' => $ca_id,
					'mod_date' => $today,
					'text' => $kw_text,
					'type' => $kw_type,
					'max_cpc' => api_translate::standardize_g_keyword_max_cpc($kw_max_cpc, $ph),
					'status' => api_translate::standardize_g_keyword_status($kw_status, $ph)
				);
				
				$sync_type = null;
				
				// don't overwrite dest urls with tracker urls, and only update if this is *not* a tracker url
				$is_tracked = true;
				if (!util::is_tracker_url($kw_dest_url))
				{
					$kw_db_data['dest_url'] = $kw_dest_url;
					$is_tracked = false;
					if (!array_key_exists($ca_id, $new_cas) && !array_key_exists($ag_id, $new_ags))
					{
						$sync_type = 'Not Tracked';
					}
				}
				
				list($exists, $cur_dest_url) = db::select_row("
					select 1, dest_url
					from g_info.keywords_{$data_id}
					where ad_group = '$ag_id' && keyword = '$kw_id'
				");
				if ($exists)
				{
					db::update("g_info.keywords_{$data_id}", $kw_db_data, "ad_group = '$ag_id' && keyword = '$kw_id'");
					if (!$is_tracked && ($kw_dest_url != $cur_dest_url) && !array_key_exists($ca_id, $new_cas) && !array_key_exists($ag_id, $new_ags))
					{
						$sync_type = 'Update';
					}
				}
				else
				{
					$kw_db_data['ad_group'] = $ag_id;
					$kw_db_data['keyword'] = $kw_id;
					db::insert("g_info.keywords_{$data_id}", $kw_db_data);
					if (!array_key_exists($ca_id, $new_cas) && !array_key_exists($ag_id, $new_ags))
					{
						$sync_type = 'New';
					}
				}
				if ($sync_type)
				{
					db::insert("eppctwo.track_sync_entities", array(
						'client' => $cl_id,
						'market' => 'g',
						'entity_type' => 'Keyword',
						'entity_id0' => $ag_id,
						'entity_id1' => $kw_id,
						'sync_type' => $sync_type
					));
				}
			}
		}
	}
	
	// defined in adwords settings.ini file
	protected function set_log_user()
	{
		self::$log_user = '';
	}
	
	protected function get_ad_structure_parse_file_vars()
	{
		return array(0, ',');
	}
	
	// already in order for google
	protected function get_ad_structure_data_vars(&$data, $eac_id)
	{
		return $data;
	}

	public function run_keyword_report_only($eac_id, $start_date, $end_date = '', $flags = null){
		$this->set_eac_id($eac_id);
		$this->set_client_account_info();

		// run keyword report to get keyword text, match types, etc
		$request = $this->build_report_request('Account', $start_date, $end_date, 'Keyword');
		$keyword_report = (class_exists('cli') && !empty(cli::$args['k'])) ? $this->get_report_path_from_cli(cli::$args['k']) : $this->run_report($request, $flags);
		if ($keyword_report === false) {
			return false;
		}		
	}
}

?>
