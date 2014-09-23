<?php

class dbg
{
	public static function init()
	{
		if (array_key_exists('dbg_start', $_REQUEST)) dbg::start();
		else if (array_key_exists('dbg_stop', $_REQUEST)) dbg::stop();
		if (dbg::is_on())
		{
			db::dbg();
		}
	}

	public static function start()
	{
		$_SESSION['dbg_mode'] = 1;
	}

	public static function stop()
	{
		unset($_SESSION['dbg_mode']);
	}

	public static function is_on()
	{
		return (is_array($_SESSION) && array_key_exists('dbg_mode', $_SESSION));
	}
}

?>