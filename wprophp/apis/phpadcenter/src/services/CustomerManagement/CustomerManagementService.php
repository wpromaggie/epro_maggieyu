<?php
namespace phpadcenter\services\CustomerManagement;

class CustomerManagementService extends \phpadcenter\AdCenterService
{
	function __construct($soap_opts = array(), $client_opts = array())
	{
		$this->wsdl_url_prod = 'https://sharedservices.adcenterapi.microsoft.com/Api/CustomerManagement/v8/CustomerManagementService.svc?wsdl';
		$this->wsdl_url_sandbox = 'https://sharedservices.api.sandbox.bingads.microsoft.com/Api/CustomerManagement/v8/CustomerManagementService.svc?wsdl';
		$this->tns_prod = 'https://adcenter.microsoft.com/api/customermanagement';
		
		parent::__construct($soap_opts, $client_opts);
	}

	/**
	 * @param integer $ManageAccountsRequestId
	 * @param integer $PaymentMethodId
	 * @return void
	 */
	public function AcceptRequestToManageAccounts($ManageAccountsRequestId, $PaymentMethodId) { return $this->SoapCall('AcceptRequestToManageAccounts', array('ManageAccountsRequestId' => $ManageAccountsRequestId, 'PaymentMethodId' => $PaymentMethodId), false); }


	/**
	 * @param Account $Account
	 * @return dateTime
	 */
	public function AddAccount($Account) { return $this->SoapCall('AddAccount', array('Account' => $Account), false); }


	/**
	 * @param Account $Account
	 * @return dateTime
	 */
	public function AddPrepayAccount($Account) { return $this->SoapCall('AddPrepayAccount', array('Account' => $Account), false); }


	/**
	 * @param User $User
	 * @param UserRole $Role
	 * @param ArrayOflong $AccountIds
	 * @return dateTime
	 */
	public function AddUser($User, $Role, $AccountIds) { return $this->SoapCall('AddUser', array('User' => $User, 'Role' => $Role, 'AccountIds' => $AccountIds), false); }


	/**
	 * @param integer $ManageAccountsRequestId
	 * @return void
	 */
	public function CancelRequestToManageAccounts($ManageAccountsRequestId) { return $this->SoapCall('CancelRequestToManageAccounts', array('ManageAccountsRequestId' => $ManageAccountsRequestId), false); }


	/**
	 * @param integer $ManageAccountsRequestId
	 * @return void
	 */
	public function DeclineRequestToManageAccounts($ManageAccountsRequestId) { return $this->SoapCall('DeclineRequestToManageAccounts', array('ManageAccountsRequestId' => $ManageAccountsRequestId), false); }


	/**
	 * @param integer $AccountId
	 * @param base64Binary $TimeStamp
	 * @return void
	 */
	public function DeleteAccount($AccountId, $TimeStamp) { return $this->SoapCall('DeleteAccount', array('AccountId' => $AccountId, 'TimeStamp' => $TimeStamp), false); }


	/**
	 * @param integer $CustomerId
	 * @param base64Binary $TimeStamp
	 * @return void
	 */
	public function DeleteCustomer($CustomerId, $TimeStamp) { return $this->SoapCall('DeleteCustomer', array('CustomerId' => $CustomerId, 'TimeStamp' => $TimeStamp), false); }


	/**
	 * @param integer $UserId
	 * @param base64Binary $TimeStamp
	 * @return void
	 */
	public function DeleteUser($UserId, $TimeStamp) { return $this->SoapCall('DeleteUser', array('UserId' => $UserId, 'TimeStamp' => $TimeStamp), false); }


	/**
	 * @param integer $CustomerId
	 * @param string $AccountFilter
	 * @param integer $TopN
	 * @param ApplicationType $ApplicationScope
	 * @return ArrayOfAccountInfo
	 */
	public function FindAccounts($CustomerId, $AccountFilter, $TopN, $ApplicationScope) { return $this->SoapCall('FindAccounts', array('CustomerId' => $CustomerId, 'AccountFilter' => $AccountFilter, 'TopN' => $TopN, 'ApplicationScope' => $ApplicationScope), true); }


	/**
	 * @param string $Filter
	 * @param integer $TopN
	 * @param ApplicationType $ApplicationScope
	 * @return ArrayOfAccountInfoWithCustomerData
	 */
	public function FindAccountsOrCustomersInfo($Filter, $TopN, $ApplicationScope) { return $this->SoapCall('FindAccountsOrCustomersInfo', array('Filter' => $Filter, 'TopN' => $TopN, 'ApplicationScope' => $ApplicationScope), true); }


	/**
	 * @param integer $CustomerId
	 * @return integer
	 */
	public function GetAccessibleCustomer($CustomerId) { return $this->SoapCall('GetAccessibleCustomer', array('CustomerId' => $CustomerId), false); }


	/**
	 * @param integer $AccountId
	 * @return Account
	 */
	public function GetAccount($AccountId) { return $this->SoapCall('GetAccount', array('AccountId' => $AccountId), false); }


	/**
	 * @param integer $CustomerId
	 * @param boolean $OnlyParentAccounts
	 * @return ArrayOfAccountInfo
	 */
	public function GetAccountsInfo($CustomerId = null, $OnlyParentAccounts = null) { return $this->SoapCall('GetAccountsInfo', array('CustomerId' => $CustomerId, 'OnlyParentAccounts' => $OnlyParentAccounts), true); }


	/**
	 * @return User
	 */
	public function GetCurrentUser() { return $this->SoapCall('GetCurrentUser', array(), false); }


	/**
	 * @param integer $CustomerId
	 * @return Customer
	 */
	public function GetCustomer($CustomerId) { return $this->SoapCall('GetCustomer', array('CustomerId' => $CustomerId), false); }


	/**
	 * @param integer $CustomerId
	 * @return ArrayOfint
	 */
	public function GetCustomerPilotFeature($CustomerId) { return $this->SoapCall('GetCustomerPilotFeature', array('CustomerId' => $CustomerId), true); }


	/**
	 * @param string $CustomerNameFilter
	 * @param integer $TopN
	 * @param ApplicationType $ApplicationScope
	 * @return ArrayOfCustomerInfo
	 */
	public function GetCustomersInfo($CustomerNameFilter, $TopN, $ApplicationScope) { return $this->SoapCall('GetCustomersInfo', array('CustomerNameFilter' => $CustomerNameFilter, 'TopN' => $TopN, 'ApplicationScope' => $ApplicationScope), true); }


	/**
	 * @return ArrayOfPilotFeature
	 */
	public function GetPilotFeaturesCountries() { return $this->SoapCall('GetPilotFeaturesCountries', array(), true); }


	/**
	 * @param integer $ManageAccountsRequestId
	 * @return ManageAccountsRequest
	 */
	public function GetRequestToManageAccounts($ManageAccountsRequestId) { return $this->SoapCall('GetRequestToManageAccounts', array('ManageAccountsRequestId' => $ManageAccountsRequestId), false); }


	/**
	 * @param string $AccountNumber
	 * @param string $CustomerNumber
	 * @param dateTime $RequestsSentAfter
	 * @param dateTime $RequestsSentBefore
	 * @param ArrayOfManageAccountsRequestStatus $RequestStatusFilter
	 * @param ArrayOfManageAccountsRequestType $RequestTypeFilter
	 * @return ArrayOfManageAccountsRequestInfo
	 */
	public function GetRequestToManageAccountsInfos($AccountNumber, $CustomerNumber, $RequestsSentAfter, $RequestsSentBefore, $RequestStatusFilter, $RequestTypeFilter) { return $this->SoapCall('GetRequestToManageAccountsInfos', array('AccountNumber' => $AccountNumber, 'CustomerNumber' => $CustomerNumber, 'RequestsSentAfter' => $RequestsSentAfter, 'RequestsSentBefore' => $RequestsSentBefore, 'RequestStatusFilter' => $RequestStatusFilter, 'RequestTypeFilter' => $RequestTypeFilter), true); }


	/**
	 * @param integer $UserId
	 * @return ArrayOflong
	 */
	public function GetUser($UserId) { return $this->SoapCall('GetUser', array('UserId' => $UserId), true); }


	/**
	 * @param integer $CustomerId
	 * @param UserLifeCycleStatus $StatusFilter
	 * @return ArrayOfUserInfo
	 */
	public function GetUsersInfo($CustomerId, $StatusFilter) { return $this->SoapCall('GetUsersInfo', array('CustomerId' => $CustomerId, 'StatusFilter' => $StatusFilter), true); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOfstring $ExternalAccountIds
	 * @return void
	 */
	public function MapAccountIdToExternalAccountIds($AccountId, $ExternalAccountIds) { return $this->SoapCall('MapAccountIdToExternalAccountIds', array('AccountId' => $AccountId, 'ExternalAccountIds' => $ExternalAccountIds), false); }


	/**
	 * @param integer $CustomerId
	 * @param string $ExternalCustomerId
	 * @return void
	 */
	public function MapCustomerIdToExternalCustomerId($CustomerId, $ExternalCustomerId) { return $this->SoapCall('MapCustomerIdToExternalCustomerId', array('CustomerId' => $CustomerId, 'ExternalCustomerId' => $ExternalCustomerId), false); }


	/**
	 * @param ManageAccountsRequest $ManageAccountsRequest
	 * @return integer
	 */
	public function SendRequestToManageAccounts($ManageAccountsRequest) { return $this->SoapCall('SendRequestToManageAccounts', array('ManageAccountsRequest' => $ManageAccountsRequest), false); }


	/**
	 * @param ManageAccountsRequest $ManageAccountsRequest
	 * @return integer
	 */
	public function SendRequestToStopManagingAccounts($ManageAccountsRequest) { return $this->SoapCall('SendRequestToStopManagingAccounts', array('ManageAccountsRequest' => $ManageAccountsRequest), false); }


	/**
	 * @param Customer $Customer
	 * @param User $User
	 * @param Account $Account
	 * @param integer $ParentCustomerId
	 * @param ApplicationType $ApplicationScope
	 * @return dateTime
	 */
	public function SignupCustomer($Customer, $User, $Account, $ParentCustomerId, $ApplicationScope = null) { return $this->SoapCall('SignupCustomer', array('Customer' => $Customer, 'User' => $User, 'Account' => $Account, 'ParentCustomerId' => $ParentCustomerId, 'ApplicationScope' => $ApplicationScope), false); }


	/**
	 * @param Account $Account
	 * @return dateTime
	 */
	public function UpdateAccount($Account) { return $this->SoapCall('UpdateAccount', array('Account' => $Account), false); }


	/**
	 * @param Customer $Customer
	 * @return dateTime
	 */
	public function UpdateCustomer($Customer) { return $this->SoapCall('UpdateCustomer', array('Customer' => $Customer), false); }


	/**
	 * @param Account $Account
	 * @return dateTime
	 */
	public function UpdatePrepayAccount($Account) { return $this->SoapCall('UpdatePrepayAccount', array('Account' => $Account), false); }


	/**
	 * @param User $User
	 * @return dateTime
	 */
	public function UpdateUser($User) { return $this->SoapCall('UpdateUser', array('User' => $User), false); }


	/**
	 * @param integer $CustomerId
	 * @param integer $UserId
	 * @param integer $NewRoleId
	 * @param ArrayOflong $NewAccountIds
	 * @param ArrayOflong $NewCustomerIds
	 * @param integer $DeleteRoleId
	 * @param ArrayOflong $DeleteAccountIds
	 * @param ArrayOflong $DeleteCustomerIds
	 * @return dateTime
	 */
	public function UpdateUserRoles($CustomerId, $UserId, $NewRoleId, $NewAccountIds, $NewCustomerIds, $DeleteRoleId, $DeleteAccountIds, $DeleteCustomerIds) { return $this->SoapCall('UpdateUserRoles', array('CustomerId' => $CustomerId, 'UserId' => $UserId, 'NewRoleId' => $NewRoleId, 'NewAccountIds' => $NewAccountIds, 'NewCustomerIds' => $NewCustomerIds, 'DeleteRoleId' => $DeleteRoleId, 'DeleteAccountIds' => $DeleteAccountIds, 'DeleteCustomerIds' => $DeleteCustomerIds), false); }


	/**
	 * @param integer $CustomerId
	 * @return void
	 */
	public function UpgradeCustomerToAgency($CustomerId) { return $this->SoapCall('UpgradeCustomerToAgency', array('CustomerId' => $CustomerId), false); }


}

?>