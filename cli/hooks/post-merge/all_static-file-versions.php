<?php

class static_file_versions
{
	public static function run()
	{
		chdir(\epro\CLI_PATH.'../');
		$g = new git();
		$files = $g->get_tree_files('HEAD ./cgi');
		for ($i = 0, $s = '', $ci = count($files); $i < $ci; ++$i)
		{
			$f = $files[$i];
			$path = $f['path'];
			
			if (self::is_static_file($path))
			{
				if ($s) $s .= ",\n";
				
				// add first 7 characters of hash as version
				// strip "cgi/" from path
				$s .= "'".substr($path, 4)."'=>'".substr($f['hash'], 0, 7)."'";
			}
		}
		$code_str = "<?php
cgi::\$file_versions = array(
$s
);
?>";
		file_put_contents(\epro\NO_CACHE_FILEPATH, $code_str);
	}
	
	private static function is_static_file($file_path)
	{
		return (preg_match("/(\.js|\.css)$/", $file_path));
	}
}

?>