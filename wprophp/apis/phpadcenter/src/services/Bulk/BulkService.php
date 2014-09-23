<?php
namespace phpadcenter\services\Bulk;

class BulkService extends \phpadcenter\AdCenterService
{
	function __construct($soap_opts = array(), $client_opts = array())
	{
		$this->wsdl_url_prod = 'https://adcenterapi.microsoft.com/Api/Advertiser/v8/CampaignManagement/BulkService.svc?wsdl';
		$this->wsdl_url_sandbox = '';
		$this->tns_prod = 'https://adcenter.microsoft.com/v8';
		
		parent::__construct($soap_opts, $client_opts);
	}

	/**
	 * @param ArrayOflong $AccountIds
	 * @param AdditionalEntity $AdditionalEntities
	 * @param dateTime $LastSyncTimeInUTC
	 * @param string $LocationTargetVersion
	 * @return string
	 */
	public function DownloadCampaignsByAccountIds($AccountIds, $AdditionalEntities = null, $LastSyncTimeInUTC = null, $LocationTargetVersion = null) { return $this->SoapCall('DownloadCampaignsByAccountIds', array('AccountIds' => $AccountIds, 'AdditionalEntities' => $AdditionalEntities, 'LastSyncTimeInUTC' => $LastSyncTimeInUTC, 'LocationTargetVersion' => $LocationTargetVersion), false); }


	/**
	 * @param ArrayOfCampaignScope $Campaigns
	 * @param AdditionalEntity $AdditionalEntities
	 * @param dateTime $LastSyncTimeInUTC
	 * @param string $LocationTargetVersion
	 * @return string
	 */
	public function DownloadCampaignsByCampaignIds($Campaigns, $AdditionalEntities = null, $LastSyncTimeInUTC = null, $LocationTargetVersion = null) { return $this->SoapCall('DownloadCampaignsByCampaignIds', array('AdditionalEntities' => $AdditionalEntities, 'Campaigns' => $Campaigns, 'LastSyncTimeInUTC' => $LastSyncTimeInUTC, 'LocationTargetVersion' => $LocationTargetVersion), false); }


	/**
	 * @param string $DownloadRequestId
	 * @return EnumOfDownloadStatus
	 */
	public function GetDownloadStatus($DownloadRequestId) { return $this->SoapCall('GetDownloadStatus', array('DownloadRequestId' => $DownloadRequestId), false); }


}

?>