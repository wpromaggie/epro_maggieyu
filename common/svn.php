<?php

class svn
{
	private static $dbg = false;
	private static $user = null;
	private static $pass = null;
	
	public static function dbg()
	{
		self::$dbg = true;
	}
	
	private static function dbg_check($cmd)
	{
		if (self::$dbg)
		{
			$endl = ((@stripos($_SERVER['GATEWAY_INTERFACE'], 'cgi') !== false) ? '<br>' : '')."\n";
			$cmd = preg_replace("/--password (\w+)/", '--password ********', $cmd);
			echo $cmd.$endl;
		}
	}
	
	// takes one or more keys and returns the values from a call to svn info
	// can be used in conjuction with list, eg list($url, $schedule) = svn_lib::get_info('URL', 'Schedule');
	/*
		Path: .
		URL: svn://cynta.wpromote.com/repos/e2_common
		Repository Root: svn://cynta.wpromote.com/repos/e2_common
		Repository UUID: 413dff72-96f5-463d-8d09-b58a5a2c48e3
		Revision: 169
		Node Kind: directory
		Schedule: normal
		Last Changed Author: kmoney
		Last Changed Rev: 169
		Last Changed Date: 2010-10-13 14:59:47 -0700 (Wed, 13 Oct 2010)
		Conflict Properties File: dir_conflicts.prej
	 */
	public static function get_info($path = '')
	{
		$keys = array_slice(func_get_args(), 1);
		$return = array();
		
		$cmd = 'svn'.self::get_user().' info'.(($path) ? ' '.$path : '');
		self::exec($cmd, $output);
		foreach ($keys as $key)
		{
			foreach ($output as $line)
			{
				$line = trim($line);
				if (preg_match("/^$key: (.*)$/", $line, $matches))
				{
					$return[] = $matches[1];
					break;
				}
			}
		}
		return $return;
	}
	
	public static function ls($path = '.', $opts = '')
	{
		$cmd = 'svn'.self::get_user().(($opts) ? ' '.$opts : '').' ls '.$path;
		self::exec($cmd, $output);
		return $output;
	}
	
	public static function st($path = '.', $opts = '')
	{
		$cmd = 'svn'.self::get_user().(($opts) ? ' '.$opts : '').' st '.$path;
		self::exec($cmd, $output);
		return $output;
	}
	
	public static function get_revision()
	{
		list($revision) = svn_lib::get_info('Revision');
		return $revision;
	}
	
	public static function get_revisions($revision)
	{
		$cmd = 'svn'.self::get_user().' -r '.$revision.' -v log';
		self::exec($cmd, $output);
		
		$files = array();
		foreach ($output as $line)
		{
			$line = trim($line);
			
			// get author
			if (preg_match("/^r\d+ \| (.*?) \|/", $line, $matches))
			{
				$author = $matches[1];
			}
			
			// Added, Modified, Deleted
			else if (preg_match("/(A|M|D)\s+(.*)$/", $line, $matches))
			{
				list($ph, $action, $path) = $matches;
				
				// check for rename
				if (preg_match("/^(.*)\s+\(from\s+(.*):\d+\).*$/", $path, $matches))
				{
					$path = $matches[1];
					
					// file name before rename
					$extra = array(
						'rename' => $matches[2]
					);
				}
				else
				{
					$extra = array();
				}
				
				$files[] = array(
					'author' => $author,
					'path' => $path,
					'action' => $action,
					'extra' => $extra
				);
			}
		}
		return $files;
	}
	
	// can take a url or a file path
	public static function get_file_contents($file, $revision = null)
	{
		if (is_numeric($revision))
		{
			$revision = ' -r '.$revision;
		}
		else
		{
			$revision = '';
		}
		
		$cmd = 'svn'.self::get_user().''.$revision.' cat '.$file;
		self::exec($cmd, $output);
		return implode("\n", $output);
	}
	
	// can take a url or a file path
	public static function get_log($file, $revision = null)
	{
		// not a url, trim slashes
		if (!preg_match("/^\w+:\/\//", $file))
		{
			$file = trim($file, '/');
		}
		if ($revision)
		{
			$file .= '@'.$revision;
		}
		$cmd = 'svn'.self::get_user().' log '.$file;
		self::exec($cmd, $output);
		print_r($output);
		$log = array();
		foreach ($output as $line)
		{
			$line = trim($line);
			
			// get author
			if (preg_match("/r(\d+) \| (.*?) \|/", $line, $matches))
			{
				$log[] = array(
					'revision' => $matches[1],
					'author' => $matches[2]
				);
			}
		}
		return $log;
	}
	
	public function get_previous_revision($repo_root, $file_path, $target_revision)
	{
		$file_log = svn_lib::get_log($file_path);
		
		// log starts from most recent, look for our target revision
		// if we miss it but find another revision before that, use this
		for ($i = 0, $ci = count($file_log); $i < $ci; ++$i)
		{
			$revision_info = $file_log[$i];
			$rev_number = $revision_info['revision'];
			$prev_revision_info = null;
			echo $i." -- ".print_r($revision_info, true)."<br>\n";
			if ($rev_number < $target_revision)
			{
				$prev_revision_info = $revision_info;
			}
			// if we find it, get the one before that
			else if ($rev_number == $target_revision)
			{
				// unless there are no previous commits of the file, first time this ss file has been synced
				if ($i == ($ci - 1))
				{
					return null;
				}
				else
				{
					$prev_revision_info = $file_log[$i + 1];
				}
			}
			if ($prev_revision_info)
			{
				$prev_revision_number = $prev_revision_info['revision'];
				$prev_revision_contents = svn_lib::get_file_contents($file_path, $prev_revision_number);
				#$prev_revision_contents = svn_lib::get_file_contents($repo_root.$file_path, $prev_revision_number);
				
				return array(
					'number' => $prev_revision_number,
					'contents' => $prev_revision_contents
				);
			}
		}
		return null;
	}
	
	private static function get_user()
	{
		return ((self::$user) ? (' --username '.self::$user.' --password '.self::$pass) : '');
	}
	
	public static function set_user($user, $pass)
	{
		self::$user = $user;
		self::$pass = $pass;
	}
	
	public static function clear_user()
	{
		self::$user = self::$pass = null;
	}
	
	private function exec($cmd, &$output)
	{
		self::dbg_check($cmd);
		exec($cmd, $output);
	}
}

?>