<?php

ini_set('mbstring.func_overload', MB_OVERLOAD_MAIL | MB_OVERLOAD_STRING | MB_OVERLOAD_REGEX);

define('WPRO_SOAP_DEBUG_NONE', 0);
define('WPRO_SOAP_DEBUG_HEADER', 1);
define('WPRO_SOAP_DEBUG_BODY', 2);
define('WPRO_SOAP_DEBUG_URL', 4);
define('WPRO_SOAP_DEBUG_REQUEST', 8);
define('WPRO_SOAP_DEBUG_RESPONSE', 16);
define('WPRO_SOAP_DEBUG_ALL', WPRO_SOAP_DEBUG_HEADER | WPRO_SOAP_DEBUG_BODY | WPRO_SOAP_DEBUG_URL | 
	WPRO_SOAP_DEBUG_REQUEST | WPRO_SOAP_DEBUG_RESPONSE);

define('WPRO_SOAP_DEFAULT_ENVELOPE_ATTRS', 'xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:ns7901="http://tempuri.org"');

define('WPRO_SOAP_REQUEST_NONE', 0);
define('WPRO_SOAP_REQUEST_EXPECT_ARRAY', 1);

abstract class base_soap
{
	protected $name;
	protected $error;
	protected $exception;
	protected $version;
	protected $keys;
	protected $quota;
	protected $debug = 0;
	protected $sandbox = false;
	protected $simulate = false;
	
	// not used yet
	protected $log = false;
	
	// we only want to clear the debug log once per request, not per call to api->debug
	public static $debug_log_cleared = false;
	public static $log_user;

	protected function get_url($url)
	{
		$curl = new wpro_curl($url);
		$curl->set_opt_array(array(CURLOPT_HEADER => 0, CURLOPT_RETURN_TRANSFER => 1));
		return ($curl->exec());
	} // get_url
	
	protected function post_url($url, $post)
	{
		$a_url = parse_url($url);

		$header = array(
			'POST '.$a_url['path'].' HTTP/1.1',
			'Host: '.$a_url['host'],
			'Connection: Keep-Alive',
			'User-Agent: WproPHP SOAP 0.0.1',
			'SOAPAction: "'.$this->get_soap_action().'"',
			'Content-Type: text/xml; charset=utf-8',
			'Content-Length: '.strlen($post)."\r\n",
			$post
		);
		
		if ($this->debug)
		{
			$this->log_request($header);
		}
		
		$curl = new wpro_curl($url);
		$curl->set_opt_array(array(
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_HEADER => 1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_HTTPHEADER => $header
		));
		$response = $curl->exec();
		
		if ($this->debug)
		{
			$this->log_response($response, $curl);
		}
		
		return $response;
	}
	
	protected function log_request($request)
	{
		$log_msg = "\n------\nREQUEST (".date('Y-m-d H:i:s').")\n------\n";
		for ($i = 0, $ci = count($request); $i < ($ci - 1); ++$i)
		{
			$log_msg .= $request[$i]."\n";
		}
		$log_msg .= preg_replace("/>\s*?</", ">\n<", $request[$i])."\n";
		file_put_contents($this->log_get_path(), $log_msg, FILE_APPEND);
	}
	
	protected function log_response($response, $curl)
	{
		$log_msg = "\n------\nRESPONSE\n------\n";
		$head_end = strpos($response, "\r\n\r\n");
		if ($head_end !== false)
		{
			$head = substr($response, 0, $head_end);
			$response = substr($response, $head_end + 4);
			$log_msg .= "$head\n\n";
		}
		$log_msg .= preg_replace("/>\s*?</", ">\n<", $response)."\n";
		file_put_contents($this->log_get_path(), $log_msg, FILE_APPEND);
	}
	
	protected function log_get_path()
	{
		return (\epro\WPROPHP_PATH.'logs/apis/'.$this->market.'/'.((self::$log_user) ? (self::$log_user.'-') : '').'soap_xml.log');
	}
	
	protected function soap_request($url, $header, $body, $envelope_attrs = WPRO_SOAP_DEFAULT_ENVELOPE_ATTRS)
	{
		$request = '<?xml version="1.0" encoding="UTF-8"?>
				<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" '.$envelope_attrs.'>
					<SOAP-ENV:Header>
						'.$header.'
					</SOAP-ENV:Header>
					<SOAP-ENV:Body>
						'.$body.'
					</SOAP-ENV:Body>
				</SOAP-ENV:Envelope>';
		
		if ($this->simulate)
		{
			$response = true;
		}
		else
		{
			$response = $this->post_url($url, strings::ml_one_line_ref($request));
		}
		
		return ($response);
	}
	
	private function process_header($header)
	{
		// msn (others?) doesn't return a header
		if (empty($header)) return;
		
		$header = api_translate::standardize_header($this->market, $header);
		if (array_key_exists('quota', $header)) $this->quota += intval($header['quota']);
	}
	
	protected function get_request($request, $response_key = '', $response_type = '', $flags = WPRO_SOAP_REQUEST_NONE)
	{
		// these 3 are all abstract
		$url = $this->get_endpoint();
		$header = $this->get_header();
		$envelope_attrs = $this->get_envolope_attrs();
		
		$response = $this->soap_request($url, $header, $request, $envelope_attrs);
		$doc = new xml_doc($response);
		
		if ($this->is_error($doc))
		{
			return false;
		}
		else if (empty($response_key))
		{
			return $doc->get_root();
		}
		else
		{
			$this->process_header($doc->get('Header'));
			$data = $doc->get($response_key, $flags & WPRO_SOAP_REQUEST_EXPECT_ARRAY);
			if ($response_type)
			{
				$rs_func = 'standardize_'.$response_type;
				if (method_exists('api_translate', $rs_func)) $data = api_translate::$rs_func($this->market, $data);
			}
			return ($data);
		}
	}
	
	public function get_error()
	{
		return $this->error;
	}
	
	protected function get_envolope_attrs()
	{
		return (WPRO_SOAP_DEFAULT_ENVELOPE_ATTRS);
	}
	
	abstract protected function get_header();
	abstract protected function get_endpoint();
	abstract protected function get_soap_action();
	abstract protected function is_error(&$doc);
	
	protected function get_inner_ml(&$xml, $element)
	{
		$ml = '';
		$pos_start = strpos($xml, '<'.$element);
		$pos_end = false;
		if ($pos_start !== false)
		{
			$pos_start = strpos($xml, '>', $pos_start);
			if ($pos_start !== false)
			{
				$pos_end = strpos($xml, '</'.$element, $pos_start);
				if ($pos_end !== false)
				{
					$ml = substr($xml, $pos_start + 1, $pos_end - $pos_start + 1);
				} // found element end
			} // element start ending
		} // found element start beginining		
		
		return (trim($ml));	
	} // get_inner_ml
	
	public function get_name()
	{
		return ($this->name);	
	}
	
	public function set_name($name)
	{
		$this->name = strval($name);
	}
	
	public function set_error($msg)
	{
		$this->error = $msg;
	}
	
	public function get_key($type)
	{
		return (isset($this->keys[$type]) ? $this->keys[$type] : '');
	}
	
	public function set_key($type, $key)
	{
		$this->keys[$type] = $key;
	}

	public function get_version()
	{
		return ($this->version);
	}
	
	public function set_version($version)
	{
		$this->version = $version;
	}
	
	public function debug($level = WPRO_SOAP_DEBUG_ALL)
	{
		$this->debug = $level;
		
		if (!self::$debug_log_cleared)
		{
			$this->set_log_user();
			
			$log_file_path = $this->log_get_path();
			if (file_exists($log_file_path))
			{
				unlink($log_file_path);
			}
			$req_info_path = str_replace('soap_xml.log', 'request_info.log', $log_file_path);
			if (file_exists($req_info_path))
			{
				unlink($req_info_path);
			}
			self::$debug_log_cleared = true;
		}
	}
	
	protected function set_log_user()
	{
		$sapi_user = (defined('PHP_SAPI')) ? PHP_SAPI : 'cgi';
		$session_user = (isset($_SESSION['id'])) ? $_SESSION['id'] : false;
		self::$log_user = $sapi_user.((empty($session_user)) ? '' : '-'.$session_user);
	}
	
	public function sandbox($true_false = true)
	{
		$this->sandbox = $true_false;
	}
	
	public function simulate()
	{
		$this->simulate = true;
		$this->debug(WPRO_SOAP_DEBUG_ALL ^ WPRO_SOAP_DEBUG_RESPONSE);
	}
	
}

?>