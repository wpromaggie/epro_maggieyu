<?php
namespace phpadcenter\services\Optimizer;

class OptimizerService extends \phpadcenter\AdCenterService
{
	function __construct($soap_opts = array(), $client_opts = array())
	{
		$this->wsdl_url_prod = 'https://adcenterapi.microsoft.com/Api/Advertiser/v8/Optimizer/OptimizerService.svc?wsdl';
		$this->wsdl_url_sandbox = '';
		$this->tns_prod = 'https://adcenter.microsoft.com/v8';
		
		parent::__construct($soap_opts, $client_opts);
	}

	/**
	 * @param integer $AccountId
	 * @param ArrayOfstring $OpportunityKeys
	 * @return void
	 */
	public function ApplyBudgetOpportunities($AccountId, $OpportunityKeys) { return $this->SoapCall('ApplyBudgetOpportunities', array('AccountId' => $AccountId, 'OpportunityKeys' => $OpportunityKeys), false); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOfstring $OpportunityKeys
	 * @return void
	 */
	public function ApplyOpportunities($AccountId, $OpportunityKeys) { return $this->SoapCall('ApplyOpportunities', array('AccountId' => $AccountId, 'OpportunityKeys' => $OpportunityKeys), false); }


	/**
	 * @param integer $AccountId
	 * @param integer $AdGroupId
	 * @param integer $CampaignId
	 * @return ArrayOfBidOpportunity
	 */
	public function GetBidOpportunities($AccountId, $AdGroupId, $CampaignId) { return $this->SoapCall('GetBidOpportunities', array('AccountId' => $AccountId, 'AdGroupId' => $AdGroupId, 'CampaignId' => $CampaignId), true); }


	/**
	 * @param integer $AccountId
	 * @return ArrayOfBudgetOpportunity
	 */
	public function GetBudgetOpportunities($AccountId) { return $this->SoapCall('GetBudgetOpportunities', array('AccountId' => $AccountId), true); }


	/**
	 * @param integer $AccountId
	 * @param integer $AdGroupId
	 * @param integer $CampaignId
	 * @return ArrayOfKeywordOpportunity
	 */
	public function GetKeywordOpportunities($AccountId, $AdGroupId, $CampaignId) { return $this->SoapCall('GetKeywordOpportunities', array('AccountId' => $AccountId, 'AdGroupId' => $AdGroupId, 'CampaignId' => $CampaignId), true); }


}

?>