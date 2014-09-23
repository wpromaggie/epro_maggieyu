<?php
namespace phpadcenter\services\CampaignManagement;

class AccountAnalyticsType extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $AccountId;

	/**
	 * var EnumOfAnalyticsType
	 */
	public $Type;

}


class AccountMigrationStatusesInfo extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $AccountId;

	/**
	 * var ArrayOfMigrationStatusInfo
	 */
	public $MigrationStatusInfo;

}


class Ad extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfAdEditorialStatus
	 */
	public $EditorialStatus;

	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var EnumOfAdStatus
	 */
	public $Status;

	/**
	 * var EnumOfAdType
	 */
	public $Type;

}


class AdExtension extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $CampaignId;

	/**
	 * var boolean
	 */
	public $EnableLocationExtension;

	/**
	 * var PhoneExtension
	 */
	public $PhoneExtension;

}


class AdExtension2 extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var EnumOfAdExtensionStatus
	 */
	public $Status;

	/**
	 * var string
	 */
	public $Type;

	/**
	 * var integer
	 */
	public $Version;

}


class AdExtensionEditorialReason extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $Location;

	/**
	 * var ArrayOfstring
	 */
	public $PublisherCountries;

	/**
	 * var integer
	 */
	public $ReasonCode;

	/**
	 * var string
	 */
	public $Term;

}


class AdExtensionEditorialReasonCollection extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $AdExtensionId;

	/**
	 * var ArrayOfAdExtensionEditorialReason
	 */
	public $Reasons;

}


class AdExtensionIdToCampaignIdAssociation extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $AdExtensionId;

	/**
	 * var integer
	 */
	public $CampaignId;

}


class AdExtensionIdentity extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var integer
	 */
	public $Version;

}


class AdGroup extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfAdDistribution
	 */
	public $AdDistribution;

	/**
	 * var EnumOfBiddingModel
	 */
	public $BiddingModel;

	/**
	 * var Bid
	 */
	public $BroadMatchBid;

	/**
	 * var Bid
	 */
	public $ContentMatchBid;

	/**
	 * var Date
	 */
	public $EndDate;

	/**
	 * var Bid
	 */
	public $ExactMatchBid;

	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var string
	 */
	public $Language;

	/**
	 * var string
	 */
	public $Name;

	/**
	 * var EnumOfNetwork
	 */
	public $Network;

	/**
	 * var Bid
	 */
	public $PhraseMatchBid;

	/**
	 * var EnumOfPricingModel
	 */
	public $PricingModel;

	/**
	 * var ArrayOfPublisherCountry
	 */
	public $PublisherCountries;

	/**
	 * var Date
	 */
	public $StartDate;

	/**
	 * var EnumOfAdGroupStatus
	 */
	public $Status;

}


class AdGroupAdRotation extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $AdGroupId;

	/**
	 * var AdRotation
	 */
	public $AdRotation;

}


class AdGroupCriterion extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $AdGroupId;

	/**
	 * var Criterion
	 */
	public $Criterion;

	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var EnumOfAdGroupCriterionStatus
	 */
	public $Status;

	/**
	 * var string
	 */
	public $Type;

}


class AdGroupNegativeKeywords extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $AdGroupId;

	/**
	 * var ArrayOfstring
	 */
	public $NegativeKeywords;

}


class AdGroupNegativeSites extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $AdGroupId;

	/**
	 * var ArrayOfstring
	 */
	public $NegativeSites;

}


class AdRotation extends \phpadcenter\AdCenterObject
{
	/**
	 * var dateTime
	 */
	public $EndDate;

	/**
	 * var dateTime
	 */
	public $StartDate;

	/**
	 * var EnumOfAdRotationType
	 */
	public $Type;

}


class AgeTarget extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfAgeTargetBid
	 */
	public $Bids;

}


class AgeTargetBid extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfAgeRange
	 */
	public $Age;

	/**
	 * var EnumOfIncrementalBidPercentage
	 */
	public $IncrementalBid;

}


class AnalyticsApiFaultDetail extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfGoalError
	 */
	public $GoalErrors;

	/**
	 * var ArrayOfOperationError
	 */
	public $OperationErrors;

}


class Bid extends \phpadcenter\AdCenterObject
{
	/**
	 * var double
	 */
	public $Amount;

}


class BiddableAdGroupCriterion extends \phpadcenter\AdCenterObject
{
	/**
	 * var CriterionBid
	 */
	public $CriterionBid;

}


class Business extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $AddressLine1;

	/**
	 * var string
	 */
	public $AddressLine2;

	/**
	 * var BusinessImageIcon
	 */
	public $BusinessImageIcon;

	/**
	 * var string
	 */
	public $City;

	/**
	 * var string
	 */
	public $CountryOrRegion;

	/**
	 * var string
	 */
	public $Description;

	/**
	 * var string
	 */
	public $Email;

	/**
	 * var EnumOfBusinessGeoCodeStatus
	 */
	public $GeoCodeStatus;

	/**
	 * var ArrayOfHoursOfOperation
	 */
	public $HrsOfOperation;

	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var boolean
	 */
	public $IsOpen24Hours;

	/**
	 * var double
	 */
	public $LatitudeDegrees;

	/**
	 * var double
	 */
	public $LongitudeDegrees;

	/**
	 * var string
	 */
	public $Name;

	/**
	 * var string
	 */
	public $OtherPaymentTypeDesc;

	/**
	 * var ArrayOfPaymentType
	 */
	public $Payment;

	/**
	 * var string
	 */
	public $Phone;

	/**
	 * var string
	 */
	public $StateOrProvince;

	/**
	 * var EnumOfBusinessStatus
	 */
	public $Status;

	/**
	 * var string
	 */
	public $URL;

	/**
	 * var string
	 */
	public $ZipOrPostalCode;

}


class BusinessImageIcon extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $CustomIconAssetId;

	/**
	 * var EnumOfStandardBusinessIcon
	 */
	public $StandardBusinessIcon;

}


class BusinessInfo extends \phpadcenter\AdCenterObject
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


class BusinessTarget extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfBusinessTargetBid
	 */
	public $Bids;

}


class BusinessTargetBid extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $BusinessId;

	/**
	 * var EnumOfIncrementalBidPercentage
	 */
	public $IncrementalBid;

	/**
	 * var integer
	 */
	public $Radius;

}


class CallAdExtension extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $CountryCode;

	/**
	 * var boolean
	 */
	public $IsCallOnly;

	/**
	 * var boolean
	 */
	public $IsCallTrackingEnabled;

	/**
	 * var string
	 */
	public $PhoneNumber;

	/**
	 * var boolean
	 */
	public $RequireTollFreeTrackingNumber;

}


class Campaign extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfBudgetLimitType
	 */
	public $BudgetType;

	/**
	 * var boolean
	 */
	public $ConversionTrackingEnabled;

	/**
	 * var double
	 */
	public $DailyBudget;

	/**
	 * var boolean
	 */
	public $DaylightSaving;

	/**
	 * var string
	 */
	public $Description;

	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var double
	 */
	public $MonthlyBudget;

	/**
	 * var string
	 */
	public $Name;

	/**
	 * var EnumOfCampaignStatus
	 */
	public $Status;

	/**
	 * var string
	 */
	public $TimeZone;

}


class CampaignAdExtension extends \phpadcenter\AdCenterObject
{
	/**
	 * var AdExtension2
	 */
	public $AdExtension;

	/**
	 * var integer
	 */
	public $CampaignId;

	/**
	 * var EnumOfCampaignAdExtensionEditorialStatus
	 */
	public $EditorialStatus;

}


class CampaignAdExtensionCollection extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfCampaignAdExtension
	 */
	public $CampaignAdExtensions;

}


class CampaignNegativeKeywords extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $CampaignId;

	/**
	 * var ArrayOfstring
	 */
	public $NegativeKeywords;

}


class CampaignNegativeSites extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $CampaignId;

	/**
	 * var ArrayOfstring
	 */
	public $NegativeSites;

}


class CityTarget extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfCityTargetBid
	 */
	public $Bids;

}


class CityTargetBid extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $City;

	/**
	 * var EnumOfIncrementalBidPercentage
	 */
	public $IncrementalBid;

}


class CountryTarget extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfCountryTargetBid
	 */
	public $Bids;

}


class CountryTargetBid extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $CountryAndRegion;

	/**
	 * var EnumOfIncrementalBidPercentage
	 */
	public $IncrementalBid;

}


class Criterion extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $Type;

}


class CriterionBid extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $Type;

}


class DayTarget extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfDayTargetBid
	 */
	public $Bids;

	/**
	 * var boolean
	 */
	public $TargetAllDays;

}


class DayTargetBid extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfDay
	 */
	public $Day;

	/**
	 * var EnumOfIncrementalBidPercentage
	 */
	public $IncrementalBid;

}


class DayTimeInterval extends \phpadcenter\AdCenterObject
{
	/**
	 * var TimeOfTheDay
	 */
	public $Begin;

	/**
	 * var TimeOfTheDay
	 */
	public $End;

}


class DeviceOS extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $DeviceName;

	/**
	 * var string
	 */
	public $OSName;

}


class DeviceOSTarget extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfDeviceOS
	 */
	public $DeviceOSList;

}


class DeviceTarget extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfDeviceType
	 */
	public $Devices;

}


class Dimension extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $Height;

	/**
	 * var integer
	 */
	public $Width;

}


class EditorialApiFaultDetail extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfBatchError
	 */
	public $BatchErrors;

	/**
	 * var ArrayOfEditorialError
	 */
	public $EditorialErrors;

	/**
	 * var ArrayOfOperationError
	 */
	public $OperationErrors;

}


class EditorialError extends \phpadcenter\AdCenterObject
{
	/**
	 * var boolean
	 */
	public $Appealable;

	/**
	 * var integer
	 */
	public $Code;

	/**
	 * var string
	 */
	public $DisapprovedText;

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

	/**
	 * var string
	 */
	public $PublisherCountry;

}


class EditorialReason extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfAdComponent
	 */
	public $Location;

	/**
	 * var ArrayOfstring
	 */
	public $PublisherCountries;

	/**
	 * var integer
	 */
	public $ReasonCode;

	/**
	 * var string
	 */
	public $Term;

}


class EditorialReasonCollection extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $AdOrKeywordId;

	/**
	 * var EnumOfAppealStatus
	 */
	public $AppealStatus;

	/**
	 * var ArrayOfEditorialReason
	 */
	public $Reasons;

}


class Entity extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfEntityType
	 */
	public $EntityType;

	/**
	 * var integer
	 */
	public $Id;

}


class EntityToExclusionsAssociation extends \phpadcenter\AdCenterObject
{
	/**
	 * var Entity
	 */
	public $AssociatedEntity;

	/**
	 * var ArrayOfExclusion
	 */
	public $Exclusions;

}


class ExcludedGeoLocation extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $LocationName;

	/**
	 * var EnumOfGeoLocationType
	 */
	public $LocationType;

}


class ExcludedRadiusLocation extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var double
	 */
	public $LatitudeDegrees;

	/**
	 * var double
	 */
	public $LongitudeDegrees;

	/**
	 * var string
	 */
	public $Name;

	/**
	 * var integer
	 */
	public $Radius;

}


class ExcludedRadiusTarget extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfExcludedRadiusLocation
	 */
	public $ExcludedRadiusLocations;

}


class Exclusion extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var string
	 */
	public $Type;

}


class ExclusionToEntityAssociation extends \phpadcenter\AdCenterObject
{
	/**
	 * var Entity
	 */
	public $AssociatedEntity;

	/**
	 * var Exclusion
	 */
	public $Exclusion;

}


class FixedBid extends \phpadcenter\AdCenterObject
{
	/**
	 * var Bid
	 */
	public $Bid;

}


class GenderTarget extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfGenderTargetBid
	 */
	public $Bids;

}


class GenderTargetBid extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfGenderType
	 */
	public $Gender;

	/**
	 * var EnumOfIncrementalBidPercentage
	 */
	public $IncrementalBid;

}


class GeoPoint extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $LatitudeInMicroDegrees;

	/**
	 * var integer
	 */
	public $LongitudeInMicroDegrees;

}


class Goal extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfCostModel
	 */
	public $CostModel;

	/**
	 * var EnumOfDaysApplicableForConversion
	 */
	public $DaysApplicableForConversion;

	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var string
	 */
	public $Name;

	/**
	 * var RevenueModel
	 */
	public $RevenueModel;

	/**
	 * var ArrayOfStep
	 */
	public $Steps;

	/**
	 * var integer
	 */
	public $YEventId;

}


class GoalError extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfBatchError
	 */
	public $BatchErrors;

	/**
	 * var integer
	 */
	public $Index;

	/**
	 * var ArrayOfBatchError
	 */
	public $StepErrors;

}


class GoalResult extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $GoalId;

	/**
	 * var ArrayOflong
	 */
	public $StepIds;

}


class HourTarget extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfHourTargetBid
	 */
	public $Bids;

	/**
	 * var boolean
	 */
	public $TargetAllHours;

}


class HourTargetBid extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfHourRange
	 */
	public $Hour;

	/**
	 * var EnumOfIncrementalBidPercentage
	 */
	public $IncrementalBid;

}


class HoursOfOperation extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfDay
	 */
	public $Day;

	/**
	 * var DayTimeInterval
	 */
	public $openTime1;

	/**
	 * var DayTimeInterval
	 */
	public $openTime2;

}


class ImpressionsPerDayRange extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $Maximum;

	/**
	 * var integer
	 */
	public $Minimum;

}


class Keyword extends \phpadcenter\AdCenterObject
{
	/**
	 * var Bid
	 */
	public $BroadMatchBid;

	/**
	 * var Bid
	 */
	public $ContentMatchBid;

	/**
	 * var EnumOfKeywordEditorialStatus
	 */
	public $EditorialStatus;

	/**
	 * var Bid
	 */
	public $ExactMatchBid;

	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var ArrayOfstring
	 */
	public $NegativeKeywords;

	/**
	 * var string
	 */
	public $Param1;

	/**
	 * var string
	 */
	public $Param2;

	/**
	 * var string
	 */
	public $Param3;

	/**
	 * var Bid
	 */
	public $PhraseMatchBid;

	/**
	 * var EnumOfKeywordStatus
	 */
	public $Status;

	/**
	 * var string
	 */
	public $Text;

}


class KeywordDestinationUrl extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $DestinationUrl;

	/**
	 * var integer
	 */
	public $KeywordId;

}


class LocationAdExtension extends \phpadcenter\AdCenterObject
{
	/**
	 * var Address
	 */
	public $Address;

	/**
	 * var string
	 */
	public $CompanyName;

	/**
	 * var EnumOfBusinessGeoCodeStatus
	 */
	public $GeoCodeStatus;

	/**
	 * var GeoPoint
	 */
	public $GeoPoint;

	/**
	 * var integer
	 */
	public $IconMediaId;

	/**
	 * var integer
	 */
	public $ImageMediaId;

	/**
	 * var string
	 */
	public $PhoneNumber;

}


class LocationExclusion extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfExcludedGeoLocation
	 */
	public $ExcludedGeoTargets;

	/**
	 * var ExcludedRadiusTarget
	 */
	public $ExcludedRadiusTarget;

}


class LocationTarget extends \phpadcenter\AdCenterObject
{
	/**
	 * var BusinessTarget
	 */
	public $BusinessTarget;

	/**
	 * var CityTarget
	 */
	public $CityTarget;

	/**
	 * var CountryTarget
	 */
	public $CountryTarget;

	/**
	 * var boolean
	 */
	public $HasPhysicalIntent;

	/**
	 * var MetroAreaTarget
	 */
	public $MetroAreaTarget;

	/**
	 * var RadiusTarget
	 */
	public $RadiusTarget;

	/**
	 * var StateTarget
	 */
	public $StateTarget;

	/**
	 * var boolean
	 */
	public $TargetAllLocations;

}


class MediaType extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfDimension
	 */
	public $Dimensions;

	/**
	 * var string
	 */
	public $Name;

}


class MetroAreaTarget extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfMetroAreaTargetBid
	 */
	public $Bids;

}


class MetroAreaTargetBid extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfIncrementalBidPercentage
	 */
	public $IncrementalBid;

	/**
	 * var string
	 */
	public $MetroArea;

}


class MigrationStatusInfo extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $MigrationType;

	/**
	 * var dateTime
	 */
	public $StartTimeInUtc;

	/**
	 * var EnumOfMigrationStatus
	 */
	public $Status;

}


class MobileAd extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $BusinessName;

	/**
	 * var string
	 */
	public $DestinationUrl;

	/**
	 * var string
	 */
	public $DisplayUrl;

	/**
	 * var string
	 */
	public $PhoneNumber;

	/**
	 * var string
	 */
	public $Text;

	/**
	 * var string
	 */
	public $Title;

}


class PhoneExtension extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $Country;

	/**
	 * var boolean
	 */
	public $EnableClickToCallOnly;

	/**
	 * var boolean
	 */
	public $EnablePhoneExtension;

	/**
	 * var string
	 */
	public $Phone;

}


class PlacementDetail extends \phpadcenter\AdCenterObject
{
	/**
	 * var ImpressionsPerDayRange
	 */
	public $ImpressionsRangePerDay;

	/**
	 * var string
	 */
	public $PathName;

	/**
	 * var integer
	 */
	public $PlacementId;

	/**
	 * var ArrayOfMediaType
	 */
	public $SupportedMediaTypes;

}


class Product extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfProductCondition
	 */
	public $Conditions;

}


class ProductAd extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $PromotionalText;

}


class ProductAdExtension extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $Name;

	/**
	 * var integer
	 */
	public $ProductCollectionId;

	/**
	 * var ArrayOfProductConditionCollection
	 */
	public $ProductSelection;

}


class ProductCondition extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $Attribute;

	/**
	 * var string
	 */
	public $Operand;

}


class ProductConditionCollection extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfProductCondition
	 */
	public $Conditions;

}


class PublisherCountry extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $Country;

	/**
	 * var boolean
	 */
	public $IsOptedIn;

}


class RadiusTarget extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfRadiusTargetBid
	 */
	public $Bids;

}


class RadiusTargetBid extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var EnumOfIncrementalBidPercentage
	 */
	public $IncrementalBid;

	/**
	 * var double
	 */
	public $LatitudeDegrees;

	/**
	 * var double
	 */
	public $LongitudeDegrees;

	/**
	 * var string
	 */
	public $Name;

	/**
	 * var integer
	 */
	public $Radius;

}


class RevenueModel extends \phpadcenter\AdCenterObject
{
	/**
	 * var double
	 */
	public $ConstantRevenueValue;

	/**
	 * var EnumOfRevenueModelType
	 */
	public $Type;

}


class SiteLink extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $DestinationUrl;

	/**
	 * var string
	 */
	public $DisplayText;

}


class SiteLinksAdExtension extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfSiteLink
	 */
	public $SiteLinks;

}


class SitePlacement extends \phpadcenter\AdCenterObject
{
	/**
	 * var Bid
	 */
	public $Bid;

	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var integer
	 */
	public $PlacementId;

	/**
	 * var EnumOfSitePlacementStatus
	 */
	public $Status;

	/**
	 * var string
	 */
	public $Url;

}


class StateTarget extends \phpadcenter\AdCenterObject
{
	/**
	 * var ArrayOfStateTargetBid
	 */
	public $Bids;

}


class StateTargetBid extends \phpadcenter\AdCenterObject
{
	/**
	 * var EnumOfIncrementalBidPercentage
	 */
	public $IncrementalBid;

	/**
	 * var string
	 */
	public $State;

}


class Step extends \phpadcenter\AdCenterObject
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
	 * var integer
	 */
	public $PositionNumber;

	/**
	 * var string
	 */
	public $Script;

	/**
	 * var EnumOfStepType
	 */
	public $Type;

}


class Target extends \phpadcenter\AdCenterObject
{
	/**
	 * var AgeTarget
	 */
	public $Age;

	/**
	 * var DayTarget
	 */
	public $Day;

	/**
	 * var DeviceTarget
	 */
	public $Device;

	/**
	 * var GenderTarget
	 */
	public $Gender;

	/**
	 * var HourTarget
	 */
	public $Hour;

	/**
	 * var integer
	 */
	public $Id;

	/**
	 * var boolean
	 */
	public $IsLibraryTarget;

	/**
	 * var LocationTarget
	 */
	public $Location;

	/**
	 * var string
	 */
	public $Name;

}


class TargetAssociation extends \phpadcenter\AdCenterObject
{
	/**
	 * var DeviceOSTarget
	 */
	public $DeviceOSTarget;

	/**
	 * var integer
	 */
	public $Id;

}


class TargetInfo extends \phpadcenter\AdCenterObject
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


class TextAd extends \phpadcenter\AdCenterObject
{
	/**
	 * var string
	 */
	public $DestinationUrl;

	/**
	 * var string
	 */
	public $DisplayUrl;

	/**
	 * var string
	 */
	public $Text;

	/**
	 * var string
	 */
	public $Title;

}


class TimeOfTheDay extends \phpadcenter\AdCenterObject
{
	/**
	 * var integer
	 */
	public $Hour;

	/**
	 * var integer
	 */
	public $Minute;

}


?>