<?php
namespace phpadcenter\services\Optimizer;

class OptimizerEnum
{
	public static $BudgetLimitType = array('MonthlyBudgetSpendUntilDepleted','DailyBudgetStandard','DailyBudgetAccelerated');
	public static $ErrorCodes      = array('InternalError','NullRequest','InvalidCredentials','UserIsNotAuthorized','QuotaNotAvailable','InvalidDateObject','RequestMissingHeaders','ApiInputValidationError','APIExecutionError','NullParameter','OperationNotSupported','InvalidVersion','NullArrayArgument','ConcurrentRequestOverLimit','InvalidAccount','TimestampNotMatch','EntityNotExistent','NameTooLong','FilterListOverLimit','InvalidAccountId','InvalidCustomerId','CustomerIdHasToBeSpecified','AccountIdHasToBeSpecified','FutureFeatureCode','InvalidOpportunityKeysList','OpportunityExpired','OpportunityAlreadyApplied','OpportunityKeysArrayShouldNotBeNullOrEmpty','OpportunityKeysArrayExceedsLimit','InvalidOpportunityKey','CampaignBudgetAmountIsAboveLimit','CampaignBudgetAmountIsBelowConfiguredLimit','CampaignBudgetAmountIsLessThanSpendAmount','CampaignBudgetLessThanAdGroupBudget','CampaignDailyTargetBudgetAmountIsInvalid','IncrementalBudgetAmountRequiredForDayTarget','BidsAmountsGreaterThanCeilingPrice','KeywordExactBidAmountsGreaterThanCeilingPrice','KeywordPhraseBidAmountsGreaterThanCeilingPrice','KeywordBroadBidAmountsGreaterThanCeilingPrice','BidAmountsLessThanFloorPrice','KeywordExactBidAmountsLessThanFloorPrice','KeywordPhraseBidAmountsLessThanFloorPrice','KeywordBroadBidAmountsLessThanFloorPrice','KeywordAlreadyExists','MaxKeywordsLimitExceededForAccount','MaxKeywordsLimitExceededForAdGroup');
}

?>