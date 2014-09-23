<?php

class sales_lib
{
	
	const AVV_BATCH_SIZE = 5;
	
	public static $avv_error = '';
	
	//expects an array of prospects
	public static function post_to_avv($prospects = array(), $opts = array())
	{
		//disabled avv
		return false;

		$opts = util::set_opt_defaults($opts, array(
			'break_on_error' => false
		));
		
		self::$avv_error = '';
		$all_responses = '';
		for ($i = 0, $ci = count($prospects); $i < $ci; $i += self::AVV_BATCH_SIZE)
		{
			$r = self::post_to_avv_batch(array_slice($prospects, $i, min(self::AVV_BATCH_SIZE, $ci - $i)));
			if ($r === false)
			{
				if ($opts['break_on_error'])
				{
					return false;
				}
			}
			else if (is_string($r))
			{
				$all_responses .= $r;
			}
		}
		// if avv_error is empty, no errors, return all responses
		// if non-empty, there was an error, return false
		return ((self::$avv_error == '') ? $all_responses : false);
	}
	
	private static function post_to_avv_batch($prospects)
	{
		$url = "https://webcontrol.avv.com/api/AdfLeadsParser.cfm?provider=326";
		
		$prospect_xml = "";
		foreach($prospects as $prospect){
			$prospect_xml .= self::format_prospect_xml($prospect);
		}
		
		$post_string = '<?xml version="1.0"?><?adf version="1.0"?><adf>'.$prospect_xml.'</adf>';
		
		$header = array(
			"POST HTTP/1.0",
			"Content-type: application/x-www-form-urlencoded",
			"Content-length: ".strlen($post_string),
			"Content-transfer-encodeing: text",
			"Connection: close\r\n",
			$post_string
		);
		
		$ch = curl_init($url);
		
		curl_setopt_array($ch, array(
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_HEADER => 1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_HTTPHEADER => $header
		));
		
		$data = curl_exec($ch);
		
		if(curl_errno($ch)){
			self::$avv_error = print_r(curl_error($ch), true);
			return false;
		}
		
		// check data for errors?
		
		curl_close($ch);
		
		return $data;
	}
	
	public static function format_prospect_xml($data=array()){
		
		$provider_name = self::map_avv_referer($data['referer']);
		
		return '<prospect>
				<id>'.$data['id'].'</id>
				<requestdate>'.date("c", strtotime($data['created'])).'</requestdate>
				<vehicle interest="buy" status="new">
					<year></year>
					<make>Unknown</make>
					<model></model>
                                        <stock>'.util::xml_data($data['cat']).'</stock>
				</vehicle>
				<customer>
					<contact>
						<name part="full">'.util::xml_data($data['name']).'</name>
						<email>'.util::xml_data($data['email']).'</email>
						<phone>'.util::xml_data($data['phone']).'</phone>
					</contact>
					
					<comments>'.util::xml_data('
						URL: '.$data['url'].';
						Company: '.$data['company'].';
						Budget: '.$data['budget'].';
						Interests: '.$data['interests'].';
						Biz Type: '.$data['cat'].';
						IP: '.$data['ip'].';
						Form Source: '.$data['source'].';
						Referer Source: '.$data['referer']).'
					</comments>
				</customer>
				
				<vendor>
					<vendorname>Wpromote</vendorname>
					<contact>
						<email>sales@wpromote.dealerspace.com</email>
					</contact>
				</vendor>
				
				<provider>
					<name part="full">'.$provider_name.'</name>
					<service>'.$provider_name.'</service>
				</provider>
				
			</prospect>';
		
	}
	
	public function map_avv_referer($referer=""){

		//Force referer to lowercase
		$referer = strtolower($referer);

		//referer mapping logic
		if(preg_match("/^(email|ew)/", $referer)){
			
			return 'Entireweb';
			
		} else if(preg_match("/^(wpn)/", $referer)){
			
			return 'Email Blast';

		} else if(preg_match("/^(ppc)/", $referer)){
			
			return 'PPC';

		} else if(preg_match("/^(ieb)/", $referer)){
			
			return 'Internal Email Blast';

		} else if(preg_match("/^(ak)/", $referer)){
			//ie. ak1, ak2, ak3
			return 'AdKnowledge';

		} else {

			switch($referer){
				case('blp'):   return 'Best Leads Plus';
				case('zl'):   return 'ZeroLag';
				case('aff'):  return 'Affiliate';
				case('yola'): return 'Yola';
				case('wm'):   return 'Website Magazine';
				case('pm'):   return 'Webz/PageModo';
				case('yh'):   return 'Yellow Hammer';
				case('vp'):   return 'Vista Print';
				case('fb'):   return 'Facebook';
				case('fbd'):  return 'Facebook Direct';
				case('ebr'):  return 'eBridge';
				case('li'):   return 'LinkedIn';
				case('db'):   return 'Dashbee';
				case('spn'):  return 'SiteProNews';
				case('affw4'): return 'Aff w4';
				case('be'):   return 'BounceExchange';
				case('lg'):   return 'ListGiant';
				case('gb'):   return 'ListGiantBulk';
				default:      return $referer;
			}
		}
	}
}
?>
