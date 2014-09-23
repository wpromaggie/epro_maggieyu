<?php
namespace phpadcenter\common;

class AdApiError extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $Code;

	/**
	 * var string
	 */
	public $Detail;

	/**
	 * var string
	 */
	public $ErrorCode;

	/**
	 * var string
	 */
	public $Message;

}


class AdApiFaultDetail extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfAdApiError
	 */
	public $Errors;

}


class ApiFaultDetail extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfBatchError
	 */
	public $BatchErrors;

	/**
	 * var ArrayOfOperationError
	 */
	public $OperationErrors;

}


class ApplicationFault extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $TrackingId;

}


class BatchError extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $Code;

	/**
	 * var string
	 */
	public $Details;

	/**
	 * var string
	 */
	public $ErrorCode;

	/**
	 * var integer
	 */
	public $Index;

	/**
	 * var string
	 */
	public $Message;

}


class OperationError extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $Code;

	/**
	 * var string
	 */
	public $Details;

	/**
	 * var string
	 */
	public $ErrorCode;

	/**
	 * var string
	 */
	public $Message;

}


class Address extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $CityName;

	/**
	 * var string
	 */
	public $CountryCode;

	/**
	 * var string
	 */
	public $PostalCode;

	/**
	 * var string
	 */
	public $ProvinceCode;

	/**
	 * var string
	 */
	public $ProvinceName;

	/**
	 * var string
	 */
	public $StreetAddress;

	/**
	 * var string
	 */
	public $StreetAddress2;

	/**
	 * var string
	 */
	public $City;

	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var string
	 */
	public $Line1;

	/**
	 * var string
	 */
	public $Line2;

	/**
	 * var string
	 */
	public $Line3;

	/**
	 * var string
	 */
	public $Line4;

	/**
	 * var string
	 */
	public $StateOrProvince;

	/**
	 * var base64Binary
	 */
	public $TimeStamp;

}


class Date extends \phpadcenter\AdCenterObject
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


class ApiFault extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfOperationError
	 */
	public $OperationErrors;

}



?>