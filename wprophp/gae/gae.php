<?php
require(__DIR__.'/env.php');

class gae
{
	const DBG_REQUEST  = 0x01;
	const DBG_RESPONSE = 0x02;
	const DBG_HEAD     = 0x04;
	
	public static $dbg = 0;
	public static $head;
	
	private static $request_headers;
	
	public static function get($url_path, $data = null)
	{
		$url = 'http://'.WPROPATH_DOMAIN.'/'.$url_path;
		$url_info = parse_url($url);
		$host = $url_info['host'];
		$path = $url_info['path'];
		$query = $url_info['query'];
		$port = $url_info['port'];
		
		if (!is_null($data))
		{
			if (is_array($data))
			{
				$data = util::array_to_query($data);
			}
			$query = ($query) ? ($query.'&'.$data) : $data;
		}
		
		$out  = "GET {$path}".(($query) ? '?'.$query : '')." HTTP/1.1\r\n";
		$out .= "Host: {$host}\r\n";
		$out .= "Accept-Encoding: gzip\r\n";
		$out .= "User-Agent: gzip\r\n";
		self::check_request_headers($out);
		$out .= "Connection: Close\r\n\r\n";
		
		return self::go($host, $port, $out);
	}
	
	/**
	*	HTTP Post implemtation using go() an abstraction of fsocket
	*		Primary use to use bzcomression when sending requests
	*		WPRO API
	*	@param string $url_path URI
	*	@param mixed[]	$data POST DATA
	*/	
	public static function post($url_path, $data)
	{
		$url = 'http://'.WPROPATH_DOMAIN.'/wapi/'.$url_path;
		$url_info = parse_url($url);
		$host = $url_info['host'];
		$path = $url_info['path'];
		$query = $url_info['query'];
		$port = $url_info['port'];
		
		if (is_array($data))
		{
			$data = util::array_to_query($data);
		}
		// not technically the head, but.. whatev
		if (self::$dbg & (self::DBG_REQUEST | self::DBG_HEAD))
		{
			echo $data."\n";
		}

		$post_str = bzcompress($data);
		$out  = "POST {$path}".(($query) ? '?'.$query : '')." HTTP/1.1\r\n";
		$out .= "Host: {$host}\r\n";
		$out .= "Accept-Encoding: gzip\r\n";
		$out .= "User-Agent: gzip\r\n";
		self::check_request_headers($out);
		$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$out .= "Content-Length: ".strlen($post_str)."\r\n";
		$out .= "Connection: Close\r\n\r\n";
		$out .= $post_str;
		
		return self::go($host, $port, $out);
	}
	
	/**
	*	Abstraction of fsocket to implement lower level protocol requests
	*		eg. HTTP as is used in static function post(url,data) here
	*
	* 	@param string $host host path
	*	@param int $port port number
	*	@param string &$out data passed by reference
	*/
	private static function go($host, $port, &$out)
	{
		$fp = fsockopen($host, ($port) ? $port : 80);
		if (!$fp) return;

		if (self::$dbg & (self::DBG_REQUEST | self::DBG_HEAD))
		{
			echo "\n------\nREQUEST\n-----\n";
			echo "host=$host:$port\ndata=$data\n$out\n";
		}
		
		fwrite($fp, $out);

		$response = '';
		while(!feof($fp))
		{
			$response .= fgets($fp, 1024);
		}
		
		fclose($fp);
		self::clear_request_headers();
		
		return self::parse_response($response);
	}

	/**
	*	
	* 	@param <type> $level
	*/	
	public static function dbg($level = null)
	{
		if (is_null($level))
		{
			$level = self::DBG_REQUEST | self::DBG_RESPONSE;
		}
		self::$dbg = $level;
	}
	
	private static function parse_response(&$response)
	{
		list($head_raw, $body_raw) = @explode("\r\n\r\n", $response);
		
		$head_lines = @explode("\n", $head_raw);
		self::$head = array();
		foreach ($head_lines as $line_num => $line)
		{
			$i = strpos($line, ':');
			if ($i)
			{
				$k = substr($line, 0, $i);
				$v = trim(substr($line, $i + 1));
			}
			else
			{
				$k = 'line_'.$line_num;
				$v = trim($line);
			}
			self::$head[$k] = $v;
		}

		if (self::$dbg & (self::DBG_RESPONSE | self::DBG_HEAD))
		{
			echo "\n------\nRESPONSE\n-----\n";
			print_r(self::$head);
		}
		if (self::$head['Content-Encoding'] == 'gzip')
		{
			$tmp_filename = tempnam(sys_get_temp_dir(), '');
			file_put_contents($tmp_filename, $body_raw);
			ob_start();
			readgzfile($tmp_filename);
			$contents = ob_get_contents();
			ob_end_clean();
			unlink($tmp_filename);
			
			if (self::$dbg & self::DBG_RESPONSE)
			{
				echo "$contents\n";
			}
			return $contents;
		}
		else
		{
			if (self::$dbg & self::DBG_RESPONSE)
			{
				echo "$body_raw\n";
			}
			return (($body_raw) ? $body_raw : '');
		}
	}
	
	public static function was_http_error()
	{
		list($http_version, $status_code) = explode(' ', gae::$head['line_0']);
		return ($status_code[0] == '4' || $status_code[0] == '5');
	}
	
	public static function add_request_headers($headers)
	{
		self::$request_headers = $headers;
	}
	
	private static function clear_request_headers()
	{
		self::$request_headers = null;
	}
	
	private static function check_request_headers(&$out)
	{
		if (self::$request_headers)
		{
			$out .= implode("\r\n", self::$request_headers)."\r\n";
		}
	}
	
}

?>