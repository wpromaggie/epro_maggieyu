<?php

class mod_service_client_list extends mod_service
{
	public $page_offset, $menu, $is_search;
	
	// defaults to manager, can be overriden by alternate filter options
	public $filter_type;
	
	public function pre_output()
	{
		parent::pre_output();
		$this->display_default = 'index';
		$this->is_search = false;
		$this->filter_type = (array_key_exists('filter_type', $_REQUEST)) ? $_REQUEST['filter_type'] : 'manager';

		$this->qselect = array(
			"account" => array("id", "name", "url", "bill_day", "next_bill_date")
		);
		$this->qwhere = array();
		$this->qdata = array();
		$this->qjoin = false;
		$this->qleft_join = false;


		$this->init_managers();
		$this->init_status();
	}

	public function pre_output_ppc()
	{
		$this->qselect["as_ppc"] = array("actual_budget as budget");
		$this->qselect["ppc_cdl"] = array("mo_spend", "yd_spend", "days_to_date", "days_remaining", "days_in_month");

		$this->qleft_join = array(
			"ppc_cdl" => "account.id = ppc_cdl.account_id"
		);
	}

	public function pre_output_seo()
	{
		// build query
		$this->qselect["u1"] = array("realname as manager");
		$this->qjoin["users as u1"] = "account.manager = u1.id";

		// if we are looking at link builders, we need link builder to exist, join
		$this->qselect["u2"] = array("realname as link_builder");
		$link_builder_table = "users as u2";
		$link_builder_join = "as_seo.link_builder_manager = u2.id";
		if ($_REQUEST['filter_type'] == 'link_builder') {
			$this->qjoin[$link_builder_table] = $link_builder_join." && u2.id = :link_builder_manager";
			$this->qdata["link_builder_manager"] = $this->manager;
		}
		// otherwise, left join
		else {
			$this->qleft_join[$link_builder_table] = $link_builder_join;
		}

		// get link builders, set as alternate filter option
		$link_builders = db::select("
			select distinct u.id, u.realname
			from eppctwo.users u, eac.as_seo as s
			where u.id = s.link_builder_manager
			order by realname asc
		");
		array_unshift($link_builders, array('', '- Select -'));
		
		cgi::add_js_var('alt_filter_options', array(
			'link_builder' => $link_builders
		));
	}

	public function print_service_links()
	{
		$services = account::$org['service'];
		foreach ($services as $service) {
			echo '
				<p>
					<a href="'.$this->href($service).'">'.$service.'</a>
				</p>
			';
		}
	}

	public function display_index()
	{
		if (empty($this->dept) && user::is_admin()) {
			return $this->print_service_links();
		}
		else if (!account::is_service($this->dept)) {
			return feedback::add_error_msg($this->dept.' is not a service, cannot show account list');
		}

		$class = "as_{$this->dept}";
		// some error building query, just create empty array
		if ($this->qerror) {
			$accounts = $class::new_array();
		}
		else {
			$accounts = $class::get_all(array(
				'select' => $this->qselect,
				'join' => $this->qjoin,
				'left_join' => $this->qleft_join,
				'where' => $this->qwhere,
				'data' => $this->qdata,
				'flatten' => true
			));

			if ($this->is_search && $accounts->count() == 0) {
				feedback::add_error_msg('Search returned no results');
			}
		}
		
		cgi::add_js_var('clients', $accounts);
		cgi::add_js_var('dept', $this->dept);
		?>
		<h1>Client List</h1>
		<div>
			<?= $this->ml_page_nav() ?>
		</div>
		<div id="filter">
		
			<div id="manager_filter">
				<label>Manager</label>
				<span></span>
			</div>
			
			<div id="status_filter">
				<label>Status</label>
				<span><?= cgi::html_select('status', $this->stati, $this->status) ?></span>
			</div>
			
			<div id="search_filter">
				<label>Search</label>
				<span><input type="text" id="search" name="search" value="<?= (($this->action == 'action_search') ? $_REQUEST['search'] : '') ?>" /></span>
				<span>
					<input type="submit" id="search_submit" a0="action_search" value="Go" />
					<input type="submit" value="Clear" />
				</span>
			</div>
			
			<div class="clear"></div>
			
		</div>
		<?= $this->dept_overview() ?>
		<div id="w_client_list" dept="<?= $this->dept ?>" ejo></div>
		<input type="hidden" name="selected_manager" value="<?php echo $this->manager; ?>" />
		<input type="hidden" name="filter_type" id="filter_type" value="<?php echo $this->filter_type; ?>" />
		<?php
	}
	
	private function dept_overview()
	{
		$this->call_member('dept_overview_'.$this->dept);
	}
	
	protected function dept_overview_ppc()
	{
		// get company data pull jobs
		util::load_lib('delly');
		$cron_jobs = ppc_lib::get_company_data_pull_jobs();
		$markets = util::get_ppc_markets('ASSOC');
		$ml = '';
		foreach ($cron_jobs as $cj) {
			if (preg_match("/-m (\w)/", $cj->args, $matches)) {
				$job_market = $matches[1];
				$market_display = (array_key_exists($job_market, $markets)) ? $markets[$job_market] : $job_market;
				if ($cj->job->jid) {
					$class = 'running';
					$job_id = $cj->job->jid;
					$job_text = '';
				}
				else {
					$class = '';
					$job_id = '';
					$job_text = 'Pending';
				}
				$ml .= '
					<tr>
						<td>'.$market_display.':</td>
						<td job_id="'.$job_id.'" class="'.$class.'">'.$job_text.'</td>
					</tr>
				';
			}
		}
		echo '
			<fieldset id="ppc_data_pull">
				<legend>Data</legend>
				<table>
					<tbody>
						'.$ml.'
					</tbody>
				</table>
			</fieldset>
		';
	}
	
	public function action_search()
	{
		$this->is_search = true;
		$search = $_POST['search'];
		if (strlen($search) < 3) {
			$this->qerror = true;
			feedback::add_error_msg('Please use at least 3 characters to search');
		}
		else {
			$this->qwhere = array("name like :search");
			$this->qdata = array("search" => "%{$search}%");
		}
	}
	
	private function init_status()
	{
		// set status
		$this->stati = array(
			array('*', ' - All'),
			array('Active', 'Active'),
			array('Inactive', 'Inactive')
		);
		$this->status = util::unempty($_REQUEST['status'], $_SESSION['client_list_'.$this->dept.'_status'], 'Active');
		
		if ($this->status != $_SESSION['client_list_'.$this->dept.'_status']) {
			$_SESSION['client_list_'.$this->dept.'_status'] = $this->status;
		}
		
		// set status query
		if (!$this->is_search) {
			switch ($this->status) {
				// anything
				case ('*'):
					// noop
					break;
				
				// active
				case ('Active'):
					$this->qwhere[] = "account.status in ('On', 'Active')";
					break;
				
				// anything else
				default:
					$this->qwhere[] = "account.status not in ('On', 'Active')";
					break;
			}
		}
	}
	
	private function init_managers()
	{
		$managers = db::select("	
			(
				select distinct u.id, u.realname
				from eppctwo.users u, eac.account a
				where
					a.dept = :dept &&
					u.id = a.manager
			)
			union
			(
				select distinct u.id, u.realname
				from eppctwo.users u
				where
					u.primary_dept = :dept &&
					u.password != ''
			)
			union
			(
				select distinct u.id, u.realname
				from eppctwo.users u, eppctwo.secondary_manager sm, eac.account a
				where
				 	0 &&
					a.dept = :dept &&
					a.id = sm.account_id &&
					sm.user_id = u.id
			)
			order by realname asc
		", array(
			'dept' => $this->dept
		));
		$this->manager = util::unempty($_REQUEST['manager'], $_SESSION['client_list_'.$this->dept.'_manager'], user::$id);
		if ($this->manager != $_SESSION['client_list_'.$this->dept.'_manager']) {
			$_SESSION['client_list_'.$this->dept.'_manager'] = $this->manager;
		}
		if (!$this->is_search && $this->filter_type == 'manager') {
			switch ($this->manager) {
				// all
				case ('*'):
					break;
				
				// unassigned
				case ('#'):
					$this->qwhere[] = "account.manager = 0";
					break;
				
				// specific manager
				default:
					$this->qwhere[] = "
						account.manager = :manager ||
						account.id in (select account_id from eppctwo.secondary_manager where user_id = :manager)
					";
					$this->qdata['manager'] = $this->manager;
					break;
			}
		}
		
		// add manager js var for select
		if (user::is_admin() || user::has_role('Leader', $this->dept)) {
			array_unshift($managers, array('#', ' - Unassigned'));
		}
		array_unshift($managers, array('*', ' - All'));
		array_unshift($managers, array('', '- Select -'));
		
		cgi::add_js_var('managers', $managers);
	}
	
	private function ml_page_nav()
	{
		$links = array();
		if (user::is_admin() || $this->is_user_leader()) {
			if ($this->dept != 'partner') {
				$links[] = '<a href="'.$this->href('add_account/'.$this->dept).'">Add Account</a>';
			}
			$links[] = '<a href="'.$this->href('download_client_list/'.$this->dept).'">Download Client List</a>';
			return implode(' &nbsp; ', $links);
		}
	}
	
	private function download_client_list_get_user_depts($type)
	{
		$user_dept_options = array();
		foreach (account::$org as $org => $org_depts) {
			foreach ($org_depts as $dept) {
				if (user::is_admin() || array_key_exists($dept, user::$roles)) {
					if ($type == 'DEPT_ONLY') {
						$user_dept_options[] = $dept;
					}
					else {
						$user_dept_options[$dept] = $org;
					}
				}
			}
		}
		return $user_dept_options;
	}
	
	private function download_client_list_get_fields()
	{
		// format:
		// key, display, table
		return array(
			array('email'   ,'Email'    ,'contacts'),
			array('name'    ,'Name'     ,'contacts'),
			array('phone'   ,'Phone'    ,'contacts'),
			array('street'  ,'Street'   ,'contacts'),
			array('city'    ,'City'     ,'contacts'),
			array('state'   ,'State'    ,'contacts'),
			array('zip'     ,'Zip'      ,'contacts'),
			array('country' ,'Country'  ,'contacts'),
			array('url'     ,'URL'      ,'account'),
			array('realname','Sales Rep','users')
		);
	}
	
	public function display_download_client_list()
	{
		if (!(user::is_admin() || user::has_role('Leader', $this->dept))) {
			return;
		}
		
		// user submitted form
		if ($_POST) {
			// can get here on localhost or form errors
			list($depts, $fields, $active_only) = util::list_assoc($_POST, 'depts', 'fields', 'active_only');
		}
		// first time to page, initialize
		else {
			$depts = array($this->dept);
			$fields = array('email', 'name');
			$active_only = 1;
		}
		$is_init = (empty($_POST));
		
		$user_dept_options = $this->download_client_list_get_user_depts('DEPT_ONLY');
		$field_options = $this->download_client_list_get_fields();
		
		$ml_dev_dl = '';
		if (util::is_dev()) {
			$ml_dev_dl = '
				<tr>
					<td><label for="do_dl">Do DL</label></td>
					<td><input type="checkbox" id="do_dl" name="do_dl" value="1" /></td>
				</tr>
			';
		}
		?>
		<h1>Download Client List</h1>
		<div id="download_client_list" ejo>
			<a href="<?= $this->href() ?>">Back to Client List</a>
			
			<!--
			<div id="dates_note">
				<p>* Leave dates blank for all time, start date blank for up to end date, end date blank for starting date till present</p>
			</div>
			-->
			
			<table>
				<tbody>
					<!--
					<?php echo cgi::date_range_picker($start_date, $end_date, array('table' => false)); ?>
					-->
					<tr>
						<td>Departments</td>
						<td><?php echo cgi::html_checkboxes('depts', $user_dept_options, $depts, array('separator' => ' &nbsp; ', 'toggle_all' => false)); ?></td>
					</tr>
					<tr>
						<td>Fields</td>
						<td><?php echo cgi::html_checkboxes('fields', $field_options, $fields, array('separator' => ' &nbsp; ', 'toggle_all' => false)); ?></td>
					</tr>
					<tr>
						<td><label for="active_only">Active Only</label></td>
						<td><input type="checkbox" id="active_only" name="active_only" value="1"<?php echo (($active_only) ? ' checked' : ''); ?> /></td>
					</tr>
					<?= $ml_dev_dl ?>
					<tr>
						<td></td>
						<td><input type="submit" a0="action_download_client_list" value="Download" /></td>
					</tr>
				</tbody>
			</table>
			
		</div>
		<?php
	}
	
	public function action_download_client_list()
	{
		list($depts, $fields, $active_only) = util::list_assoc($_POST, 'depts', 'fields', 'active_only');
		if (!$depts) {
			feedback::add_error_msg('Plesase select at least one department');
			return false;
		}
		if (!$fields) {
			feedback::add_error_msg('Plesase select at least one field');
			return false;
		}
		$user_dept_options = $this->download_client_list_get_user_depts('DEPT_AND_ORG');
		if ($depts != '*') {
			$depts = explode("\t", $depts);
		}
		
		// convert all fields to assoc array
		$all_fields = array();
		$tmp_fields = $this->download_client_list_get_fields();
		for ($i = 0; list($key, $display, $table) = $tmp_fields[$i]; ++$i) {
			$all_fields[$key] = array($display, $table);
		}
		
		// build select and order queries and headers for excel
		$selected_fields = ($fields == '*') ? array_keys($all_fields) : explode("\t", $fields);
		$qselect = array();
		$qorder = $selected_fields[0];
		$headers = array();
		foreach ($selected_fields as $field) {
			list($display, $table) = $all_fields[$field];
			
			$qselect[$table][] = $field;
			$headers[] = $display;
			if ($field == 'Name') {
				$qorder = 'name';
			}
		}

		$qwhere = array(
			"contacts.name <> ''",
			"contacts.email <> ''",
			"contacts.email not like '\_%'"
		);
		$qdata = array();
		if ($depts != '*') {
			$qwhere[] = "account.dept in (:depts)";
			$qdata["depts"] = $depts;
		}
		if ($active_only) {
			$qwhere[] = "account.status = 'Active'";
		}

		util::load_lib('sales');
		$list = account::get_all(array(
			'select' => $qselect,
			'distinct' => true,
			'left_join_many' => array(
				"contacts" => "contacts.client_id = account.client_id"
			),
			'left_join' => array(
				"sales_client_info" => "sales_client_info.account_id = account.id",
				"users" => "users.id = sales_client_info.sales_rep"
			),
			'where' => $qwhere,
			'data' => $qdata,
			'order_by' => "{$qorder} asc"
		));

		$data_str = '';
		foreach ($list as $account) {
			$users = $account->users;
			$vals = array();
			// hack if no contact fields were selected
			if ($account->contacts->count() == 0) {
				$account->contacts->push(new contacts());
			}
			foreach ($account->contacts as $contacts) {
				foreach ($selected_fields as $field) {
					list($display, $table) = $all_fields[$field];
					$vals[] = $$table->$field;
				}
			}
			$data_str .= '"'.implode('","', $vals)."\"\n";
		}
		$csv = implode(",", $headers)."\n".$data_str;
		
		if (util::is_dev() && empty($_POST['do_dl'])) {
			echo '<pre>'.$csv.'</pre>';
		}
		else {
			header('Content-Type: text/csv');
			header('Content-Disposition: attachment;filename="client-list_'.date(util::DATE).'.csv"');
			
			echo $csv;
			exit;
		}
	}
	
	public function display_add_account()
	{
		if (!(user::is_admin() || user::has_role('Leader', $this->dept))) {
			return;
		}
		
		$q = $_GET['q'];
		$cl_id = $_POST['add_client_id'];
		
		// we have a client, show form to add them to department
		if ($cl_id) {
			$r_add_client = $this->action_add_client($cl_id);
		}
		
		// we have a query, set search results
		if ($q && !$r_add_client) {
			$ml_search_results = $this->ml_add_client_search_results($q);
		}
		
		?>
		<h1>Add Account</h1>
		<div id="add_client" ejo>
			<a href="<?= $this->href($this->dept) ?>">Back to Client List</a>
			<table id="search_table">
				<tbody>
					<tr>
						<td>Search for client</td>
						<td><input type="text" name="q" id="q" value="<?php echo htmlentities($q); ?>" focus_me=1 /></td>
					</tr>
					<tr>
						<td></td>
						<td><input type="submit" id="add_client_search" value="Submit" /></td>
					</tr>
				</tbody>
			</table>
			<?php echo $ml_search_results; ?>
		</div>
		<?php
	}
	
	private function action_add_client($cl_id)
	{
		$name = $_POST['add_client_name'];
		$is_already_in_department = db::select_one("
			select count(*)
			from eppctwo.clients_{$this->dept}
			where client = '$cl_id'
		");
		
		if ($is_already_in_department)
		{
			feedback::add_error_msg($name.' is already in '.$this->dept);
			return false;
		}
		else
		{
			db::insert("eppctwo.clients_{$this->dept}", array('client' => $cl_id));
			feedback::add_success_msg($name.' succesfully added');
			return true;
		}
	}
	
	private function ml_add_client_search_results($q)
	{
		$clients = db::select("
			select id, name, status
			from eppctwo.clients
			where name like '%".db::escape($q)."%'
			order by name asc
		");
		
		$max_results = 25;
		
		$ci = count($clients);
		if ($ci == 0)
		{
			feedback::add_error_msg('Search did not match any clients');
			return '';
		}
		else if ($ci > $max_results)
		{
			$clients = array_slice($clients, 0, $max_results);
			feedback::add_error_msg('Search returned over '.$max_results.' results, please narrow criteria');
		}
		
		$ml = '';
		for ($i = 0; list($cl_id, $cl_name, $cl_status) = $clients[$i]; ++$i)
		{
			$payments = db::select("
				select pp.type, min(p.date_attributed) min_date, max(p.date_attributed) max_date, sum(pp.amount)
				from client_payment p, client_payment_part pp
				where p.client_id='{$cl_id}' && p.id = pp.client_payment_id
				group by type
				order by type asc
			");
			if ($payments)
			{
				$ml_payments = '';
				for ($j = 0; list($type, $min_date, $max_date, $amount) = $payments[$j]; ++$j)
				{
					$ml_payments .= '<td>'.$type.' ('.util::format_dollars($amount).')</td>';
				}
			}
			else
			{
				$ml_payments = '<td colspan=10>No Payments</td>';
			}
			$ml .= '
				<tr cl_id="'.$cl_id.'" class="'.(($i & 1) ? 'odd' : 'even').'">
					<td>'.($i + 1).'</td>
					<td><input type="submit" class="small_button add" value="Add to '.$this->dept.'" /></td>
					<td class="cl_name">'.$cl_name.'</td>
					<td>'.$cl_status.'</td>
					'.$ml_payments.'
				</tr>
			';
		}
		return '
			<table id="search_results_table">
				<thead>
					<tr>
						<th></th>
						<th></th>
						<th>Name</th>
						<th>Status</th>
						<th colspan=10>Payments</th>
					</tr>
				</thead>
				<tbody>
					'.$ml.'
				</tbody>
			</table>
		';
	}
	
	// func must be callable 
	public function register_filter_alternate_options_func($func)
	{
		$this->filter_alternate_options_func = $func;
	}
}

?>