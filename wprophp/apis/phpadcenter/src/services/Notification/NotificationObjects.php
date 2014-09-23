<?php
namespace phpadcenter\services\Notification;

class AccountNotification extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $AccountId;

	/**
	 * var string
	 */
	public $AccountNumber;

}


class BudgetDepletedCampaignInfo extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $CurrencyCode;

	/**
	 * var dateTime
	 */
	public $BudgetDepletedDate;

}


class BudgetDepletedNotification extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfBudgetDepletedCampaignInfo
	 */
	public $AffectedCampaigns;

	/**
	 * var string
	 */
	public $AccountName;

}


class CampaignInfo extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $CampaignId;

	/**
	 * var string
	 */
	public $CampaignName;

	/**
	 * var double
	 */
	public $BudgetAmount;

}


class CreditCardPendingExpirationNotification extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $CardType;

	/**
	 * var string
	 */
	public $LastFourDigits;

	/**
	 * var string
	 */
	public $AccountName;

}


class EditorialRejectionNotification extends \phpadcenter\AdCenterObject
{
}


class ExpiredCreditCardNotification extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $CardType;

	/**
	 * var string
	 */
	public $LastFourDigits;

	/**
	 * var string
	 */
	public $AccountName;

}


class ExpiredInsertionOrderNotification extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $BillToCustomerName;

}


class LowBudgetBalanceCampaignInfo extends \phpadcenter\AdCenterObject
{
	/**
	 * var double
	 */
	public $EstimatedBudgetBalance;

	/**
	 * var integer
	 */
	public $EstimatedImpressions;

}


class LowBudgetBalanceNotification extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $CustomerId;

	/**
	 * var ArrayOfLowBudgetBalanceCampaignInfo
	 */
	public $AffectedCampaigns;

	/**
	 * var string
	 */
	public $AccountName;

}


?>