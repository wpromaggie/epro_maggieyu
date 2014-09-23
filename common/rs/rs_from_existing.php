<?php
require_once(__DIR__.'/rs.php');

class rs_from_existing
{
	private static function get_rs_definition($db, $table, $opts)
	{
		$table_cols = db::select("show columns from {$db}.{$table}");
		
		$col_data = array();
		// get the max length of each field so we can make output pretty
		$col_max_lengths = array(
			'field' => 0,
			'type' => 0,
			'size' => 0,
			'default' => 0
		);
		for ($i = 0, $ci = count($table_cols); list($field, $type, $null, $key, $default, $extra) = $table_cols[$i]; ++$i)
		{
			preg_match("/^\w+/", $type, $matches);
			$col_type = $matches[0];
			
			$rs_extra = null;
			if (preg_match("/\((\d+)\)/", $type, $matches))
			{
				$size = $matches[1];
			}
			else
			{
				$size = 'null';
				if ($col_type == 'enum')
				{
					$r = preg_match("/\(.*?\)/", $type, $matches);
					$rs_extra = "array{$matches[0]}";
				}
			}
			
			$flags = array();
			if (preg_match("/^\w+.*?\sunsigned/", $type))
			{
				$flags[] = 'rs::UNSIGNED';
			}
			if (strcasecmp($null, 'no') === 0)
			{
				$flags[] = 'rs::NOT_NULL';
			}
			if ($extra == 'auto_increment')
			{
				$default = null;
				$flags[] = 'rs::AUTO_INCREMENT';
			}
			$code_default = (is_null($default)) ? "null" : "'".addslashes($default)."'";
			$code_flags = ($flags) ? implode(' | ', $flags) : 0;
			$code_extra = (empty($rs_extra)) ? '' : ", $rs_extra";
			
			$d = array(
				'field' => "'$field'",
				'type' => "'$col_type'",
				'size' => $size,
				'default' => $code_default,
				'flags' => $code_flags,
				'extra' => $code_extra
			);
			foreach ($col_max_lengths as $key => $max_len)
			{
				$len = strlen($d[$key]);
				if ($len > $max_len)
				{
					$col_max_lengths[$key] = $len;
				}
			}
			$col_data[] = $d;
		}
		$rs_cols = '';
		$i = 0;
		foreach ($col_data as $d)
		{
			$pretty_cols = '';
			foreach ($col_max_lengths as $key => $max_len)
			{
				$pretty_cols .= str_pad($d[$key], $max_len, ' ', STR_PAD_RIGHT).',';
			}
			$rs_cols .= "\t\t\tnew rs_col({$pretty_cols}{$d['flags']}{$d['extra']})";
			if (++$i != $ci)
			{
				$rs_cols .= ',';
			}
			$rs_cols .= "\n";
		}
		
		
		$primary_key_var = $uniques_var = $indexes_var = '';
		$indexes_grouped = array();
		$table_indexes = db::select("show index from {$db}.{$table}");
		for ($i = 0; list($itable, $non_unique, $key_name, $seq_in_index, $col_name, $collation, $cardinality, $sub_part, $packed, $null, $index_type, $comment) = $table_indexes[$i]; ++$i)
		{
			if ($key_name == 'PRIMARY')
			{
				$index_type = 'primary_key';
				$primary_key_var = ', $primary_key';
			}
			else
			{
				if ($non_unique)
				{
					$index_type = 'indexes';
					$indexes_var = ', $indexes';
				}
				else
				{
					$index_type = 'uniques';
					$uniques_var = ', $uniques';
				}
			}
			$indexes_grouped[$index_type][$key_name][] = $col_name;
		}
		
		$rs_index_names = '';
		$rs_indexes = '';
		foreach ($indexes_grouped as $index_type => $index_info)
		{
			$rs_indexes .= "\t\tself::\$$index_type = array(";
			if ($index_type == 'primary_key')
			{
				$rs_indexes .= "'".implode("','", $index_info['PRIMARY'])."');\n";
			}
			else
			{
				$rs_indexes .= "\n";
				$i = 0;
				foreach ($index_info as $index_name => $cols)
				{
					if ($i++ > 0)
					{
						$rs_indexes .= ",\n";
					}
					$rs_indexes .= "\t\t\t'{$index_name}' => array('".implode("','", $cols)."')";
				}
				$rs_indexes .= "\n\t\t);\n";
			}
		}
		
		if ($opts[$table]['extends'])
		{
			$class_extends = $opts[$table]['extends'];
			$var_extends = ", \$extends";
			$rs_extends = "\t\tself::\$extends = '{$opts[$table]['extends']}';\n";
		}
		else
		{
			$class_extends = 'rs_object';
			$var_extends = '';
			$rs_extends = '';
		}
		
		$table_desc = "
class {$table} extends {$class_extends}
{
	public static \$db, \$cols{$primary_key_var}{$uniques_var}{$indexes_var}{$var_extends};
	
	public static function set_table_definition()
	{
		self::\$db = '{$db}';
".implode("\n", array_filter(array_map('rtrim', array($rs_indexes, $rs_extends))))."
		self::\$cols = self::init_cols(
".rtrim($rs_cols)."
		);
	}
}
";
		return $table_desc;
	}
	
	private static function set_opts(&$opts)
	{
		rs::set_opt_defaults($opts, array(
			'file_path' => null,
			'do_include_php_tags' => false
		));
		
		// table opts can also be set, at $opts[$table_name][$opt_key]
		// table keys:
		// 1. extends
	}
	
	public static function convert_table($db, $table, $opts = array())
	{
		self::set_opts($opts);
		$code = self::get_rs_definition($db, $table, $opts);
		return self::handle_code($code, $opts);
	}
	
	public static function convert_database($db, $opts = array())
	{
		self::set_opts($opts);
		$tables = db::select("show tables in {$db}");
		$code = '';
		foreach ($tables as $table)
		{
			$code .= self::convert_table($db, $table, $opts);
		}
		return self::handle_code($code, $opts);
	}
	
	private static function handle_code($code, $opts)
	{
		if (!$code)
		{
			return false;
		}
		if ($opts['do_include_php_tags'])
		{
			$code = "<?php\n\n$code\n\n?>";
		}
		if ($opts['file_path'])
		{
			file_put_contents($file_path, $code);
			return true;
		}
		else
		{
			return $code;
		}
	}
}

if (strpos(__FILE__, $argv[0]) !== false)
{
	$args = array();
	for ($i = 1; $i < count($argv); ++$i)
	{
		$arg = $argv[$i];
		$args[$arg[1]] = @substr($arg, 2);
	}
	if (!array_key_exists('d', $args))
	{
		echo "Error: no database\nUsage: {$argv[0]} -ddatabse [-ttable -ffilepath]\n";
		exit(1);
	}
	db::connect(\rs\MYSQL_HOST, \rs\MYSQL_USER, \rs\MYSQL_PASS);
	if (array_key_exists('t', $args))
	{
		$r = rs_from_existing::convert_table($args['d'], $args['t'], $args['f'], true);
	}
	else
	{
		$r = rs_from_existing::convert_database($args['d'], $args['f'], true);
	}
	if (empty($args['f']))
	{
		echo $r;
	}
}

?>