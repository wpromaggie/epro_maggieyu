<?php

class mod_product_queues extends mod_product
{
	public function pre_output()
	{
		parent::pre_output();
		cgi::add_js_var('g_date_options', array(array('clear', 'Clear')));
		$this->display_default = 'new_orders';
	}
	
	public function get_page_menu()
	{
		return array(
			array('new_orders', 'New Orders'),
			array('recent_orders', 'Recent Orders'),
			array('recent_orders_sbe', 'SB Express'),
			array('updates', 'Updates'),
			array('billing_failures', 'Billing Failures'),
			array('ql_3_days', 'QL 3 Days'),
			array('ql_7_days', 'QL 7 Days')
		);
	}
	
	public function head()
	{
		$pages = array_slice(g::$pages, 1);
		if (empty(g::$p4))
		{
			g::$p4 = $this->display_default;
			$pages[] = $this->display_default;
		}
		echo '
			<h1>
				'.implode(' :: ', array_map(array('util', 'display_text'), $pages)).'
			</h1>
			'.$this->page_menu($this->get_page_menu(), 'product/queues/').'
		';
		cgi::add_js_var('qtype', g::$p4);
	}
	
	public function init_recent_orders_form()
	{
		list($this->start_date, $this->end_date, $this->manager, $this->status) = util::list_assoc($_POST, 'start_date', 'end_date', 'manager', 'status');

		// dates
		if (empty($this->start_date))
		{
			$this->end_date = date(util::DATE);
			$this->start_date = date(util::DATE, strtotime("$this->end_date -7 days"));
		}
		if ($this->end_date < $this->start_date)
		{
			$this->end_date = $this->start_date;
		}
		$q[] = "account.signup_dt between '{$this->start_date} 00:00:00' and '{$this->end_date} 23:59:59'";

		// manager
		$manager_options = product::get_manager_options();
		array_unshift($manager_options, array('', 'All'));
		// init with user if user is a manager
		if (empty($_POST)) {
			foreach ($manager_options as $option) {
				if ($option[0] == user::$id) {
					$this->manager = user::$id;
					break;
				}
			}
		}
		if (!empty($this->manager)) {
			$q[] = "account.manager = '{$this->manager}'";
		}

		// status
		$status_options = account::$status_options;
		if (empty($_POST)) {
			$this->status = array('New', 'Active');
		}
		else {
			$this->status = ($this->status == '*') ? '*' : explode("\t", $this->status);
		}
		if ($this->status != '*') {
			$q[] = "account.status in ('".implode("','", $this->status)."')";
		}

		$this->q_common = implode(" && ", $q);
		?>
		<table>
			<tbody>
				<?= cgi::date_range_picker($this->start_date, $this->end_date, array('table' => false)) ?>
				<tr>
					<td>Manager</td>
					<td><?= cgi::html_select('manager', $manager_options, $this->manager) ?></td>
				</tr>
				<tr>
					<td>Status</td>
					<td><?= cgi::html_checkboxes('status', $status_options, $this->status, array('separator' => ' &nbsp; ')) ?></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	public function display_recent_orders()
	{
		$this->init_recent_orders_form();
		$this->print_queue($this->q_common);
	}
	
	public function display_recent_orders_sbe()
	{
		$this->init_recent_orders_form();
		$this->print_queue("
			{$this->q_common} &&
			account.dept = 'sb' &&
			account.plan = 'Express'
		");
	}
	
	public function display_new_orders()
	{
		$this->print_queue("account.status = 'New'");
		//echo '<input type="submit" a0="action_new_orders_clear_incomplete" value="Clear Incomplete" />'."\n";
	}
	
	public function display_ql_3_days()
	{
		$this->print_ql_day_queue('3');
	}

	public function display_ql_7_days()
	{
		$this->print_ql_day_queue('7');
	}

	private function print_ql_day_queue($days)
	{
		$cutoff = date(util::DATE_TIME, time() - ($days * 86400));
		$this->print_queue("
			account.status = 'Active' &&
			ap_ql.is_{$days}_day_done = 0 &&
			(account_note.dt < '$cutoff' && account_note.note = 'Activated')
		", array(
			'show_done' => true,
			'join' => array(
				'ap_ql' => "product.id = ap_ql.id",
				'account_note' => "product.id = account_note.ac_id"
			)
		));
	}
	
	private function print_queue($where, $opts = array())
	{
		util::set_opt_defaults($opts, array(
			'show_done' => false,
			'join' => false
		));
		$join = array("contacts" => "account.client_id = contacts.client_id");
		if ($opts['join']) {
			$join = array_merge($join, $opts['join']);
		}
		$accounts = product::get_all(array(
			'select' => array(
				"account" => array("id", "dept", "url", "signup_dt as signup", "status", "manager", "plan", "partner", "source", "subid"),
				"product" => array("oid"),
				"contacts" => array("name", "email"),
				"users" => array("realname as rep")
			),
			'left_join' => array(
			    "users" => "account.sales_rep = users.id"
			), 
			'join' => $join,
			'where' => $where,
			'order_by' => "account.signup_dt desc, product.oid desc"
		));
		cgi::add_js_var('show_done', $opts['show_done']);
		cgi::add_js_var('accounts', $accounts);
		echo '<div id="w_queue"></div>';
	}
	
	public function action_ql_3_days_done_submit()
	{
		$this->do_ql_day_queue_done('3');
	}

	public function action_ql_7_days_done_submit()
	{
		$this->do_ql_day_queue_done('7');
	}

	private function do_ql_day_queue_done($days)
	{
		ap_ql::update_all(array(
			'set' => array("is_{$days}_day_done" => 1),
			'where' => "id = :id",
			'data' => array('id' => $_POST['done_id'])
		));
		feedback::add_success_msg("{$days} Day Done");
	}

	public function action_new_orders_clear_incomplete()
	{
		db::update("eppctwo.{$this->account_table}", array('status' => 'Incomplete'), "status = 'New' && oid = ''");
		feedback::add_success_msg('Incomplete Cleared');
	}
	
	public function display_billing_failures()
	{
		$bfs = db::select("
			select a.id id, a.dept, a.url, c.name, c.email, b.details, b.first_fail, b.last_fail, b.num_fails, b.last_contact, b.num_contacts
			from eac.account a, eppctwo.sbs_billing_failure b, eppctwo.contacts c
			where
				a.is_billing_failure &&
				a.status in ('Active', 'NonRenewing') &&
				a.id = b.account_id &&
				a.client_id = c.client_id
			order by first_fail desc
		");
		
		$ml = '';
		for ($i = 0; list($aid, $dept, $url, $name, $email, $details, $first_fail, $last_fail, $num_fails, $last_contact, $num_contacts) = $bfs[$i]; ++$i)
		{
			$ml .= '
				<tr>
					<td>'.($i + 1).'</td>
					<td>'.strtoupper($dept).'</td>
					<td><a href="'.cgi::href('account/product/'.$dept.'/billing?aid=').$aid.'">'.$url.'</a></td>
					<td>'.$name.'</td>
					<td>'.$email.'</td>
					<td>'.$details.'</td>
					<td>'.$first_fail.'</td>
					<td>'.$last_fail.'</td>
					<td>'.$num_fails.'</td>
					<td>'.$last_contact.'</td>
					<td>'.$num_contacts.'</td>
				</tr>
			';
		}
		?>
		<table id="q_table" ejo>
			<thead id="q_thead">
				<tr>
					<th></th>
					<th>Dept</th>
					<th>URL</th>
					<th>Name</th>
					<th>Email</th>
					<th>Details</th>
					<th>First Fail</th>
					<th>Last Fail</th>
					<th>Num Fails</th>
					<th>Last Contact</th>
					<th>Num Contacts</th>
				</tr>
			</thead>
			<tbody id="q_tbody">
				<?php echo $ml; ?>
			</tbody>
		</table>
		<?php
	}
	
	protected function do_done_submit($updates)
	{
		$url_id = $_POST['done_id'];
		$url = db::select_one("select url from eppctwo.{$this->account_table} where id = '$url_id'");
		db::update("eppctwo.{$this->account_table}", $updates, "id = {$url_id}");
		feedback::add_success_msg("$url done");
	}
	
	public function display_updates()
	{
		$updates = db::select("
			select a.id aid, a.dept, u.id _id, concat('<a href=\"\" target=\"_blank\" class=\"update_url\">', a.url, '</a>') url, c.name, c.email, u.dt, u.type, u.data
			from eac.account a, eppctwo.contacts c, eppctwo.sbs_client_update u
			where
				u.users_id = 0 &&
				a.status in ('Active', 'NonRenewing') &&
				a.id = u.account_id &&
				a.client_id = c.client_id
		", 'ASSOC');
		
		cgi::add_js_var('updates', $updates);
		echo '<table id="updates_table"></table>'."\n";
	}
	
	public function action_updates_done_submit()
	{
		sbs_lib::client_update_done($_POST['done_id']);
	}
}

?>