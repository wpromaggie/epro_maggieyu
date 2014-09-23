<?php
namespace phpadcenter\services\AdIntelligence;

class AdIntelligenceService extends \phpadcenter\AdCenterService
{
	function __construct($soap_opts = array(), $client_opts = array())
	{
		$this->wsdl_url_prod = 'https://adcenterapi.microsoft.com/Api/Advertiser/v8/CampaignManagement/AdIntelligenceService.svc?wsdl';
		$this->wsdl_url_sandbox = '';
		$this->tns_prod = 'https://adcenter.microsoft.com/v8';
		
		parent::__construct($soap_opts, $client_opts);
	}

	/**
	 * @param ArrayOflong $KeywordIds
	 * @param TargetAdPosition $TargetPositionForAds
	 * @return ArrayOfKeywordIdEstimatedBid
	 */
	public function GetEstimatedBidByKeywordIds($KeywordIds, $TargetPositionForAds = null) { return $this->SoapCall('GetEstimatedBidByKeywordIds', array('KeywordIds' => $KeywordIds, 'TargetPositionForAds' => $TargetPositionForAds), true); }


	/**
	 * @param ArrayOfstring $Keywords
	 * @param ArrayOfMatchType $MatchTypes
	 * @param TargetAdPosition $TargetPositionForAds
	 * @param string $Language
	 * @param ArrayOfstring $PublisherCountries
	 * @param Currency $Currency
	 * @param string $CampaignId
	 * @param string $AdgroupId
	 * @return ArrayOfKeywordEstimatedBid
	 */
	public function GetEstimatedBidByKeywords($Keywords, $MatchTypes, $TargetPositionForAds = null, $Language = null, $PublisherCountries = null, $Currency = null, $CampaignId = null, $AdgroupId = null) { return $this->SoapCall('GetEstimatedBidByKeywords', array('Keywords' => $Keywords, 'TargetPositionForAds' => $TargetPositionForAds, 'Language' => $Language, 'PublisherCountries' => $PublisherCountries, 'Currency' => $Currency, 'MatchTypes' => $MatchTypes, 'CampaignId' => $CampaignId, 'AdgroupId' => $AdgroupId), true); }


	/**
	 * @param ArrayOflong $KeywordIds
	 * @param double $MaxBid
	 * @return ArrayOfKeywordIdEstimatedPosition
	 */
	public function GetEstimatedPositionByKeywordIds($KeywordIds, $MaxBid) { return $this->SoapCall('GetEstimatedPositionByKeywordIds', array('KeywordIds' => $KeywordIds, 'MaxBid' => $MaxBid), true); }


	/**
	 * @param ArrayOfstring $Keywords
	 * @param double $MaxBid
	 * @param ArrayOfMatchType $MatchTypes
	 * @param string $Language
	 * @param ArrayOfstring $PublisherCountries
	 * @param Currency $Currency
	 * @param string $CampaignId
	 * @param string $AdgroupId
	 * @return ArrayOfKeywordEstimatedPosition
	 */
	public function GetEstimatedPositionByKeywords($Keywords, $MaxBid, $MatchTypes, $Language = null, $PublisherCountries = null, $Currency = null, $CampaignId = null, $AdgroupId = null) { return $this->SoapCall('GetEstimatedPositionByKeywords', array('Keywords' => $Keywords, 'MaxBid' => $MaxBid, 'Language' => $Language, 'PublisherCountries' => $PublisherCountries, 'Currency' => $Currency, 'MatchTypes' => $MatchTypes, 'CampaignId' => $CampaignId, 'AdgroupId' => $AdgroupId), true); }


	/**
	 * @param ArrayOfstring $Keywords
	 * @param TimeInterval $TimeInterval
	 * @param AdPosition $TargetAdPosition
	 * @param MatchType $MatchType
	 * @param string $Language
	 * @param ArrayOfstring $PublisherCountries
	 * @return ArrayOfKeywordHistoricalPerformance
	 */
	public function GetHistoricalKeywordPerformance($Keywords, $TimeInterval = null, $TargetAdPosition = null, $MatchType = null, $Language = null, $PublisherCountries = null) { return $this->SoapCall('GetHistoricalKeywordPerformance', array('Keywords' => $Keywords, 'TimeInterval' => $TimeInterval, 'TargetAdPosition' => $TargetAdPosition, 'MatchType' => $MatchType, 'Language' => $Language, 'PublisherCountries' => $PublisherCountries), true); }


	/**
	 * @param ArrayOfstring $Keywords
	 * @param ArrayOfMatchType $MatchTypes
	 * @param string $Language
	 * @param ArrayOfstring $Devices
	 * @param TimeInterval $TimeInterval
	 * @param AdPosition $TargetAdPosition
	 * @param ArrayOfstring $PublisherCountries
	 * @return ArrayOfKeywordHistoricalPerformanceByDevice
	 */
	public function GetHistoricalKeywordPerformanceByDevice($Keywords, $MatchTypes, $Language, $Devices, $TimeInterval = null, $TargetAdPosition = null, $PublisherCountries = null) { return $this->SoapCall('GetHistoricalKeywordPerformanceByDevice', array('Keywords' => $Keywords, 'TimeInterval' => $TimeInterval, 'TargetAdPosition' => $TargetAdPosition, 'MatchTypes' => $MatchTypes, 'Language' => $Language, 'PublisherCountries' => $PublisherCountries, 'Devices' => $Devices), true); }


	/**
	 * @param ArrayOfstring $Keywords
	 * @param MonthAndYear $StartMonthAndYear
	 * @param MonthAndYear $EndMonthAndYear
	 * @param string $Language
	 * @param ArrayOfstring $PublisherCountries
	 * @return ArrayOfKeywordSearchCount
	 */
	public function GetHistoricalSearchCount($Keywords, $StartMonthAndYear, $EndMonthAndYear, $Language = null, $PublisherCountries = null) { return $this->SoapCall('GetHistoricalSearchCount', array('Keywords' => $Keywords, 'Language' => $Language, 'PublisherCountries' => $PublisherCountries, 'StartMonthAndYear' => $StartMonthAndYear, 'EndMonthAndYear' => $EndMonthAndYear), true); }


	/**
	 * @param ArrayOfstring $Keywords
	 * @param string $Language
	 * @param DayMonthAndYear $StartTimePeriod
	 * @param DayMonthAndYear $EndTimePeriod
	 * @param ArrayOfstring $PublisherCountries
	 * @param string $TimePeriodRollup
	 * @param ArrayOfstring $Devices
	 * @return ArrayOfKeywordSearchCountByDevice
	 */
	public function GetHistoricalSearchCountByDevice($Keywords, $Language, $StartTimePeriod, $EndTimePeriod, $PublisherCountries = null, $TimePeriodRollup = null, $Devices = null) { return $this->SoapCall('GetHistoricalSearchCountByDevice', array('Keywords' => $Keywords, 'Language' => $Language, 'PublisherCountries' => $PublisherCountries, 'StartTimePeriod' => $StartTimePeriod, 'EndTimePeriod' => $EndTimePeriod, 'TimePeriodRollup' => $TimePeriodRollup, 'Devices' => $Devices), true); }


	/**
	 * @param ArrayOfstring $Keywords
	 * @param string $Language
	 * @param string $PublisherCountry
	 * @param integer $MaxCategories
	 * @return ArrayOfKeywordCategoryResult
	 */
	public function GetKeywordCategories($Keywords, $Language, $PublisherCountry, $MaxCategories = null) { return $this->SoapCall('GetKeywordCategories', array('Keywords' => $Keywords, 'Language' => $Language, 'PublisherCountry' => $PublisherCountry, 'MaxCategories' => $MaxCategories), true); }


	/**
	 * @param ArrayOfstring $Keywords
	 * @param string $Language
	 * @param string $PublisherCountry
	 * @param ArrayOfstring $Device
	 * @return ArrayOfKeywordDemographicResult
	 */
	public function GetKeywordDemographics($Keywords, $Language, $PublisherCountry = null, $Device = null) { return $this->SoapCall('GetKeywordDemographics', array('Keywords' => $Keywords, 'Language' => $Language, 'PublisherCountry' => $PublisherCountry, 'Device' => $Device), true); }


	/**
	 * @param ArrayOfstring $Keywords
	 * @param string $Language
	 * @param string $PublisherCountry
	 * @param ArrayOfstring $Device
	 * @param integer $Level
	 * @param string $ParentCountry
	 * @param integer $MaxLocations
	 * @return ArrayOfKeywordLocationResult
	 */
	public function GetKeywordLocations($Keywords, $Language, $PublisherCountry = null, $Device = null, $Level = null, $ParentCountry = null, $MaxLocations = null) { return $this->SoapCall('GetKeywordLocations', array('Keywords' => $Keywords, 'Language' => $Language, 'PublisherCountry' => $PublisherCountry, 'Device' => $Device, 'Level' => $Level, 'ParentCountry' => $ParentCountry, 'MaxLocations' => $MaxLocations), true); }


	/**
	 * @param ArrayOfstring $Keywords
	 * @param TimeInterval $TimeInterval
	 * @return ArrayOfKeywordPerformance
	 */
	public function GetPublisherKeywordPerformance($Keywords, $TimeInterval) { return $this->SoapCall('GetPublisherKeywordPerformance', array('Keywords' => $Keywords, 'TimeInterval' => $TimeInterval), true); }


	/**
	 * @param string $Url
	 * @param string $Language
	 * @param integer $MaxKeywords
	 * @param double $MinConfidenceScore
	 * @param boolean $ExcludeBrand
	 * @return ArrayOfKeywordAndConfidence
	 */
	public function SuggestKeywordsForUrl($Url, $Language = null, $MaxKeywords = null, $MinConfidenceScore = null, $ExcludeBrand = null) { return $this->SoapCall('SuggestKeywordsForUrl', array('Url' => $Url, 'Language' => $Language, 'MaxKeywords' => $MaxKeywords, 'MinConfidenceScore' => $MinConfidenceScore, 'ExcludeBrand' => $ExcludeBrand), true); }


	/**
	 * @param ArrayOfstring $Keywords
	 * @param string $Language
	 * @param ArrayOfstring $PublisherCountries
	 * @param integer $MaxSuggestionsPerKeyword
	 * @param integer $SuggestionType
	 * @param boolean $RemoveDuplicates
	 * @param boolean $ExcludeBrand
	 * @return ArrayOfKeywordSuggestion
	 */
	public function SuggestKeywordsFromExistingKeywords($Keywords, $Language = null, $PublisherCountries = null, $MaxSuggestionsPerKeyword = null, $SuggestionType = null, $RemoveDuplicates = null, $ExcludeBrand = null) { return $this->SoapCall('SuggestKeywordsFromExistingKeywords', array('Keywords' => $Keywords, 'Language' => $Language, 'PublisherCountries' => $PublisherCountries, 'MaxSuggestionsPerKeyword' => $MaxSuggestionsPerKeyword, 'SuggestionType' => $SuggestionType, 'RemoveDuplicates' => $RemoveDuplicates, 'ExcludeBrand' => $ExcludeBrand), true); }


}

?>