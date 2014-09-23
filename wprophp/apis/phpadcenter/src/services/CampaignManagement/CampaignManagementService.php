<?php
namespace phpadcenter\services\CampaignManagement;

class CampaignManagementService extends \phpadcenter\AdCenterService
{
	function __construct($soap_opts = array(), $client_opts = array())
	{
		$this->wsdl_url_prod = 'https://adcenterapi.microsoft.com/Api/Advertiser/v8/CampaignManagement/CampaignManagementService.svc?wsdl';
		$this->wsdl_url_sandbox = 'https://api.sandbox.bingads.microsoft.com/Api/Advertiser/v8/CampaignManagement/CampaignManagementService.svc?wsdl';
		$this->tns_prod = 'https://adcenter.microsoft.com/v8';
		
		parent::__construct($soap_opts, $client_opts);
	}

	/**
	 * @param integer $AccountId
	 * @param ArrayOfAdExtension2 $AdExtensions
	 * @return ArrayOfAdExtensionIdentity
	 */
	public function AddAdExtensions($AccountId, $AdExtensions) { return $this->SoapCall('AddAdExtensions', array('AccountId' => $AccountId, 'AdExtensions' => $AdExtensions), true); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOfAdGroupCriterion $AdGroupCriterions
	 * @return ArrayOflong
	 */
	public function AddAdGroupCriterions($AccountId, $AdGroupCriterions) { return $this->SoapCall('AddAdGroupCriterions', array('AccountId' => $AccountId, 'AdGroupCriterions' => $AdGroupCriterions), true); }


	/**
	 * @param integer $CampaignId
	 * @param ArrayOfAdGroup $AdGroups
	 * @return ArrayOflong
	 */
	public function AddAdGroups($CampaignId, $AdGroups) { return $this->SoapCall('AddAdGroups', array('CampaignId' => $CampaignId, 'AdGroups' => $AdGroups), true); }


	/**
	 * @param integer $AdGroupId
	 * @param ArrayOfAd $Ads
	 * @return ArrayOflong
	 */
	public function AddAds($AdGroupId, $Ads) { return $this->SoapCall('AddAds', array('AdGroupId' => $AdGroupId, 'Ads' => $Ads), true); }


	/**
	 * @param ArrayOfBusiness $Businesses
	 * @return ArrayOflong
	 */
	public function AddBusinesses($Businesses) { return $this->SoapCall('AddBusinesses', array('Businesses' => $Businesses), true); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOfCampaign $Campaigns
	 * @return ArrayOflong
	 */
	public function AddCampaigns($AccountId, $Campaigns) { return $this->SoapCall('AddCampaigns', array('AccountId' => $AccountId, 'Campaigns' => $Campaigns), true); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOfGoal $Goals
	 * @return ArrayOfGoalResult
	 */
	public function AddGoals($AccountId, $Goals) { return $this->SoapCall('AddGoals', array('AccountId' => $AccountId, 'Goals' => $Goals), true); }


	/**
	 * @param integer $AccountId
	 * @param string $Data
	 * @param ImageType $Type
	 * @return integer
	 */
	public function AddImage($AccountId, $Data, $Type) { return $this->SoapCall('AddImage', array('AccountId' => $AccountId, 'Data' => $Data, 'Type' => $Type), false); }


	/**
	 * @param integer $AdGroupId
	 * @param ArrayOfKeyword $Keywords
	 * @return ArrayOflong
	 */
	public function AddKeywords($AdGroupId, $Keywords) { return $this->SoapCall('AddKeywords', array('AdGroupId' => $AdGroupId, 'Keywords' => $Keywords), true); }


	/**
	 * @param integer $AdGroupId
	 * @param ArrayOfSitePlacement $SitePlacements
	 * @return ArrayOflong
	 */
	public function AddSitePlacements($AdGroupId, $SitePlacements) { return $this->SoapCall('AddSitePlacements', array('AdGroupId' => $AdGroupId, 'SitePlacements' => $SitePlacements), true); }


	/**
	 * @param integer $AdGroupId
	 * @param Target $Target
	 * @return integer
	 */
	public function AddTarget($AdGroupId, $Target) { return $this->SoapCall('AddTarget', array('AdGroupId' => $AdGroupId, 'Target' => $Target), false); }


	/**
	 * @param ArrayOfTarget $Targets
	 * @return ArrayOflong
	 */
	public function AddTargetsToLibrary($Targets) { return $this->SoapCall('AddTargetsToLibrary', array('Targets' => $Targets), true); }


	/**
	 * @param ArrayOflong $EntityIds
	 * @param EntityType $EntityType
	 * @param string $JustificationText
	 * @return void
	 */
	public function AppealEditorialRejections($EntityIds, $EntityType, $JustificationText) { return $this->SoapCall('AppealEditorialRejections', array('EntityIds' => $EntityIds, 'EntityType' => $EntityType, 'JustificationText' => $JustificationText), false); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOflong $AdExtensionIds
	 * @return void
	 */
	public function DeleteAdExtensions($AccountId, $AdExtensionIds) { return $this->SoapCall('DeleteAdExtensions', array('AccountId' => $AccountId, 'AdExtensionIds' => $AdExtensionIds), false); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOfAdExtensionIdToCampaignIdAssociation $AdExtensionIdToCampaignIdAssociations
	 * @return void
	 */
	public function DeleteAdExtensionsFromCampaigns($AccountId, $AdExtensionIdToCampaignIdAssociations) { return $this->SoapCall('DeleteAdExtensionsFromCampaigns', array('AccountId' => $AccountId, 'AdExtensionIdToCampaignIdAssociations' => $AdExtensionIdToCampaignIdAssociations), false); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOflong $AdGroupCriterionIds
	 * @param integer $AdGroupId
	 * @return void
	 */
	public function DeleteAdGroupCriterions($AccountId, $AdGroupCriterionIds, $AdGroupId) { return $this->SoapCall('DeleteAdGroupCriterions', array('AccountId' => $AccountId, 'AdGroupCriterionIds' => $AdGroupCriterionIds, 'AdGroupId' => $AdGroupId), false); }


	/**
	 * @param integer $CampaignId
	 * @param ArrayOflong $AdGroupIds
	 * @return void
	 */
	public function DeleteAdGroups($CampaignId, $AdGroupIds) { return $this->SoapCall('DeleteAdGroups', array('CampaignId' => $CampaignId, 'AdGroupIds' => $AdGroupIds), false); }


	/**
	 * @param integer $AdGroupId
	 * @param ArrayOflong $AdIds
	 * @return void
	 */
	public function DeleteAds($AdGroupId, $AdIds) { return $this->SoapCall('DeleteAds', array('AdGroupId' => $AdGroupId, 'AdIds' => $AdIds), false); }


	/**
	 * @param ArrayOflong $BusinessIds
	 * @return void
	 */
	public function DeleteBusinesses($BusinessIds) { return $this->SoapCall('DeleteBusinesses', array('BusinessIds' => $BusinessIds), false); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOflong $CampaignIds
	 * @return void
	 */
	public function DeleteCampaigns($AccountId, $CampaignIds) { return $this->SoapCall('DeleteCampaigns', array('AccountId' => $AccountId, 'CampaignIds' => $CampaignIds), false); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOflong $GoalIds
	 * @return void
	 */
	public function DeleteGoals($AccountId, $GoalIds) { return $this->SoapCall('DeleteGoals', array('AccountId' => $AccountId, 'GoalIds' => $GoalIds), false); }


	/**
	 * @param integer $AdGroupId
	 * @param ArrayOflong $KeywordIds
	 * @return void
	 */
	public function DeleteKeywords($AdGroupId, $KeywordIds) { return $this->SoapCall('DeleteKeywords', array('AdGroupId' => $AdGroupId, 'KeywordIds' => $KeywordIds), false); }


	/**
	 * @param integer $AdGroupId
	 * @param ArrayOflong $SitePlacementIds
	 * @return void
	 */
	public function DeleteSitePlacements($AdGroupId, $SitePlacementIds) { return $this->SoapCall('DeleteSitePlacements', array('AdGroupId' => $AdGroupId, 'SitePlacementIds' => $SitePlacementIds), false); }


	/**
	 * @param integer $AdGroupId
	 * @return void
	 */
	public function DeleteTarget($AdGroupId) { return $this->SoapCall('DeleteTarget', array('AdGroupId' => $AdGroupId), false); }


	/**
	 * @param integer $AdGroupId
	 * @return void
	 */
	public function DeleteTargetFromAdGroup($AdGroupId) { return $this->SoapCall('DeleteTargetFromAdGroup', array('AdGroupId' => $AdGroupId), false); }


	/**
	 * @param integer $CampaignId
	 * @return void
	 */
	public function DeleteTargetFromCampaign($CampaignId) { return $this->SoapCall('DeleteTargetFromCampaign', array('CampaignId' => $CampaignId), false); }


	/**
	 * @param ArrayOflong $TargetIds
	 * @return void
	 */
	public function DeleteTargetsFromLibrary($TargetIds) { return $this->SoapCall('DeleteTargetsFromLibrary', array('TargetIds' => $TargetIds), false); }


	/**
	 * @param ArrayOflong $AccountIds
	 * @param string $MigrationType
	 * @return ArrayOfAccountMigrationStatusesInfo
	 */
	public function GetAccountMigrationStatuses($AccountIds, $MigrationType) { return $this->SoapCall('GetAccountMigrationStatuses', array('AccountIds' => $AccountIds, 'MigrationType' => $MigrationType), true); }


	/**
	 * @param ArrayOflong $AdIds
	 * @param integer $AccountId
	 * @return ArrayOfEditorialReasonCollection
	 */
	public function GetAdEditorialReasonsByIds($AdIds, $AccountId) { return $this->SoapCall('GetAdEditorialReasonsByIds', array('AdIds' => $AdIds, 'AccountId' => $AccountId), true); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOflong $CampaignIds
	 * @param AdExtensionsTypeFilter $AdExtensionType
	 * @return ArrayOfCampaignAdExtensionCollection
	 */
	public function GetAdExtensionsByCampaignIds($AccountId, $CampaignIds, $AdExtensionType) { return $this->SoapCall('GetAdExtensionsByCampaignIds', array('AccountId' => $AccountId, 'CampaignIds' => $CampaignIds, 'AdExtensionType' => $AdExtensionType), true); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOflong $AdExtensionIds
	 * @param AdExtensionsTypeFilter $AdExtensionType
	 * @return ArrayOfAdExtension2
	 */
	public function GetAdExtensionsByIds($AccountId, $AdExtensionIds, $AdExtensionType) { return $this->SoapCall('GetAdExtensionsByIds', array('AccountId' => $AccountId, 'AdExtensionIds' => $AdExtensionIds, 'AdExtensionType' => $AdExtensionType), true); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOfAdExtensionIdToCampaignIdAssociation $AdExtensionIdToCampaignIdAssociations
	 * @param AdExtensionsTypeFilter $AdExtensionType
	 * @return ArrayOfAdExtensionEditorialReasonCollection
	 */
	public function GetAdExtensionsEditorialReasonsByCampaignIds($AccountId, $AdExtensionIdToCampaignIdAssociations, $AdExtensionType) { return $this->SoapCall('GetAdExtensionsEditorialReasonsByCampaignIds', array('AccountId' => $AccountId, 'AdExtensionIdToCampaignIdAssociations' => $AdExtensionIdToCampaignIdAssociations, 'AdExtensionType' => $AdExtensionType), true); }


	/**
	 * @param integer $AccountId
	 * @param integer $AdGroupId
	 * @param CriterionType $CriterionTypeFilter
	 * @return ArrayOfAdGroupCriterion
	 */
	public function GetAdGroupCriterionsByAdGroupId($AccountId, $AdGroupId, $CriterionTypeFilter) { return $this->SoapCall('GetAdGroupCriterionsByAdGroupId', array('AccountId' => $AccountId, 'AdGroupId' => $AdGroupId, 'CriterionTypeFilter' => $CriterionTypeFilter), true); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOflong $AdGroupCriterionIds
	 * @return ArrayOfAdGroupCriterion
	 */
	public function GetAdGroupCriterionsByIds($AccountId, $AdGroupCriterionIds) { return $this->SoapCall('GetAdGroupCriterionsByIds', array('AccountId' => $AccountId, 'AdGroupCriterionIds' => $AdGroupCriterionIds), true); }


	/**
	 * @param integer $CampaignId
	 * @return ArrayOfAdGroup
	 */
	public function GetAdGroupsByCampaignId($CampaignId) { return $this->SoapCall('GetAdGroupsByCampaignId', array('CampaignId' => $CampaignId), true); }


	/**
	 * @param integer $CampaignId
	 * @param ArrayOflong $AdGroupIds
	 * @return ArrayOfAdGroup
	 */
	public function GetAdGroupsByIds($CampaignId, $AdGroupIds) { return $this->SoapCall('GetAdGroupsByIds', array('CampaignId' => $CampaignId, 'AdGroupIds' => $AdGroupIds), true); }


	/**
	 * @param ArrayOflong $AdGroupIds
	 * @param integer $CampaignId
	 * @return ArrayOfAdRotation
	 */
	public function GetAdRotationByAdGroupIds($AdGroupIds, $CampaignId) { return $this->SoapCall('GetAdRotationByAdGroupIds', array('AdGroupIds' => $AdGroupIds, 'CampaignId' => $CampaignId), true); }


	/**
	 * @param integer $AdGroupId
	 * @return ArrayOfAd
	 */
	public function GetAdsByAdGroupId($AdGroupId) { return $this->SoapCall('GetAdsByAdGroupId', array('AdGroupId' => $AdGroupId), true); }


	/**
	 * @param integer $AdGroupId
	 * @param AdEditorialStatus $EditorialStatus
	 * @return ArrayOfAd
	 */
	public function GetAdsByEditorialStatus($AdGroupId, $EditorialStatus) { return $this->SoapCall('GetAdsByEditorialStatus', array('AdGroupId' => $AdGroupId, 'EditorialStatus' => $EditorialStatus), true); }


	/**
	 * @param integer $AdGroupId
	 * @param ArrayOflong $AdIds
	 * @return ArrayOfAd
	 */
	public function GetAdsByIds($AdGroupId, $AdIds) { return $this->SoapCall('GetAdsByIds', array('AdGroupId' => $AdGroupId, 'AdIds' => $AdIds), true); }


	/**
	 * @param ArrayOflong $AccountIds
	 * @return ArrayOfAnalyticsType
	 */
	public function GetAnalyticsType($AccountIds) { return $this->SoapCall('GetAnalyticsType', array('AccountIds' => $AccountIds), true); }


	/**
	 * @param ArrayOflong $BusinessIds
	 * @return ArrayOfBusiness
	 */
	public function GetBusinessesByIds($BusinessIds) { return $this->SoapCall('GetBusinessesByIds', array('BusinessIds' => $BusinessIds), true); }


	/**
	 * @return ArrayOfBusinessInfo
	 */
	public function GetBusinessesInfo() { return $this->SoapCall('GetBusinessesInfo', array(), true); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOflong $CampaignIds
	 * @return ArrayOfAdExtension
	 */
	public function GetCampaignAdExtensions($AccountId, $CampaignIds) { return $this->SoapCall('GetCampaignAdExtensions', array('AccountId' => $AccountId, 'CampaignIds' => $CampaignIds), true); }


	/**
	 * @param integer $AccountId
	 * @return ArrayOfCampaign
	 */
	public function GetCampaignsByAccountId($AccountId) { return $this->SoapCall('GetCampaignsByAccountId', array('AccountId' => $AccountId), true); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOflong $CampaignIds
	 * @return ArrayOfCampaign
	 */
	public function GetCampaignsByIds($AccountId, $CampaignIds) { return $this->SoapCall('GetCampaignsByIds', array('AccountId' => $AccountId, 'CampaignIds' => $CampaignIds), true); }


	/**
	 * @param integer $AdGroupId
	 * @param ArrayOflong $KeywordIds
	 * @return ArrayOfstring
	 */
	public function GetDestinationUrlByKeywordIds($AdGroupId, $KeywordIds) { return $this->SoapCall('GetDestinationUrlByKeywordIds', array('AdGroupId' => $AdGroupId, 'KeywordIds' => $KeywordIds), true); }


	/**
	 * @param ArrayOflong $TargetIds
	 * @return ArrayOfTargetAssociation
	 */
	public function GetDeviceOSTargetsByIds($TargetIds) { return $this->SoapCall('GetDeviceOSTargetsByIds', array('TargetIds' => $TargetIds), true); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOflong $EntityIds
	 * @param EntityType $EntityType
	 * @return ArrayOfEditorialReasonCollection
	 */
	public function GetEditorialReasonsByIds($AccountId, $EntityIds, $EntityType) { return $this->SoapCall('GetEditorialReasonsByIds', array('AccountId' => $AccountId, 'EntityIds' => $EntityIds, 'EntityType' => $EntityType), true); }


	/**
	 * @param ArrayOfEntity $Entities
	 * @param ExclusionType $ExclusionType
	 * @param string $LocationTargetVersion
	 * @return ArrayOfEntityToExclusionsAssociation
	 */
	public function GetExclusionsByAssociatedEntityIds($Entities, $ExclusionType, $LocationTargetVersion) { return $this->SoapCall('GetExclusionsByAssociatedEntityIds', array('Entities' => $Entities, 'ExclusionType' => $ExclusionType, 'LocationTargetVersion' => $LocationTargetVersion), true); }


	/**
	 * @param integer $AccountId
	 * @return ArrayOfGoal
	 */
	public function GetGoals($AccountId) { return $this->SoapCall('GetGoals', array('AccountId' => $AccountId), true); }


	/**
	 * @param integer $AccountId
	 * @param integer $MediaId
	 * @return string
	 */
	public function GetImageById($AccountId, $MediaId) { return $this->SoapCall('GetImageById', array('AccountId' => $AccountId, 'MediaId' => $MediaId), false); }


	/**
	 * @param ArrayOflong $KeywordIds
	 * @param integer $AccountId
	 * @return ArrayOfEditorialReasonCollection
	 */
	public function GetKeywordEditorialReasonsByIds($KeywordIds, $AccountId) { return $this->SoapCall('GetKeywordEditorialReasonsByIds', array('KeywordIds' => $KeywordIds, 'AccountId' => $AccountId), true); }


	/**
	 * @param integer $AdGroupId
	 * @return ArrayOfKeyword
	 */
	public function GetKeywordsByAdGroupId($AdGroupId) { return $this->SoapCall('GetKeywordsByAdGroupId', array('AdGroupId' => $AdGroupId), true); }


	/**
	 * @param integer $AdGroupId
	 * @param KeywordEditorialStatus $EditorialStatus
	 * @return ArrayOfKeyword
	 */
	public function GetKeywordsByEditorialStatus($AdGroupId, $EditorialStatus) { return $this->SoapCall('GetKeywordsByEditorialStatus', array('AdGroupId' => $AdGroupId, 'EditorialStatus' => $EditorialStatus), true); }


	/**
	 * @param integer $AdGroupId
	 * @param ArrayOflong $KeywordIds
	 * @return ArrayOfKeyword
	 */
	public function GetKeywordsByIds($AdGroupId, $KeywordIds) { return $this->SoapCall('GetKeywordsByIds', array('AdGroupId' => $AdGroupId, 'KeywordIds' => $KeywordIds), true); }


	/**
	 * @param integer $CampaignId
	 * @param ArrayOflong $AdGroupIds
	 * @return ArrayOfAdGroupNegativeKeywords
	 */
	public function GetNegativeKeywordsByAdGroupIds($CampaignId, $AdGroupIds) { return $this->SoapCall('GetNegativeKeywordsByAdGroupIds', array('CampaignId' => $CampaignId, 'AdGroupIds' => $AdGroupIds), true); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOflong $CampaignIds
	 * @return ArrayOfCampaignNegativeKeywords
	 */
	public function GetNegativeKeywordsByCampaignIds($AccountId, $CampaignIds) { return $this->SoapCall('GetNegativeKeywordsByCampaignIds', array('AccountId' => $AccountId, 'CampaignIds' => $CampaignIds), true); }


	/**
	 * @param integer $CampaignId
	 * @param ArrayOflong $AdGroupIds
	 * @return ArrayOfAdGroupNegativeSites
	 */
	public function GetNegativeSitesByAdGroupIds($CampaignId, $AdGroupIds) { return $this->SoapCall('GetNegativeSitesByAdGroupIds', array('CampaignId' => $CampaignId, 'AdGroupIds' => $AdGroupIds), true); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOflong $CampaignIds
	 * @return ArrayOfCampaignNegativeSites
	 */
	public function GetNegativeSitesByCampaignIds($AccountId, $CampaignIds) { return $this->SoapCall('GetNegativeSitesByCampaignIds', array('AccountId' => $AccountId, 'CampaignIds' => $CampaignIds), true); }


	/**
	 * @param ArrayOfstring $Strings
	 * @param string $Language
	 * @param boolean $RemoveNoise
	 * @return ArrayOfstring
	 */
	public function GetNormalizedStrings($Strings, $Language, $RemoveNoise) { return $this->SoapCall('GetNormalizedStrings', array('Strings' => $Strings, 'Language' => $Language, 'RemoveNoise' => $RemoveNoise), true); }


	/**
	 * @param ArrayOfstring $Urls
	 * @return ArrayOfArrayOfPlacementDetail
	 */
	public function GetPlacementDetailsForUrls($Urls) { return $this->SoapCall('GetPlacementDetailsForUrls', array('Urls' => $Urls), true); }


	/**
	 * @param integer $AdGroupId
	 * @return ArrayOfSitePlacement
	 */
	public function GetSitePlacementsByAdGroupId($AdGroupId) { return $this->SoapCall('GetSitePlacementsByAdGroupId', array('AdGroupId' => $AdGroupId), true); }


	/**
	 * @param integer $AdGroupId
	 * @param ArrayOflong $SitePlacementIds
	 * @return ArrayOfSitePlacement
	 */
	public function GetSitePlacementsByIds($AdGroupId, $SitePlacementIds) { return $this->SoapCall('GetSitePlacementsByIds', array('AdGroupId' => $AdGroupId, 'SitePlacementIds' => $SitePlacementIds), true); }


	/**
	 * @param integer $AdGroupId
	 * @param string $LocationTargetVersion
	 * @return Target
	 */
	public function GetTargetByAdGroupId($AdGroupId, $LocationTargetVersion) { return $this->SoapCall('GetTargetByAdGroupId', array('AdGroupId' => $AdGroupId, 'LocationTargetVersion' => $LocationTargetVersion), false); }


	/**
	 * @param ArrayOflong $AdGroupIds
	 * @param string $LocationTargetVersion
	 * @return ArrayOfTarget
	 */
	public function GetTargetsByAdGroupIds($AdGroupIds, $LocationTargetVersion) { return $this->SoapCall('GetTargetsByAdGroupIds', array('AdGroupIds' => $AdGroupIds, 'LocationTargetVersion' => $LocationTargetVersion), true); }


	/**
	 * @param ArrayOflong $CampaignIds
	 * @param string $LocationTargetVersion
	 * @return ArrayOfTarget
	 */
	public function GetTargetsByCampaignIds($CampaignIds, $LocationTargetVersion) { return $this->SoapCall('GetTargetsByCampaignIds', array('CampaignIds' => $CampaignIds, 'LocationTargetVersion' => $LocationTargetVersion), true); }


	/**
	 * @param ArrayOflong $TargetIds
	 * @param string $LocationTargetVersion
	 * @return ArrayOfTarget
	 */
	public function GetTargetsByIds($TargetIds, $LocationTargetVersion) { return $this->SoapCall('GetTargetsByIds', array('TargetIds' => $TargetIds, 'LocationTargetVersion' => $LocationTargetVersion), true); }


	/**
	 * @return ArrayOfTargetInfo
	 */
	public function GetTargetsInfoFromLibrary() { return $this->SoapCall('GetTargetsInfoFromLibrary', array(), true); }


	/**
	 * @param integer $CampaignId
	 * @param ArrayOflong $AdGroupIds
	 * @return void
	 */
	public function PauseAdGroups($CampaignId, $AdGroupIds) { return $this->SoapCall('PauseAdGroups', array('CampaignId' => $CampaignId, 'AdGroupIds' => $AdGroupIds), false); }


	/**
	 * @param integer $AdGroupId
	 * @param ArrayOflong $AdIds
	 * @return void
	 */
	public function PauseAds($AdGroupId, $AdIds) { return $this->SoapCall('PauseAds', array('AdGroupId' => $AdGroupId, 'AdIds' => $AdIds), false); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOflong $CampaignIds
	 * @return void
	 */
	public function PauseCampaigns($AccountId, $CampaignIds) { return $this->SoapCall('PauseCampaigns', array('AccountId' => $AccountId, 'CampaignIds' => $CampaignIds), false); }


	/**
	 * @param integer $AdGroupId
	 * @param ArrayOflong $KeywordIds
	 * @return void
	 */
	public function PauseKeywords($AdGroupId, $KeywordIds) { return $this->SoapCall('PauseKeywords', array('AdGroupId' => $AdGroupId, 'KeywordIds' => $KeywordIds), false); }


	/**
	 * @param integer $AdGroupId
	 * @param ArrayOflong $SitePlacementIds
	 * @return void
	 */
	public function PauseSitePlacements($AdGroupId, $SitePlacementIds) { return $this->SoapCall('PauseSitePlacements', array('AdGroupId' => $AdGroupId, 'SitePlacementIds' => $SitePlacementIds), false); }


	/**
	 * @param integer $CampaignId
	 * @param ArrayOflong $AdGroupIds
	 * @return void
	 */
	public function ResumeAdGroups($CampaignId, $AdGroupIds) { return $this->SoapCall('ResumeAdGroups', array('CampaignId' => $CampaignId, 'AdGroupIds' => $AdGroupIds), false); }


	/**
	 * @param integer $AdGroupId
	 * @param ArrayOflong $AdIds
	 * @return void
	 */
	public function ResumeAds($AdGroupId, $AdIds) { return $this->SoapCall('ResumeAds', array('AdGroupId' => $AdGroupId, 'AdIds' => $AdIds), false); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOflong $CampaignIds
	 * @return void
	 */
	public function ResumeCampaigns($AccountId, $CampaignIds) { return $this->SoapCall('ResumeCampaigns', array('AccountId' => $AccountId, 'CampaignIds' => $CampaignIds), false); }


	/**
	 * @param integer $AdGroupId
	 * @param ArrayOflong $KeywordIds
	 * @return void
	 */
	public function ResumeKeywords($AdGroupId, $KeywordIds) { return $this->SoapCall('ResumeKeywords', array('AdGroupId' => $AdGroupId, 'KeywordIds' => $KeywordIds), false); }


	/**
	 * @param integer $AdGroupId
	 * @param ArrayOflong $SitePlacementIds
	 * @return void
	 */
	public function ResumeSitePlacements($AdGroupId, $SitePlacementIds) { return $this->SoapCall('ResumeSitePlacements', array('AdGroupId' => $AdGroupId, 'SitePlacementIds' => $SitePlacementIds), false); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOfAdExtensionIdToCampaignIdAssociation $AdExtensionIdToCampaignIdAssociations
	 * @return void
	 */
	public function SetAdExtensionsToCampaigns($AccountId, $AdExtensionIdToCampaignIdAssociations) { return $this->SoapCall('SetAdExtensionsToCampaigns', array('AccountId' => $AccountId, 'AdExtensionIdToCampaignIdAssociations' => $AdExtensionIdToCampaignIdAssociations), false); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOfAdGroupCriterion $AdGroupCriterions
	 * @return void
	 */
	public function SetAdGroupCriterions($AccountId, $AdGroupCriterions) { return $this->SoapCall('SetAdGroupCriterions', array('AccountId' => $AccountId, 'AdGroupCriterions' => $AdGroupCriterions), false); }


	/**
	 * @param ArrayOfAdGroupAdRotation $AdGroupAdRotations
	 * @param integer $CampaignId
	 * @return void
	 */
	public function SetAdRotationToAdGroups($AdGroupAdRotations, $CampaignId) { return $this->SoapCall('SetAdRotationToAdGroups', array('AdGroupAdRotations' => $AdGroupAdRotations, 'CampaignId' => $CampaignId), false); }


	/**
	 * @param ArrayOfAccountAnalyticsType $AccountAnalyticsTypes
	 * @return void
	 */
	public function SetAnalyticsType($AccountAnalyticsTypes) { return $this->SoapCall('SetAnalyticsType', array('AccountAnalyticsTypes' => $AccountAnalyticsTypes), false); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOfAdExtension $AdExtensions
	 * @return void
	 */
	public function SetCampaignAdExtensions($AccountId, $AdExtensions) { return $this->SoapCall('SetCampaignAdExtensions', array('AccountId' => $AccountId, 'AdExtensions' => $AdExtensions), false); }


	/**
	 * @param integer $AdGroupId
	 * @param ArrayOfKeywordDestinationUrl $KeywordDestinationUrls
	 * @return void
	 */
	public function SetDestinationUrlToKeywords($AdGroupId, $KeywordDestinationUrls) { return $this->SoapCall('SetDestinationUrlToKeywords', array('AdGroupId' => $AdGroupId, 'KeywordDestinationUrls' => $KeywordDestinationUrls), false); }


	/**
	 * @param ArrayOfExclusionToEntityAssociation $ExclusionToEntityAssociations
	 * @return void
	 */
	public function SetExclusionsToAssociatedEntities($ExclusionToEntityAssociations) { return $this->SoapCall('SetExclusionsToAssociatedEntities', array('ExclusionToEntityAssociations' => $ExclusionToEntityAssociations), false); }


	/**
	 * @param integer $CampaignId
	 * @param ArrayOfAdGroupNegativeKeywords $AdGroupNegativeKeywords
	 * @return void
	 */
	public function SetNegativeKeywordsToAdGroups($CampaignId, $AdGroupNegativeKeywords) { return $this->SoapCall('SetNegativeKeywordsToAdGroups', array('CampaignId' => $CampaignId, 'AdGroupNegativeKeywords' => $AdGroupNegativeKeywords), false); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOfCampaignNegativeKeywords $CampaignNegativeKeywords
	 * @return void
	 */
	public function SetNegativeKeywordsToCampaigns($AccountId, $CampaignNegativeKeywords) { return $this->SoapCall('SetNegativeKeywordsToCampaigns', array('AccountId' => $AccountId, 'CampaignNegativeKeywords' => $CampaignNegativeKeywords), false); }


	/**
	 * @param integer $CampaignId
	 * @param ArrayOfAdGroupNegativeSites $AdGroupNegativeSites
	 * @return void
	 */
	public function SetNegativeSitesToAdGroups($CampaignId, $AdGroupNegativeSites) { return $this->SoapCall('SetNegativeSitesToAdGroups', array('CampaignId' => $CampaignId, 'AdGroupNegativeSites' => $AdGroupNegativeSites), false); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOfCampaignNegativeSites $CampaignNegativeSites
	 * @return void
	 */
	public function SetNegativeSitesToCampaigns($AccountId, $CampaignNegativeSites) { return $this->SoapCall('SetNegativeSitesToCampaigns', array('AccountId' => $AccountId, 'CampaignNegativeSites' => $CampaignNegativeSites), false); }


	/**
	 * @param integer $AdGroupId
	 * @param integer $TargetId
	 * @return void
	 */
	public function SetTargetToAdGroup($AdGroupId, $TargetId) { return $this->SoapCall('SetTargetToAdGroup', array('AdGroupId' => $AdGroupId, 'TargetId' => $TargetId), false); }


	/**
	 * @param integer $CampaignId
	 * @param integer $TargetId
	 * @return void
	 */
	public function SetTargetToCampaign($CampaignId, $TargetId) { return $this->SoapCall('SetTargetToCampaign', array('CampaignId' => $CampaignId, 'TargetId' => $TargetId), false); }


	/**
	 * @param integer $AdGroupId
	 * @return void
	 */
	public function SubmitAdGroupForApproval($AdGroupId) { return $this->SoapCall('SubmitAdGroupForApproval', array('AdGroupId' => $AdGroupId), false); }


	/**
	 * @param integer $CampaignId
	 * @param ArrayOfAdGroup $AdGroups
	 * @return void
	 */
	public function UpdateAdGroups($CampaignId, $AdGroups) { return $this->SoapCall('UpdateAdGroups', array('CampaignId' => $CampaignId, 'AdGroups' => $AdGroups), false); }


	/**
	 * @param integer $AdGroupId
	 * @param ArrayOfAd $Ads
	 * @return void
	 */
	public function UpdateAds($AdGroupId, $Ads) { return $this->SoapCall('UpdateAds', array('AdGroupId' => $AdGroupId, 'Ads' => $Ads), false); }


	/**
	 * @param ArrayOfBusiness $Businesses
	 * @return void
	 */
	public function UpdateBusinesses($Businesses) { return $this->SoapCall('UpdateBusinesses', array('Businesses' => $Businesses), false); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOfCampaign $Campaigns
	 * @return void
	 */
	public function UpdateCampaigns($AccountId, $Campaigns) { return $this->SoapCall('UpdateCampaigns', array('AccountId' => $AccountId, 'Campaigns' => $Campaigns), false); }


	/**
	 * @param ArrayOfTargetAssociation $TargetAssociations
	 * @return void
	 */
	public function UpdateDeviceOSTargets($TargetAssociations) { return $this->SoapCall('UpdateDeviceOSTargets', array('TargetAssociations' => $TargetAssociations), false); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOfGoal $Goals
	 * @return ArrayOfGoalResult
	 */
	public function UpdateGoals($AccountId, $Goals) { return $this->SoapCall('UpdateGoals', array('AccountId' => $AccountId, 'Goals' => $Goals), true); }


	/**
	 * @param integer $AdGroupId
	 * @param ArrayOfKeyword $Keywords
	 * @return void
	 */
	public function UpdateKeywords($AdGroupId, $Keywords) { return $this->SoapCall('UpdateKeywords', array('AdGroupId' => $AdGroupId, 'Keywords' => $Keywords), false); }


	/**
	 * @param integer $AdGroupId
	 * @param ArrayOfSitePlacement $SitePlacements
	 * @return void
	 */
	public function UpdateSitePlacements($AdGroupId, $SitePlacements) { return $this->SoapCall('UpdateSitePlacements', array('AdGroupId' => $AdGroupId, 'SitePlacements' => $SitePlacements), false); }


	/**
	 * @param integer $AdGroupId
	 * @param Target $Target
	 * @return void
	 */
	public function UpdateTarget($AdGroupId, $Target) { return $this->SoapCall('UpdateTarget', array('AdGroupId' => $AdGroupId, 'Target' => $Target), false); }


	/**
	 * @param ArrayOfTarget $Targets
	 * @return void
	 */
	public function UpdateTargetsInLibrary($Targets) { return $this->SoapCall('UpdateTargetsInLibrary', array('Targets' => $Targets), false); }


}

?>