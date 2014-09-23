<?php


// database functions
class db
{
	private static $dbg = false;
	private static $read_only = false;
	private static $insert_or_update = null;
	private static $c = null;
	private static $error = '';
	private static $hooks; /** @var string[] array of registered hooks */
	private static $q_current_state; /** @var object holds the current state of a row in the table prior to change */



	public static function dbg()
	{
		db::$dbg = true;
	}

	public static function dbg_off()
	{
		db::$dbg = false;
	}

	public static function read_only()
	{
		self::dbg_off();
		db::$read_only = true;
	}

	/**
	*	DB CRUD Execution
	*	read_write
	*	w - write (eg update, insert, delete), default
	*	r - read
	*	@param string $q query string
	*	@param mixed[] $data Array structure of key value pairs to be bound
	*	@param string $read_write flag passes string value r (read) or w  (write) defaults to w
	*
	*	@return mixed[] boolean, int (on successful write)
	*/
	public static function exec($q, $data = array(), $read_write = 'w')
<<<<<<< HEAD
	{	
=======
	{
>>>>>>> FETCH_HEAD
		try {
			// gotta loop twice:
			// 1. have to check for arrays and literals before prepare
			// 2. have to prepare before we bind
			foreach ($data as $k => $v) {
				$replace_str = false;
				if (is_array($v)) {
					// replace key in query with multiple keys
					// add all vals in sub array to data array
					$replace_str = '';
					foreach ($v as $i => $sub_val) {
						$sub_key = "{$k}{$i}";
						$replace_str .= (($replace_str) ? ',' : '').":{$sub_key}";
						$data[$sub_key] = $sub_val;
					}
				}
				else if (self::is_literal($v)) {
					$replace_str = self::get_val_from_literal($v);
				}

				if ($replace_str) {
					$q = str_replace(":{$k}", $replace_str, $q);
					unset($data[$k]);
				}
			}
			if (self::$dbg || self::$read_only) $dbg_str = "$q";

			$prep = self::$c->prepare($q);
			foreach ($data as $k => $v) {
				if (self::$dbg || self::$read_only) $dbg_str .= "(:$k = $v)";
				$prep->bindValue(":$k", $v);
			}
			if (self::$read_only && $read_write != 'r') {
				echo "$dbg_str".ENDL;
				return true;
			}

			$r = $prep->execute();
		}
		catch (Exception $e) {
			self::$error = $e->getMessage();
			if (self::$dbg || self::$read_only) echo "$dbg_str -> ".self::$error.ENDL;
			return false;
		}

		if ($read_write == 'w') {
			$count = $prep->rowCount();
			if (self::$dbg) echo "$dbg_str -> $count".ENDL;
			return $count;
		}
		else {
			if (self::$dbg || self::$read_only) echo "$dbg_str".ENDL;
			return $prep;
		}
	}

	public static function start_transaction()
	{
		return self::$c->beginTransaction();
	}

	public static function commit()
	{
		return self::$c->commit();
	}

	/**
	*	set set something to a literal value instead of binding
	*	@param string $x value to set to literal
	*
	*	@return mixed[]
	*/
	public static function literal($x)
	{
		return ((object) array('type' => 'literal', 'value' => $x));
	}

	public static function is_literal($x)
	{
		return (($x instanceof stdClass) && $x->type == 'literal');
	}

	public static function get_val_from_literal($x)
	{
		return (self::is_literal($x) ? $x->value : false);
	}

	// cannot bind dbs, tables, columns. can use this simple escape
	public static function escape($x)
	{
		return str_replace("'", "\'", $x);
	}

	public static function use_db($db)
	{
		return (self::exec("use ".self::escape($db)) !== false);
	}

	/**
	*	Connect to the database using PDO
	*	@param string $host 	host address
	*	@param string $user 	username
	*	@param string $pass 	password
	*	@param string $db 		database name
	*	
	*	Connection handle self::$c is initialized here 	
	*	TODO: make this an singleton instance
	*
	*	@return mixed boolean or void
	*/
	public static function connect($host, $user = '', $pass = '', $db = '')
	{
		if (is_array($host)) {
			$user = $host['user'];
			$pass = $host['pass'];
			$db = (isset($host['db'])) ? $host['db'] : '';
			$host = $host['host'];
		}
		if (db::$dbg || db::$read_only) echo 'connect: host='.$host.', user='.$user.', db='.$db.ENDL;
		try {
			self::$c = new PDO('mysql:host='.$host, $user, $pass);
			self::$c->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		catch (PDOException $e)	{
			return false;
		}
		if (!empty($db)) {
			db::use_db($db);
		}
	}

	/**
	*	Close connection handle by setting it to null
	*/
	public static function close()
	{
		self::$c = null;
	}

	/**
	*
	* the db::add_to_array_x functions recurse over the INDEXES, they only add 1 "row" of data to the array
	* there are only 2 differences (commented below) between the two, but we want these to be fast
	*
	*	
	*/
	private static function add_to_array_num(&$a, &$d, &$indexes, $i, $count)
	{
		$index = $indexes[$i];
		if (is_array($index)) {
			$do_group = true;
			$index = $index[0];
		}
		else {
			$do_group = false;
		}
		$key = $d[$index];

		// splice numerical
		array_splice($d, $index, 1);

		if ($i == ($count - 1)) {
			// if there is only one item in array, convert to scalar
			if ($do_group) {
				$a[$key][] = (count($d) > 1) ? $d : $d[0];
			}
			else {
				$a[$key] = (count($d) > 1) ? $d : $d[0];
			}
		}
		else {
			if (!array_key_exists($key, $a)) $a[$key] = array();
			db::add_to_array_num($a[$key], $d, $indexes, $i + 1, $count);
		}
	}

	private static function add_to_array_assoc(&$a, &$d, &$indexes, $i, $count)
	{
		$index = $indexes[$i];
		if (is_array($index)) {
			$do_group = true;
			$index = $index[0];
		}
		else {
			$do_group = false;
		}
		$key = $d[$index];

		// unset associative
		unset($d[$index]);

		if ($i == ($count - 1)) {
			// keep as vector
			if ($do_group) {
				$a[$key][] = $d;
			}
			else {
				$a[$key] = $d;
			}
		}
		else {
			if (!array_key_exists($key, $a)) $a[$key] = array();
			db::add_to_array_assoc($a[$key], $d, $indexes, $i + 1, $count);
		}
	}

	/**	
	* 	Takes a string value and a magical second parameter if specified by use of func_get_args()
	* optional arg options:
	* a) data, type and keys
	* b) data, type
	* c) data
	* d) type and keys
	* e) type
	* f) nothing
	* note: cannot pass only data and keys!
	* if NUM (which is Default)
	* Use column number as row value array key
	* Check if the index is empty. 
	*	if NO INDEX
	*	iterate the results ⇒ a
	*	
	*	if INDEX exists
	*	use column number as the array key ⇒ a[index]
	*
	* if ASSOC
	* same as NUM but use column name as array key
	* @param string $q query string
	* @param mixed **args
	*
	* @return mixed[] $a
	*/
	public static function select($q)
	{
		//Get argument in position 1 if specified
		$opt_args = array_slice(func_get_args(), 1);
		$num_args = count($opt_args);
		// default
		$assoc_or_num = 'NUM';
		if ($num_args == 0) {
			$data = array();
			$index_slice_start = 0;
		}
		else {
			// next arg is array, must be data for query binding
			if (is_array($opt_args[0])) {
				$data = $opt_args[0];
				$index_slice_start = 2;
				// if more args, next arg must be num/assoc
				if ($num_args > 1) {
					$assoc_or_num = $opt_args[1];
				}
			}
			else {
				$data = array();
				$assoc_or_num = $opt_args[0];
				$index_slice_start = 1;
			}
		}
		$r = self::exec($q, $data, 'r');

		if ($r === false) {
			return false;
		}
		// check for optional indexes
		$indexes = array_slice($opt_args, $index_slice_start);

		$a = array();
		if ($assoc_or_num == 'NUM') {
			if (empty($indexes)) {
				while ($d = $r->fetch(PDO::FETCH_NUM))
					$a[] = (count($d) > 1) ? $d : $d[0];
			}
			else {
				// re-calibrate numerical indexes
				for ($i = 0, $count = count($indexes); $i < $count; ++$i) {
					$i1 = $indexes[$i];
					$i1_num = (is_array($i1)) ? $i1[0] : $i1;
					for ($j = $i + 1; $j < $count; ++$j) {
						$i2 = $indexes[$j];
						if (is_array($i2)) {
							if ($i2[0] > $i1_num) $indexes[$j][0]--;
						}
						else {
							if ($i2 > $i1_num) $indexes[$j]--;
						}
					}
				}
				while ($d = $r->fetch(PDO::FETCH_NUM))
					db::add_to_array_num($a, $d, $indexes, 0, $count);
			}
		}
		// ASSOCIATIVE
		else {
			if (empty($indexes)) {
				while ($d = $r->fetch(PDO::FETCH_ASSOC))
					$a[] = $d;
			}
			else {
				$count = count($indexes);
				while ($d = $r->fetch(PDO::FETCH_ASSOC))
					db::add_to_array_assoc($a, $d, $indexes, 0, $count);
			}
		}
		return $a;
	}

	/**
	* Count the selected results
	*
	* @todo consider optimizing. Assumes developer is responsible
	*		potentially returns all columns, and rows, only need one column eg index.
	* 
	* @param string $q query string
	* @param mixed[] $data bound query args
	*
	* @return int count(array)
	*/
	public static function count_select($q, $data = array())
	{
		$a = self::select($q, $data);
		return count($a);
	}

	/**
	*	Select one the first value from the table
	*	@param string $q standard SELECT query string
	*	@param mixed[] $data array with bound values
	*
	*	@return mixed array or boolean(false) if no results
	*/
	public static function select_one($q, $data = array())
	{
		$r = self::exec($q, $data, 'r');
		if ($r) {
			$d = $r->fetch(PDO::FETCH_NUM);
			if (is_array($d) && count($d) > 0) {
				return $d[0];
			}
		}
		return false;
	}
	
	/**
	* Select a single row
	* select_row determines how the query results should be returned based on the magic field
	* if no type argument is passed, then the default return format is by NUM association where
	* NUM represents the column order in order of the called query eg. select a, b, c ==> r[0],r[1],r[2]
	* ASSOC retults in a mapping the column name to the index like mysqli::fetch_object()
	* optional arg options:
	* a) data and type
	* b) data
	* c) type
	* d) nothing
	*	@param string $q query string
	*	@param **opt_args optional arguments [NUM, ASSOC] 
	*
	* 	@return mixed $array or boolean(false) if no results
	*/
	public static function select_row($q)
	{
		$opt_args = array_slice(func_get_args(), 1);
		$num_args = count($opt_args);
		// case d
		if ($num_args == 0) {
			$data = array();
			$type_index = false;
		}
		else {
			// case a or b
			if (is_array($opt_args[0])) {
				$data = $opt_args[0];
				$type_index = ($num_args > 1) ? 1 : false;
			}
			// case c
			else {
				$data = array();
				$type_index = 0;
			}
		}
		$assoc_or_num = ($type_index !== false) ? $opt_args[$type_index] : 'NUM';
		$r = self::exec($q, $data, 'r');
		if ($r) {
			return $r->fetch(($assoc_or_num == 'NUM') ? PDO::FETCH_NUM : PDO::FETCH_ASSOC);
		}
		return false;
	}



	/**
	*
	*	Execute standard update php PDO query map where data to binding
	* 	Example db::update("`db`.`table`",array('id'=>10),"id=:id",array('id'=>5));
	*
	*	@todo  embed the change log within db::update(*arg,**args)
	* 	
	*	
	*	@param string $table
	* 	@param mixed[] $set_data
	*	@param string $where_query
	*	@param mixed[] $where_data
	*
	* 	@return self::exec results
	*/
	public static function update($table, $set_data, $where_query = '', $where_data = array())
	{
		$qwhere = (empty($where_query)) ? '' : "where $where_query";

		$qset = '';
		foreach ($set_data as $k => $v) {
			if (!empty($qset)) $qset .= ', ';
			$qset .= "`$k` = :$k";
		}
		$q = "
			update {$table}
			set $qset
			$qwhere
		";

		// Check if the queried table has a registered hook
		// Possible selecting multiple rows so you'll have to handle
		// updating as many row as you select
		if(self::table_has_hook('update',$table)){
			//STORE CURRENT VALUES
			$cols = implode(',',array_keys($set_data));
			$r_was = db::select("SELECT $cols FROM $table $qwhere", $where_data, 'ASSOC');
		}


		//perform normal operations
		$r = self::exec($q, array_merge($set_data, $where_data));

		// If the operation was successful and there exists a registered hook operation
		if($r && ($hook_func = self::table_has_hook('update',$table))){
			foreach($r_was as $kv){
				$diff = array_diff($kv,$set_data);
				call_user_func_array($hook_func,
									 array(util::get_current_user_id(),$table,$diff));
			}
		}
		return $r;
	}


	/**
	*	Execute INSERT with ON DUPLICATE KEY UPDATE
	*		if key exists UPDATE the record
	* 
	* 	@param string $table
	*	@param mixed[] $key_cols
	*	@param mixed[] $data
	*	@param boolean	$do_get_id	if true get the last inserted id
	*
	*	@return mixed return last inserted id or the db result object
	*/
	public static function insert_update($table, $key_cols, $data, $do_get_id = true)
	{
		$cols = '`'.implode('`, `', array_keys($data)).'`';
		$insert = '';
		$update = '';

		/* find current $key_col values in $data */
		$qcols = array_intersect_key($data,array_flip($key_cols));
		$qcur = array();
		foreach($qcols as $k => $v){
			$qcur[] = "`$k` = :{$k}";	
		}

		$qcur = implode(',',$qcur); //TODO: Double work, fix this inline

		if(self::table_has_hook('update',$table)){
			$r_was = db::select("SELECT * FROM $table WHERE $qcur",$qcols,'ASSOC');
		}

		/* upsert operation being */
		foreach ($data as $k => $v) {
			// all cols go in insert
			if (!empty($insert)) $insert .= ', ';
			$insert .= ":{$k}";

			// only non-key cols go in update
			if (in_array($k, $key_cols)) continue;
			if (!empty($update)) $update .= ', ';
			$update .= "`$k` = :{$k}";
		}
		// update can be empty if entire table consists of key_cols
		$update_query = ($update) ? " on duplicate key update $update" : '';
		$q = "insert into $table ($cols) values ($insert){$update_query}";
		$r = self::exec($q, $data);
		/* !upsert operation end */


	
		if(($hook_func = self::table_has_hook('update',$table)) !== false){
			$r_was = (is_array($r_was[0]))? $r_was[0] : array(); 
			$diff = array_diff($r_was,$data);
			if(!empty($diff)){
				$tmp = array_keys($qcols);
				$pk = array('name'=>$tmp[0],
							'value'=>array_shift($qcols));
				call_user_func_array($hook_func,array(util::get_current_user_id(),$table,$diff,$pk));
			}
		}
		

		if ($r === false) {
			return false;
		}
		else {
			if ($do_get_id && !self::$read_only) {
				return self::last_id();
			}
			else {
				return $r;
			}
		}
	}


	public static function last_was_insert() { return (self::$insert_or_update == 'insert'); }
	public static function last_was_update() { return (self::$insert_or_update == 'update'); }


	/**
	*	
	*	Standard INSERT number of affected rows or returns false if failed
	* 	@param string $table
	*	@param mixed[] $key_cols
	*	@param boolean	$do_get_id	(Default true )if true get the last inserted id
	*	@param boolean	$is_insert_update (Default false) if true 
	*
	*	@return mixed [boolean, int] false if failed to insert interger
	*/
	public static function insert($table, $data, $do_get_id = true, $is_insert_update = false)
	{
		$keys = array_keys($data);
		$q = "
			insert into $table
			(`".implode("`,`", $keys)."`)
			values
			(:".implode(', :', $keys).")
		";
		$num_affected = self::exec($q, $data);
		if ($num_affected === false) return false;
		if ($is_insert_update) {
			self::$insert_or_update = ($num_affected === 1) ? 'insert' : 'update';
			if (self::$insert_or_update == 'update') {
				return $num_affected;
			}
		}
		if ($do_get_id && !self::$read_only) {
			return self::last_id();
		}
		return $num_affected;
	}

	/**
	* Delete QUERY with WHERE conditional retuns number of number of db result object
	*		if readonly flag is set, r and eturns false
	*		if read
	* 
	*/
	public static function delete($table, $qwhere = '', $data = array())
	{
		$q = "
			delete from {$table}
			".(($qwhere) ? "where $qwhere" : "")."
		";


		if (db::$read_only) {
			$delete_simulation = db::select_one(preg_replace("/\s*delete/ms", 'select count(*)', $q), $data);
			if ($delete_simulation === false) {
				return false;
			}
			else {
				echo 'read only delete: '.$delete_simulation[0].ENDL;
				return $delete_simulation[0];
			}
		}
		else {
			//change log
			if(self::table_has_hook('delete',$table)){
				$r_was = db::select("SELECT * FROM $table WHERE $qwhere", $data, 'ASSOC');	
			}
			$r = self::exec($q, $data);

			// If the operation was successful and there exists a registered hook operation
			if($r && ($hook_func = self::table_has_hook('delete',$table))){
				foreach($r_was as $kv){
					call_user_func_array($hook_func,
										 array(util::get_current_user_id(),$table,$kv));
				}
			}

			return $r;
		}
	}

	/**
	*	Run raw query 
	* 	backwards compatable, synonym for exec
	*	@param string $q query string
	*	@param mixed[] $data argument for query to be bound
	* 
	*	@return object db results
	*/
	public static function query($q, $data = array())
	{
		return self::exec($q, $data);
	}

	/**
	* 	Run a raw query and capture time to execute
	*
	*	@param string $q query string
	*	@param mixed[] $data argument for query to be bound
	* 
	*	@return float $query_time
	*	
	*/
	public static function time_query($q, $data = array())
	{
		$querytime_before = microtime(true);
		$r = self::exec($q, $data);
		$querytime_after = microtime(true);

		$query_time = sprintf('%01.4f', $querytime_after - $querytime_before);

		return $query_time;
	}


	/**
	*	Get Last DB Query Error
	*	@return object
	*/
	public static function last_error()
	{
		return self::$error;
	}

	/**
	*	Get Last Insert Id
	*	@return integer self::$c->lastInsertId(); PDO Method
	*/
	public static function last_id()
	{
		return self::$c->lastInsertId();
	}

	/**
	*	Get if Table Exists
	*
	*	@param string $tname table name
	*	
	*	@return boolean [true|false]
	*/
	public static function table_exists($tname)
	{
		$r = self::select("show columns from $tname");
		return ($r !== false);
	}


	/**
	*	Get if column exists
	*	
	*	@param string $tname
	*	@param string $col
	*
	*	@return boolean 
	*/
	public static function col_exists($tname, $col)
	{
		$cols = self::select("show columns from $tname");
		foreach ($cols as $col_info) {
			if ($col == $col_info[0]) {
				return true;
			}
		}
		return false;
	}


	/**
	*	Get Get Table Columns as array
	*
	*	@param string $tname table name
	*	@param boolean $col_names_only return table names as array values without mapping to position
	*
	*	@return string[] 
	*/
	public static function get_cols($tname, $col_names_only = true)
	{
		// Field, Type, Null, Key, Default, Extra
		$cols = self::select("show columns from $tname");
		if ($col_names_only) {
			$temp = array();
			foreach ($cols as $col_info) {
				$temp[] = $col_info[0];
			}
			return $temp;
		}
		return $cols;
	}

	/**
	*	@return string[]
	*/
	public static function get_enum_vals($db, $table, $col)
	{
		$enum = db::select_one("
			select column_type
			from information_schema.columns
			where table_schema = :db && table_name = :table && column_name = :col
		", array(
			'db' => $db,
			'table' => $table,
			'col' => $col
		));

		$vals = substr($enum, strpos($enum, '(\'') + 2);
		$vals = substr($vals, 0, strpos($vals, '\')'));
		$vals = explode("','", $vals);

		return $vals;
	}

	/**
	* Get  
	* @todo is this still used by anything?
	*
	* @return mixed boolean (false) if no match 
	*/
	public static function get_set_vals($table, $col, $num_or_text = 'NUM')
	{
		list($table, $table_def) = db::select_row("show create table $table");
		preg_match("/`$col`\s+set\((.*)\)/", $table_def, $all_vals_match);
		if ($all_vals_match && $all_vals_match[1])
		{
			if (preg_match_all("/'(.*?)'/", $all_vals_match[1], $matches))
			{
				$text_vals = $matches[1];
				if ($num_or_text == 'NUM')
				{
					$num_vals = array();
					for ($i = 0, $count = count($text_vals); $i < $count; ++$i)
					{
						$num_vals[$text_vals[$i]] = 1 << $i;
					}
					return $num_vals;
				}
				else
				{
					return $text_vals;
				}
			}
		}
		return false;
	}

	public static function clear_hooks($type = false)
	{
		if ($type) {
			self::$hooks[$type] = array();
		}
		else {
			self::$hooks = array();
		}
	}

	/**
	 *	register_table_hook method to register user-defined hooks
	 *	eg. delta_meta is an rs class that implements a hooking function to log changes
	 *	Example Usage: db::register_hook('update','delta_meta','update_hook');	
	 *
	 *	@param string $type
	 *	@param string $hook
	 *	@param string $table
	 *
	 *	@return 
	 */
	public static function register_hook($type,$hook,$table){
		$tables = (is_array($table))? $table : array($table);

		foreach($tables as $tbl){
				self::$hooks[$type][] = array('hook'=>$hook,
											  'table'=>$tbl,
										);
		}
	}


	/**
	 *	table_has_hook checks to see if the table has a registered hook
	 *  @todo remove this comment ... -> known also as hook_table_check...	
	 *
	 *	@param string $type
	 * 	@param string $table
	 *
	 *	@return hook callable-func array or false (boolean)
	 */
	public static function table_has_hook($type,$table){
		if (!isset(self::$hooks[$type])) {
			return false;
		}
		foreach(self::$hooks[$type] as $key => $hook_info){
			$compare =& $hook_info['table'];
			if(preg_match("/$compare/",$table)){
				return $hook_info['hook'];
			}
		}
		return false;
	}
}

?>
