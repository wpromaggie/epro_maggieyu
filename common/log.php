<?php

/**
* 	log class implements data collection from running processes and stores them in table log 
* 	@method void function init($opts = array())
*	@method void function shutdown()
*	@method void function write($msg = '', $t0 = 'note', $t1 = '', $frame = null)
*	@method void function get_error_type_string($error_type_num)
*/
class log
{
	public static $quiet;
	public static $start_time, $end_time;
	
	public static function init($opts = array())
	{
		// more accurate if done at very beginning of very entry point
		// but it's all relative, right?
		self::$start_time = microtime(true);
		if (!array_key_exists('quiet', $opts)) $opts['quiet'] = false;
		if ($opts['quiet'])
		{
			ini_set('display_errors', false);
			error_reporting(0);
		}

		self::register_hooks();
		register_shutdown_function(array('log', 'shutdown'));
	}
	

	/**
	* At the end of a log's execution gather statistics about the operation eg.
	* 	start time, end time, duration, who ran the process....
	*
	* @param void
	* @return void
	*/
	public static function shutdown()
	{
		self::$end_time = microtime(true);
		if (util::is_cgi()) {
			$interface = 'cgi';
			$user = user::$id;
			if (cgi::is_ajax()) {
				$context = 'ajax';
			}
			else {
				$context = 'browser';
			}
			$url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			$ip = cgi::$ip;
			$path = '';
			$args = '';
		}elseif (util::is_api()) {
			$interface = 'api';
			$url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			$context = '';
			$ip = $_SERVER['REMOTE_ADDR'];
			$path = $_SERVER['PWD'].'/'.$_SERVER['PHP_SELF'];
			// todo: args passed within quotes? how to get raw command line?
			//$args = implode(' ', array_slice($_SERVER['argv'], 1));
		}
		else {
			$interface = 'cli';
			$user = '';
			$context = '';
			$url = '';
			$ip = '';
			$path = $_SERVER['PWD'].'/'.$_SERVER['PHP_SELF'];
			// todo: args passed within quotes? how to get raw command line?
			$args = implode(' ', array_slice($_SERVER['argv'], 1));
		}
		$is_error = 0;
		$e = error_get_last();
		if (@is_array($e)) {
			$type = $e['type'];
			if (!($type & (E_NOTICE | E_STRICT))) {
				$is_error = 1;
			}
		}
		$req_data = array(
			'interface' => $interface,
			'user' => $user,
			'context' => $context,
			'start_time' => self::$start_time,
			'end_time' => self::$end_time,
			'elapsed' => self::$end_time - self::$start_time,
			'hostname' => \epro\HOSTNAME,
			'max_memory' => memory_get_peak_usage(true),
			'is_error' => $is_error,
			'url' => $url,
			'ip' => $ip,
			'path' => $path,
			'args' => $args
		);
		request::create($req_data);

		if ($is_error) {
			$entry = self::write($e['message'], 'error', self::get_error_type_string($type), new frame(array(
				'i' => 0,
				'file' => $e['file'],
				'line' => $e['line']
			)));
		}
	}

		
	public static function write($msg = '', $t0 = 'note', $t1 = '', $frame = null)
	{
		$is_cgi = (util::is_cgi());
		$entry = new entry(array(
			'dt' => date(util::DATE_TIME),
			'type0' => $t0,
			'type1' => $t1,
			'interface' => ($is_cgi) ? 'cgi' : 'cli',
			'message' => $msg
		));
		if ($is_cgi)
		{
			$entry->cgi_info = new cgi_info(array(
				'user' => user::$id,
				'url' => '/'.implode('/', g::$pages)
			));
		}
		
		$entry->put();
		if ($frame)
		{
			$frame->entry_id = $entry->id;
			$frame->put();
		}
		else
		{
			$trace = debug_backtrace();
			for ($ci = count($trace) - 1, $i = $ci; $i > -1; --$i)
			{
				$t = $trace[$i];
				$frame = new frame(array(
					'entry_id' => $entry->id,
					'i' => $ci - $i,
					'file' => $t['file'],
					'class' => $t['class'],
					'function' => $t['function'],
					'line' => $t['line']
				));
				$frame->put();
			}
		}
		
		if (util::is_dev()) {
			e($entry);
		}
		return $entry;
	}
	
	public static function get_error_type_string($error_type_num)
	{
		$error_type_strs = array('E_ERROR','E_WARNING','E_PARSE','E_NOTICE','E_CORE_ERROR','E_CORE_WARNING','E_COMPILE_ERROR','E_COMPILE_WARNING','E_USER_ERROR','E_USER_WARNING','E_USER_NOTICE','E_STRICT','E_RECOVERABLE_ERROR','E_DEPRECATED','E_USER_DEPRECATED','E_ALL');
		foreach ($error_type_strs as $error_type_str)
		{
			if (constant($error_type_str) == $error_type_num)
			{
				return $error_type_str;
			}
		}
		return 'E_UNKNOWN';
	}

	/**
	 * init_hooks() Initialize all database hooks
	 * Use regex rules to include all or excluse some or choose specific tables
	 * Applies to the database
	 * All Tables <dbname>.*
	 * One Table <dbname>.<tablename>
	 * Exclude <dbname>.*|f=[table,separated,by,comma] //do not warp the excluded tables in brackets 
	 *
	 * @return void
	 */
	public static function register_hooks(){
			db::register_hook('update',array('delta_meta','update_hook'),array(	
				'eppctwo.client_payment',
				'eppctwo.client_payment_part',
				'eppctwo.clients',
				'eppctwo.clients_ppc',
				'eppctwo.clients_seo',
				'eppctwo.clients_smo',
				'eac.account',
			));

			db::register_hook('delete',array('delta_meta','delete_hook'),array(
				'eppctwo.client_payment',
				'eppctwo.client_payment_part',
				'eppctwo.clients',
				'eppctwo.clients_ppc',
				'eppctwo.clients_seo',
				'eppctwo.clients_smo',
				'eac.account',
			));
	}
}

?>