<?php
namespace phpadcenter\services\Notification;

class NotificationService extends \phpadcenter\AdCenterService
{
	function __construct($soap_opts = array(), $client_opts = array())
	{
		$this->wsdl_url_prod = 'https://sharedservices.adcenterapi.microsoft.com/Api/Notification/v8/NotificationService.svc?wsdl';
		$this->wsdl_url_sandbox = '';
		$this->tns_prod = 'https://adcenter.microsoft.com/api/notifications';
		
		parent::__construct($soap_opts, $client_opts);
	}

	/**
	 * @param NotificationType $NotificationTypes
	 * @param integer $TopN
	 * @param dateTime $StartDate
	 * @param dateTime $EndDate
	 * @return ArrayOfNotification
	 */
	public function GetArchivedNotifications($NotificationTypes, $TopN, $StartDate, $EndDate) { return $this->SoapCall('GetArchivedNotifications', array('NotificationTypes' => $NotificationTypes, 'TopN' => $TopN, 'StartDate' => $StartDate, 'EndDate' => $EndDate), true); }


	/**
	 * @param NotificationType $NotificationTypes
	 * @param integer $TopN
	 * @return ArrayOfNotification
	 */
	public function GetNotifications($NotificationTypes, $TopN) { return $this->SoapCall('GetNotifications', array('NotificationTypes' => $NotificationTypes, 'TopN' => $TopN), true); }


}

?>