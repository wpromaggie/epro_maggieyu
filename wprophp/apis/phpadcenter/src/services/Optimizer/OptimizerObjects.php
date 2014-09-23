<?php
namespace phpadcenter\services\Optimizer;

class BidOpportunity extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $AdGroupId;

	/**
	 * var double
	 */
	public $CurrentBid;

	/**
	 * var integer
	 */
	public $EstimatedIncreaseInClicks;

	/**
	 * var double
	 */
	public $EstimatedIncreaseInCost;

	/**
	 * var integer
	 */
	public $EstimatedIncreaseInImpressions;

	/**
	 * var integer
	 */
	public $KeywordId;

	/**
	 * var string
	 */
	public $MatchType;

	/**
	 * var double
	 */
	public $SuggestedBid;

}


class BudgetOpportunity extends \phpadcenter\AdCenterObject
{
	/**
	 * var dateTime
	 */
	public $BudgetDepletionDate;

	/**
	 * var EnumOfBudgetLimitType
	 */
	public $BudgetType;

	/**
	 * var integer
	 */
	public $CampaignId;

	/**
	 * var double
	 */
	public $CurrentBudget;

	/**
	 * var integer
	 */
	public $IncreaseInClicks;

	/**
	 * var integer
	 */
	public $IncreaseInImpressions;

	/**
	 * var integer
	 */
	public $PercentageIncreaseInClicks;

	/**
	 * var integer
	 */
	public $PercentageIncreaseInImpressions;

	/**
	 * var double
	 */
	public $RecommendedBudget;

}


class KeywordOpportunity extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $AdGroupId;

	/**
	 * var integer
	 */
	public $CampaignId;

	/**
	 * var double
	 */
	public $Competition;

	/**
	 * var integer
	 */
	public $MatchType;

	/**
	 * var integer
	 */
	public $MonthlySearches;

	/**
	 * var double
	 */
	public $SuggestedBid;

	/**
	 * var string
	 */
	public $SuggestedKeyword;

}


class Opportunity extends \phpadcenter\AdCenterObject
{
	/**
	 * var dateTime
	 */
	public $ExpirationDate;

	/**
	 * var string
	 */
	public $OpportunityKey;

}


?>