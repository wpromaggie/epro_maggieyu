<?php
ini_set('memory_limit', '1G');

chdir(__DIR__);

require('../common/env.php');

require_once(\epro\WPROPHP_PATH.'apis/apis.php');
require_once(\epro\WPROPHP_PATH.'strings.php');
require_once(\epro\WPROPHP_PATH.'curl.php');
require_once(\epro\WPROPHP_PATH.'file_iterator.php');

require_once(\epro\COMMON_PATH.'db.php');
require_once(\epro\COMMON_PATH.'rs/rs.php');
require_once(\epro\COMMON_PATH.'util.php');

cli::init();

class cli
{
	public static $args;
	
	public static function init()
	{
		session_start();
		util::load_lib('e2', 'log', 'delly', 'account');
		log::init();
		db::connect(\epro\DB_HOST, \epro\DB_USER, \epro\DB_PASS, \epro\DB_NAME);
		cli::$args = cli::parse_args($GLOBALS['argv']);
	}
	
	public static function parse_args($before)
	{
		if (is_string($before)) {
			$before = self::str_to_args($before);
		}
		$after = array();
		for ($i = 0, $ci = count($before); $i < $ci; $i++)
		{
			$arg = trim($before[$i]);
			
			// if it's a dash, set the option
			if ($arg[0] == '-')
			{
				$arg = trim($arg, '-');
				$after[$arg] = 1;
				$previous_arg = $arg;
			}
			// otherwise, assume it is data for previous option
			else if (!empty($previous_arg))
			{
				$after[$previous_arg] = $arg;
				$previous_arg = '';
			}
		}
		return $after;
	}

	// todo: check for quotes
	public static function str_to_args($s)
	{
		return explode(' ', $s);
	}
	
	public static function exec_verbose($cmd)
	{
		echo "$cmd\n";
		$output = array();
		exec($cmd, $output);
		return $output;
	}

	// run command in background
	// returns pid
	public static function bg_exec($cmd, $opts = array())
	{
		util::set_opt_defaults($opts, array(
			'verbose' => false,
			'stdout' => '/dev/null',
			'stderr' => '&1'
		));

		/*
		if (defined(\epro\OS) && \epro\OS=='windows'){
			$WshShell = new COM("WScript.Shell");
			$oExec = $WshShell->Run(\epro\PHP_PATH." -f ".$cmd, 0, false);
			return 1;
		}
		else {
		*/
		$cmd .= ' 1>'.$opts['stdout'].' 2>'.$opts['stderr'].' & echo $!';
		if ($opts['verbose']) {
			echo $cmd."\n";
		}
		exec($cmd, $output);
		return $output[0];
		/*
		}
		*/
		
	}
	
	public static function run()
	{
		global $argv;
		
		$file = $argv[0];
		$file_info = pathinfo($file);
		$classname = $file_info['filename'];
		if (class_exists($classname))
		{
			$func_args = func_get_args();
			if ($func_args)
			{
				$method = $func_args[0];
			}
			// check for function passed in on command line
			else if (count($argv) > 1 && $argv[1][0] != '-')
			{
				$method = $argv[1];
			}
			
			if ($method)
			{
				if (method_exists($classname, $method))
				{
					$c = new $classname();
					$c->$method();
					exit;
				}
				else
				{
					echo "\n---\nError: $classname has no method $method\n---\n";
					exit(1);
				}
			}
			// get all public static methods for class, give user option which to run
			else
			{
				$rc = new ReflectionClass($classname);
				$static_methods = $rc->getMethods(ReflectionMethod::IS_STATIC);
				$static_public_methods = array();
				foreach ($static_methods as $i => $method)
				{
					if ($method->isPublic())
					{
						$static_public_methods[] = $method->name;
					}
				}
				sort($static_public_methods);
				while (1)
				{
					for ($i = 0, $ci = count($static_public_methods); $i < $ci; ++$i)
					{
						echo "$i. {$static_public_methods[$i]}\n";
					}
					$method_index = cli::readline('Enter number for method. ');
					$method = @$static_public_methods[$method_index];
					if ($method && method_exists($classname, $method))
					{
						$classname::$method();
						exit;
					}
					else
					{
						echo "\n---\nError: Bad method number\n---\n";
					}
				}
			}
		}
	}
	
	public static function readline($prompt = '')
	{
		if (!empty($prompt)) echo $prompt;
		return trim(fgets(STDIN));
	}
	
	public static function wait_for_data()
	{
		if (util::is_dev()) return;
		$num_markets = 4;
		$yesterday = date(util::DATE, time() - 86400);
		while (1)
		{
			$num_markets_completed = db::select_one("select count(*) from eppctwo.market_data_status where d = '$yesterday' && status = 'Completed'");
			if ($num_markets_completed == $num_markets) break;
			sleep(39);
		}
	}
	
	public static function usage($usage)
	{
		global $argv;
		
		cli::error('Usage: '.$argv[0].' '.$usage);
	}
	
	public static function error($msg, $is_fatal = true)
	{
		echo "$msg\n";
		if ($is_fatal)
		{
			exit(1);
		}
	}
	
	public static function get_args($arg_definitions, $opts = array())
	{
		$vals = array();
		foreach ($arg_definitions as $arg => $params)
		{
			if (array_key_exists($arg, cli::$args))
			{
				$vals[] = cli::$args[$arg];
			}
			else if (is_array($params) && $params['required'])
			{
				cli::usage(($opts['usage'] ? $opts['usage'] : ''));
			}
		}
		return $vals;
	}
	
	public static function email_error($to, $subject, $msg, $is_fatal = false)
	{
		util::mail('errors@wpromote.com', $to, $subject, $msg);
		if ($is_fatal)
		{
			exit(1);
		}
	}
}

class loop
{
	public $batch_count = 64;
	
	public $batch_size, $batch_time, $ci, $start_time, $i, $obj;
	function __construct(&$obj, $opts = array())
	{
		if (is_numeric($opts['batch_count']))
		{
			$this->batch_count = $opts['batch_count'];
		}
		$this->ci = count($obj);
		$this->batch_size = max(floor($this->ci / $this->batch_count), 1);
		$this->start_time = time();
		
		$this->i = 0;
		$this->obj = $obj;
	}
	
	function eol(&$item)
	{
		if ($this->i == $this->ci)
		{
			echo "end: ".date('Y-m-d H:i:s')."\n";
			return true;
		}
		else if ($this->i % $this->batch_size == 0)
		{
			$t = microtime(true);
			$batch_num = count($this->batch_times);
			if ($batch_num)
			{
				$batch_elapsed = $t - $this->batch_times[$batch_num - 1];
				$total_elapsed = $t - $this->batch_times[0];
				$num_left = $this->batch_count - $batch_num;
				
				$seconds_remaining = round(($total_elapsed / ($batch_num + 1)) * $num_left);
				$eta = date('Y-m-d H:i:s', time() + $seconds_remaining);
				
				$hours = str_pad(floor($seconds_remaining / 3600), 2, '0', STR_PAD_LEFT);
				$seconds_remaining = $seconds_remaining % 3600;
				$minutes = str_pad(floor($seconds_remaining / 60), 2, '0', STR_PAD_LEFT);
				$seconds = str_pad($seconds_remaining % 60, 2, '0', STR_PAD_LEFT);
				
				echo "$this->i / $this->ci: $eta, {$hours}:{$minutes}:{$seconds}\n";
			}
			else
			{
				echo "start: ".date('Y-m-d H:i:s')."\n";
			}
			$this->batch_times[] = $t;
		}
		$item = $this->obj[$this->i];
		return false;
	}
	
	function next()
	{
		$this->i++;
	}
}

?>