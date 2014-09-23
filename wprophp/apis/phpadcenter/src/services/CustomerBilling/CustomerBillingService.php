<?php
namespace phpadcenter\services\CustomerBilling;

class CustomerBillingService extends \phpadcenter\AdCenterService
{
	function __construct($soap_opts = array(), $client_opts = array())
	{
		$this->wsdl_url_prod = 'https://sharedservices.adcenterapi.microsoft.com/Api/Billing/v8/CustomerBillingService.svc?wsdl';
		$this->wsdl_url_sandbox = '';
		$this->tns_prod = 'https://adcenter.microsoft.com/api/customerbilling';
		
		parent::__construct($soap_opts, $client_opts);
	}

	/**
	 * @param InsertionOrder $InsertionOrder
	 * @return dateTime
	 */
	public function AddInsertionOrder($InsertionOrder) { return $this->SoapCall('AddInsertionOrder', array('InsertionOrder' => $InsertionOrder), false); }


	/**
	 * @param integer $AccountId
	 * @param dateTime $MonthYear
	 * @return double
	 */
	public function GetAccountMonthlySpend($AccountId, $MonthYear) { return $this->SoapCall('GetAccountMonthlySpend', array('AccountId' => $AccountId, 'MonthYear' => $MonthYear), false); }


	/**
	 * @param ArrayOflong $InvoiceIds
	 * @param DataType $Type
	 * @return ArrayOfInvoice
	 */
	public function GetDisplayInvoices($InvoiceIds, $Type) { return $this->SoapCall('GetDisplayInvoices', array('InvoiceIds' => $InvoiceIds, 'Type' => $Type), true); }


	/**
	 * @param integer $AccountId
	 * @param ArrayOflong $InsertionOrderIds
	 * @return ArrayOfInsertionOrder
	 */
	public function GetInsertionOrdersByAccount($AccountId, $InsertionOrderIds) { return $this->SoapCall('GetInsertionOrdersByAccount', array('AccountId' => $AccountId, 'InsertionOrderIds' => $InsertionOrderIds), true); }


	/**
	 * @param ArrayOflong $InvoiceIds
	 * @param DataType $Type
	 * @return ArrayOfInvoice
	 */
	public function GetInvoices($InvoiceIds, $Type) { return $this->SoapCall('GetInvoices', array('InvoiceIds' => $InvoiceIds, 'Type' => $Type), true); }


	/**
	 * @param ArrayOflong $AccountIds
	 * @param dateTime $StartDate
	 * @param dateTime $EndDate
	 * @return ArrayOfInvoiceInfo
	 */
	public function GetInvoicesInfo($AccountIds, $StartDate, $EndDate) { return $this->SoapCall('GetInvoicesInfo', array('AccountIds' => $AccountIds, 'StartDate' => $StartDate, 'EndDate' => $EndDate), true); }


	/**
	 * @param ArrayOfstring $InvoiceIds
	 * @return ArrayOfInvoice
	 */
	public function GetKOHIOInvoices($InvoiceIds) { return $this->SoapCall('GetKOHIOInvoices', array('InvoiceIds' => $InvoiceIds), true); }


	/**
	 * @param InsertionOrder $InsertionOrder
	 * @return dateTime
	 */
	public function UpdateInsertionOrder($InsertionOrder) { return $this->SoapCall('UpdateInsertionOrder', array('InsertionOrder' => $InsertionOrder), false); }


}

?>