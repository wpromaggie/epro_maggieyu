<?php
/**
* rs_table is an abstract class that defines the base relationship between the table object model and the implementation
* uses the var term prop which stands for property 
* @todo verify that prop == property
*/
abstract class rs_table
{
	/**
	 * function user defines to set table definition
	 */
	abstract public static function set_table_definition();
	
	/**
	 * implementation sets properties
	 * user sets attributes
	 * attrs() will also return properties
	 * careful when calling prop functions as they do not check for initialization
	 * use attr functions to be safe
	 */
	public static $prop_list = array(
		'class',
		'table',
		'parent',
		'extends'
	);
	
	public static $attr_list = array(
		'db'          => null,    // req, string
		'cols'        => null,    // req, see example
		'primary_key' => array(), // opt, array of the names of the columns representing the primary key
		'uniques'     => array(), // opt, array of arrays of the names of the columns representing each unique constraint
		'indexes'     => array(), // opt, array of arrays of the names of the columns representing each index
		'oto'         => array(), // opt, one-to-one
		'otm'         => array(), // opt, one-to-many
		'ofo'         => array(), // opt, one-from-one
		'ofm'         => array(), // opt, one-from-many
		'mtm'         => array(), // opt, many-to-many
	);
	
	/** keep track of the props */
	public static $props = array();
	
	/**
	*	Loads a table definition if not already loaded
	*	public: user probably shouldn't be calling this but ok for other rs stuff (eg sync)	  
	* 	@return void
	*/ 
	public static function load_table_definition()
	{
		// check class, if not set, this table definition has not been loaded
		$class = self::get_prop('class');
		if (!$class)
		{
			// extends contains entire inheritance chain up to rs base, so initialize as array
			$class = self::set_prop('class', get_called_class());
			self::set_prop('table', self::get_table_from_class_name($class)); // alias of class
			$parent = self::set_prop('parent', get_parent_class($class));
			$extends = self::set_prop('extends', ($parent == rs::BASE_CLASSNAME) ? false : array($parent));
			
			// it is possible for a class in the inheritance chain to not
			//  represent a database table, check this property
			if (self::is_rs_table($class))
			{
				$class::set_table_definition();
			}
			if ($extends)
			{
				// ancestors of parent, does not include parent
				$ancestors = self::load_parent_definition($class);
				self::inherit_from_parent($class, $ancestors);
			}
		}
	}
	
	/*
	 * can use $classname::(has|get|set)_prop to call on any class
	 */	
	private static function has_prop($k, $class = false)
	{
		if (!$class) $class = get_called_class();
		return (array_key_exists($class, self::$props) && array_key_exists($k, self::$props[$class]));
	}
	
	/**
	 *	get_prop get property
	 * 	@param string $k key possible values [class, extends, table]
	 *
	 *	@return mixed property value or false is the property does not exist
	 */
	private static function get_prop($k)
	{
		$class = get_called_class();
		return (self::has_prop($k, $class)) ? self::$props[$class][$k] : false;
	}
	
	/**
	 * also return the prop we set
	 * @param string $k key
	 * @param string $v value
	 *
	 * @return string $v value that was set 
	 */
	private static function set_prop($k, $v)
	{
		$class = get_called_class();
		if (!array_key_exists($class, self::$props)) {
			self::$props[$class] = array();
		}
		self::$props[$class][$k] = $v;
		return $v;
	}

	/**
	 * Check if the object is an rs_table by checking if the property cols exists.
	 * @param string $class name of class
	 * 
	 * @return boolean
	 */	
	protected static function is_rs_table($class)
	{
		return property_exists($class, 'cols');
	}
	
	protected static function load_parent_definition($class)
	{
		// we flatten class hierarchy, add any non-immediate 
		$ancestors = array();
		$extends = self::get_prop('extends');
		foreach ($extends as $ancestor_class) {
			$ancestor_class::load_table_definition();
			
			if ($ancestor_class::get_prop('extends')) {
				$ancestors = array_merge($ancestor_class::get_prop('extends'), $ancestors);
			}
		}
		return $ancestors;
	}

	public static function get_base()
	{
		$extends = self::get_prop('extends');
		if ($extends === false) {
			return get_called_class();
		}
		// extends is ordered so that older ancestors come first, so first elem is base
		else {
			return $extends[0];
		}
	}
	
	public static function attrs()
	{
		self::load_table_definition();
		$class = self::get_prop('class');
		$args = func_get_args();
		
		$data = array();
		foreach ($args as $key)
		{
			$data[] = self::attr($class, $key);
		}
		return $data;
	}
	
	// only for internal use, does not check that load_definition has been called
	// clients must use attrs
	protected static function attr($class, $key)
	{
		if (in_array($key, self::$prop_list)) {
			return $class::get_prop($key);
		}
		else
		{
			// that just happened
			return (property_exists($class, $key) ? $class::$$key : null);
		}
	}
	
	protected static function inherit_from_parent($class, $ancestors)
	{
		$extends = self::get_prop('extends');
		$extends_rs_tables = array();
		$new_cols = array();
		foreach ($extends as $ancestor_class)
		{
			if (self::is_rs_table($ancestor_class))
			{
				$extends_rs_tables[] = $ancestor_class;
				foreach ($ancestor_class::$cols as $col_name => $col)
				{
					if (!array_key_exists($col_name, $class::$cols))
					{
						$child_col = clone $col;
						$child_col->ancestor_table = ($child_col->ancestor_table) ? $child_col->ancestor_table : $ancestor_class;
						$new_cols[$col_name] = $child_col;
					}
					// combine attrs if col defined in multiple places
					// eg, id in ancestor class defined as auto increment
					else
					{
						$class::$cols[$col_name]->attrs |= $col->attrs;
					}
				}
			}
		}
		// we want parent cols to come before child cols by default
		if ($new_cols)
		{
			$class::$cols = array_merge($new_cols, $class::$cols);
		}
		// we originally assume ancestors are rs tables, if not, remove from extends here
		$non_rs_tables = array_diff($extends, $extends_rs_tables);
		if ($non_rs_tables)
		{
			$class::set_prop('extends', ($extends_rs_tables) ? false : $extends_rs_tables);
		}
		if ($ancestors && $extends_rs_tables)
		{
			// older ancestors first
			$class::set_prop('extends', array_merge($ancestors, $extends_rs_tables));
		}
	}
	
	/*
	 * called by set_table_definition when defining an rs_object
	 * 
	 * self::$cols = self::init_cols(
	 *  new rs_col(...),
	 *  ...
	 * );
	 */
	protected static function init_cols()
	{
		$args = func_get_args();
		$cols = array();
		foreach ($args as $col)
		{
			$cols[$col->name] = $col;
		}
		return $cols;
	}
	
	public static function sync_table_structure($flags = 0)
	{
		list($db, $table) = self::attrs('db', 'table');
		
		if ($flags & rs::FORCE_CREATE)
		{
			db::exec("drop table {$db}.{$table}");
		}
		
		if (!db::table_exists($db.'.'.$table) && !self::is_table_renamed($db, $table, $flags))
		{
			self::create_table($db, $table);
		}
		else
		{
			self::update_table($db, $table, $flags);
		}
	}
	
	public static function db_exists()
	{
		list($db) = self::attrs('db');
		return (db::db_exists(self::$db));
	}
	
	public static function table_exists()
	{
		list($db, $table) = self::attrs('db', 'table');
		return (db::table_exists($db.'.'.$table));
	}
	
	private static function is_table_renamed($db, $table, $flags)
	{
		$cur_alter_count = self::set_unmade_alterations($alters, $db, $table, $flags);
		
		// no new alterations, nothing to do
		if ($cur_alter_count === false)
		{
			return false;
		}
		
		for ($i = 0, $ci = count($alters); $i < $ci; ++$i)
		{
			$i_alters = $alters[$i];
			foreach ($i_alters as $alter)
			{
				if ($alter->type == rs::TABLE_RENAME)
				{
					return true;
				}
			}
		}
		return false;
	}
	
	public static function create_table($db = false, $table = false, $opts = array())
	{
		rs::set_opt_defaults($opts, array(
			'create' => true,
			'get_sql' => false
		));

		list($tmp_db, $tmp_table, $cols, $primary_key, $uniques, $indexes, $engine) = self::attrs('db', 'table', 'cols', 'primary_key', 'uniques', 'indexes', 'engine');
		if ($db === false) {
			$db = $tmp_db;
		}
		if ($table === false) {
			$table = $tmp_table;
		}
		
		$sql_cols = array();
		foreach ($cols as $col_name => $col) {
			if (!$col->ancestor_table) {
				$sql_cols[] = $col->get_definition(property_exists($table, 'extends'));
			}
		}
		
		if ($primary_key) {
			$sql_cols[] = "primary key (`".implode("`,`", $primary_key)."`)";
		}
		if ($uniques) {
			foreach ($uniques as $i => $unique_cols) {
				$sql_cols[] = "unique (`".implode("`,`", $unique_cols)."`)";
			}
		}
		if ($indexes) {
			foreach ($indexes as $i => $index_cols) {
				$sql_cols[] = "index (`".implode("`,`", $index_cols)."`)";
			}
		}
		if (empty($engine)) {
			$engine = rs::DEFAULT_ENGINE;
		}
		
		$q = "
			create table {$db}.{$table} (
				".implode(",\n", $sql_cols)."
			) engine={$engine}
		";
		if ($opts['create']) {
			$r = db::exec($q);
		}
		if ($opts['get_sql']) {
			return $q;
		}
		else {
			return $r;
		}
	}
	
	public static function truncate()
	{
		list($db, $table) = self::attrs('db', 'table');
		db::exec("truncate table {$db}.{$table}");
	}
	
	public static function count($opts = array())
	{
		list($db, $table) = self::attrs('db', 'table');
		return db::select_one("
			select count(*)
			from {$db}.{$table}
			".(empty($opts["where"]) ? "" : " where {$opts['where']}")."
		", (empty($opts['data']) ? array() : $opts['data']));
	}
	
	public static function get_cols($do_include_inhereted_cols = true)
	{
		list($cols) = self::attrs('cols');
		if ($do_include_inhereted_cols) {
			return array_keys($cols);
		}
		else {
			$self_cols = array();
			foreach ($cols as $col_name => $col_info) {
				if (empty($col_info->ancestor_table)) {
					$self_cols[] = $col_name;
				}
			}
			return $self_cols;
		}
	}
	
	// get empty rs array
	public static function new_array($opts = array())
	{
		list($class) = self::attrs('class');
		return new rs_array($class, $opts);
	}
	
	/*
	 * returns an rs_array of rs_objects
	 */
	public static function get_all($opts = array())
	{
		list($class) = self::attrs('class');
		$a = new rs_array($class);
		$r = $a->get($opts);
		// if we are getting for an object, return result of get, which will be count
		// otherwise return the array
		return (isset($opts['obj'])) ? $r : $a;
	}
	
	// calls insert, returns new object on success, false on failure
	public static function create($data, $opts = array())
	{
		$class = get_called_class();
		$obj = new $class($data, array('do_get' => false));
		$r = $obj->insert($opts);
		
		return (($r) ? $obj : false);
	}

	// calls put, returns object on success, false on failure
	public static function save($data, $opts = array())
	{
		$class = get_called_class();
		$obj = new $class($data, array('do_get' => false));
		$r = $obj->put($opts);
		
		return (($r) ? $obj : false);
	}

	public static function delete_all($opts)
	{
		list($db, $table) = self::attrs('db', 'table');
		
		// if user wants to delete everything, make sure they explicitly say so
		if (!isset($opts['where']) && empty($opts['do_delete_all'])) {
			return false;
		}
		return db::delete("{$db}.{$table}", $opts['where'], (empty($opts['data'])) ? array() : $opts['data']);
	}
	
	public static function update_all($opts)
	{
		list($db, $table) = self::attrs('db', 'table');
		
		return db::update("{$db}.{$table}", $opts['set'], $opts['where'], (empty($opts['data'])) ? array() : $opts['data']);
	}
	
	public static function get_enum_vals($col)
	{
		return self::get_col_options($col);
	}

	public static function get_set_vals($col)
	{
		return self::get_col_options($col);
	}
	
	public static function get_col_options($col)
	{
		list($cols, $table) = @self::attrs('cols', 'table');
		
		$options_func = 'get_'.$col.'_options';
		$options_key = $col.'_options';
		if (method_exists($table, $options_func))
		{
			return $table::$options_func();
		}
		else if (property_exists($table, $options_key))
		{
			return $table::$$options_key;
		}
		else
		{
			return ((array_key_exists($col, $cols)) ? $cols[$col]->extra : false);
		}
	}
	
	public static function new_from_post($data = false)
	{
		list($cols, $table) = @self::attrs('cols', 'table');

		if ($data === false) {
			$data = $_POST;
		}
		$obj = new $table();
		foreach ($cols as $col_name => $col) {
			$key = $table.'_'.$col_name;
			if (array_key_exists($key, $data)) {
				$obj->$col_name = $data[$key];
			}
		}
		return $obj;
	}
	
	public static function html_form_new($opts = array())
	{
		rs::set_opt_defaults($opts, array(
			'submit_type' => 'new'
		));
		$class = get_called_class();
		$obj = new $class();
		return $obj->html_form($opts);
	}
	
	// an rs records table is usually the same as the class name
	// can use this to set the db and/or table if needed
	// can pass in
	// 1. db and table
	// 2. just table
	public static function set_location($mixed, $table = false)
	{
		// make sure we've loaded the table definition
		// as if we have not, what gets set here will be overwritten
		self::load_table_definition();
		if ($table === false) {
			self::set_prop('table', $mixed);
		}
		else {
			list($class) = self::attrs('class');
			$class::$db = $mixed;
			self::set_prop('table', $table);
		}
	}

	/**
	 * get_table_from_class_name()
	 * takes input from get_called_class() and parses the table name from the 
	 * designated autoload format {type}_{database}_{table_name}.rs.php
	 */
	protected static function get_table_from_class_name($called_class){
		$pieces = explode('_',$called_class);
		$type = array_shift($pieces);
		$database = array_shift($pieces);
		$table = implode('_',$pieces);

		return $table;
	}
}

?>