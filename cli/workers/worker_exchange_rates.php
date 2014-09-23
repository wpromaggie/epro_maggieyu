<?php
util::load_lib('ql', 'sbs');

class worker_exchange_rates extends worker
{
	const API_URL = 'https://openexchangerates.org/api';
	const API_KEY = 'a29951f8661446329eecddb8d526b692';
	
	public function run()
	{
		$date = (array_key_exists('d', cli::$args)) ? cli::$args['d'] : date('Y-m-d', time() - 86400);
		
		// get active currencies
		$active_currencies = array();
		$markets = util::get_ppc_markets();
		$markets[] = 'y';
		foreach ($markets as $market) {
			$currencies = db::select("select distinct currency from {$market}_accounts where status='On' && currency<>'USD'");
			foreach ($currencies as $currency) {
				$active_currencies[$currency] = 1;
			}
		}

		$path = tempnam(sys_get_temp_dir(), 'ex-rates-');
		$url = self::API_URL.'/historical/'.$date.'.json?app_id='.self::API_KEY;

		if ($this->dbg) {
			echo "url=$url, p=$path\n";
		}

		exec('wget --no-check-certificate -q '.$url.' -O '.$path);

		if (!file_exists($path)) {
			cli::email_error('chimdi@wpromote.com', 'Ex Rate Grabber', 'Could not write file', true);
		}

		$response = file_get_contents($path);
		unlink($path);

		if (!$response) {
			cli::email_error('chimdi@wpromote.com', 'Ex Rate Grabber', 'Empty response', true);
		}

		if ($this->dbg) {
			echo "response=$response\n";
		}

		$data = json_decode($response, true);
		if (!$data) {
			cli::email_error('chimdi@wpromote.com', 'Ex Rate Grabber', 'Could not decode response: '.$response, true);
		}
		if (array_key_exists('error', $data)) {
			cli::email_error('chimdi@wpromote.com', 'Ex Rate Grabber', 'API Error: '.$response['message'].' - '.$response['description'], true);
		}
		
		foreach ($data['rates'] as $currency => $rate) {
			if (array_key_exists($currency, $active_currencies)) {
				$to_usd = 1 / $rate;
				db::insert("eppctwo.exchange_rates", array(
					'd' => $date,
					'currency' => $currency,
					'rate' => $to_usd
				));
			}
		}
	}
}

?>