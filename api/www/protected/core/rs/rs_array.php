<?php

class rs_array implements IteratorAggregate
{
	public $db, $table, $class, $a;
	
	/*
	 * type of array: NUM or ASSOC
	 */
	public $key_type;

	// set/used by get()
	private $table_aliases, $many_tables;
	
	function __construct($class, $opts = array())
	{
		$this->a = array();
		$this->class = $class;
		list($this->db, $this->table) = $class::attrs('db', 'table');
		if (isset($opts['type'])) {
			$this->key_type = $opts['type'];
		}
	}
	
	public function count()
	{
		return count($this->a);
	}
	
	/*
	 * functions for "manually" adding/removing from rs array
	 */
	public function push($obj)
	{
		return array_push($this->a, $obj);
	}
	
	public function pop()
	{
		return array_pop($this->a);
	}
	
	public function unshift($obj)
	{
		return array_unshift($this->a, $obj);
	}

	public function shift()
	{
		return array_shift($this->a);
	}
	
	public function splice($offset, $length = 0, $replacement = null)
	{
		return array_splice($this->a, $offset, $length, $replacement);
	}
	
	public function find($field, $val)
	{
		foreach ($this->a as &$obj) {
			if ($obj->$field == $val) {
				return $obj;
			}
		}
		return false;
	}
	
	// for associative array
	public function set($key, $obj)
	{
		$this->a[$key] = $obj;
	}
	
	/*
	 * end manual functions
	 */
	 
	// get obj at $key
	public function i($key)
	{
		return $this->a[$key];
	}
	
	/*
	 * iterator functions
	 */
	 
	public function reset()
	{
		return reset($this->a);
	}
	
	public function current()
	{
		return current($this->a);
	}
	
	public function next()
	{
		return next($this->a);
	}
	
	public function prev()
	{
		return prev($this->a);
	}
	
	public function end()
	{
		return end($this->a);
	}
	
	public function key()
	{
		return key($this->a);
	}
	
	/*
	 * end iterator functions
	 */
	
	private function join_sql(&$joins)
	{
		if ($joins) {
			$sql = '';
			foreach ($joins as $table => $join_info) {
				// check for alias
				if (array_key_exists($table, $this->table_aliases)) {
					$actual_table = $this->table_aliases[$table];
					$qtable = "{$actual_table} as {$table}";
				}
				else {
					$actual_table = $table;
					$qtable = $table;
				}
				list($db) = $actual_table::attrs('db');
				$join_type = (strpos($join_info['type'], 'join') === 0) ? "join" : "left join";

				$sql .= "{$join_type} {$db}.{$qtable} on {$join_info['on']}\n";
			}
			return $sql;
		}
		else {
			return '';
		}
	}
	
	private function select_append($select, $appendage)
	{
		if (is_string($select)) {
			foreach ($appendage as $table => $cols) {
				$select .= (($select) ? ", " : "").implode(", ", array_map(create_function('$x', 'return "'.$table.'.$x";'), $cols));
			}
		}
		// array
		else {
			foreach ($appendage as $table => $cols) {
				if (array_key_exists($table, $select)) {
					$select[$table] = array_unique(array_merge($select[$table], $cols));
				}
				else {
					$select[$table] = $cols;
				}
			}
		}
		return $select;
	}
	
	private function get_select_type(&$tinfo)
	{
		if ($tinfo['is_in_self_chain']) {
			return ($tinfo['is_leaf']) ? 'self_leaf' : 'self_branch';
		}
		else {
			return ($tinfo['is_leaf']) ? 'leaf' : 'branch';
		}
	}

	private function select_sql($select, &$table_info, &$opts)
	{
		if (is_string($select)) {
			$select_sql = $select;
		}
		else {
			// order within each group does not matter
			// 1. self leaf first
			// 2. self other tables first
			// 3. any other leaves
			// 4. any other branch nodes
			$typed_select = array(
				'self_leaf' => '',
				'self_branch' => '',
				'leaf' => '',
				'branch' => ''
			);
			foreach ($select as $table => $cols) {
				$col_sql = '';
				foreach ($cols as $col) {
					if ($col_sql) {
						$col_sql .= ", ";
					}
					// if the column has a (, assume an sql function is being called
					// todo? parse function to prepend table to col
					// currently user must disambiguate if necessary
					$col_sql .= (strpos($col, "(") === false) ? "$table.$col" : $col;
				}

				if (array_key_exists($table, $this->table_aliases)) {
					$table = $this->table_aliases[$table];
				}

				// get select type
				$select_type = $this->get_select_type($table_info[$table]);
				$typed_select[$select_type] .= (($typed_select[$select_type]) ? ', ' : '').$col_sql;
			}
			$select_sql = '';
			foreach ($typed_select as $select_type => $sql) {
				if ($sql) {
					$select_sql .= (($select_sql) ? ', ' : '').$sql;
				}
			}
		}
		return "select".(($opts['distinct']) ? " distinct" : "")." {$select_sql}";
	}
	
	// order matters for joins
	// looping over opts in order allows user to dictate
	private function get_joins_and_aliases(&$opts)
	{
		$this->table_aliases = array();
		$this->many_tables = array();
		$joins = array();
		foreach ($opts as $k => $v) {
			if ((strpos($k, 'join') === 0 || strpos($k, 'left_join') === 0) && $v) {
				foreach ($v as $join_table => $join_on) {
					if (preg_match("/(.*?)\s+as\s+(.*)$/", $join_table, $matches)) {
						list($ph, $table, $join_table) = $matches;
						$this->table_aliases[$join_table] = $table;
					}
					if (strpos($k, 'many') !== false) {
						$this->many_tables[$join_table] = 1;
					}
					$joins[$join_table] = array(
						'on' => $join_on,
						'type' => $k
					);
				}
			}
		}
		return $joins;
	}
	
	/*
	 * todo: add hook so data can be transformed as we get it
	 */
	public function get($opts = array())
	{
		// check this before we set option defaults
		$is_user_select = (array_key_exists('select', $opts));
		
		rs::set_opt_defaults($opts, array(
			'select' => $this->table.'.*',
			'distinct' => false,
			'join' => array(),
			'join_many' => array(),
			'left_join' => array(),
			'left_join_many' => array(),
			'where' => '',
			'group_by' => '',
			'order_by' => '',
			'limit' => '',

			// key/vals for db binding
			'data' => array(),

			// use key_col as index into rs_array
			'key_col' => false,
			// values stored at key_col should be an array themselves
			'key_grouped' => false,

			// undo column aliases after data is fetched
			'de_alias' => false,
			
			// true: joins do not create sub tables, keys are all tied to base table
			// array: keys are table column would normally be on, values are new table to map to
			'flatten' => false
		));
		
		// todo: force select to be array
		$select = $opts['select'];
		$from   = "{$this->db}.{$this->table}".(($this->class != $this->table) ? " as {$this->class}" : "");
		$joins  = $this->get_joins_and_aliases($opts);
		$where  = (is_array($opts['where']) && !empty($opts['where'])) ? "(".implode(") && (", $opts['where']).")" : $opts['where'];
		$group  = $opts['group_by'];
		$order  = $opts['order_by'];
		$limit  = $opts['limit'];
		
		$class = $this->class;
		list($primary_key, $has_many, $extends) = $class::attrs('primary_key', 'has_many', 'extends');
		// parent tables
		if ($extends) {
			foreach ($extends as $ancestor_table) {
				if (!$is_user_select) {
					$select = $this->select_append($select, array($ancestor_table => array("*")));
				}
				// move these joins to the front
				// pk must match in parent and child
				$sql_pk = array();
				foreach ($primary_key as $pk_col) {
					$sql_pk[] = "{$this->class}.{$pk_col} = {$ancestor_table}.{$pk_col}";
				}
				$ancestor_join = array($ancestor_table => array('on' => implode(" && ", $sql_pk), 'type' => 'join'));
				$joins = array_merge($ancestor_join, $joins);
			}
		}
		
		// build map of column name to table
		// collisions will result in overwrite, user can use alias to resolve
		// don't need to worry about extends tables as $class will inherit columns
		$all_tables = array_merge(array_keys($joins), array($class));
		$all_extensions = array();
		$col_map = array();
		$table_info = array();
		foreach ($all_tables as $table) {
			if (array_key_exists($table, $this->table_aliases)) {
				$table = $this->table_aliases[$table];
			}
			// don't need to set info if we've already seen this table
			if (isset($all_extensions[$table])) {
				continue;
			}
			list($sub_extends) = $table::attrs('extends');
			$all_extensions[$table] = $sub_extends;
			// init table info assuming tables are both the leaf and root at depth of 0
			$table_info[$table] = array(
				'is_leaf' => true,
				'depth' => 0,
				'root' => $table,
				'is_in_self_chain' => 0
			);
			// if select is not an array, build the col map from all columns
			if (!is_array($select)) {
				list($table_cols) = $table::attrs('cols');
				if (!empty($sub_extends) && $table !== $this->class) {
					$table = $sub_extends[0];
				}
				$col_map = array_merge($col_map, array_combine(array_keys($table_cols), array_fill(0, count($table_cols), $table)));
			}
		}
		
		// we cannot always tell until we actually look at data what the leaf
		// for a given chain of tables will be
		// eg if b and c both extend a
		// 1. left joining with both b and c
		// 2. left joining with b (can get rows of type c even if not explicitly part of query)
		// todo: optimize in cases when it can be determined
		//  should we make a more complete map of tables?
		// calling class is self chain
		$table_info[$this->class]['is_in_self_chain'] = 1;
		foreach ($all_extensions as $table => $derived_classes) {
			if ($derived_classes) {
				$table_info[$table]['root'] = $derived_classes[0];
				$table_info[$table]['depth'] = count($derived_classes);
				foreach ($derived_classes as $i => $derived_class) {
					$table_info[$derived_class]['is_leaf'] = false;
					if ($table == $this->class) {
						$table_info[$derived_class]['is_in_self_chain'] = 1;
					}
				}
			}
		}

		// build and execut the query
		$query = "
			".$this->select_sql($select, $table_info, $opts)."
			from $from
			".$this->join_sql($joins)."
			".(($where) ? "where {$where}" : "")."
			".(($group) ? "group by {$group}" : "")."
			".(($order) ? "order by {$order}" : "")."
			".(($limit) ? "limit {$limit}" : "").";
		";
		$r = db::exec($query, $opts['data'], 'r');
		// check for error
		if ($r === false) {
			return false;
		}

		$is_many_join = ($opts['join_many'] || $opts['left_join_many']);
		$main_pk_cols = $primary_key;
		if (is_array($select)) {
			// build col map based on what user selected
			$col_map = array();

			// init col map and check for column aliases for
			// 1. map from column to table
			// 2. map from alias to pk table for many join aggregating
			// also set table user specifies if doesn't match col map generated above (which
			//  can be ambiguous)
			foreach ($select as $select_table => $select_cols) {
				foreach ($select_cols as $select_col) {
					if ($select_col == '*') {
						$table_cols = $select_table::get_cols(false);
						$col_map = array_merge($col_map, array_combine($table_cols, array_fill(0, count($table_cols), $select_table)));
					}
					else {
						if (preg_match("/(.*?)\s+as\s+(.*)$/", $select_col, $matches)) {
							list($ph, $name_before, $name_after) = $matches;
							$col_map[$name_after] = array($select_table, $name_before);
							if ($is_many_join && $select_table == $this->class && in_array($name_before, $primary_key)) {
								$main_pk_cols[] = $name_after;
							}
						}
						else {
							$col_map[$select_col] = $select_table;
						}
					}
				}
			}
		}

		return $this->parse_get_result($r, $col_map, $opts, $main_pk_cols, $table_info);
	}
	
	private function get_pk_data_str(&$primary_key, &$d)
	{
		$many_key = '';
		foreach ($primary_key as $key) {
			if (array_key_exists($key, $d)) {
				$many_key .= (($many_key) ? "\t" : '').$d[$key];
			}
		}
		return $many_key;
	}
	
	private function parse_get_result(&$r, &$col_map, &$opts, &$primary_key, &$table_info)
	{
		// set what type of array we're building
		if ($opts['key_col']) {
			$this->key_type = 'ASSOC';
			$key_col = $opts['key_col'];
		}
		else {
			$this->key_type = 'NUM';
			$key_col = false;
		}

		// if we're expecting more than one result per object, we need
		// to know where to tie the ones we've already seen
		$many_obj_map = array();

		// loop over results
		$count = 0;
		while ($d = $r->fetch(PDO::FETCH_ASSOC)) {
			// do we need a new object or have we already seen this guy?
			$obj = false;
			$is_new_obj = false;
			// $many_key is unrelated to $key_col, $many_key is how we tie
			// rows representing the same object together
			$many_key = $this->get_pk_data_str($primary_key, $d);
			// user did not select primary key, nothing to do but assume each object is unique
			// achieved by assigning count to many_key
			if ($many_key === '') {
				$many_key = $count;
			}
			if ($key_col) {
				if ($this->key_exists($d[$key_col])) {
					if ($opts['key_grouped']) {
						if (array_key_exists($many_key, $many_obj_map)) {
							// ok..
							$obj = $this->a[$d[$key_col]][$many_obj_map[$many_key]];
						}
					}
					// not grouped
					else {
						$obj = $this->a[$d[$key_col]];
					}
				}
			}
			// no key col
			else {
				if (array_key_exists($many_key, $many_obj_map)) {
					$obj = $this->a[$many_obj_map[$many_key]];
				}
			}
			// indexes into the array for each many table that we joined with
			$many_table_indexes = array();
			$undetermined_leaves = array();
			foreach ($d as $k => $v) {
				if (array_key_exists($k, $col_map)) {
					/*
					 * figure out which table this key belongs to
					 */
					$table = $col_map[$k];
					if (is_array($table)) {
						list($table, $original_col_name) = $table;
						// check de alias (unless function)
						if ($opts['de_alias'] && strpos($original_col_name, '(') === false) {
							$k = $original_col_name;
						}
					}

					if ($opts['flatten'] === true) {
						$table = $this->class;
					}
					else if (is_array($opts['flatten']) && array_key_exists($table, $opts['flatten'])) {
						$table = $opts['flatten'][$table];
					}

					if (array_key_exists($table, $this->table_aliases)) {
						$table_alias = $table;
						$table = $this->table_aliases[$table];
					}
					else {
						$table_alias = false;
					}

					$tinfo = $table_info[$table];
					$root_table = $tinfo['root'];
					if (!$obj) {
						$is_new_obj = true;
						// object was passed in
						if (isset($opts['obj'])) {
							$obj = $opts['obj'];
						}
						else {
							// table should always be in self chain because of how we order select, unless
							// no columns from self chain were selected
							if (!$tinfo['is_in_self_chain']) {
								$obj_class = $this->class;
							}
							else {
								$obj_class = $table;
							}
							$obj = new $obj_class();
						}
					}
					/*
					 * we have our object, add value to it at appropriate place
					 */
					// key belongs to self
					if ($tinfo['is_in_self_chain']) {
						$obj->$k = $v;
					}
					// key belongs to some sub-table
					else {
						// check alias
						// todo: what if a root table is aliased more than once and extended.
						//  sub classes would not know which root they belong to
						if ($table == $root_table && $table_alias) {
							$table_key = $table_alias;
						}
						else {
							$table_key = $root_table;
						}
						// we haven't yet created the table this key belongs to
						if (!$obj->$table_key) {
							// many, use rs_array at key
							// the array will be of type root table, the objects themselves are created below
							// and will be of type table
							if (isset($this->many_tables[$table_key])) {
								$obj->$table_key = $root_table::new_array();
							}
							// just one
							else if ($is_new_obj) {
								$obj->$table_key = $this->create_new_object($root_table, $table, $v, $undetermined_leaves);
							}
						}
						// do not check if many table object, they are checked below
						else if (!isset($this->many_tables[$table_key])) {
							if (isset($undetermined_leaves[$root_table]) && !is_null($v)) {
								$this->$table_key = $this->create_determined_object($this->$table_key, $root_table, $table, $undetermined_leaves);
							}
						}

						// set value $v at $k on object

						if (isset($this->many_tables[$table_key])) {
							// first time we have seen a column for this table
							if (!array_key_exists($table_key, $many_table_indexes)) {
								// same logic as above, exc
								$new_many_table = $this->create_new_object($root_table, $table, $v, $undetermined_leaves);
								// push? should we have many_index option?
								$obj->$table_key->push($new_many_table);
								$many_table_indexes[$table_key] = $obj->$table_key->count() - 1;
							}
							else {
								if (isset($undetermined_leaves[$root_table]) && !is_null($v)) {
									$obj->$table_key->a[$many_table_indexes[$table_key]] = $this->create_determined_object($obj->$table_key->a[$many_table_indexes[$table_key]], $root_table, $table, $undetermined_leaves);
								}
							}
							$obj->$table_key->a[$many_table_indexes[$table_key]]->$k = $v;
						}
						// just one
						else if ($is_new_obj) {
							$obj->$table_key->$k = $v;
						}
					}
				}
			}
			// add to our internal array
			if ($is_new_obj) {
				// $many_key will have been set above!
				if ($key_col) {
					if ($opts['key_grouped']) {
						$many_obj_map[$many_key] = count($this->a[$obj->$key_col]);
						$this->a[$obj->$key_col][] = $obj;
					}
					else {
						$this->a[$obj->$key_col] = $obj;
					}
				}
				else {
					$many_obj_map[$many_key] = $this->count();
					$this->a[] = $obj;
				}
				$count++;
			}
		}
		return $count;
	}

	private function create_new_object($root_table, $table, $v, &$undetermined_leaves)
	{
		// what are we??
		if ($table != $root_table && is_null($v)) {
			// use root table
			// until we can determine what we are
			$obj = new $root_table();
			$undetermined_leaves[$root_table] = 1;
		}
		else {
			$obj = new $table();
		}
		return $obj;
	}

	private function create_determined_object($prev_obj, $root_table, $table, &$undetermined_leaves)
	{
		$obj = new $table();
		foreach ($prev_obj as $k => $v) {
			$obj->$k = $v;
		}
		unset($undetermined_leaves[$root_table]);
		return $obj;
	}

	public function put()
	{
		foreach ($this->a as $i => &$obj) {
			$obj->put();
		}
	}
	
	public function delete($opts = array())
	{
		rs::set_opt_defaults($opts, array(
			'use_pk' => false
		));
		if ($opts['use_pk']) {
			$class = $this->class;
			list($pk) = $class::attrs('primary_key');
			$in = '';
			foreach ($this->a as $obj) {
				$obj_in = '';
				foreach ($pk as $col) {
					$obj_in .= (($obj_in) ? ',' : '')."'".db::escape($obj->$col)."'";
				}
				$in .= (($in) ? ',' : '').'('.$obj_in.')';
			}
			db::delete("{$this->db}.{$this->table}", "(`".implode('`', $pk)."`) in ($in)");
		}
		else {
			foreach ($this->a as $i => &$obj) {
				$obj->delete();
			}
		}
	}
	
	// can either be object or index
	public function remove($mixed)
	{
		if (rs::is_rs_object($mixed)) {
			foreach ($this->a as $i => &$obj) {
				if ($obj === $mixed) {
					unset($this->a[$i]);
					return true;
				}
			}
			return false;
		}
		else if (is_scalar($mixed)) {
			if ($this->key_exists($mixed)) {
				unset($this->a[$mixed]);
				return true;
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}

	public function key_exists($key)
	{
		return (array_key_exists($key, $this->a));
	}
	
	public function merge()
	{
		$args = func_get_args();
		foreach ($args as $arg) {
			if ($this->key_type == 'NUM') {
				for ($i = 0, $ci = $arg->count(); $i < $ci; ++$i) {
					$this->a[] = $arg->i($i);
				}
			}
			else {
				foreach ($arg as $key => $obj) {
					$this->a[$key] = $obj;
				}
			}
		}
	}
	
	public function sort($cmp)
	{
		usort($this->a, $cmp);
	}
	
	public function __get($key)
	{
		$vals = array();
		foreach ($this->a as &$obj) {
			$vals[] = $obj->$key;
		}
		return $vals;
	}
	
	public function __set($key, $val)
	{
		if (count($this->a) == 0) {
			if (property_exists($this, $key)) {
				$this->$key = $val;
			}
		}
		else {
			foreach ($this->a as &$obj) {
				$obj->$key = (is_callable($val)) ? call_user_func($val, $obj) : $val;
			}
		}
	}
	
	public function __call($method, $args)
	{
		$r = array();
		foreach ($this->a as &$obj) {
			$r[] = call_user_func_array(array($obj, $method), $args);
		}
		return $r;
	}
	
	public function to_array($max_depth = false)
	{
		return rs::to_array($this->a, $max_depth);
	}
	
	public function json_encode()
	{
		return json_encode($this->to_array());
	}
	
	// implement IteratorAggregate
	public function getIterator()
	{
		return (new ArrayIterator(new ArrayObject($this->a)));
	}
}

?>