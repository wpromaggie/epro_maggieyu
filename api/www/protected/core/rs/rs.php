<?php

/*
 * rdb inheritance: Generalization Specialization Relational Modeling
 *  some interesting thoughts: http://stackoverflow.com/questions/4361381/how-do-we-implement-an-is-a-relationship
 */

require('env.config.php');
require_once(__DIR__.'/rs_table.php');
require_once(__DIR__.'/rs_record.php');
require_once(__DIR__.'/rs_object.php');
require_once(__DIR__.'/rs_col.php');
require_once(__DIR__.'/rs_array.php');
require_once(__DIR__.'/db/'.\rs\DB_TYPE.'/'.\rs\DB_TYPE.'.php');

/*
 * constants
 */


class rs
{
	const DEFAULT_ENGINE = 'innodb';
	
	// class user extends to create an rs object
	const BASE_CLASSNAME = 'rs_object';
	
	// column options
	const NO_ATTRS       = 0x00;
	const UNSIGNED       = 0x01;
	const AUTO_INCREMENT = 0x02;
	const BINARY         = 0x04;
	const NOT_NULL       = 0x08;
	const READ_ONLY      = 0x10; // for rs, does not impact mysql
	const SECRET         = 0x20; // for rs, does not impact mysql
	
	// alter types (do we use these anymore?)
	const COL_SET      = 0x01;
	const COL_DROP     = 0x02;
	const TABLE_RENAME = 0x04;
	
	// sync options (do we use these anymore?)
	const FORCE_CREATE = 0x01;
	const FORCE_UPDATE = 0x02;
	
	// find options
	const FIND_ANY      = 0x01;
	const FIND_KEY_ONLY = 0x02;
	
	// defaults
	const DDT = '0000-00-00 00:00:00';
	const DD  = '0000-00-00';
	const DT  = '00:00:00';
	
	const NUL = 'NULL';
	
	public static function set_opt_defaults(&$opts, $defaults)
	{
		if (!$opts)
		{
			$opts = array();
		}
		foreach ($defaults as $k => $v)
		{
			if (!array_key_exists($k, $opts))
			{
				$opts[$k] = $v;
			}
		}
	}
	
	public static function dbg()
	{
		db::dbg();
	}
		
	public static function dbg_off()
	{
		db::dbg_off();
	}
	
	public static function read_only()
	{
		db::dbg_off();
		db::read_only();
	}
	
	public static function display_text($text)
	{
		return ucwords(str_replace('_', ' ', $text));
	}
	
	public static function simple_text($text)
	{
		$simple = preg_replace("/[^\w -]/", '', $text);
		$simple = str_replace(array(' ', '-'), '_', $simple);
		return strtolower($simple);
	}
	
	public function html_select($name, $options, $selected = null, $attrs = null)
	{
		if (!is_array($options[0]))
		{
			$tmp_options = array();
			foreach ($options as $option)
			{
				$tmp_options[] = array($option, $option);
			}
			$options = $tmp_options;
		}
		$ml_options = '';
		$found_selected = false;
		for ($i = 0; list($value, $text) = $options[$i]; ++$i)
		{
			if (empty($text))
			{
				$text = $value;
			}
			if ($value == $selected && !$found_selected)
			{
				$ml_selected = ' selected';
				$found_selected = true;
			}
			else
			{
				$ml_selected = '';
			}
			$ml_options .= '<option value="'.$value.'"'.$ml_selected.'>'.$text."</option>\n";
		}

		$ml_attrs = '';
		if (is_array($attrs))
		{
			foreach ($attrs as $k => $v)
			{
				$ml_attrs .= ' '.$k.'="'.htmlentities($v).'"';
			}
		}

		return '
			<select id="'.$name.'" name="'.$name.'"'.$ml_attrs.'>
				'.$ml_options.'
			</select>
		';
	}
	
	public static function is_rs_object($x)
	{
		return (self::is_rs_record($x) || self::is_rs_array($x));
	}
	
	public static function is_rs_record($x)
	{
		if (!is_object($x)) {
			return false;
		}
		return self::is_rs_record_ancestor(get_parent_class($x));
	}
	
	private static function is_rs_record_ancestor($ancestor, $depth = 0)
	{
		if ($ancestor == 'rs_record') {
			return true;
		}
		else if (empty($ancestor)) {
			return false;
		}
		else {
			return self::is_rs_record_ancestor(get_parent_class($ancestor));
		}
	}
	
	public static function is_rs_array($x)
	{
		return (is_object($x) && strtolower(get_class($x)) == 'rs_array');
	}
	
	// converts rs objects to arrays
	// also loops over any interable values, as they may contain rs objects
	public static function to_array($src, $max_depth = false, $depth = 1)
	{
		$dst = array();
		foreach ($src as $k => $v) {
			if (
				($max_depth === false || $depth < $max_depth) &&
				(rs::is_rs_object($v) || is_array($v) || $v instanceof Traversable)
			) {
				$dst[$k] = self::to_array($v, $max_depth, $depth + 1);
			}
			else {
				$dst[$k] = $v;
			}
		}
		return $dst;

	}

	public static function get_error()
	{
		return db::last_error();
	}
}

?>