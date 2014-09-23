<?php


function http_post(&$return, $url, $post_data)
{
	http_set_post_string($post_str, $post_data);
	$headers = http_get_headers('POST', $url, $post_str);
	
	$curl = curl_init($url);
	curl_setopt_array($curl, array(
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_HEADER => 1,
		CURLOPT_HTTPHEADER => $headers,
		CURLOPT_POST => 1,
		CURLOPT_POSTFIELDS => $post_str
	));
	
	http_go($return, $curl, $headers);
}

function http_set_post_string(&$post_str, &$post_data)
{
	if (is_array($post_data))
	{
		$post_str = '';
		foreach ($post_data as $k => $v)
		{
			if (!empty($post_str)) $post_str .= '&';
			$post_str .= urlencode($k).'='.urlencode($v);
		}
	}
	else
	{
		$post_str = $post_data;
	}
}

function http_get_headers($method, $url, $post_str = '')
{
	$url_info = parse_url($url);
	$path = $url_info['path'];
	if (!empty($url_info['query'])) $path .= '?'.$url_info['query'];
	
	$headers = array(
		$method.' '.$path.' HTTP/1.1',
		'Host: '.$url_info['host'],
		'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.5) Gecko/2008120122 Firefox/3.0.5',
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
		'Accept-Language: en-us,en;q=0.5',
		'Accept-Encoding: gzip,deflate',
		'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
		'Keep-Alive: 300',
		'Connection: keep-alive'
	);
	
	if ($method == 'POST')
	{
		$headers[] = 'Content-Type: application/x-www-form-urlencoded';
		$headers[] = 'Content-Length: '.strlen($post_str);
	}
	
	return $headers;
}

function http_go(&$return, &$curl, &$headers)
{
	$response = curl_exec($curl);
	
	http_handle_response($return, $response);
	
	@curl_close($curl);
	http_handle_header($return);
}

function http_handle_response(&$return, &$response)
{
	if (!$response) return false;
	
	// set type of end line
	$sample = substr($response, 0, 256);
	if (strpos($sample, "\r\n")) $endl = "\r\n";
	else if (strpos($sample, "\n")) $endl = "\n";
	else $endl = "\r";
	
	// init return
	$return = array(
		'headers' => array(),
		'body' => ''
	);
	
	// set headers and body
	// ai=arbitrary index (apparently ms sends multiple sets of headers sometimes, which is why we have to do this at all.
	// max is probably 2, we'll do 5 just for fun)
	$is_gzipped = false;
	for ($ai = 0; $ai < 5; ++$ai)
	{
		// look for headers/body separator
		$i = strpos($response, "$endl$endl");
		if ($i === false) return false;
		
		$headers = explode($endl, substr($response, 0, $i));
		
		$return['headers'][] = $headers;
		
		// see if body is gzipped
		foreach ($headers as $header)
		{
			if (preg_match("/^Content-Encoding: (.*?)$/i", $header, $matches))
			{
				$encoding = strtolower($matches[1]);
				if ($encoding != 'gzip') return false;
				$is_gzipped = true;
				break;
			}
		}
		
		$response = @substr($response, $i + (strlen($endl) * 2));
		
		// see if there are more headers, if so, loop
		if (preg_match("/http\/\d\.\d \d+/i", substr($response, 0, 32))) continue;
		
		if ($is_gzipped) gzdecode($response);
		
		// omigod some servers return responses that contain \r\n and just \n in the same body???!?!
		$tmp = explode("\r\n", $response);
		$body = array();
		foreach ($tmp as $parts)
		{
			$body = array_merge($body, explode("\n", $parts));
		}
		$return['body'] = $body;
		break;
	}
}

function gzdecode(&$data)
{
  $gzf = fopen(GZIP_TMPFILE, 'w');
  fwrite($gzf, $data);
  fclose($gzf);

  $gzf = gzopen(GZIP_TMPFILE, 'r');
  
  ob_start();
  gzpassthru($gzf);
  ob_stop($data);
  
  // empty the file
  $gzf = fopen(GZIP_TMPFILE, 'w');
  fwrite($gzf, '');
  fclose($gzf);
}

function http_handle_header(&$response)
{
	$cookies;
	
	// loop over headers looking for cookies
	$headers = $response['headers'];
	foreach ($headers as $header_set)
	{
		foreach ($header_set as $header)
		{
			list($key, $val) = explode(':', $header, 2);
			if ($key == 'Set-Cookie')
			{
				// we don't need the path, expriation, etc
				list($cookie_val) = explode(';', $val, 2);
				$cookie_val = trim($cookie_val);
				
				if (empty($cookies)) $cookies = $cookie_val;
				else $cookies .= '; '.$cookie_val;
			}
		}
	}
}

?>