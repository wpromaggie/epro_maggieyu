<?php
namespace phpadcenter\services\CustomerBilling;

class ApiBatchFault extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfBatchError
	 */
	public $BatchErrors;

}


class InsertionOrder extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $AccountId;

	/**
	 * var double
	 */
	public $BalanceAmount;

	/**
	 * var string
	 */
	public $BookingCountryCode;

	/**
	 * var string
	 */
	public $Comment;

	/**
	 * var dateTime
	 */
	public $EndDate;

	/**
	 * var integer
	 */
	public $InsertionOrderId;

	/**
	 * var integer
	 */
	public $LastModifiedByUserId;

	/**
	 * var dateTime
	 */
	public $LastModifiedTime;

	/**
	 * var double
	 */
	public $NotificationThreshold;

	/**
	 * var integer
	 */
	public $ReferenceId;

	/**
	 * var double
	 */
	public $SpendCapAmount;

	/**
	 * var dateTime
	 */
	public $StartDate;

}


class Invoice extends \phpadcenter\AdCenterObject
{
	/**
	 * var base64Binary
	 */
	public $Data;

	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var EnumOfDataType
	 */
	public $Type;

}


class InvoiceInfo extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $AccountId;

	/**
	 * var string
	 */
	public $AccountName;

	/**
	 * var string
	 */
	public $AccountNumber;

	/**
	 * var double
	 */
	public $Amount;

	/**
	 * var string
	 */
	public $CurrencyCode;

	/**
	 * var dateTime
	 */
	public $InvoiceDate;

	/**
	 * var integer
	 */
	public $InvoiceId;

}


?>