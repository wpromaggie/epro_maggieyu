<?php

/*
 * abstract: does not implement set_table_definition
 */

abstract class rs_record extends rs_table
{
	private $is_db_data_set;

	function __construct($data = array(), $opts = array())
	{
		list($table, $cols, $primary_key) = self::attrs('table', 'cols', 'primary_key', 'has_one', 'has_many');
		
		rs::set_opt_defaults($opts, array(
			'do_get' => false
		));
		
		$cols_set = array();
		foreach ($data as $col_name => $value) {
			if (array_key_exists($col_name, $cols) && isset($value)) {
				$this->$col_name = $value;
				$cols_set[] = $col_name;
			}
		}
		// if all we set is the primary key and we want to get everything, get it
		if ($opts['do_get'] || ($primary_key && !array_diff($cols_set, $primary_key) && !array_diff($primary_key, $cols_set))) {
			$this->get($opts);
		}
	}
	
	public function is_in_db()
	{
		return (!empty($this->is_db_data_set));
	}
	
	public function put($opts = array())
	{
		list($db, $table, $cols, $primary_key, $has_one, $has_many, $extends) = self::attrs('db', 'table', 'cols', 'primary_key', 'has_one', 'has_many', 'extends');
		
		rs::set_opt_defaults($opts, array(
			'insert' => false,
			'update' => false
		));
		
		// we are put'ing an ancestor
		if (isset($opts['ancestor_table'])) {
			$table = $opts['ancestor_table'];
		}
		// see if we have ancestors, recursive call
		else if ($extends) {
			foreach ($extends as $ancestor_table) {
				if ($this->put(array_merge($opts, array('ancestor_table' => $ancestor_table))) === false) {
					return false;
				}
			}
		}
		
		$data = array();
		$is_auto_inc_col = false;
		$is_auto_inc_empty = false;
		$auto_inc_col_name = false;
		foreach ($cols as $col_name => $col) {
			if ($col->attrs & rs::AUTO_INCREMENT) {
				$is_auto_inc_col = true;
				$auto_inc_col_name = $col_name;
				// col value is null and is an auto_increment
				// either this is an insert or an update with auto_inc col not set, set auto_inc_col_name so we can grab id after insert
				if (is_null($this->$col_name)) {
					$is_auto_inc_empty = true;
				}
			}
			if (
				(isset($this->$col_name))
				&&
				(
					// !$opts['update'] - can't update primary key using put()
					(in_array($col_name, $primary_key) && !$opts['update'])
					||
					(
						((!$col->ancestor_table && !$opts['ancestor_table']) || $col->ancestor_table == $table) &&
						(!$opts['cols'] || in_array($col_name, $opts['cols']))
					)
				)
			) {
				if (!($col->attrs & rs::NOT_NULL) && $this->$col_name === rs::NUL) {
					$data[$col_name] = null;
				}
				else {
					$data[$col_name] = $this->$col_name;
				}
			}
		}
		
		// check for insert
		if ($opts['insert']) {
			// if primary key not set and user defined id insertion
			$do_insert = true;
			if (array_diff($primary_key, array_keys($data))) {
				// check for user defind primary key function
				if (method_exists($this, 'uprimary_key')) {
					$do_insert = false;
					$r = $this->insert_uprimary_key($db, $table, $primary_key, $data);
				}
			}
			if ($do_insert) {
				$r = db::insert("{$db}.{$table}", $data, $is_auto_inc_col);
			}
		}
		// only need to run updates/puts if we have data
		else if ($data) {
			if ($opts['update']) {
				// don't need to update if all we have is primary key
				if (!($primary_key && !array_diff(array_keys($data), $primary_key))) {
					list($qwhere, $qdata) = $this->get_optimal_where_query_and_data(rs::FIND_ANY, $table);
					$r = db::update("{$db}.{$table}", $data, $qwhere, $qdata);
				}
				else {
					// not an error, but nothing updated (zero rows affected)
					$r = 0;
				}
			}
			else {
				$r = db::insert_update("{$db}.{$table}", $primary_key, $data, $is_auto_inc_col);
			}
		}
		// nothing to do, return true
		else {
			return true;
		}
		
		// if it was an insert or insert_update, and table has an auto inc column that isn't set, set it
		if (!$opts['update'] && $is_auto_inc_col && empty($this->$auto_inc_col_name) && $r) {
			$this->$auto_inc_col_name = $r;
		}
		if ($r === false) {
			return false;
		}
		else {
			$this->is_db_data_set = true;
			return $r;
		}
	}
	
	// user defined function to create primary key
	// we are changing data, caller must be ok with that
	// max number of attempts
	const UPK_MAX_ATTEMPTS = 1024;
	
	//TODO: local_rand() function to create new seed for mt_rand
	//  if we reach UPK_MAX_ATTEMPS. can create defaults for
	//  *nix and windows
	
	protected function insert_uprimary_key($db, $table, $pk_cols, &$data)
	{
		for ($i = 0; $i < self::UPK_MAX_ATTEMPTS; ++$i)
		{
			$pk = $this->uprimary_key($i);
			
			// if not array, use first pk_col to create one
			if (!is_array($pk))
			{
				$pk = array($pk_cols[0] => $pk);
			}
			
			foreach ($pk as $k => $v)
			{
				$data[$k] = $v;
			}
			$r = db::insert("{$db}.{$table}", $data, false);
			if ($r)
			{
				// success, set fields in object
				foreach ($pk as $k => $v)
				{
					$this->$k = $v;
				}
				return true;
			}
			// inspect error
			// if it is primary key error, we want to keep trying
			// if it is some other error, we can return false now
			else
			{
				$error = rs::get_error();
				if (strpos($error, "for key 'PRIMARY'") === false)
				{
					return false;
				}
			}
		}
		throw new Exception('Could not create user primary key');
	}
	
	public function update($opts = array())
	{
		$opts['update'] = true;
		return $this->put($opts);
	}
	
	// set values in object from array and then update
	public function update_from_array($a, $opts = array())
	{
		list($cols) = self::attrs('cols');
		if (!array_key_exists('cols', $opts)) {
			$do_set_cols = true;
			$opts['cols'] = array();
		}
		foreach ($cols as $col_name => $col) {
			if (array_key_exists($col_name, $a)) {
				if ($do_set_cols) {
					$opts['cols'][] = $col_name;
				}
				$this->$col_name = $a[$col_name];
			}
		}
		$opts['update'] = true;
		return $this->put($opts);
	}
	
	/*
	 * cannot update primary key using put
	 * can use this to update other fields besides just primary key
	 */
	public function update_primary_key($new_vals)
	{
		if (!is_array($new_vals)) {
			$new_vals = array($new_vals);
		}
		list($db, $table, $extends) = self::attrs('db', 'table', 'extends');
		list($qwhere, $qdata) = $this->get_optimal_where_query_and_data(rs::FIND_ANY, $table);

		// avoid conflicts in param names
		foreach ($qdata as $k => $v) {
			if (isset($new_vals[$k])) {
				for ($i = 0; ; ++$i) {
					$k_no_conflict = "{$k}{$i}";
					if (!isset($new_vals[$k_no_conflict]) && !isset($qdata[$k_no_conflict])) {
						break;
					}
				}
				$qwhere = preg_replace("/:{$k}\b/", ":{$k_no_conflict}", $qwhere);
				$qdata[$k_no_conflict] = $v;
				unset($qdata[$k]);
			}
		}

		$update_tables = ($extends) ? array_merge($extends, array($table)) : array($table);
		foreach ($update_tables as $update_table) {
			// ok i guess
			$qwhere_table = ($update_table != $table) ? str_replace("{$table}.", "{$update_table}.", $qwhere) : $qwhere;
			$r = db::update("{$db}.{$update_table}", $new_vals, $qwhere_table, $qdata);
			if ($r === false) {
				return false;
			}
		}
		foreach ($new_vals as $k => $v) {
			$this->$k = $v;
		}
		return true;
	}
	
	public function insert($opts = array())
	{
		$opts['insert'] = true;
		return $this->put($opts);
	}
	
	public function put_from_post($opts = array())
	{
		list($db, $table, $cols) = self::attrs('db', 'table', 'cols');
		
		rs::set_opt_defaults($opts, array(
			'do_use_table_prefix' => true
		));
		$fields_updated = array();
		// use clone, only put fields that have changed on an update
		$clone = $this->clone_from_optimal();
		foreach ($cols as $col_name => &$col) {
			if ($opts['do_use_table_prefix']) {
				$post_key = $table.'_'.$col_name;
			}
			else {
				$post_key = $col_name;
			}
			if (array_key_exists($post_key, $_POST)) {
				$post_val = $_POST[$post_key];
				if ($post_val != $this->$col_name) {
					$fields_updated[] = $col_name;
					if ($clone !== false) {
						$clone->$col_name = $post_val;
					}
					$this->$col_name = $post_val;
				}
			}
		}
		if ($fields_updated) {
			if (!isset($opts['cols'])) {
				$opts['cols'] = $fields_updated;
			}
			if (isset($opts['extra_cols'])) {
				foreach ($opts['extra_cols'] as $col) {
					if (!in_array($col, $opts['cols'])) {
						$opts['cols'][] = $col;
					}
				}
			}
			if ($clone !== false) {
				$r = $clone->put($opts);
			}
			else {
				$r = $this->put($opts);
			}
			if ($r === false) {
				return false;
			}
		}
		
		return $fields_updated;
	}
	
	// 08.01.2013 i think i did this so that a clone would be used if there was not a primary key set?
	private function clone_from_optimal()
	{
		list($table) = self::attrs('table');
		$optimal_data = $this->get_optimal_where_data(rs::FIND_KEY_ONLY);
		
		return ($optimal_data) ? clone($this) : false;
	}
	
	public function get_children($child_type)
	{
		$child_type::load_table_definition();
		list($parents) = $child_type::attrs('parents');
		list($table) = self::attrs('table');
		
		$q_where = array();
		$parent_cols = $parents[$table];
		foreach ($parent_cols as $child_col_name => $parent_col_name)
		{
			$q_where[] = "`$child_col_name` = '".db::escape($this->$parent_col_name)."'";
		}
		if (empty($q_where))
		{
			return false;
		}
		$member_name = '_child_'.$child_type;
		$this->$member_name = $child_type::get_all(array('where' => implode(" && ", $q_where)));
		return true;
	}
	
	public function get_parent($parent_type)
	{
		$parent_type::load_table_definition();
		list($parent_db, $parent_table) = $parent_type::attrs('db', 'table');
		list($parents) = self::attrs('parents');
		
		$q_where = array();
		$parent_cols = $parents[$parent_table];
		foreach ($parent_cols as $child_col_name => $parent_col_name)
		{
			$q_where[] = "`$parent_col_name` = '".db::escape($this->$child_col_name)."'";
		}
		if (empty($q_where))
		{
			return false;
		}
		
		$data = db::select_row("select * from {$parent_db}.{$parent_table} where ".implode(" && ", $q_where), 'ASSOC');
		if (empty($data))
		{
			return false;
		}
		
		$member_name = '_parent_'.$parent_type;
		$this->$member_name = new $parent_type($data);
		return true;
	}
	
	// see rs_array->get() for all opts
	public function get($opts = array())
	{
		list($db, $table, $extends, $has_one, $has_many) = self::attrs('db', 'table', 'extends', 'has_one', 'has_many');

		// todo: force user to provide a select?
		rs::set_opt_defaults($opts, array(
			'find' => rs::FIND_ANY,
			'from' => $db.'.'.$table,
			'select' => '*',
			'where' => '',
			'data' => array()
		));
		
		if (empty($opts['where'])) {
			list($qwhere, $qdata) = $this->get_optimal_where_query_and_data($opts['find']);
			$opts['where'] = $qwhere;
			$opts['data'] = $qdata;
		}
		$opts['obj'] = $this;
		$r = self::get_all($opts);
		if ($r === false) {
			return false;
		}
		if ($r > 0) {
			$this->is_db_data_set = true;
		}
		return $r;
	}
	
	public function delete($do_delete_ancestors = true)
	{
		if ($do_delete_ancestors) {
			list($extends) = self::attrs('extends');
			if (!empty($extends)) {
				foreach ($extends as $ancestor) {
					list($qwhere, $qdata) = $this->get_optimal_where_query_and_data(rs::FIND_ANY, $ancestor);
					if (!empty($qwhere)) {
						$ancestor::delete_all(array("where" => $qwhere, "data" => $qdata));
					}
				}
			}
		}
		list($db, $table) = self::attrs('db', 'table');
		list($qwhere, $qdata) = $this->get_optimal_where_query_and_data(rs::FIND_ANY, $table);
		if (!empty($qwhere)) {
			return db::delete("{$db}.{$table}", $qwhere, $qdata);
		}
	}
	
	private function get_optimal_where_data($find = rs::FIND_ANY)
	{
		list($cols, $primary_key, $uniques) = self::attrs('cols', 'primary_key', 'uniques');
		
		if ($primary_key && (($optimal_data = $this->get_data_from_cols($primary_key)) !== false))
		{
			return $optimal_data;
		}
		if ($uniques)
		{
			foreach ($uniques as $unique_set)
			{
				$optimal_data = $this->get_data_from_cols($unique_set);
				if ($optimal_data)
				{
					return $optimal_data;
				}
			}
		}
		if ($find & rs::FIND_KEY_ONLY)
		{
			return false;
		}
		$optimal_data = $this->get_data_from_cols($cols, 'ASSOC', true);
		if ($optimal_data)
		{
			return $optimal_data;
		}
		return false;
	}
	
	private function get_optimal_where_query($find = rs::FIND_ANY, $table = false)
	{
		$optimal_data = $this->get_optimal_where_data($find);
		if ($optimal_data)
		{
			return $this->get_query_from_data($optimal_data, $table);
		}
		else
		{
			return false;
		}
	}
	
	private function get_optimal_where_query_and_data($find = rs::FIND_ANY, $table = false)
	{
		$optimal_data = $this->get_optimal_where_data($find);
		if ($optimal_data) {
			return array($this->get_query_from_data($optimal_data, $table), $optimal_data);
		}
		else {
			return false;
		}
	}
	
	private function set_data($db, $table, $optimal_where, $opts = array())
	{
		$d = db::select_row("
			select {$opts['select']}
			from {$opts['from']}
			where {$optimal_where}".(($opts['where']) ? " && {$opts['where']}" : "")."
		", 'ASSOC');
		
		if (!$d) {
			return false;
		}
		foreach ($d as $col_name => $val) {
			$this->$col_name = $val;
		}
		return true;
	}
	
	/*
	 * allow table to be passed in. because inserts and updates cannot reference
	 * multiple table, it is a little more awkward to deal with inheritance. see
	 * 'ancestor_table' stuff in put()
	 */
	private function get_query_from_data($data, $table = false)
	{
		if (!$table) {
			list($table) = self::attrs('table');
		}
		$sql = array();
		foreach ($data as $k => $v) {
			$sql[] = "{$table}.`$k` = :$k";
		}
		return implode(" && ", $sql);
	}
	
	private function get_data_from_cols($cols, $num_or_assoc = 'NUM', $is_null_ok = false)
	{
		$optimal_data = array();
		foreach ($cols as $k => $v) {
			$col_name = ($num_or_assoc == 'NUM') ? $v : $k;
			$val = (isset($this->$col_name)) ? $this->$col_name : null;
			if (is_null($val)) {
				if (!$is_null_ok) {
					return false;
				}
			}
			else {
				$optimal_data[$col_name] = $val;
			}
		}
		return $optimal_data;
	}

	public function extend()
	{
		if (method_exists($this, 'get_extender_classname')) {
			$extender_class = $this->get_extender_classname();
			return new $extender_class($this->get_optimal_where_data());	
		}
		else {
			return false;
		}
	}
	
	public function is_key_set()
	{
		return ($this->get_optimal_where_data(rs::FIND_KEY_ONLY));
	}
	
	public function to_array($max_depth = false)
	{
		return rs::to_array($this, $max_depth);
	}
	
	public function json_encode()
	{
		return json_encode($this->to_array());
	}
	
	public function html_form($opts = array())
	{
		rs::set_opt_defaults($opts, array(
			'table' => true,
			'ignore' => array(),
			'values' => array(),
			'show' => null,
			'relatives' => true,
			'labels' => array(),
			'cancel' => false,
			'delete' => false,
			'hidden' => array(),
			'submit_prefix' => ''
		));
		
		list($table, $cols, $has_one) = self::attrs('table', 'cols', 'has_one');
		
		$ml = '';
		if ($opts['relatives'] && $has_one)
		{
			$one_opts = $opts;
			$one_opts['table'] = false;
			foreach ($has_one as $one)
			{
				if ($this->$one)
				{
					$ml .= $this->$one->html_form($one_opts);
				}
			}
		}
		
		foreach ($cols as $k => &$col)
		{
			$v = (array_key_exists($k, $opts['values'])) ? $opts['values'][$k] : ((isset($this->$k)) ? $this->$k : $col->default);
			if (
				(!($col->attrs & rs::READ_ONLY)) &&
				(!(in_array($k, $opts['ignore']) || in_array("{$table}.{$k}", $opts['ignore']))) &&
				(!is_array($opts['show']) || in_array($k, $opts['show']))
			) {
				if (in_array($k, $opts['hidden'])) {
					$ml .= $col->get_form_input_hidden($table, $v);
				}
				else {
					$label = (array_key_exists($k, $opts['labels'])) ? $opts['labels'][$k] : rs::display_text($k);
					$ml .= '
						<tr>
							<td>'.$label.'</td>
							<td>'.$col->get_form_input($table, $v).'</td>
						</tr>
					';
				}
			}
		}
		if ($opts['table']) {
			// ugh too many ways to set action
			if ($opts['action']) {
				// e2.js sets a0 based on name
				// but that is confusing as an option
				// so we have action option
				// which if set, we actually set the name
				// so that e2.js sets the correct action
				$name = $opts['action'];
			}
			else {
				$name = (($opts['submit_prefix']) ? $opts['submit_prefix'] : '').$table.'_submit';
			}
			return '
				<table>
					<tbody>
						'.$ml.'
						<tr>
							<td></td>
							<td>
								<input type="submit" class="rs_submit submit"'.(($opts['submit_type']) ? ' submit_type="'.$opts['submit_type'].'"' : '').' name="'.$name.'" value="Submit" />
								'.(($opts['delete']) ? '<input type="submit" class="rs_submit delete" name="delete_'.$table.'_submit" value="Delete" />' : '').'
								'.(($opts['cancel']) ? '<input type="submit" class="rs_submit cancel" value="Cancel" />' : '').'
							</td>
						</tr>
					</tbody>
				</table>
			';
		}
		else {
			return $ml;
		}
	}
}

?>