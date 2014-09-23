<?php
require(__DIR__.'/../rs_sync.php');
require(__DIR__.'/../rs_from_existing.php');

$app = new sync_cgi_app();
$app->act($_REQUEST['act']);

?><html>
<head>
<title>RS Sync</title>
<script type="text/javascript" src="jquery.js"></script>
<script type="text/javascript" src="sync.js"></script>
<link rel="stylesheet" type="text/css" href="sync.css" />
</head>
<body>
<form id="form" method="post">

<?php $app->view($_REQUEST['view']); ?>

<input type="hidden" id="cur_act" name="cur_act" value="<?php echo $app->action; ?>" />
<input type="hidden" id="cur_view" name="cur_view" value="<?php echo $app->view; ?>" />

</form>
</body>
</html>

<?php

class sync_cgi_app
{
	public $action, $view, $msg;
	
	private $files;
	
	// the ways a db column can be changed
	// not related to $action
	// altered and deleted should be before new so new columns are added in the correct place
	private static $action_types = array('altered', 'deleted', 'new');
	
	// the columns in our preview of sync changes
	private static $preview_cols = array('name', 'type', 'action', 'details');
	
	public function __construct()
	{
		db::connect(\rs\MYSQL_HOST, \rs\MYSQL_USER, \rs\MYSQL_PASS);
		$this->set_files();
	}
	
	public function msg()
	{
		if (!$this->msg)
		{
			return;
		}
		?>
		<div id="msg">
			<?php echo $this->msg; ?>
		</div>
		<?php
	}
	
	public function act($action)
	{
		$act_func = 'action_'.$action;
		if (method_exists($this, $act_func))
		{
			$this->action = $action;
			$this->$act_func();
		}
	}
	
	// qs: queries
	// ms: messages
	public $qs, $ms;
	
	public function action_process_changes()
	{
		$sync_tables = $this->get_sync_tables();
		
		// file loop
		$this->qs = array();
		$this->ms = array();
		foreach ($sync_tables as $file_path => $file_tables)
		{
			require($file_path);
			
			// table loop
			foreach ($file_tables as $table)
			{
				list($db) = $table::attrs('db');
				
				// table exists, see if it's altered
				if (db::table_exists("{$db}.{$table}"))
				{
					// check for table overwrite
					if (!empty($_POST["overwrite_table\t{$db}\t{$table}"])) {
						db::exec("drop table {$db}.{$table}");
						$this->do_create_new_table($db, $table);
					}
					else {
						$alterations = $this->get_table_alterations($db, $table);
						$this->do_process_col_changes($db, $table, $alterations['cols']);
						$this->do_process_index_changes($db, $table, $alterations['indexes']);
					}
				}
				// new table!
				else
				{
					$this->do_create_new_table($db, $table);
				}
			}
		}
		$this->do_add_changes_to_sync_log();
		$this->msg = implode("<br />\n", $this->ms);
	}
	
	private function do_create_new_table($db, $table)
	{
		$q = $table::create_table($db, $table, array(
			'create' => false,
			'get_sql' => true
		));
		$this->process_query($q);
	}
	
	private function do_process_col_changes($db, $table, $db_cols)
	{
		// change type loop
		foreach (self::$action_types as $action_type)
		{
			// db column loop
			foreach ($db_cols[$action_type] as $db_col => $db_col_info)
			{
				if (!is_array($db_col_info))
				{
					$db_col_info = array($db_col_info);
				}
				
				// db col change loop
				foreach ($db_col_info as $db_col_info_key => $db_col_info_val)
				{
					$input_key = $this->get_col_input_key($action_type, $db, $table, $db_col, $db_col_info_key);
					if (array_key_exists($input_key, $_POST))
					{
						$func = 'do_get_col_query_'.$action_type;
						$q = $this->$func($input_key, $db, $table, $db_col, $db_col_info_key, $db_col_info_val);
						$this->process_query($q);
					}
				}
			}
		}
	}
	
	private function do_process_index_changes($db, $table, $indexes)
	{
		foreach ($indexes as $index_type => $index_info)
		{
			foreach ($index_info as $action_type => $action_info)
			{
				if ($action_info)
				{
					foreach ($action_info as $index_name => $details)
					{
						$ml_details = '';
						if ($action_type == 'altered')
						{
							$ml_details = '('.implode(',', $details['before']).') &rarr; ('.implode(',', $details['after']).')';
						}
						$input_key = "index\t".$this->get_col_input_key($action_type, $db, $table, $index_name);
						if (array_key_exists($input_key, $_POST))
						{
							switch ($action_type)
							{
								case ('altered'):
									$this->do_delete_index($db, $table, $index_name);
									$this->do_create_index($db, $table, $index_type, $index_name, $details['after']);
									break;
									
								case ('deleted'):
									$this->do_delete_index($db, $table, $index_name);
									break;
								
								case ('new'):
									$this->do_create_index($db, $table, $index_type, $index_name, $details);
									break;
							}
						}
					}
				}
			}
		}
	}
	
	private function do_delete_index($db, $table, $index_name)
	{
		$this->process_query("alter table {$db}.{$table} drop index `{$index_name}`");
	}
	
	private function do_create_index($db, $table, $index_type, $index_name, $details)
	{
		$sql_type = ($index_type == 'uniques') ? 'unique' : 'index';
		$this->process_query("alter table {$db}.{$table} add {$sql_type} (`".implode("`,`", $details)."`)");
	}
	
	private function do_add_changes_to_sync_log()
	{
		// no queries successfully executed
		if (!$this->qs)
		{
			return;
		}
		// create log file if it does not exist
		if (file_exists(\rs\SYNC_LOG_PATH))
		{
			require(\rs\SYNC_LOG_PATH);
			$log = \rs\sync_log::$log;
		}
		else
		{
			$log = array();
		}
		
		$today = date('Y-m-d', $_SERVER['REQUEST_TIME']);
		if (!array_key_exists($today, $log))
		{
			// array merge so most recent are first
			$log = array_merge(array($today => array()), $log);
		}
		// add qs to log
		foreach ($this->qs as $q)
		{
			$log[$today][] = $q;
		}
		
		// write php code representing log to file
		$code = '';
		$i = 0;
		$ci = count($log);
		foreach ($log as $date => $entries)
		{
			$code_date = '';
			for ($j = 0, $cj = count($entries); $j < $cj; ++$j)
			{
				if ($j)
				{
					$code_date .= ',';
				}
				$code_date .= "\n\t\t\t\"".str_replace('"', '\\"', $entries[$j])."\"";
			}
			if ($code)
			{
				$code .= ',';
			}
			$code .= "\n\t\t'{$date}' => array({$code_date}\n\t\t)";
		}
		
		$code = "<?php
namespace rs;

class sync_log
{
	public static \$log = array({$code}
	);
}

?>";
		file_put_contents(\rs\SYNC_LOG_PATH, $code);
	}
	
	private function do_get_col_query_new($input_key, $db, $table, $db_col, $db_col_info_key, $db_col_info_val)
	{
		list($rs_cols) = $table::attrs('cols');
		$col = $rs_cols[$db_col];
		
		// find index of new column in array of columns, set position accordingly
		$col_names = array_keys($rs_cols);
		$col_index = array_search($db_col, $col_names);
		$col_position = ($col_index == 0) ? " first" : " after `".$col_names[$col_index - 1]."`";
		
		return ("alter table {$db}.{$table} add ".$col->get_definition().$col_position);
	}
	
	private function do_get_col_query_altered($input_key, $db, $table, $db_col, $db_col_info_key, $db_col_info_val)
	{
		list($rs_cols) = $table::attrs('cols');
		$col = $rs_cols[$db_col];
		return ("alter table {$db}.{$table} change `{$db_col}` ".$col->get_definition());
	}
	
	private function do_get_col_query_deleted($input_key, $db, $table, $db_col, $db_col_info_key, $db_col_info_val)
	{
		$delete_or_rename = $_POST["{$input_key}\tdelete_or_rename"];
		if ($delete_or_rename == 'delete')
		{
			return ("alter table {$db}.{$table} drop `{$db_col}`");
		}
		else
		{
			list($rs_cols) = $table::attrs('cols');
			$new_col_name = $_POST["{$input_key}\trename_to"];
			$col = $rs_cols[$new_col_name];
			return ("alter table {$db}.{$table} change `{$db_col}` ".$col->get_definition());
		}
	}
	
	private function process_query($q)
	{
		$r = db::exec($q);
		$msg = $q.'.. ';
		if ($r)
		{
			$this->qs[] = $q;
			$this->ms[] = $msg.'Success!';
		}
		else
		{
			$this->ms[] = $msg.'Error: '.db::last_error();
		}
	}
	
	private function get_table_alterations($db, $table)
	{
		$mysql_contents = rs_from_existing::convert_table($db, $table);
		
		// alter class name in code generated from mysql so we can load both classes to compare
		$rs_sync_table_name = '__RS_SYNC_'.$table.'__';
		$mysql_contents = str_replace('class '.$table.' extends', 'class '.$rs_sync_table_name.' extends', $mysql_contents);
		eval($mysql_contents);
		
		return rs_sync::compare_rs_object($rs_sync_table_name, $table);
	}
	
	public function view($view)
	{
		if (!method_exists($this, 'view_'.$view))
		{
			$view = 'index';
		}
		$this->view = $view;
		$view_func = 'view_'.$view;
		
		echo '<div id="'.$view_func.'">';
		$this->$view_func();
		echo '</div>';
	}
	
	private function view_index()
	{
		$ml = '';
		$row_toggle = 1;
		foreach ($this->files as $i => $file_info)
		{
			$path = $file_info['path'];
			$file_name = $file_info['file_name'];
			$file_path = $file_info['file_path'];
			$mtime = $file_info['mtime'];
			
			if (array_key_exists('tables', $file_info))
			{
				$rs_tables = $file_info['tables'];
				$num_tables = count($rs_tables);
				if ($num_tables > 0)
				{
					$row_toggle = ($row_toggle + 1) % 2;
				}
				foreach ($rs_tables as $j => $table)
				{
					if ($j)
					{
						$ml_spacer = '';
						$ml_path = '';
						$ml_file_name = '';
					}
					else
					{
						$ml_spacer = '<tr><td class="spacer" colspan="32"></td></tr>';
						$ml_path = $path;
						$ml_file_name = $file_name.' (<i>'.date('Y-m-d H:i:s', $mtime).'</i>)';
					}
					
					// default when page is requested: check files updated in last 24 hours
					if ($_SERVER['REQUEST_METHOD'] == 'GET')
					{
						$ml_checked = (0 && (time() - $mtime) < 86400) ? ' checked' : '';
					}
					// on post, leave checked what user checked
					else
					{
						$ml_checked = (array_key_exists(str_replace('.', '_', 'TODO'), $_POST)) ? ' checked' : '';
					}
					
					if ($num_tables > 1 && $j == 0)
					{
						$ml_file_checkbox = '<input class="file_cbox" type="checkbox"'.$ml_checked.' />';
					}
					else
					{
						$ml_file_checkbox = '';
					}
					
					$cbox_id = 'table_'.$i.'_'.$j;
					$ml .= '
						'.$ml_spacer.'
						<tr file_block="'.$i.'" class="r'.$row_toggle.'">
							<td class="path_cell">'.$ml_path.'</td>
							<td class="file_cell">'.$ml_file_name.'</td>
							<td>'.$ml_file_checkbox.'</td>
							<td><input class="table_cbox" type="checkbox" id="'.$cbox_id.'" value="'.$path.$file_name."\t".$table.'"'.$ml_checked.' /></td>
							<td><label for="'.$cbox_id.'">'.$table.'</label></td>
						</tr>
					';
				}
			}
		}
		?>
		<h1>RS Table List</h1>
		<?php $this->msg(); ?>
		<table id="rs_table">
			<thead>
				<tr>
					<th>Directory</th>
					<th>File (<i>mtime</i>)</th>
					<th><!-- toggle all --></th>
					<th colspan="2">RS Object</th>
				</tr>
			</thead>
			<tbody>
				<?php echo $ml; ?>
			</tbody>
		</table>
		<div id="submit_buttons">
			<input type="submit" value="Sync Preview" view="preview" />
		</div>
		<div class="clear"></div>
		<?php
	}
	
	private function get_sync_tables()
	{
		$sync_tables = array();
		for ($i = 0; ($table_key = 'table_'.$i) && array_key_exists($table_key, $_POST); ++$i)
		{
			list($file_path, $table) = explode("\t", $_POST[$table_key]);
			$sync_tables[$file_path][] = $table;
		}
		return $sync_tables;
	}
	
	public function view_preview()
	{
		// add selected tables to their file
		$sync_tables = $this->get_sync_tables();
		
		// preview market for our tables
		$ml = '';
		$this->preview_table_count = 0;
		foreach ($sync_tables as $file_path => $tables)
		{
			$ml .= $this->ml_preview_file_tables($file_path, $tables);
		}
		
		$menu = array(
			array('sync.php', 'Sync Home')
		);
		?>
		<h1>RS Sync Preview</h1>
		<?php $this->print_menu($menu); ?>
		<div id="preview_files">
			<?php echo $ml; ?>
		</div>
		<div id="submit_buttons">
			<input type="submit" value="Process Changes" act="process_changes" view="index" />
		</div>
		<div class="clear"></div>
		<?php
	}
	
	private function ml_preview_file_tables($file_path, $sync_tables)
	{
		// require the rs file so table are loaded
		require($file_path);
		
		$ml = '';
		foreach ($sync_tables as $table)
		{
			list($db) = $table::attrs('db');
			
			// table exists, see if it's altered
			if (db::table_exists("{$db}.{$table}"))
			{
				$ml_table = $this->ml_table_alterations($db, $table);
			}
			// new table!
			else
			{
				$ml_table = $this->ml_table_new($db, $table);
			}
			$ml .= '
				<div class="table_div r'.($this->preview_table_count % 2).'"">
					<p class="db_header">DB: '.$db.'</p>
					<p class="table_header">Table: '.$table.'</p>
					'.$ml_table.'
					<input type="hidden" name="table_'.$this->preview_table_count.'" value="'.$file_path."\t".$table.'" />
				</div>
				<div class="clear"></div>
			';
			$this->preview_table_count++;
		}
		return '
			<div class="file_div">
				<h2>File: '.$file_path.'</h2>
				'.$ml.'
			</div>
		';
	}
	
	private function ml_table_alterations($db, $table)
	{
		$alterations = $this->get_table_alterations($db, $table);
		
		// first col for checkbox
		$ml_headers = '<th><input type="checkbox" class="preview_col_toggle_all" /></th>';
		foreach (self::$preview_cols as $col)
		{
			$ml_headers .= '<th class="preview_col_'.$col.'">'.ucwords($col).'</th>';
		}
		
		$overwrite_key = "overwrite_table\t{$db}\t{$table}";
		return '
			<div>
				<input type="checkbox" id="'.$overwrite_key.'" name="'.$overwrite_key.'" value=1 />
				<label for="'.$overwrite_key.'">Overwrite Table</label>
			</div>
			<table class="col_changes_table">
				<thead>
					<tr>
						'.$ml_headers.'
					</tr>
				</thead>
				<tbody>'.
					$this->ml_table_col_alters($db, $table, $alterations['cols']).
					$this->ml_table_index_alters($db, $table, $alterations['indexes']).'
				</tbody>
			</table>
		';
	}
	
	private function ml_table_col_alters($db, $table, $cols)
	{
		$ml = '';
		foreach (self::$action_types as $action_type)
		{
			$ml .= $this->ml_table_col_alters_type($action_type, $db, $table, $cols);
		}
		return $ml;
	}
	
	private function ml_table_col_alters_type($action_type, $db, $table, $db_cols)
	{
		$ml = '';
		// loop over all database columns that have changed
		foreach ($db_cols[$action_type] as $db_col => $db_col_info)
		{
			// convert db_col_info to array
			// for 'new' and 'deleted' action_types db_col_info is just a placeholder anyway
			// 'altered' should already be an array
			// where key is what was altered (size, attributes, etc)
			// and val is an assoc array with keys 'before' and 'after'
			if (!is_array($db_col_info))
			{
				$db_col_info = array($db_col_info);
			}
			
			foreach ($db_col_info as $db_col_info_key => $db_col_info_val)
			{
				$ml_row = '';
				// loop over the columns in our preview table
				foreach (self::$preview_cols as $preview_col)
				{
					// each cell has a default function which can be overridden depending on the type of the change
					$func_base = "preview_col_{$preview_col}_";
					$func_type = $func_base.$action_type;
					$func = (method_exists($this, $func_type)) ? $func_type : $func_base.'default';
					$ml_row .= $this->$func($action_type, $db, $table, $db_col, $db_col_info_key, $db_col_info_val);
				}
				$ml .= '
					<tr>'.$ml_row.'</tr>
				';
			}
		}
		return $ml;
	}
	
	private function preview_col_name_default($action_type, $db, $table, $db_col, $db_col_info_key, $db_col_info_val)
	{
		$input_key = $this->get_col_input_key($action_type, $db, $table, $db_col, $db_col_info_key);
		return $this->preview_col_name_ml($input_key, $db_col);
	}
	
	private function preview_col_name_altered($action_type, $db, $table, $db_col, $db_col_info_key, $db_col_info_val)
	{
		$input_key = $this->get_col_input_key($action_type, $db, $table, $db_col, $db_col_info_key);
		list($rs_cols) = $table::attrs('cols');
		$rs_col = $rs_cols[$db_col];
		
		// if column is checked by default
		$checked = (!(
			// mysql table definitions have size values for numeric columns
			// easy to define tables without specifying a numeric size, so default to unchecked
			($db_col_info_key == 'size' && ($rs_col->is_numeric() || $rs_col->type == 'bool')) ||
			
			// table extends something else and it is an attrs change -> attrs are inherited
			($db_col_info_key == 'attrs' && property_exists($table, 'extends'))
		));
		return $this->preview_col_name_ml($input_key, $db_col, $checked);
	}
	
	private function preview_col_name_ml($input_key, $db_col, $is_checked = true)
	{
		$ml_checked = ($is_checked) ? ' checked' : '';
		return '
			<td><input type="checkbox" class="col_change_cbox" name="'.$input_key.'" id="'.$input_key.'" value=1'.$ml_checked.' /></td>
			<td><label for="'.$input_key.'">'.$db_col.'</label></td>
		';
	}
	
	private function preview_col_type_default($action_type, $db, $table, $db_col, $db_col_info_key, $db_col_info_val)
	{
		return '<td>Column</td>';
	}
	
	private function preview_col_action_default($action_type, $db, $table, $db_col, $db_col_info_key, $db_col_info_val)
	{
		return '<td>'.ucwords($action_type).'</td>';
	}
	
	private function preview_col_details_default($action_type, $db, $table, $db_col, $db_col_info_key, $db_col_info_val)
	{
		return '<td></td>';
	}
	
	private function preview_col_action_deleted($action_type, $db, $table, $db_col, $db_col_info_key, $db_col_info_val)
	{
		$select_key = $this->get_col_input_key($action_type, $db, $table, $db_col, $db_col_info_key)."\tdelete_or_rename";
		return '
			<td>
				<select class="delete_or_rename" id="'.$select_key.'" name="'.$select_key.'">
					<option value="delete">Delete</option>
					<option value="rename">Rename</option>
				</select>
			</td>
		';
	}
	
	private function preview_col_details_altered($action_type, $db, $table, $db_col, $db_col_info_key, $db_col_info_val)
	{
		return '<td>'.$db_col_info_key.': '.$db_col_info_val['before'].' &rarr; '.$db_col_info_val['after'].'</td>';
	}
	
	private function preview_col_details_deleted($action_type, $db, $table, $db_col, $db_col_info_key, $db_col_info_val)
	{
		$rename_key = $this->get_col_input_key($action_type, $db, $table, $db_col, $db_col_info_key)."\trename_to";
		return '
			<td>
				<select class="rename_to_col_select" id="'.$rename_key.'" name="'.$rename_key.'"></select>
			</td>
		';
	}
	
	private function get_col_input_key($action_type, $db, $table, $col, $col_detail = null)
	{
		$input_key = "{$action_type}\t{$db}\t{$table}\t{$col}";
		if ($action_type == 'altered')
		{
			$input_key .= "\t{$col_detail}";
		}
		return $input_key;
	}
	
	private function ml_table_index_alters($db, $table, $indexes)
	{
		$ml = '';
		foreach ($indexes as $index_type => $index_info)
		{
			foreach ($index_info as $action_type => $action_info)
			{
				if ($action_info)
				{
					foreach ($action_info as $index_name => $details)
					{
						$ml_details = '';
						if ($action_type == 'altered')
						{
							$ml_details = '('.implode(',', $details['before']).') &rarr; ('.implode(',', $details['after']).')';
						}
						else if ($action_type == 'new')
						{
							$ml_details = 'cols=['.implode(', ', $details).']';
						}
						else if ($action_type == 'deleted')
						{
							$ml_details = 'cols=['.implode(', ', $details).']';
						}
						$input_key = "index\t".$this->get_col_input_key($action_type, $db, $table, $index_name);
						$ml .= '
							<tr>
								<td><input type="checkbox" class="col_change_cbox" name="'.$input_key.'" id="'.$input_key.'" value=1'.$ml_checked.' /></td>
								<td><label for="'.$input_key.'">'.$index_type.'-'.$index_name.'</label></td>
								<td>'.ucwords($index_type).'</td>
								<td>'.ucwords($action_type).'</td>
								<td>'.$ml_details.'</td>
							</tr>
						';
					}
				}
			}
		}
		return $ml;
	}
	
	private function ml_table_new($db, $table)
	{
		$table_key = "create_table\t{$db}\t{$table}";
		return '
			<input type="checkbox" id="'.$table_key.'" name="'.$table_key.'" value=1 checked />
			<label for="'.$table_key.'">Create New Table</label>
		';
	}
	
	private function set_files()
	{
		$this->files = array();
		foreach (\rs\env::$sync_dirs as $path)
		{
			$dir = @opendir($path);
			if ($dir)
			{
				while (($file = readdir($dir)) !== false)
				{
					if (strpos($file, '.rs.') !== false)
					{
						$file_path = $path.$file;
						$this->files[] = array(
							'path' => $path,
							'file_name' => $file,
							'file_path' => $file_path,
							'mtime' => filemtime($file_path)
						);
					}
				}
				closedir($dir);
			}
		}
		usort($this->files, array($this, 'mtime_cmp'));
		$prev_path = '';
		foreach ($this->files as $i => $file_info)
		{
			preg_match_all("/^class (.*?) extends/m", file_get_contents($file_info['file_path']), $matches);
			$rs_tables = $matches[1];
			if ($rs_tables)
			{
				sort($rs_tables);
				$this->files[$i]['tables'] = $rs_tables;
			}
		}
	}
	
	private function print_menu($menu)
	{
		$ml = '';
		for ($i = 0; list($href, $text) = $menu[$i]; ++$i)
		{
			$ml .= '<a class="page_menu_link" href="'.$href.'">'.$text.'</a>';
		}
		?>
		<div id="page_menu_div">
			<?php echo $ml; ?>
		</div>
		<?php
	}
	
	public function mtime_cmp($a, $b)
	{
		return ($b['mtime'] - $a['mtime']);
	}
	
	private function dbg()
	{
		$args = func_get_args();
		echo "<pre>\n";
		foreach ($args as $arg)
		{
			echo '(';
			if (is_null($arg))
			{
				echo 'NULL';
			}
			else if (is_scalar($arg))
			{
				echo $arg;
			}
			else if (is_array($arg))
			{
				print_r($arg);
			}
			else
			{
				var_dump($arg);
			}
			echo ')';
		}
		echo "</pre>\n";
	}
}

?>