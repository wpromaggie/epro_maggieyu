<?php
require('cli.php');
util::load_lib('git');

// functions can't have dashes
cli::run(str_replace('-', '_', $argv[1]));

class hook_runner
{
	public static function post_merge()
	{
		$hooks = array_merge(glob('hooks/post-merge/all*'), glob('hooks/post-merge/nix*'));
		foreach ($hooks as $hook_path)
		{
			require_once($hook_path);
			
			echo "running hook $hook_path\n";
			$info = pathinfo($hook_path);
			$hook_class = str_replace('-', '_', substr($info['filename'], strpos($info['filename'], '_') + 1));
			$hook_class::run();
		}
	}
}

?>