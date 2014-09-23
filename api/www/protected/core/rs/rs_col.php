<?php

class rs_col
{
	public $name, $type, $size, $default, $attrs, $extra;
	
	// set by rs_table when static class info in initialized
	public $ancestor_table;
	
	/**
	 * rs_col is designed to mimic the declarative structure of table in an SQL table
	 * The 6 basic structure elements defined are name, type, size, default, attrs and extra
	 * 
	 * @param string $name
	 * @param string $type
	 * @param int $size
	 * @param string $default
	 * @param string $attrs
	 * @param string $extra
	 * 
	 * @return void
	 */
	function __construct($name, $type, $size = null, $default = null, $attrs = null, $extra = null)
	{
		$this->name = $name;
		$this->type = $type;
		$this->size = $size;
		$this->default = $default;
		$this->attrs = $attrs;
		$this->extra = $extra;
	}

	/**	
	 * @param boolean $does_table_extend = false
	 * meh. do we need to | extended attribute? better way so that on puts base class knows to auto inc?
	 */
	public function get_definition($does_table_extend = false)
	{
		$sql_size = (!is_null($this->size)) ? "({$this->size})" : '';
		$sql_unsigned = ($this->attrs & rs::UNSIGNED) ? " unsigned" : '';
		$sql_auto_inc = (($this->attrs & rs::AUTO_INCREMENT) && !$does_table_extend) ? " auto_increment" : '';
		$sql_null = ($this->attrs & rs::NOT_NULL) ? " not null" : " null";
		if (is_null($this->default))
		{
			$sql_default = '';
		}
		else
		{
			if ($this->do_enclose_default())
			{
				$sql_default = " default '{$this->default}'";
			}
			else
			{
				$sql_default = " default ".(($this->default === false) ? 0 : $this->default);
			}
		}
		if ($this->type == 'set')
		{
			$sql_size = "('".implode("','", array_map('addslashes', $this->extra))."')";
		}
		
		if ($this->type == 'enum')
		{
			$sql_type = 'char';
		}
		else
		{
			$sql_type = $this->type;
		}
		return "`{$this->name}` {$sql_type}{$sql_size}{$sql_unsigned}{$sql_null}{$sql_auto_inc}{$sql_default}";
	}
	
	public static function get_sql_type($type)
	{
		switch ($type)
		{
			case ('enum'): return 'char';
			case ('bool'): return 'tinyint';
			default:       return $type;
		}
	}
	
	public function do_enclose_default()
	{
		return (
			$this->type == 'enum' ||
			$this->type == 'set' ||
			strpos($this->type, 'char') !== false ||
			strpos($this->type, 'text') !== false ||
			strpos($this->type, 'blob') !== false ||
			strpos($this->type, 'date') !== false ||
			$this->type == 'time'
		);
	}
	
	public function is_numeric()
	{
		return ($this->is_integer() || $this->is_decimal());
	}
	
	public function is_integer()
	{
		return ($this->type == 'tinyint' || $this->type == 'smallint' || $this->type == 'int' || $this->type == 'bigint');
	}
	
	public function is_decimal()
	{
		return ($this->type == 'double' || $this->type == 'float' || $this->type == 'real');
	}
	
	public function get_form_input($table, $val)
	{
		// check if table or any parent table has a form input function for this column
		list($all_tables) = $table::attrs('extends');
		$all_tables[] = $table;
		$table_def_func = $this->name.'_form_input';
		
		// start at child table
		for ($i = count($all_tables) - 1; $i > -1; --$i)
		{
			$test_table = $all_tables[$i];
			if (method_exists($test_table, $table_def_func))
			{
				return $test_table::$table_def_func($table, $this, $val);
			}
		}
		// call default
		$func = 'get_form_input_'.$this->type;
		return $this->$func($table, $val);
	}
	
	public function get_form_input_char($table, $val) { return $this->get_form_input_varchar($table, $val); }
	public function get_form_input_varchar($table, $val)
	{
		return '<input type="text" name="'.$table.'_'.$this->name.'" maxlength="'.$this->size.'" value="'.htmlentities($val).'" />';
	}
	
	public function get_form_input_text($table, $val)
	{
		return '<textarea name="'.$table.'_'.$this->name.'">'.htmlentities($val).'</textarea>';
	}
	
	public function get_form_input_blob($table, $val)
	{
		return '<input type="text" name="'.$table.'_'.$this->name.'" value="'.htmlentities($val).'" />';
	}
	
	public function get_form_input_tinyint($table, $val)  { return $this->get_form_input_integer($table, $val); }
	public function get_form_input_smallint($table, $val) { return $this->get_form_input_integer($table, $val); }
	public function get_form_input_int($table, $val)      { return $this->get_form_input_integer($table, $val); }
	public function get_form_input_bigint($table, $val)   { return $this->get_form_input_integer($table, $val); }
	public function get_form_input_integer($table, $val)
	{
		return '<input type="text" name="'.$table.'_'.$this->name.'" value="'.htmlentities($val).'" />';
	}
	
	public function get_form_input_double($table, $val)   { return $this->get_form_input_decimal($table, $val); }
	public function get_form_input_float($table, $val)    { return $this->get_form_input_decimal($table, $val); }
	public function get_form_input_real($table, $val)     { return $this->get_form_input_decimal($table, $val); }
	public function get_form_input_decimal($table, $val)
	{
		return '<input type="text" name="'.$table.'_'.$this->name.'" value="'.htmlentities($val).'" />';
	}
	
	public function get_form_input_date($table, $val)
	{
		return '<input type="text" kind="date" class="date_input" name="'.$table.'_'.$this->name.'" value="'.htmlentities($val).'" />';
	}
	
	public function get_form_input_datetime($table, $val)
	{
		return '<input type="text" kind="date" class="datetime_input" name="'.$table.'_'.$this->name.'" value="'.htmlentities($val).'" />';
	}
	
	public function get_form_input_time($table, $val)
	{
		return '<input type="text" class="time_input" name="'.$table.'_'.$this->name.'" value="'.htmlentities($val).'" />';
	}
	
	public function get_form_input_enum($table, $val)
	{
		$options = ($this->extra) ? $this->extra : $table::get_enum_vals($this->name);
		if (!($this->attrs & rs::NOT_NULL)) {
			foreach ($options as $i => $option) {
				$options[$i] = array($option, $option);
			}
			array_unshift($options, array(rs::NUL, ' - Select - '));
		}
		return rs::html_select($table.'_'.$this->name, $options, $val);
	}
	
	public function get_form_input_set($table, $val)
	{
		$options = ($this->extra) ? $this->extra : $table::get_set_vals($this->name);
		$ml = '';
		foreach ($options as $i => $option)
		{
			$key = $table.'_'.$this->name.'_'.rs::simple_text($option);
			$ml .= '
				<input type="checkbox" id="'.$key.'" name="'.$key.'" value="'.$i.'"'.(($val & $i) ? ' checked' : '').' />
				<label for="'.$key.'">'.$option.'</label>
			';
		}
		return $ml;
	}
	
	public function get_form_input_bool($table, $val)
	{
		return '
			<input type="radio" name="'.$table.'_'.$this->name.'" id="'.$this->name.'_1" value="1"'.(($val) ? ' checked' : '').' /> <label for="'.$this->name.'_1">Yes</label> &nbsp;
			<input type="radio" name="'.$table.'_'.$this->name.'" id="'.$this->name.'_0" value="0"'.(($val) ? '' : ' checked').' /> <label for="'.$this->name.'_0">No</label>
		';
	}
	
	public function get_form_input_hidden($table, $val)
	{
		return '<input type="hidden" name="'.$table.'_'.$this->name.'" value="'.htmlentities($val).'" />';
	}
}

?>