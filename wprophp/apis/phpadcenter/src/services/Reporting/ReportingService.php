<?php
namespace phpadcenter\services\Reporting;

class ReportingService extends \phpadcenter\AdCenterService
{
	function __construct($soap_opts = array(), $client_opts = array())
	{
		$this->wsdl_url_prod = 'https://adcenterapi.microsoft.com/Api/Advertiser/v8/Reporting/ReportingService.svc?wsdl';
		$this->wsdl_url_sandbox = '';
		$this->tns_prod = 'https://adcenter.microsoft.com/v8';
		
		parent::__construct($soap_opts, $client_opts);
	}

	/**
	 * @param string $ReportRequestId
	 * @return ReportRequestStatus
	 */
	public function PollGenerateReport($ReportRequestId) { return $this->SoapCall('PollGenerateReport', array('ReportRequestId' => $ReportRequestId), false); }


	/**
	 * @param ReportRequest $ReportRequest
	 * @param ReportRequest $ReportRequest
	 * @param EnumOfReportRequestType $ReportRequestType
	 * @return string
	 */
	public function SubmitGenerateReport($ReportRequest, $ReportRequestType) { return $this->SoapCall('SubmitGenerateReport', array('ReportRequest' => new \SoapVar($ReportRequest, SOAP_ENC_OBJECT, $ReportRequestType, $this->tns_prod)), false); }


}

?>