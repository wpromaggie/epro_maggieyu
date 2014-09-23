<?php
namespace phpadcenter\services\Bulk;

class BulkEnum
{
	public static $AdditionalEntity = array('CampaignNegativeKeywords','AdGroupNegativeKeywords','CampaignTargets','AdGroupTargets','CampaignNegativeSites','AdGroupNegativeSites','AdEditorialRejectionReasons','KeywordEditorialRejectionReasons','CampaignSiteLinksAdExtensions','CampaignProductAdExtensions','CampaignLocationAdExtensions','CampaignCallAdExtensions');
	public static $DownloadStatus   = array('InProgress','Failed','Success','PartialSuccess');
}

?>