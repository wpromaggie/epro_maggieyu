<?php
namespace phpadcenter\services\CampaignManagement;

class CampaignManagementEnum
{
	public static $AdComponent                        = array('Unknown','Keyword','KeywordParam1','KeywordParam2','KeywordParam3','AdTitleDescription','AdTitle','AdDescription','DisplayUrl','DestinationUrl','LandingUrl','SiteDomain','BusinessName','PhoneNumber','CashbackTextParam','AltText','Audio','Video','Flash','CAsset','Image','Destination','Asset','Ad','Order','BiddingKeyword','Association','Script','SiteLinkDestinationUrl','SiteLinkDisplayText','BusinessImage','MapIcon','AddressLine1','AddressLine2','LocationExtensionBusinessName','Country');
	public static $AdDistribution                     = array('Search','Content');
	public static $AdEditorialStatus                  = array('Active','Disapproved','Inactive');
	public static $AdExtensionStatus                  = array('Active','Deleted');
	public static $AdExtensionsTypeFilter             = array('SiteLinksAdExtension','LocationAdExtension','CallAdExtension','ProductsAdExtension');
	public static $AdGroupCriterionStatus             = array('Active','Paused','Deleted');
	public static $AdGroupStatus                      = array('Draft','Active','Paused','Deleted');
	public static $AdRotationType                     = array('OptimizeForClicks','RotateAdsEvenly');
	public static $AdStatus                           = array('Inactive','Active','Paused','Deleted');
	public static $AdType                             = array('Text','Image','Mobile','RichSearch','Product');
	public static $AgeRange                           = array('EighteenToTwentyFive','TwentyFiveToThirtyFive','ThirtyFiveToFifty','FiftyToSixtyFive','SixtyFiveAndAbove');
	public static $AnalyticsType                      = array('Enabled','Disabled','CampaignLevel');
	public static $AppealStatus                       = array('Appealable','AppealPending','NotAppealable');
	public static $BiddingModel                       = array('Keyword','SitePlacement');
	public static $BudgetLimitType                    = array('MonthlyBudgetSpendUntilDepleted','DailyBudgetAccelerated','DailyBudgetStandard');
	public static $BusinessGeoCodeStatus              = array('Pending','Complete','Invalid','Failed');
	public static $BusinessStatus                     = array('Active','Inactive','Pending');
	public static $CampaignAdExtensionEditorialStatus = array('Active','Disapproved','Inactive','ActiveLimited');
	public static $CampaignStatus                     = array('Active','Paused','BudgetPaused','BudgetAndManualPaused','Deleted');
	public static $CostModel                          = array('None','NonAdvertising','Taxed','Shipped');
	public static $CriterionType                      = array('Product');
	public static $Day                                = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
	public static $DaysApplicableForConversion        = array('Seven','Fifteen','Thirty','FortyFive');
	public static $DeviceType                         = array('Smartphones','Computers');
	public static $EntityType                         = array('Campaign','AdGroup','Target','Ad','Keyword','AdExtension');
	public static $ExclusionType                      = array('Location');
	public static $GenderType                         = array('Male','Female');
	public static $GeoLocationType                    = array('Country','SubGeography','MetroArea','City');
	public static $HourRange                          = array('ThreeAMToSevenAM','SevenAMToElevenAM','ElevenAMToTwoPM','TwoPMToSixPM','SixPMToElevenPM','ElevenPMToThreeAM');
	public static $ImageType                          = array('Image','Icon');
	public static $IncrementalBidPercentage           = array('ZeroPercent','TenPercent','TwentyPercent','ThirtyPercent','FortyPercent','FiftyPercent','SixtyPercent','SeventyPercent','EightyPercent','NinetyPercent','OneHundredPercent','NegativeTenPercent','NegativeTwentyPercent','NegativeThirtyPercent','NegativeFortyPercent','NegativeFiftyPercent','NegativeSixtyPercent','NegativeSeventyPercent','NegativeEightyPercent','NegativeNinetyPercent','NegativeOneHundredPercent');
	public static $KeywordEditorialStatus             = array('Active','Disapproved','Inactive');
	public static $KeywordStatus                      = array('Active','Paused','Deleted','Inactive');
	public static $MigrationStatus                    = array('NotInPilot','NotStarted','InProgress','Completed');
	public static $Network                            = array('OwnedAndOperatedAndSyndicatedSearch','OwnedAndOperatedOnly','SyndicatedSearchOnly');
	public static $PaymentType                        = array('Cash','AmericanExpress','MasterCard','DinersClub','DirectDebit','Visa','TravellersCheck','PayPal','Invoice','CashOnDelivery','Other');
	public static $PricingModel                       = array('Cpc','Cpm');
	public static $RevenueModelType                   = array('Constant','Variable','None');
	public static $SitePlacementStatus                = array('Active','Paused','Deleted','Inactive');
	public static $StandardBusinessIcon               = array('MoviesOrVideo','PubOrBarOrLiquor','Accommodations','RestaurantOrDining','CafeOrCoffeeShop','FlowersOrGarden','CarDealerOrServiceOrRental','GroceryOrDepartmentStore','ShoppingOrBoutique','HousewaresOrRealEstateOrHomeRepair','PhonesOrServiceProvider','BankOrFinanceOrCurrencyExchange','BankOrFinanceOrCurrencyExchangeUK','BankOrFinanceOrCurrencyExchangeEUR','HardwareOrRepair','HairdresserOrBarberOrTailor');
	public static $StepType                           = array('Lead','Browse','Prospect','Conversion');
}

?>