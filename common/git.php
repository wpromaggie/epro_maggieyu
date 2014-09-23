<?php

class git
{
	private $verbose = false;
	
	public function __construct($opts = array())
	{
		util::set_opt_defaults($opts, array(
			'path' => false,
			'verbose' => false
		));
		if ($opts['path'])
		{
			chdir($opts['path']);
		}
		$this->verbose = $opts['verbose'];
	}
	
	public function exec($cmd, &$output = null, &$r = null)
	{
		if ($this->verbose)
		{
			echo "$cmd\n";
		}
		exec($cmd, $output, $r);
		if ($this->verbose)
		{
			echo implode("\n", $output)."\n\n";
		}
	}
	
	public function get_tree_files($path)
	{
		$output = array();
		// server version of git does not support --full-tree
		#exec('git ls-tree --full-tree -r '.$path, $output);
		
		// use --full-name with relative path instead
		$this->exec('git ls-tree --full-name -r '.$path, $output, $r);
		if ($r != 0)
		{
			return false;
		}
		
		$files = array();
		for ($i = 0, $ci = count($output); $i < $ci; ++$i)
		{
			if (preg_match("/^(\w+)\s+(\w+)\s+(\w+)\s+(.*)$/", $output[$i], $matches))
			{
				list($ph, $mode, $type, $hash, $path) = $matches;
				$files[] = array(
					'mode' => $mode,
					'type' => $type,
					'hash' => $hash,
					'path' => $path
				);
			}
		}
		return $files;
	}
	
	public function branch_current()
	{
		$this->exec('git symbolic-ref HEAD 2>/dev/null', $output, $r);
		if ($output)
		{
			return trim(preg_replace("/^refs\/heads\//", '', $output[0]));
		}
		else
		{
			return false;
		}
	}
	
	public function branch_list()
	{
		$this->exec('git branch -l', $output, $r);
		if ($output)
		{
			return array_map(create_function('$x', 'return trim(substr($x, 2));'), $output);
		}
		else
		{
			return false;
		}
	}
	
	public function status()
	{
		$this->exec('git status --porcelain', $output, $r);
		if ($r != 0)
		{
			return false;
		}
		$status = array();
		foreach ($output as $line)
		{
			if (preg_match("/^(.)(.) (.*)$/", $line, $matches))
			{
				list($ph, $s0, $s1, $file_path) = $matches;
				if ($s0 == '?' && $s1 == '?')
				{
					$status['untracked'][] = $file_path;
				}
				else if ($s0 == 'M' || $s1 == 'M')
				{
					$status['modified'][] = $file_path;
				}
			}
		}
		return $status;
	}
}

?>