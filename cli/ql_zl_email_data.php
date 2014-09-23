<?php
require_once('cli.php');

$recipients = array();
$day_offset = 1;
for ($i = 1; $i < count($argv); ++$i)
{
	$arg = $argv[$i];
	if (preg_match("/^d-(\d+)$/", $arg, $matches))
	{
		$day_offset = $matches[1];
	}
	else if (preg_match("/^[\w_.-]+@[\w_.-]+\.[\w_.-]+$/", $arg))
	{
		$recipients[] = $arg;
	}
}
if (empty($recipients))
{
	echo "Error: no recipients\n";
	exit(1);
}

$date = date('Y-m-d', time() - (86400 * $day_offset));
if (!$date)
{
	echo "Error: bad day offset - $day_offset\n";
	exit(1);
}
$us_date = date('n/j/y', strtotime($date));

$zl_data = zl_get_data($date);

foreach ($recipients as $recipient)
{
	wmail('no-reply@reporting.wpromote.com', $recipient, 'ZL Impressions Report - '.$us_date, array(
		'text_plain' => '',
		'attachments' => array(
			array('data' => $zl_data, 'name' => 'zl_data_'.$date.'.csv')
		)
	));
}

function wmail($from, $to, $subject, $data = array())
{
	$text_plain = $data['text_plain'];
	$text_html = $data['text_html'];
	$attachments = $data['attachments'];
	$other_headers = $data['headers'];
	
	$boundary = md5(time());
	
	$headers = array();
	
	if ($attachments)
	{
		$mixed_boundary = 'm'.$boundary;
		$headers[] = 'Content-Type: multipart/mixed; boundary='.$mixed_boundary;
		
		if ($text_html)
		{
			$alt_boundary = 'a'.$boundary;
			$message = "--{$mixed_boundary}
Content-Type: multipart/alternative; boundary={$alt_boundary}

".wmail_multipart_alternative($alt_boundary, $text_plain, $text_html)."
".wmail_encode_attachments($mixed_boundary, $attachments)."--{$mixed_boundary}--
";
		}
		else
		{
			$message = "--{$mixed_boundary}
Content-Type: text/plain; charset=ISO-8859-1

{$text_plain}
".wmail_encode_attachments($mixed_boundary, $attachments)."--{$mixed_boundary}--
";
		}
	}
	else
	{
		if ($text_html)
		{
			$headers[] = 'Content-Type: multipart/alternative; boundary='.$boundary;
			$message = wmail_mulipart_alternative($boundary, $text_plain, $text_html);
		}
		else
		{
			$headers[] = 'Content-Type: text/plain; charset=ISO-8859-1';
			$message = "{$text_plain}\n";
		}
	}
	$headers[] = 'From: '.$from;
	$headers = implode("\n", $headers);
	
/*
	for ($i = 0; $i < strlen($headers); ++$i)
	{
		echo str_pad($i, 5, ' ', STR_PAD_LEFT).': '.str_pad(ord($headers[$i]), 5, ' ', STR_PAD_RIGHT).': '.$headers[$i]."\n";
	}
	echo "$to, $subject, $message, $headers";
	//return;
*/
	mail($to, $subject, $message, $headers);
}


function wmail_encode_attachments($boundary, $attachments)
{
	$encoded = '';
	for ($i = 0, $ci = count($attachments); $i < $ci; ++$i)
	{
		$attachment = $attachments[$i];
		if (array_key_exists('path', $attachment))
		{
			$path =  $attachment['path'];
			if (array_key_exists('name', $attachment))
			{
				$name = $attachment['name'];
			}
			else
			{
				$pathinfo = pathinfo($path);
				$name = $pathinfo['basename'];
			}
			$file_data = file_get_contents($path);
		}
		else if (array_key_exists('data', $attachment) && array_key_exists('name', $attachment))
		{
			$file_data = $attachment['data'];
			$name = $attachment['name'];
		}
		else
		{
			continue;
		}
		$encoded .= "\n--{$boundary}
Content-Type: application/octet-stream; name=\"{$name}\"
Content-Disposition: attachment; filename=\"{$name}\"
Content-Transfer-Encoding: base64

".chunk_split(base64_encode($file_data), 76, "\n");
	}
	return $encoded;
}


function wmail_multipart_alternative($boundary, $text_plain, $text_html)
{
	return "--{$boundary}
Content-Type: text/plain; charset=ISO-8859-1

{$text_plain}

--{$boundary}
Content-Type: text/html; charset=ISO-8859-1

{$text_html}

--{$boundary}--
";
}

function zl_get_data($date)
{
	$urls = db::select("
		select id, oid, url, signup_date, 0 imps
		from eppctwo.ql_url
		where
			status = 'Active' &&
			plan like 'lal%' &&
			url not like '%.example%'
	", 'ASSOC', 'id');
	echo "c=".count($urls)."\n";

	$markets = array('g', 'm');
	foreach ($markets as $market)
	{
		$market_data = db::select("
			select client, sum(imps)
			from {$market}_data.ad_groups_ql
			where data_date = '$date'
			group by client
		");
		echo "$market: ".count($market_data)."\n";
		for ($i = 0; list($url_id, $imps) = $market_data[$i]; ++$i)
		{
			if (!array_key_exists($url_id, $urls))
			{
				continue;
			}
			$urls[$url_id]['imps'] += $imps;
		}
	}
	$url_data = array();
	foreach ($urls as $url_id => $data)
	{
		//if ($data['status'] == 'On' || $data['imps'] > 0)
		if (!empty($data['oid']))
		{
			$url_data[] = $data;
		}
	}
	#print_r($url_data);
	usort($url_data, 'zl_data_sort');
	#print_r($url_data);
	$out = "OID,URL,Signup,Impressions\n";
	for ($i = 0, $ci = count($url_data); $i < $ci; ++$i)
	{
		$d = $url_data[$i];
		$oid = $d['oid'];
		$imps = $d['imps'];
		$signup_date = $d['signup_date'];
		$url = $d['url'];
		$out .= "$oid,$url,$signup_date,$imps\n";
	}
	return $out;
}

function zl_data_sort($a, $b)
{
	return ($b['imps'] - $a['imps']);
}

?>