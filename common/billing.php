<?php
/*
 * deal with credit cards and payment processors and the like
 */

define('BILLING_PROCESSOR_HOST', 'secure.linkpt.net');
define('BILLING_PROCESSOR_PORT', '1129');
define('BILLING_PROCESSOR_CONFIG_FILE', '1001198720');

// charge/refund types to be send to processor
define('BILLING_CC_PREAUTH', 'PREAUTH');
define('BILLING_CC_CHARGE' , 'SALE');
define('BILLING_CC_REFUND' , 'CREDIT');

define('BILLING_TEST_SUCCESS', 'j123412341234123');
define('BILLING_TEST_FAILURE', 'f789078907890789');

class billing
{
	public static $error_msg, $order_id, $order_avs, $order_error;
	
	private static $dbg = false;
	
	public static function dbg()
	{
		self::$dbg = true;
	}
	
	public static function is_dbg()
	{
		return (self::$dbg);
	}
	
	public static function cc_new($foreign_table, $foreign_id, $d)
	{
		return db::insert("eppctwo.ccs", array(
			'foreign_table' => $foreign_table,
			'foreign_id' => $foreign_id,
			'name' => $d['name'],
			'country' => $d['country'],
			'zip' => $d['zip'],
			'cc_number' => util::encrypt($d['cc_number']),
			'cc_type' => $d['cc_type'],
			'cc_exp_month' => $d['cc_exp_month'],
			'cc_exp_year' => $d['cc_exp_year'],
			'cc_code' => util::encrypt($d['cc_code'])
		));
	}
	
	/**
	 * on data updates, sensitive info is *'d out in the form.
	 * if *'d data is submitted, we don't want to actually use that to update
	 * so check if what we have is actually a number.
	 * if it's not, unset the key in the array so it is not sent to database
	 * if it is a number, encrypt it
	 * @param $d array data to update
	 * @param ... string all subsequest arguments are keys to check per above
	 * @return void
	 */
	private static function crypt_check(&$d)
	{
		$argv = func_get_args();
		for ($i = 1, $loop_end = count($argv); $i < $loop_end; ++$i)
		{
			$key = $argv[$i];
			$val = $d[$key];
			
			if (!is_numeric($val) && ($val != BILLING_TEST_SUCCESS) && ($val != BILLING_TEST_FAILURE)) unset($d[$key]);
			else $d[$key] = util::encrypt($val);
		}
	}
	
	public static function cc_update($cc_id, $d)
	{
		self::crypt_check($d, 'cc_number', 'cc_code');
		return db::update("eppctwo.ccs", $d, "id = '$cc_id'");
	}

	public static function cc_get($id)
	{
		$d = db::select_row("
			select *
			from ccs
			where id='{$id}'
		", 'ASSOC');
		
		$d['cc_number'] = util::decrypt($d['cc_number']);
		$d['cc_code'] = util::decrypt($d['cc_code']);

		return $d;
	}

	public static function decrypt_val($v)
	{
		return util::decrypt($v);
	}

	public static function cc_get_actual($mixed, $keys = array())
	{
		if (is_scalar($mixed)) {
			return self::cc_get($mixed);
		}
		else if ($mixed) {
			if (!$keys) {
				$keys = array('cc_number', 'cc_code');
			}
			foreach ($keys as $key) {
				if (is_array($mixed)) {
					if (isset($mixed[$key]) && $mixed[$key]) {
						$mixed[$key] = util::decrypt($mixed[$key]);
					}
				}
				else {
					if (isset($mixed->$key) && $mixed->$key) {
						$mixed->$key = util::decrypt($mixed->$key);
					}
				}
			}
		}
		return $mixed;
	}
	
	public static function cc_get_display($id)
	{
		$d = self::cc_get($id);
		self::cc_obscure($d);
		
		return $d;
	}
	
	public static function cc_obscure(&$d, $cc_number_key = 'cc_number', $cc_code_key = 'cc_code')
	{
		// * out the middle, only show first 4 and last 4
		if (array_key_exists($cc_number_key, $d)) $d[$cc_number_key] = self::cc_obscure_number($d[$cc_number_key]);
		
		// never show cc code
		if (array_key_exists($cc_code_key, $d))
		{
			$cc_code = $d[$cc_code_key];
			$d[$cc_code_key] = (is_numeric($cc_code)) ? str_repeat('*', strlen($cc_code)) : '';
		}
	}
	
	public static function cc_obscure_number($cc_num)
	{
		for ($i = 4, $loopend = strlen($cc_num) - 4; $i < $loopend; ++$i) $cc_num[$i] = '*';
		return $cc_num;
	}
	
	public static function check_new($foreign_table, $foreign_id, $d)
	{
		return db::insert("eppctwo.checks", array(
			'foreign_table' => $foreign_table,
			'foreign_id' => $foreign_id,
			'name' => $d['name'],
			'phone' => $d['phone'],
			'account_type' => $d['account_type'],
			'account_number' => util::encrypt($d['account_number']),
			'routing_number' => util::encrypt($d['routing_number']),
			'check_number' => $d['check_number'],
			'drivers_license' => $d['drivers_license'],
			'drivers_license_state' => $d['drivers_license_state']
		));
	}

	public static function check_update($check_id, $d)
	{
		self::crypt_check($d, 'account_number', 'routing_number');
		return db::update("eppctwo.checks", $d, "id = '$check_id'");
	}

	public static function check_get($id)
	{
		$d = db::select_row("
			select *
			from checks
			where id='$id'
		", 'ASSOC');
		
		$d['account_number'] = util::decrypt($d['account_number']);
		$d['routing_number'] = util::decrypt($d['routing_number']);
		
		return $d;
	}

	public static function check_get_actual($id)
	{
		return self::check_get($id);
	}

	public static function check_get_display($id)
	{
		$d = self::check_get($id);
		
		// * out everything after first 4 for account and routing
		$keys = array('account_number', 'routing_number');
		foreach ($keys as $key)
		{
			$val = $d[$key];
			for ($i = 4, $count = strlen($val); $i < $count; ++$i)
				$val[$i] = '*';
			$d[$key] = $val;
		}
		
		return $d;
	}
	
	public static function get_error($all = true)
	{
            if($all){
		return self::$error_msg;
            } else {
                return self::$order_error;
            }
	}
	
	/**
	 * charge a credit card!
	 * @param $cc_id int id of credit card we are charging
	 * @param $amount you guessed it
	 * @param $charge_type string 
	 * @return bool true on success, false on fail. member $order_id and $error_msg are set accordingly
	 */
	public static function charge($cc_id, $amount, $charge_type = BILLING_CC_CHARGE)
	{
		require_once('lphp.php');
		require_once(\epro\WPROPHP_PATH.'strings.php');
		
		$cc_info = self::cc_get_actual($cc_id);
		
		// constants
		$pdata = array();
		$pdata['host']       = BILLING_PROCESSOR_HOST;
		$pdata['port']       = BILLING_PROCESSOR_PORT;
		
		$pdata['keyfile']    = \epro\BILLING_KEYFILE_PATH;
		$pdata['configfile'] = BILLING_PROCESSOR_CONFIG_FILE;
		
		// required fields
		$pdata['cardnumber']    = $cc_info['cc_number'];
		$pdata['cardexpmonth']  = str_pad($cc_info['cc_exp_month'], 2, '0', STR_PAD_LEFT);
		$pdata['cardexpyear']   = substr($cc_info['cc_exp_year'], strlen($cc_info['cc_exp_year']) - 2, 2);
		$pdata['chargetotal']   = number_format($amount, 2, '.', '');
		$pdata['ordertype']     = $charge_type;
		
		// card code
		if (array_key_exists('cc_code', $cc_info) && is_numeric($cc_info['cc_code']))
		{
			$pdata['cvmindicator'] = 'provided';
			$pdata['cvmvalue'] = $cc_info['cc_code'];
		}
		else
		{
			$pdata['cvmindicator'] = 'not_provided';
		}
		
		// optional stuff (wpro key, processor key)
		$optional_fields = array(
			'name' => 'name',
			'country' => 'country',
			'zip' => 'zip'
		);
		foreach ($optional_fields as $wpro_key => $processor_key)
		{
			if (array_key_exists($wpro_key, $cc_info))
				$pdata[$processor_key] = strings::xml_encode($cc_info[$wpro_key]);
		}
		
		if (self::is_dbg())
		{
			$dbg_data = $pdata;
			self::cc_obscure($dbg_data, 'cardnumber', 'cvmvalue');
			e($dbg_data);
			$pdata['debug'] = 'true';
		}
		
		// uncomment to test success/failure, is_dev check for extra safety
		//if (util::is_dev()) $pdata['cardnumber'] = BILLING_TEST_FAILURE;
		if ($pdata['cardnumber'] == BILLING_TEST_SUCCESS)
		{
			$result = array(
				'r_approved' => 'APPROVED',
				'r_ordernum' => 'test_ordernum',
				'r_avs' => 'test_avs'
			);
		}
		else if ($pdata['cardnumber'] == BILLING_TEST_FAILURE)
		{
			$result = array(
				'r_approved' => 'FAILURE',
				'r_error' => 'test_error'
			);
		}
		// send the charge to linkpoint
		else
		{
			$mylphp = new lphp;
			$result = $mylphp->curl_process($pdata);
		}

		if (self::is_dbg())
		{
			e($result);
		}
		
		if (@$result['r_approved'] == 'APPROVED')
		{
			self::$order_id = $result['r_ordernum'];
			self::$order_avs = $result['r_avs'];
			return true;
		}
		else
		{
			self::$error_msg = '';
			//echo "<b>billing set error:</b>";
			
			
			if(is_array($result)){
				foreach ($result as $k => $v)
				{
					self::$error_msg .= "($k=$v)";
					if ($k == 'r_error')
					{
						self::$order_error = $v;
					}
				}
			} else if (is_string($result)){
				self::$error_msg .= self::$order_error = $result;
			} else {
				self::$error_msg .= self::$order_error = "empty linkpoint response";
			}
			return false;
		}
	}
	
	private static function init_pay_data($data)
	{
		require_once(\epro\WPROPHP_PATH.'strings.php');
		return array_merge($data, array(
			'host'       => BILLING_PROCESSOR_HOST,
			'port'       => BILLING_PROCESSOR_PORT,
			'keyfile'    => \epro\BILLING_KEYFILE_PATH,
			'configfile' => BILLING_PROCESSOR_CONFIG_FILE
		));
	}
	
	// actually get the money from a pre-auth
	public static function post_auth($order_id)
	{
		$pdata = self::init_pay_data(array(
			'oid'       => $order_id,
			'ordertype' => 'POSTAUTH'
		));
		
		return self::send_to_processor($pdata);
	}
	
	// void a pre-auth
	public static function void($order_id)
	{
		$pdata = self::init_pay_data(array(
			'oid'       => $order_id,
			'ordertype' => 'VOID'
		));
		
		return self::send_to_processor($pdata);
	}
	
	// return funds from a pre-auth
	public static function return_auth($order_id, $amount)
	{
		$pdata = self::init_pay_data(array(
			'oid'         => $order_id,
			'chargetotal' => number_format($amount, 2, '.', ''),
			'ordertype'   => 'return'
		));
		
		return self::send_to_processor($pdata);
	}
	
	private static function send_to_processor($pdata)
	{
		require_once('lphp.php');
		
		if (self::is_dbg())
		{
			$dbg_data = $pdata;
			self::cc_obscure($dbg_data, 'cardnumber', 'cvmvalue');
			e($dbg_data);
			$pdata['debug'] = 'true';
		}
		
		if ($pdata['cardnumber'] == BILLING_TEST_SUCCESS)
		{
			$result = array(
				'r_approved' => 'APPROVED',
				'r_ordernum' => 'test_ordernum',
				'r_avs' => 'test_avs'
			);
		}
		else if ($pdata['cardnumber'] == BILLING_TEST_FAILURE)
		{
			$result = array(
				'r_approved' => 'FAILURE',
				'r_error' => 'test_error'
			);
		}
		// send the charge to linkpoint
		else
		{
			$mylphp = new lphp;
			$result = $mylphp->curl_process($pdata);
		}

		if (self::is_dbg())
		{
			e($result);
		}
		
		if (@$result['r_approved'] == 'APPROVED')
		{
			self::$order_id = $result['r_ordernum'];
			self::$order_avs = $result['r_avs'];
			return true;
		}
		else
		{
			self::$error_msg = '';
			if (is_array($result))
			{
				foreach ($result as $k => $v)
				{
					self::$error_msg .= "($k=$v)";
					if ($k == 'r_error')
					{
						self::$order_error = $v;
					}
				}
			}
			else if (is_string($result))
			{
				self::$error_msg .= self::$order_error = $result;
			}
			else
			{
				self::$error_msg .= self::$order_error = "empty linkpoint response";
			}
			return false;
		}
	}
	
	public static function get_prospect_cc_id($pid, $client_id=null)
	{
		// first check cc table for prospect
		$cc_id = db::select_one("select id from eppctwo.ccs where foreign_table = 'prospects' && foreign_id = '$pid'");
		if ($cc_id)
		{
			return $cc_id;
		}
		// if we couldn't find it, check if they are actual client
		else if ($client_id)
		{
			return db::select_one("
				select c.id
				from eppctwo.ccs c
				where
					c.foreign_table = 'clients' &&
					c.foreign_id = '$cid'
			");
		}
		return false;
	}
}

?>
