<?php
require_once(__DIR__.'/rs.php');

class rs_sync
{
	public static $files, $tables;
	
	public static function sync_dirs($dirs, $flags = 0)
	{
		$file_opts = (array_key_exists('files', $dir_options)) ? $dir_options['files'] : null;
		$target_tables = (array_key_exists('tables', $dir_options)) ? $dir_options['tables'] : null;
		
		$tables = array();
		foreach ($opts['dirs'] as $path => $dir_options)
		{
			$dir = @opendir($path);
			if (!$dir)
			{
				continue;
			}
			while (($file = readdir($dir)) !== false)
			{
				$file_path = $path.'/'.$file;
				if (is_array($file_opts) && array_key_exists($file, $file_opts))
				{
					$file_tables = self::sync_get_tables_from_files($file_path, $file_opts);
					$tables = array_merge($tables, $file_tables);
				}
				if (strpos($file, '.rs.') !== false)
				{
					$file_tables = self::sync_get_tables_from_files($file_path, $tables);
					$tables = array_merge($tables, $file_tables);
				}
			}
			closedir($dir);
		}
	}
	
	public static function sync_files($files, $flags = 0)
	{
		if (!is_array($files))
		{
			$files = array($files);
		}
		foreach ($files as $file_path)
		{
			if (!file_exists($file_path))
			{
				continue;
			}
			require_once($file_path);
			preg_match_all("/^class (.*?) extends/m", file_get_contents($file_path), $matches);
			$rs_objects = $matches[1];
			foreach ($rs_objects as $table)
			{
				if (empty(self::$tables) || in_array($table, self::$tables))
				{
					echo "\n\nsync: $table\n\n";
					
					call_user_func(array($table, 'set_table_definition'));
					if (method_exists($table, 'set_table_alterations'))
					{
						call_user_func(array($table, 'set_table_alterations'));
					}
					call_user_func(array($table, 'sync_table_structure'), $flags);
				}
			}
		}
	}
	
	public static function sync_tables($tables, $flags)
	{
		foreach ($tables as $table)
		{
			if (empty(self::$tables) || in_array($table, self::$tables))
			{
				echo "\n\nsync: $table\n\n";
				
				call_user_func(array($table, 'set_table_definition'));
				if (method_exists($table, 'set_table_alterations'))
				{
					call_user_func(array($table, 'set_table_alterations'));
				}
				call_user_func(array($table, 'sync_table_structure'), $flags);
			}
		}
	}
	
	public static function sync($opts, $flags = 0)
	{
		// we need either directory or files
		if (!array_key_exists('files', $opts) && !array_key_exists('dirs', $opts))
		{
			return;
		}
		if (array_key_exists('files', $opts))
		{
			self::$files = $opts['files'];
		}
		else
		{
			self::$files = array();
		}
		if (array_key_exists('dirs', $opts))
		{
			foreach ($opts['dirs'] as $path => $dir_options)
			{
				$dir = @opendir($path);
				if (!$dir)
				{
					continue;
				}
				while (($file = readdir($dir)) !== false)
				{
					if (strpos($file, '.rs.') !== false)
					{
						self::$files[] = $path.'/'.$file;
					}
				}
				closedir($dir);
			}
		}
		if (empty(self::$files))
		{
			return;
		}
		
		if (array_key_exists('tables', $opts))
		{
			self::$tables = $opts['tables'];
		}
		print_r(self::$files);
		print_r(self::$tables);
		SS_Sync::sync_files(self::$files, $flags);
	}
	
	public static function prepare_rs_contents_for_compare($rev_number, $rev_contents)
	{
		// get rid of php open/close tags so we can eval() it
		$rev_contents = str_replace(array('<?php', '?>'), '', $rev_contents);
		
		// append revision to class names so they do not conflict with other files
		if ($rev_number)
		{
			$rev_contents = preg_replace("/^class (.*?) extends/m", "class \${1}_{$rev_number} extends", $rev_contents);
		}
		return $rev_contents;
	}
	
	// set rev contents and return tables
	public static function prep_file_for_compare($rev_number, &$rev_contents)
	{
		$rev_contents = self::prepare_rs_contents_for_compare($rev_number, $rev_contents);
		
		$tables = self::get_file_tables($rev_contents);
		
		// only eval if classes in content don't exist
		if ($tables && !class_exists($tables[0]))
		{
			eval($rev_contents);
		}
		return $tables;
	}
	
	// compare an earlier version of a rs file with a later version
	public static function compare_rs_files($before_rev_number, $before_rev_contents, $after_rev_number, $after_rev_contents)
	{
		$before_tables = self::prep_file_for_compare($before_rev_number, $before_rev_contents);
		$after_tables = self::prep_file_for_compare($after_rev_number, $after_rev_contents);
		
		$deleted_tables = array();
		$undeleted_tables = array();
		$altered_tables = array();
		$unaltered_tables = array();
		
		foreach ($before_tables as $before_table)
		{
			$after_table = $before_table;
			if ($before_rev_number)
			{
				$after_table = str_replace('_'.$before_rev_number, '', $after_table);
			}
			$base_table = $after_table;
			if ($after_rev_number)
			{
				$after_table .= '_'.$after_rev_number;
			}
			if (!in_array($after_table, $after_tables))
			{
				$deleted_tables[] = $base_table;
			}
			else
			{
				$undeleted_tables[] = $after_table;
				$alterations = self::compare_rs_object($before_table, $after_table);
				if ($alterations)
				{
					$altered_tables[] = $base_table;
				}
				else
				{
					$unaltered_tables[] = $base_table;
				}
			}
		}
		$new_tables = array_diff($after_tables, $undeleted_tables);
		
		echo "\n---\n$before_rev_number -> $after_rev_number\n---\n";
		echo "deleted: ".implode(', ', $deleted_tables)."\n";
		echo "undeleted: ".implode(', ', $undeleted_tables)."\n";
		echo "altered: ".implode(', ', $altered_tables)."\n";
		echo "unaltered: ".implode(', ', $unaltered_tables)."\n";
		echo "new: ".implode(', ', $new_tables)."\n";
	}
	
	public static function get_file_tables($file_contents)
	{
		preg_match_all("/^(?:abstract |)class (.*?) extends/m", $file_contents, $matches);
		$rs_objects = $matches[1];
		return (($rs_objects) ? $rs_objects : array());
	}
	
	public static function compare_rs_object($before, $after)
	{
		$before::load_table_definition();
		$after::load_table_definition();
		
		// get rid of columns that are in ancestor tables
		// before is loaded dynamically from existing mysql table
		// so only need to do this for after
		foreach ($after::$cols as $col_name => $col_info)
		{
			if ($col_info->ancestor_table)
			{
				unset($after::$cols[$col_name]);
			}
		}
		
		$deleted_cols = array();
		$undeleted_cols = array();
		$altered_cols = array();
		$unaltered_cols = array();
		
		foreach ($before::$cols as $col_name => $col_info)
		{
			if (!array_key_exists($col_name, $after::$cols))
			{
				$deleted_cols[$col_name] = 1;
			}
			else
			{
				$undeleted_cols[] = $col_name;
				$alterations = self::compare_rs_col($col_info, $after::$cols[$col_name]);
				if ($alterations)
				{
					
					// check if col is primary key "inherited" from ancestor table??
					
					$altered_cols[$col_name] = $alterations;
				}
				else
				{
					$unaltered_cols[] = $col_name;
				}
			}
		}
		$new_cols = array_flip(array_values(array_diff(array_keys($after::$cols), $undeleted_cols)));
		
		$index_changes = array();
		$index_types = array('primary_key', 'uniques', 'indexes');
		foreach ($index_types as $index_type)
		{
			list($before_indexes) = $before::attrs($index_type);
			list($after_indexes) = $after::attrs($index_type);
			if ($before_indexes || $after_indexes)
			{
				if ($before_indexes && $after_indexes)
				{
					$index_changes[$index_type] = self::compare_indexes($index_type, $before_indexes, $after_indexes);
				}
				else if (!$before_indexes)
				{
					$index_changes[$index_type]['new'] = $after_indexes;
				}
				else
				{
					$index_changes[$index_type]['deleted'] = $before_indexes;
				}
			}
		}
		
		return array(
			'cols' => array('new' => $new_cols, 'deleted' => $deleted_cols, 'altered' => $altered_cols),
			'indexes' => $index_changes
		);
	}
	
	public static function compare_indexes($type, $before, $after)
	{
		// uniques and indexes are arrays of arrays, primary key is single level, convert
		if ($type == 'primary_key')
		{
			$before = array($before);
			$after = array($after);
		}
		
		// not looking for altered indexes anymore, just delete and re-create
		$altered = array();
		$deleted = array();
		foreach ($before as $before_name => $before_def)
		{
			$is_before_in_after = false;
			foreach ($after as $after_name => $after_def)
			{
				if (count($before_def) == count($after_def) && !(array_diff($before_def, $after_def)))
				{
					$is_before_in_after = true;
					unset($after[$after_name]);
					break;
				}
			}
			if (!$is_before_in_after)
			{
				$deleted[$before_name] = $before_def;
			}
		}
		
		// everything left in after is new
		return array(
			'altered' => $altered,
			'deleted' => $deleted,
			'new' => $after
		);
	}
	
	public static function rs_index_type_to_mysql_index_type($type)
	{
		switch ($type)
		{
			case ('primary_key'): return 'primary key';
			case ('uniques'):     return 'index';
			case ('indexes'):     return 'index';
		}
	}
	
	public static function compare_rs_col($before, $after)
	{
		$alterations = array();
		foreach ($before as $key => $before_val)
		{
			$after_val = $after->$key;
			if ($key == 'type')
			{
				$after_val = rs_col::get_sql_type($after_val);
			}
			if ($before_val != $after_val)
			{
				$alterations[$key] = array('before' => $before_val, 'after' => $after_val);
			}
		}
		return $alterations;
	}
}

if (strpos($argv[0], 'rs_sync.php') !== false)
{
	$args = array();
	for ($i = 1; $i < count($argv); ++$i)
	{
		$arg = $argv[$i];
		$args[$arg[1]] = @substr($arg, 2);
	}
	if (!array_key_exists('d', $args) && !array_key_exists('f', $args))
	{
		echo "Error: directory or file path needed\nUsage: {$argv[0]} (-ddir_path | -ffilepath)\n";
		exit(1);
	}
	db::connect(\rs\MYSQL_HOST, \rs\MYSQL_USER, \rs\MYSQL_PASS);
	db::dbg();
	if (array_key_exists('d', $args))
	{
		rs_sync::sync_dirs($args['d']);
	}
	else
	{
		rs_sync::sync_files($args['f'], rs::FORCE_CREATE);
	}
}

?>