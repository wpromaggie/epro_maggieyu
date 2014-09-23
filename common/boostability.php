<?php

// docs - https://secure.boostability.com/partner/documentation/

// we wrap SoapClient instead of extending it so that we can properly use
// the __call magic function
class boostability
{
	// our soap client
	private $cl;
	
	// return false on error, provide getter for message
	private $error_msg;
	
	// headers of body of request and response
	private $transaction_str;
	
	private static $wsdl_url = 'https://service.boostability.com/v1/PartnerService.svc?wsdl';
	private static $ns = 'https://service.boostability.com';
	private static $dev_token = '/yz58T2T5GXefBaUcszvmj1/8DnySyWq/uKHUP2s7U/jZes1rqDHsE8DdpfVhPmq';
	private static $token = 'XT91HHhyGMMtLRstJhChDNDlGfp2G5+wN/V3OUL/cWswXX8yaxiUluPL56GpZwWM';
	
	public function __construct()
	{
		$this->cl = new SoapClient(self::$wsdl_url, array(
			'trace' => true,
			'exceptions' => true,
			'connection_timeout' => 9999,
			'soap_version' => SOAP_1_1
		));
	}
	
	public function __call($method, $args)
	{
		// see if method is defined by SoapClient
		if (method_exists($this->cl, $method))
		{
			return call_user_func_array(array($this->cl, $method), $args);
		}
		else
		{
			return $this->call_wsdl_method($method, $args);
		}
	}
	
	private function call_wsdl_method($method, $args)
	{
		if (!(is_array($args) && $args))
		{
			$args = array(array());
		}
		$data = array_merge(array('token' => self::$token), $args[0]);
		$params = array();
		foreach ($data as $k => $v)
		{
			$params[] = new SoapVar('<ns1:'.$k.'>'.$v.'</ns1:'.$k.'>', XSD_ANYXML);
		}
    $var = new SoapVar($params, SOAP_ENC_OBJECT, $method, self::$ns);
		$r = $this->cl->$method($var);
		$this->set_transaction_str();
		if ($r)
		{
			$result_key = $method.'Result';
			if ($r->$result_key)
			{
				$result = $r->$result_key;
				if (property_exists($result, 'BooError'))
				{
					// success!
					if (!$result->BooError)
					{
						return $result->Data;
					}
					else
					{
						if ($result->BooError->DisplayMessage)
						{
							$this->error_msg = $result->BooError->DisplayMessage;
						}
						else
						{
							$this->error_msg = 'Could not find error display message';
						}
					}
				}
				else
				{
					$this->error_msg = 'Could not find error object';
				}
			}
			else
			{
				$this->error_msg = 'Could not find result';
			}
		}
		else
		{
			$this->error_msg = 'Result is empty';
		}
		return false;
	}
	
	public function get_error()
	{
		return $this->error_msg;
	}
	
	public function get_transaction()
	{
		return $this->transaction_str;
	}
	
	private function set_transaction_str()
	{
		$this->transaction_str  = "\n---\nREQUEST -- ".date('Y-m-d H:i:s')." -- {$this->wsdl_url}\n---\n";
		$this->transaction_str .= $this->cl->__getLastRequestHeaders();
		$this->transaction_str .= str_replace("><", ">\n<", $this->cl->__getLastRequest());
		$this->transaction_str .= "\n---\nRESPONSE\n---\n";
		$this->transaction_str .= $this->cl->__getLastResponseHeaders()."\n";
		$this->transaction_str .= str_replace("><", ">\n<", $this->cl->__getLastResponse());
	}
}

?>