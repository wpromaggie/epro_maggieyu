<?php
namespace phpadcenter;

class AdCenterService extends \SoapClient
{
	public $wsdl_url, $wsdl_url_prod, $wsdl_url_sandbox, $tns_prod, $headers;
	
	public $action;
	
	public $log_str, $error, $error_str;
	
	public function __construct($client_opts = array(), $soap_opts = array())
	{
		$this->wsdl_url = (array_key_exists('mode', $client_opts) && $client_opts['mode'] == 'sandbox') ? $this->wsdl_url_sandbox : $this->wsdl_url_prod;
		$this->errors = array();
		$this->headers = array();
		
		// trace default on
		if (!array_key_exists('trace', $soap_opts)) {
			$soap_opts['trace'] = true;
		}
		parent::__construct($this->wsdl_url, $soap_opts);
	}
	
	public function SetHeaders($headers)
	{
		$is_change = false;
		if (!$this->headers) {
			$this->headers = $headers;
			$is_change = true;
		}
		else {
			$new_headers = array_diff_assoc($headers, $this->headers);
			if ($new_headers) {
				$is_change = true;
				$this->__setSoapHeaders(null);
				$this->headers = array_merge($this->headers, $new_headers);
			}
		}
		
		if ($is_change) {
			$soap_headers = array();
			foreach ($this->headers as $k => $v) {
				$soap_headers[] = new \SoapHeader($this->tns_prod, $k, $v);
			}
			$this->__setSoapHeaders($soap_headers);
		}
	}
	
	public function SoapCall($action, $args, $do_return_array)
	{
		try {
			$this->action = $action;
			$r = parent::__soapCall($action, array($action.'Request' => $args));
			return $this->_parseResponse($r, $do_return_array);
		}
		catch (\Exception $e) {
			$this->_handleException($e);
			return false;
		}
	}
	
	public function Log()
	{
		$s  = "\n---\nREQUEST -- ".date('Y-m-d H:i:s')." -- {$this->wsdl_url}\n---\n";
		$s .= $this->__getLastRequestHeaders();
		$s .= preg_replace("/(Password>)(.*?)(<\/.*?Password)/ms", '$1***$3', str_replace("><", ">\n<", $this->__getLastRequest()));
		$s .= "\n---\nRESPONSE\n---\n";
		$s .= $this->__getLastResponseHeaders()."\n";
		$s .= str_replace("><", ">\n<", $this->__getLastResponse());
		
		return $s;
	}
	
	private function _parseResponse($r, $do_return_array)
	{
		if ($do_return_array) {
			$tmp = @current(current($r));
			if (!is_array($tmp)) {
				$tmp = array($tmp);
			}
			$return = array();
			foreach ($tmp as &$obj) {
				$return[] = $this->_parseResponseObject($obj);
			}
			return $return;
		}
		else {
			$tmp = @current($r);
			return (($tmp) ? $this->_parseResponseObject($tmp) : $r);
		}
	}
	
	private function _parseResponseObject(&$x)
	{
		if (is_a($x, 'stdClass')) {
			$r = array();
			foreach ($x as $k => &$v) {
				$r[$k] = $this->_parseResponseObject($v);
			}
			return $r;
		}
		else {
			return $x;
		}
	}
	
	private function _handleException($exception)
	{
		$this->error = $exception;
		$this->error_str = $this->_findKey($exception->detail, 'Message');
		if (!$this->error_str) {
			$this->error_str = '???';
		}
	}
	
	// todo: value can be false?
	private function _findKey(&$x, $target_key)
	{
		if (is_array($x) || is_a($x, 'stdClass') || $x instanceof Traversable) {
			foreach ($x as $k => &$v) {
				if ($k == $target_key) {
					return $v;
				}
				if (!is_scalar($v)) {
					$tmp = $this->_findKey($v, $target_key);
					if ($tmp !== false) {
						return $tmp;
					}
				}
			}
		}
		return false;
	}
	
}

?>