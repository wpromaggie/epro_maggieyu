<?php
require(__DIR__.'/AdCenterLib.php');

define('ACC_VERBOSE_ACTIONS', 0x01);
define('ACC_VERBOSE_XML'    , 0x02);
define('ACC_VERBOSE_ALL'    , ACC_VERBOSE_ACTIONS | ACC_VERBOSE_XML);

class AdCenterClient
{
	private $client_opts, $soap_opts, $services, $headers, $last_service;
	
	function __construct($headers = array(), $client_opts = array(), $soap_opts = array())
	{
		$this->headers = array();
		$this->client_opts = $client_opts;
		$this->soap_opts = $soap_opts;
		
		$this->services = array();
		$this->last_service = null;
		
		// if client passes in true, default to verbose all
		if (isset($this->client_opts['verbose']) && $this->client_opts['verbose'] === true) {
			$this->client_opts['verbose'] = ACC_VERBOSE_ALL;
		}
		// default to overwrite log
		if ($this->_doLog() && (!isset($this->client_opts['log_preserve']) || $this->client_opts['log_preserve'] == false)) {
			file_put_contents($this->client_opts['log_file'], '');
		}
		if ($headers) {
			$this->SetHeaders($headers);
		}
	}
	
	public function __call($method, $args)
	{
		if (!isset(phpadcenter\AdCenterLib::$action_map[$method])) {
			trigger_error('"'.$method.'" is not a valid action for this client', E_USER_ERROR);
		}
		$service_name = phpadcenter\AdCenterLib::$action_map[$method];
		if (!isset($this->services[$service_name])) {
			$class_name = 'phpadcenter\services\\'.$service_name.'\\'.$service_name.'Service';
			if (!class_exists($class_name)) {
				$this->_loadService($service_name);
			}
			$this->services[$service_name] = new $class_name($this->client_opts, $this->soap_opts);
			if ($this->headers) {
				$this->services[$service_name]->SetHeaders($this->headers);
			}
		}
		$this->last_service = $this->services[$service_name];
		if ($this->_isVerbose(ACC_VERBOSE_ACTIONS)) {
			echo $service_name.'->'.$method."\n";
		}
		$r = call_user_func_array(array($this->last_service, $method), $args);
		if ($this->_doLog() || $this->_isVerbose(ACC_VERBOSE_XML)) {
			$log_str = $this->last_service->Log();
			
			if ($this->_doLog()) {
				file_put_contents($this->client_opts['log_file'], $log_str, FILE_APPEND);
			}
			
			if ($this->_isVerbose(ACC_VERBOSE_XML)) {
				echo $log_str;
			}
		}
		return $r;
	}
	
	public function SetHeaders($headers)
	{
		// overwrite any old values with new values
		$this->headers = array_merge($this->headers, $headers);
		
		// pass new headers to all services we have inited
		foreach ($this->services as $service) {
			$service->SetHeaders($headers);
		}
	}

	public function SetAccount($account_id, $user = '', $pass = '')
	{
		$headers = array('CustomerAccountId' => $account_id);
		if ($user) {
			$headers['UserName'] = $user;
			$headers['Password'] = $pass;
		}
		$this->SetHeaders($headers);
	}

	public function GetErrorObj()
	{
		return $this->last_service->error;
	}

	public function GetErrorMsg()
	{
		return $this->last_service->error_str;
	}

	public function GetSoapClient($name = false)
	{
		return (($name) ? $this->services[$name] : $this->last_service);
	}

	private function _loadService($service_name)
	{
		require_once(__DIR__.'/services/'.$service_name.'/'.$service_name.'Service.php');
	}

	private function _doLog()
	{
		return (isset($this->client_opts['log']) && isset($this->client_opts['log_file']));
	}
	
	private function _isVerbose($level = ACC_VERBOSE_ALL)
	{
		return (isset($this->client_opts['verbose']) && $this->client_opts['verbose'] & $level);
	}
}
?>