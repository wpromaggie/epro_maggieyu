<?php
namespace phpadcenter\services\AdIntelligence;

class DayMonthAndYear extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $Day;

	/**
	 * var integer
	 */
	public $Month;

	/**
	 * var integer
	 */
	public $Year;

}


class EstimatedBidAndTraffic extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfMatchType
	 */
	public $MatchType;

	/**
	 * var integer
	 */
	public $MinClicksPerWeek;

	/**
	 * var integer
	 */
	public $MaxClicksPerWeek;

	/**
	 * var double
	 */
	public $AverageCPC;

	/**
	 * var integer
	 */
	public $MinImpressionsPerWeek;

	/**
	 * var integer
	 */
	public $MaxImpressionsPerWeek;

	/**
	 * var double
	 */
	public $CTR;

	/**
	 * var double
	 */
	public $MinTotalCostPerWeek;

	/**
	 * var double
	 */
	public $MaxTotalCostPerWeek;

	/**
	 * var EnumOfCurrency
	 */
	public $Currency;

	/**
	 * var double
	 */
	public $EstimatedMinBid;

}


class EstimatedPositionAndTraffic extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfMatchType
	 */
	public $MatchType;

	/**
	 * var integer
	 */
	public $MinClicksPerWeek;

	/**
	 * var integer
	 */
	public $MaxClicksPerWeek;

	/**
	 * var double
	 */
	public $AverageCPC;

	/**
	 * var integer
	 */
	public $MinImpressionsPerWeek;

	/**
	 * var integer
	 */
	public $MaxImpressionsPerWeek;

	/**
	 * var double
	 */
	public $CTR;

	/**
	 * var double
	 */
	public $MinTotalCostPerWeek;

	/**
	 * var double
	 */
	public $MaxTotalCostPerWeek;

	/**
	 * var EnumOfCurrency
	 */
	public $Currency;

	/**
	 * var EnumOfAdPosition
	 */
	public $EstimatedAdPosition;

}


class HistoricalSearchCount extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $SearchCount;

	/**
	 * var MonthAndYear
	 */
	public $MonthAndYear;

}


class HistoricalSearchCountPeriodic extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $SearchCount;

	/**
	 * var DayMonthAndYear
	 */
	public $DayMonthAndYear;

}


class KeywordAndConfidence extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $SuggestedKeyword;

	/**
	 * var double
	 */
	public $ConfidenceScore;

}


class KeywordCategory extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $Category;

	/**
	 * var double
	 */
	public $ConfidenceScore;

}


class KeywordCategoryResult extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $Keyword;

	/**
	 * var ArrayOfKeywordCategory
	 */
	public $KeywordCategories;

}


class KeywordDemographic extends \phpadcenter\AdCenterObject
{
	/**
	 * var double
	 */
	public $Age18_24;

	/**
	 * var double
	 */
	public $Age25_34;

	/**
	 * var double
	 */
	public $Age35_49;

	/**
	 * var double
	 */
	public $Age50_64;

	/**
	 * var double
	 */
	public $Age65Plus;

	/**
	 * var double
	 */
	public $AgeUnknown;

	/**
	 * var double
	 */
	public $Female;

	/**
	 * var double
	 */
	public $Male;

	/**
	 * var double
	 */
	public $GenderUnknown;

}


class KeywordDemographicResult extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $Keyword;

	/**
	 * var string
	 */
	public $Device;

	/**
	 * var KeywordDemographic
	 */
	public $KeywordDemographics;

}


class KeywordEstimatedBid extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $Keyword;

	/**
	 * var ArrayOfEstimatedBidAndTraffic
	 */
	public $EstimatedBids;

}


class KeywordEstimatedPosition extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $Keyword;

	/**
	 * var ArrayOfEstimatedPositionAndTraffic
	 */
	public $EstimatedPositions;

}


class KeywordHistoricalPerformance extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $Keyword;

	/**
	 * var ArrayOfKeywordKPI
	 */
	public $KeywordKPIs;

}


class KeywordHistoricalPerformanceByDevice extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $Keyword;

	/**
	 * var string
	 */
	public $Device;

	/**
	 * var ArrayOfKeywordKPI
	 */
	public $KeywordKPIs;

}


class KeywordIdEstimatedBid extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $KeywordId;

	/**
	 * var KeywordEstimatedBid
	 */
	public $KeywordEstimatedBid;

}


class KeywordIdEstimatedPosition extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $KeywordId;

	/**
	 * var KeywordEstimatedPosition
	 */
	public $KeywordEstimatedPosition;

}


class KeywordKPI extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfMatchType
	 */
	public $MatchType;

	/**
	 * var EnumOfAdPosition
	 */
	public $AdPosition;

	/**
	 * var integer
	 */
	public $Clicks;

	/**
	 * var integer
	 */
	public $Impressions;

	/**
	 * var double
	 */
	public $AverageCPC;

	/**
	 * var double
	 */
	public $CTR;

	/**
	 * var double
	 */
	public $TotalCost;

	/**
	 * var double
	 */
	public $AverageBid;

}


class KeywordLocation extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $Location;

	/**
	 * var double
	 */
	public $Percentage;

}


class KeywordLocationResult extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $Keyword;

	/**
	 * var string
	 */
	public $Device;

	/**
	 * var ArrayOfKeywordLocation
	 */
	public $KeywordLocations;

}


class KeywordPerformance extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $Keyword;

	/**
	 * var double
	 */
	public $AverageCpc;

	/**
	 * var EnumOfScale
	 */
	public $Impressions;

	/**
	 * var EnumOfScale
	 */
	public $BidDensity;

}


class KeywordSearchCount extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $Keyword;

	/**
	 * var ArrayOfHistoricalSearchCount
	 */
	public $HistoricalSearchCounts;

}


class KeywordSearchCountByDevice extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $Keyword;

	/**
	 * var string
	 */
	public $Device;

	/**
	 * var ArrayOfHistoricalSearchCountPeriodic
	 */
	public $HistoricalSearchCounts;

}


class KeywordSuggestion extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $Keyword;

	/**
	 * var ArrayOfKeywordAndConfidence
	 */
	public $SuggestionsAndConfidence;

}


class MonthAndYear extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $Month;

	/**
	 * var integer
	 */
	public $Year;

}


?>