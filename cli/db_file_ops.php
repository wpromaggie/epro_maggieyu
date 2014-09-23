<?php
require_once('cli.php');

cli::run();

class db_file_ops
{
	/*
	 * import a table from a file
	 */
	public static function import($db = null, $table = null, $file_path = null)
	{
		if (!self::set_params($call_source, $db, $table, $file_path))
		{
			return false;
		}
		
		// import table from file
		exec('mysql -u'.\epro\DB_USER.' -p'.\epro\DB_PASS.' '.$db.' < '.$file_path, $output, $result);
		return self::done($call_source, $result);
	}
	
	/*
	 * export a table to a file
	 */
	public static function export($db = null, $table = null, $file_path = null, $where = null, $no_create = null)
	{
		if (!self::set_params($call_source, $db, $table, $file_path, $where, $no_create))
		{
			return false;
		}
		// check that table exists
		if (!db::table_exists("{$db}.{$table}"))
		{
			if ($call_source == 'cli')
			{
				cli::error("Table '{$db}.{$table}' could not be accessed\n");
			}
			else
			{
				return false;
			}
		}
		
		// just data, no drop/create table
		$dump_opts = '';
		if ($no_create)
		{
			$dump_opts .= ' --skip-opt --no-create-info --add-locks --disable-keys --extended-insert --lock-tables --quick';
		}
		// where
		if ($where)
		{
			$dump_opts .= ' -w"'.$where.'"';
		}
		
		// dump table to file
		cli::exec_verbose('mysqldump -u'.\epro\DB_USER.' -p'.\epro\DB_PASS.$dump_opts.' '.$db.' '.$table.' > '.$file_path, $output, $result);
		return self::done($call_source, $result);
	}
	
	/*
	 * sets params and does error checking/reporting
	 */
	private static function set_params(&$call_source, &$db, &$table, &$file_path, &$where = null, &$no_create = null)
	{
		global $argv;
		
		if ($db && $table)
		{
			$call_source = 'function';
		}
		else
		{
			$call_source = 'cli';
			// flag style
			if (array_key_exists('d', cli::$args) && array_key_exists('t', cli::$args))
			{
				list($db, $table, $path, $where, $no_create) = util::list_assoc(cli::$args, 'd', 't', 'p', 'w', 'n');
			}
			// list style
			else
			{
				list($ph1, $ph2, $db, $table, $path, $where, $no_create) = $argv;
			}
		}
		
		// check that we have db and table
		if (!$db || !$table)
		{
			if ($call_source == 'cli')
			{
				cli::usage("(import|export) -d database -t table [-p path][-w where][-n]\n");
			}
			else
			{
				return false;
			}
		}
		// set file path
		$file_path = (($path) ? $path : \epro\CLI_PATH.'tmp/').$db.'.'.$table.'.mysql';
		if (!is_dir(dirname($file_path)))
		{
			if ($call_source == 'cli')
			{
				cli::error("Filepath '$file_path' invalid\n");
			}
			else
			{
				return false;
			}
		}
		
		return true;
	}
	
	private static function done($call_source, $result)
	{
		if ($call_source == 'cli')
		{
			echo "Done ($result)\n";
			exit($result);
		}
		else
		{
			return (!$result);
		}
	}
}

?>