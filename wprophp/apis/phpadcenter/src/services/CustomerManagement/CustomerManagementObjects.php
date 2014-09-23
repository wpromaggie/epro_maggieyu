<?php
namespace phpadcenter\services\CustomerManagement;

class Account extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfAccountType
	 */
	public $AccountType;

	/**
	 * var integer
	 */
	public $BillToCustomerId;

	/**
	 * var string
	 */
	public $CountryCode;

	/**
	 * var EnumOfCurrencyType
	 */
	public $CurrencyType;

	/**
	 * var EnumOfAccountFinancialStatus
	 */
	public $AccountFinancialStatus;

	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var EnumOfLanguageType
	 */
	public $Language;

	/**
	 * var integer
	 */
	public $LastModifiedByUserId;

	/**
	 * var dateTime
	 */
	public $LastModifiedTime;

	/**
	 * var string
	 */
	public $Name;

	/**
	 * var string
	 */
	public $Number;

	/**
	 * var integer
	 */
	public $ParentCustomerId;

	/**
	 * var integer
	 */
	public $PaymentMethodId;

	/**
	 * var EnumOfPaymentMethodType
	 */
	public $PaymentMethodType;

	/**
	 * var integer
	 */
	public $PrimaryUserId;

	/**
	 * var EnumOfAccountLifeCycleStatus
	 */
	public $AccountLifeCycleStatus;

	/**
	 * var base64Binary
	 */
	public $TimeStamp;

	/**
	 * var EnumOfTimeZoneType
	 */
	public $TimeZone;

	/**
	 * var unsignedByte
	 */
	public $PauseReason;

}


class AccountInfo extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var string
	 */
	public $Name;

	/**
	 * var string
	 */
	public $Number;

	/**
	 * var EnumOfAccountLifeCycleStatus
	 */
	public $AccountLifeCycleStatus;

	/**
	 * var unsignedByte
	 */
	public $PauseReason;

}


class AccountInfoWithCustomerData extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $CustomerId;

	/**
	 * var string
	 */
	public $CustomerName;

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
	 * var EnumOfAccountLifeCycleStatus
	 */
	public $AccountLifeCycleStatus;

	/**
	 * var unsignedByte
	 */
	public $PauseReason;

}


class AdvertiserAccount extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $AgencyContactName;

	/**
	 * var integer
	 */
	public $AgencyCustomerId;

	/**
	 * var integer
	 */
	public $SalesHouseCustomerId;

}


class ContactInfo extends \phpadcenter\AdCenterObject
{
	/**
	 * var Address
	 */
	public $Address;

	/**
	 * var boolean
	 */
	public $ContactByPhone;

	/**
	 * var boolean
	 */
	public $ContactByPostalMail;

	/**
	 * var string
	 */
	public $Email;

	/**
	 * var EnumOfEmailFormat
	 */
	public $EmailFormat;

	/**
	 * var string
	 */
	public $Fax;

	/**
	 * var string
	 */
	public $HomePhone;

	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var string
	 */
	public $Mobile;

	/**
	 * var string
	 */
	public $Phone1;

	/**
	 * var string
	 */
	public $Phone2;

}


class Customer extends \phpadcenter\AdCenterObject
{
	/**
	 * var Address
	 */
	public $CustomerAddress;

	/**
	 * var EnumOfCustomerFinancialStatus
	 */
	public $CustomerFinancialStatus;

	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var EnumOfIndustry
	 */
	public $Industry;

	/**
	 * var integer
	 */
	public $LastModifiedByUserId;

	/**
	 * var dateTime
	 */
	public $LastModifiedTime;

	/**
	 * var string
	 */
	public $MarketCountry;

	/**
	 * var EnumOfLanguageType
	 */
	public $MarketLanguage;

	/**
	 * var string
	 */
	public $Name;

	/**
	 * var EnumOfServiceLevel
	 */
	public $ServiceLevel;

	/**
	 * var EnumOfCustomerLifeCycleStatus
	 */
	public $CustomerLifeCycleStatus;

	/**
	 * var base64Binary
	 */
	public $TimeStamp;

	/**
	 * var string
	 */
	public $Number;

}


class CustomerInfo extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var string
	 */
	public $Name;

}


class ManageAccountsRequest extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfstring
	 */
	public $AdvertiserAccountNumbers;

	/**
	 * var string
	 */
	public $AgencyCustomerNumber;

	/**
	 * var Date
	 */
	public $EffectiveDate;

	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var integer
	 */
	public $LastModifiedByUserId;

	/**
	 * var dateTime
	 */
	public $LastModifiedDateTime;

	/**
	 * var string
	 */
	public $Notes;

	/**
	 * var integer
	 */
	public $PaymentMethodId;

	/**
	 * var dateTime
	 */
	public $RequestDate;

	/**
	 * var string
	 */
	public $RequesterContactEmail;

	/**
	 * var string
	 */
	public $RequesterContactName;

	/**
	 * var string
	 */
	public $RequesterContactPhoneNumber;

	/**
	 * var string
	 */
	public $RequesterCustomerNumber;

	/**
	 * var EnumOfManageAccountsRequestStatus
	 */
	public $RequestStatus;

	/**
	 * var ArrayOfstring
	 */
	public $RequestStatusDetails;

	/**
	 * var EnumOfManageAccountsRequestType
	 */
	public $RequestType;

	/**
	 * var base64Binary
	 */
	public $TimeStamp;

}


class ManageAccountsRequestInfo extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfstring
	 */
	public $AdvertiserAccountNumbers;

	/**
	 * var string
	 */
	public $AgencyCustomerNumber;

	/**
	 * var dateTime
	 */
	public $RequestDate;

	/**
	 * var Date
	 */
	public $EffectiveDate;

	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var EnumOfManageAccountsRequestStatus
	 */
	public $Status;

}


class PersonName extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $FirstName;

	/**
	 * var string
	 */
	public $LastName;

	/**
	 * var string
	 */
	public $MiddleInitial;

}


class PilotFeature extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var ArrayOfstring
	 */
	public $Countries;

}


class PublisherAccount extends \phpadcenter\AdCenterObject
{
}


class User extends \phpadcenter\AdCenterObject
{
	/**
	 * var ContactInfo
	 */
	public $ContactInfo;

	/**
	 * var EnumOfApplicationType
	 */
	public $CustomerAppScope;

	/**
	 * var integer
	 */
	public $CustomerId;

	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var string
	 */
	public $JobTitle;

	/**
	 * var integer
	 */
	public $LastModifiedByUserId;

	/**
	 * var dateTime
	 */
	public $LastModifiedTime;

	/**
	 * var EnumOfLCID
	 */
	public $Lcid;

	/**
	 * var PersonName
	 */
	public $Name;

	/**
	 * var string
	 */
	public $Password;

	/**
	 * var string
	 */
	public $SecretAnswer;

	/**
	 * var EnumOfSecretQuestion
	 */
	public $SecretQuestion;

	/**
	 * var EnumOfUserLifeCycleStatus
	 */
	public $UserLifeCycleStatus;

	/**
	 * var base64Binary
	 */
	public $TimeStamp;

	/**
	 * var string
	 */
	public $UserName;

}


class UserInfo extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var string
	 */
	public $UserName;

}


?>