<?php
namespace phpadcenter\services\Reporting;

class AccountPerformanceReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfAdDistributionReportFilter
	 */
	public $AdDistribution;

	/**
	 * var EnumOfDeviceTypeReportFilter
	 */
	public $DeviceType;

}


class AccountPerformanceReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfAccountPerformanceReportColumn
	 */
	public $Columns;

	/**
	 * var AccountPerformanceReportFilter
	 */
	public $Filter;

	/**
	 * var AccountReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class AccountReportScope extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOflong
	 */
	public $AccountIds;

}


class AccountThroughAdGroupReportScope extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOflong
	 */
	public $AccountIds;

	/**
	 * var ArrayOfAdGroupReportScope
	 */
	public $AdGroups;

	/**
	 * var ArrayOfCampaignReportScope
	 */
	public $Campaigns;

}


class AccountThroughCampaignReportScope extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOflong
	 */
	public $AccountIds;

	/**
	 * var ArrayOfCampaignReportScope
	 */
	public $Campaigns;

}


class AdDynamicTextPerformanceReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfAdDistributionReportFilter
	 */
	public $AdDistribution;

	/**
	 * var EnumOfAdTypeReportFilter
	 */
	public $AdType;

	/**
	 * var EnumOfDeviceTypeReportFilter
	 */
	public $DeviceType;

	/**
	 * var EnumOfLanguageAndRegionReportFilter
	 */
	public $LanguageAndRegion;

	/**
	 * var ArrayOfstring
	 */
	public $LanguageCode;

}


class AdDynamicTextPerformanceReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfNonHourlyReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfAdDynamicTextPerformanceReportColumn
	 */
	public $Columns;

	/**
	 * var AdDynamicTextPerformanceReportFilter
	 */
	public $Filter;

	/**
	 * var AccountThroughAdGroupReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class AdExtensionByAdExtensionItemRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfAdExtensionByAdExtensionItemColumn
	 */
	public $Columns;

	/**
	 * var AccountThroughCampaignReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class AdExtensionByAdsReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfAdExtensionByAdsReportColumn
	 */
	public $Columns;

	/**
	 * var AccountThroughCampaignReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class AdExtensionByKeywordReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfAdExtensionByKeywordReportColumn
	 */
	public $Columns;

	/**
	 * var AccountThroughCampaignReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class AdExtensionDimensionReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfAdExtensionDimensionReportColumn
	 */
	public $Columns;

	/**
	 * var AccountReportScope
	 */
	public $Scope;

}


class AdGroupPerformanceReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfAdDistributionReportFilter
	 */
	public $AdDistribution;

	/**
	 * var EnumOfDeviceTypeReportFilter
	 */
	public $DeviceType;

	/**
	 * var EnumOfLanguageAndRegionReportFilter
	 */
	public $LanguageAndRegion;

	/**
	 * var ArrayOfstring
	 */
	public $LanguageCode;

	/**
	 * var EnumOfAdGroupStatusReportFilter
	 */
	public $Status;

}


class AdGroupPerformanceReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfAdGroupPerformanceReportColumn
	 */
	public $Columns;

	/**
	 * var AdGroupPerformanceReportFilter
	 */
	public $Filter;

	/**
	 * var AccountThroughAdGroupReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class AdGroupReportScope extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $ParentAccountId;

	/**
	 * var integer
	 */
	public $ParentCampaignId;

	/**
	 * var integer
	 */
	public $AdGroupId;

}


class AdPerformanceReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfAdDistributionReportFilter
	 */
	public $AdDistribution;

	/**
	 * var EnumOfAdTypeReportFilter
	 */
	public $AdType;

	/**
	 * var EnumOfDeviceTypeReportFilter
	 */
	public $DeviceType;

	/**
	 * var EnumOfLanguageAndRegionReportFilter
	 */
	public $LanguageAndRegion;

	/**
	 * var ArrayOfstring
	 */
	public $LanguageCode;

}


class AdPerformanceReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfNonHourlyReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfAdPerformanceReportColumn
	 */
	public $Columns;

	/**
	 * var AdPerformanceReportFilter
	 */
	public $Filter;

	/**
	 * var AccountThroughAdGroupReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class AgeGenderDemographicReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfAdDistributionReportFilter
	 */
	public $AdDistribution;

	/**
	 * var EnumOfLanguageAndRegionReportFilter
	 */
	public $LanguageAndRegion;

	/**
	 * var ArrayOfstring
	 */
	public $LanguageCode;

}


class AgeGenderDemographicReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfNonHourlyReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfAgeGenderDemographicReportColumn
	 */
	public $Columns;

	/**
	 * var AgeGenderDemographicReportFilter
	 */
	public $Filter;

	/**
	 * var AccountThroughAdGroupReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class BehavioralPerformanceReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfAdDistributionReportFilter
	 */
	public $AdDistribution;

	/**
	 * var EnumOfAdTypeReportFilter
	 */
	public $AdType;

	/**
	 * var ArrayOflong
	 */
	public $BehavioralIds;

	/**
	 * var EnumOfDeliveredMatchTypeReportFilter
	 */
	public $DeliveredMatchType;

	/**
	 * var EnumOfLanguageAndRegionReportFilter
	 */
	public $LanguageAndRegion;

}


class BehavioralPerformanceReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfBehavioralPerformanceReportColumn
	 */
	public $Columns;

	/**
	 * var BehavioralPerformanceReportFilter
	 */
	public $Filter;

	/**
	 * var AccountThroughAdGroupReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class BehavioralTargetReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfAdDistributionReportFilter
	 */
	public $AdDistribution;

	/**
	 * var ArrayOflong
	 */
	public $BehavioralIds;

	/**
	 * var EnumOfLanguageAndRegionReportFilter
	 */
	public $LanguageAndRegion;

}


class BehavioralTargetReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfNonHourlyReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfBehavioralTargetReportColumn
	 */
	public $Columns;

	/**
	 * var BehavioralTargetReportFilter
	 */
	public $Filter;

	/**
	 * var AccountThroughAdGroupReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class BudgetSummaryReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfBudgetSummaryReportColumn
	 */
	public $Columns;

	/**
	 * var AccountReportScope
	 */
	public $Scope;

	/**
	 * var BudgetSummaryReportTime
	 */
	public $Time;

}


class BudgetSummaryReportTime extends \phpadcenter\AdCenterObject
{
	/**
	 * var Date
	 */
	public $CustomDateRangeEnd;

	/**
	 * var Date
	 */
	public $CustomDateRangeStart;

	/**
	 * var EnumOfBudgetSummaryReportTimePeriod
	 */
	public $PredefinedTime;

}


class CampaignPerformanceReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfAdDistributionReportFilter
	 */
	public $AdDistribution;

	/**
	 * var EnumOfDeviceTypeReportFilter
	 */
	public $DeviceType;

	/**
	 * var EnumOfCampaignStatusReportFilter
	 */
	public $Status;

}


class CampaignPerformanceReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfCampaignPerformanceReportColumn
	 */
	public $Columns;

	/**
	 * var CampaignPerformanceReportFilter
	 */
	public $Filter;

	/**
	 * var AccountThroughCampaignReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class CampaignReportScope extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $ParentAccountId;

	/**
	 * var integer
	 */
	public $CampaignId;

}


class ConversionPerformanceReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfDeviceTypeReportFilter
	 */
	public $DeviceType;

	/**
	 * var ArrayOfstring
	 */
	public $Keywords;

}


class ConversionPerformanceReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfNonHourlyReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfConversionPerformanceReportColumn
	 */
	public $Columns;

	/**
	 * var ConversionPerformanceReportFilter
	 */
	public $Filter;

	/**
	 * var AccountThroughAdGroupReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class DestinationUrlPerformanceReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfAdDistributionReportFilter
	 */
	public $AdDistribution;

	/**
	 * var EnumOfDeviceTypeReportFilter
	 */
	public $DeviceType;

	/**
	 * var EnumOfLanguageAndRegionReportFilter
	 */
	public $LanguageAndRegion;

	/**
	 * var ArrayOfstring
	 */
	public $LanguageCode;

}


class DestinationUrlPerformanceReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfNonHourlyReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfDestinationUrlPerformanceReportColumn
	 */
	public $Columns;

	/**
	 * var DestinationUrlPerformanceReportFilter
	 */
	public $Filter;

	/**
	 * var AccountThroughAdGroupReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class GoalsAndFunnelsReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOflong
	 */
	public $GoalIds;

}


class GoalsAndFunnelsReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfNonHourlyReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfGoalsAndFunnelsReportColumn
	 */
	public $Columns;

	/**
	 * var GoalsAndFunnelsReportFilter
	 */
	public $Filter;

	/**
	 * var AccountThroughAdGroupReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class KeywordMigrationReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfKeywordMigrationReportColumn
	 */
	public $Columns;

	/**
	 * var AccountThroughCampaignReportScope
	 */
	public $Scope;

}


class KeywordPerformanceReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfAdDistributionReportFilter
	 */
	public $AdDistribution;

	/**
	 * var EnumOfAdTypeReportFilter
	 */
	public $AdType;

	/**
	 * var EnumOfBidMatchTypeReportFilter
	 */
	public $BidMatchType;

	/**
	 * var EnumOfCashbackReportFilter
	 */
	public $Cashback;

	/**
	 * var EnumOfDeliveredMatchTypeReportFilter
	 */
	public $DeliveredMatchType;

	/**
	 * var EnumOfDeviceTypeReportFilter
	 */
	public $DeviceType;

	/**
	 * var ArrayOfint
	 */
	public $KeywordRelevance;

	/**
	 * var ArrayOfstring
	 */
	public $Keywords;

	/**
	 * var ArrayOfint
	 */
	public $LandingPageRelevance;

	/**
	 * var ArrayOfint
	 */
	public $LandingPageUserExperience;

	/**
	 * var EnumOfLanguageAndRegionReportFilter
	 */
	public $LanguageAndRegion;

	/**
	 * var ArrayOfstring
	 */
	public $LanguageCode;

	/**
	 * var ArrayOfint
	 */
	public $QualityScore;

}


class KeywordPerformanceReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfKeywordPerformanceReportColumn
	 */
	public $Columns;

	/**
	 * var KeywordPerformanceReportFilter
	 */
	public $Filter;

	/**
	 * var AccountThroughAdGroupReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class MetroAreaDemographicReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfAdDistributionReportFilter
	 */
	public $AdDistribution;

	/**
	 * var EnumOfCountryReportFilter
	 */
	public $Country;

	/**
	 * var EnumOfLanguageAndRegionReportFilter
	 */
	public $LanguageAndRegion;

	/**
	 * var ArrayOfstring
	 */
	public $LanguageCode;

}


class MetroAreaDemographicReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfNonHourlyReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfMetroAreaDemographicReportColumn
	 */
	public $Columns;

	/**
	 * var MetroAreaDemographicReportFilter
	 */
	public $Filter;

	/**
	 * var AccountThroughAdGroupReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class NegativeKeywordConflictReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfNegativeKeywordConflictReportColumn
	 */
	public $Columns;

	/**
	 * var AccountThroughAdGroupReportScope
	 */
	public $Scope;

}


class PublisherUsagePerformanceReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfAdDistributionReportFilter
	 */
	public $AdDistribution;

	/**
	 * var EnumOfLanguageAndRegionReportFilter
	 */
	public $LanguageAndRegion;

	/**
	 * var ArrayOfstring
	 */
	public $LanguageCode;

}


class PublisherUsagePerformanceReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfNonHourlyReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfPublisherUsagePerformanceReportColumn
	 */
	public $Columns;

	/**
	 * var PublisherUsagePerformanceReportFilter
	 */
	public $Filter;

	/**
	 * var AccountThroughAdGroupReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class ReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfReportFormat
	 */
	public $Format;

	/**
	 * var EnumOfReportLanguage
	 */
	public $Language;

	/**
	 * var string
	 */
	public $ReportName;

	/**
	 * var boolean
	 */
	public $ReturnOnlyCompleteData;

}


class ReportRequestStatus extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $ReportDownloadUrl;

	/**
	 * var EnumOfReportRequestStatusType
	 */
	public $Status;

}


class ReportTime extends \phpadcenter\AdCenterObject
{
	/**
	 * var Date
	 */
	public $CustomDateRangeEnd;

	/**
	 * var Date
	 */
	public $CustomDateRangeStart;

	/**
	 * var EnumOfReportTimePeriod
	 */
	public $PredefinedTime;

}


class RichAdComponentPerformanceReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfComponentTypeFilter
	 */
	public $ComponentType;

	/**
	 * var EnumOfRichAdSubTypeFilter
	 */
	public $RichAdSubType;

}


class RichAdComponentPerformanceReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfNonHourlyReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfRichAdComponentPerformanceReportColumn
	 */
	public $Columns;

	/**
	 * var RichAdComponentPerformanceReportFilter
	 */
	public $Filter;

	/**
	 * var AccountThroughAdGroupReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class SearchCampaignChangeHistoryReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfChangeTypeReportFilter
	 */
	public $HowChanged;

	/**
	 * var EnumOfChangeEntityReportFilter
	 */
	public $ItemChanged;

}


class SearchCampaignChangeHistoryReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfSearchCampaignChangeHistoryReportColumn
	 */
	public $Columns;

	/**
	 * var SearchCampaignChangeHistoryReportFilter
	 */
	public $Filter;

	/**
	 * var AccountThroughAdGroupReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class SearchQueryPerformanceReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfAdStatusReportFilter
	 */
	public $AdStatus;

	/**
	 * var EnumOfAdTypeReportFilter
	 */
	public $AdType;

	/**
	 * var EnumOfCampaignStatusReportFilter
	 */
	public $CampaignStatus;

	/**
	 * var EnumOfDeliveredMatchTypeReportFilter
	 */
	public $DeliveredMatchType;

	/**
	 * var EnumOfLanguageAndRegionReportFilter
	 */
	public $LanguageAndRegion;

	/**
	 * var ArrayOfstring
	 */
	public $LanguageCode;

	/**
	 * var ArrayOfstring
	 */
	public $SearchQueries;

}


class SearchQueryPerformanceReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfSearchQueryReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfSearchQueryPerformanceReportColumn
	 */
	public $Columns;

	/**
	 * var SearchQueryPerformanceReportFilter
	 */
	public $Filter;

	/**
	 * var AccountThroughAdGroupReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class SegmentationReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfAgeGroupReportFilter
	 */
	public $AgeGroup;

	/**
	 * var EnumOfCountryReportFilter
	 */
	public $Country;

	/**
	 * var EnumOfGenderReportFilter
	 */
	public $Gender;

	/**
	 * var ArrayOflong
	 */
	public $GoalIds;

	/**
	 * var ArrayOfstring
	 */
	public $Keywords;

}


class SegmentationReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfNonHourlyReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfSegmentationReportColumn
	 */
	public $Columns;

	/**
	 * var SegmentationReportFilter
	 */
	public $Filter;

	/**
	 * var AccountThroughAdGroupReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class ShareOfVoiceReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfAdDistributionReportFilter
	 */
	public $AdDistribution;

	/**
	 * var EnumOfBidMatchTypeReportFilter
	 */
	public $BidMatchType;

	/**
	 * var EnumOfDeliveredMatchTypeReportFilter
	 */
	public $DeliveredMatchType;

	/**
	 * var ArrayOfstring
	 */
	public $Keywords;

	/**
	 * var EnumOfLanguageAndRegionReportFilter
	 */
	public $LanguageAndRegion;

	/**
	 * var ArrayOfstring
	 */
	public $LanguageCode;

}


class ShareOfVoiceReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfNonHourlyReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfShareOfVoiceReportColumn
	 */
	public $Columns;

	/**
	 * var ShareOfVoiceReportFilter
	 */
	public $Filter;

	/**
	 * var AccountThroughAdGroupReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class SitePerformanceReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfAdDistributionReportFilter
	 */
	public $AdDistribution;

	/**
	 * var EnumOfAdTypeReportFilter
	 */
	public $AdType;

	/**
	 * var EnumOfDeliveredMatchTypeReportFilter
	 */
	public $DeliveredMatchType;

	/**
	 * var EnumOfDeviceTypeReportFilter
	 */
	public $DeviceType;

	/**
	 * var EnumOfLanguageAndRegionReportFilter
	 */
	public $LanguageAndRegion;

	/**
	 * var ArrayOflong
	 */
	public $SiteIds;

}


class SitePerformanceReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfSitePerformanceReportColumn
	 */
	public $Columns;

	/**
	 * var SitePerformanceReportFilter
	 */
	public $Filter;

	/**
	 * var AccountThroughAdGroupReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class TacticChannelReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOflong
	 */
	public $ChannelIds;

	/**
	 * var ArrayOflong
	 */
	public $TacticIds;

	/**
	 * var ArrayOflong
	 */
	public $ThirdPartyAdGroupIds;

	/**
	 * var ArrayOflong
	 */
	public $ThirdPartyCampaignIds;

}


class TacticChannelReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfNonHourlyReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfTacticChannelReportColumn
	 */
	public $Columns;

	/**
	 * var TacticChannelReportFilter
	 */
	public $Filter;

	/**
	 * var AccountThroughAdGroupReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


class TrafficSourcesReportFilter extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOflong
	 */
	public $GoalIds;

}


class TrafficSourcesReportRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfNonHourlyReportAggregation
	 */
	public $Aggregation;

	/**
	 * var ArrayOfTrafficSourcesReportColumn
	 */
	public $Columns;

	/**
	 * var TrafficSourcesReportFilter
	 */
	public $Filter;

	/**
	 * var AccountReportScope
	 */
	public $Scope;

	/**
	 * var ReportTime
	 */
	public $Time;

}


?>