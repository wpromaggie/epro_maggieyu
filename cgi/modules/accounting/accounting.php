<?php

class mod_accounting extends module_base
{
	// the departments the user has accounting access to and currently selected department
	private $departments, $department;
	
	private $pay_methods, $pay_method;
	
	// attributed or received, corresponding db column, and which dates to show
	private $date_type, $date_type_col, $do_show_both_date_types;
	
	private $do_show_sales_reps;
	
	/**
	 * get_menu()
	 * Set menu options
	 * @return void
	 */
	public function get_menu()
	{
		return new Menu(
			array(
				new MenuItem('Agency'       ,'index'     , array('role' => 'Leader')),
				new MenuItem('SBS'          ,'sbs'       , array('role' => 'Leader')),
				new MenuItem('PPC Spend'    ,'ppc_spend' , array('role' => 'Leader')),
				new MenuItem('LP Checker'   ,'lp_checker', array('role' => 'Leader')),
				new MenuItem('Search'       ,'search'    , array('role' => 'Billing Search')),
				new MenuItem('Payment Types','types'     , array('role' => 'Leader'))
			),
			'accounting'
		);
	}
	
	/**
	 * pre_output()
	 * set page view defaults 
	 * @return void
	 */
	public function pre_output()
	{
		// gross we need better user access blabhlief
		if ($this->is_user_leader()) {
			$this->display_default = 'index';
		}
		else if (user::has_role('Billing Search')) {
			$this->display_default = 'search';
		}
	}

	/**
	 * pre_output_index()
	 * 
	 */
	public function pre_output_index()
	{
		util::load_lib('as');
		
		$this->manager_depts = array(
			'ppc' => 1,
			'seo' => 1,
			'smo' => 1,
			'email' => 1
		);
		
		$this->set_department();
		$this->set_pay_method();
		$this->set_date_type();
		$this->do_show_sales_reps = $_POST['do_show_sales_reps'];
	}
	
	public function pre_output_search()
	{
		if (!user::has_role('Billing Search')) {
			cgi::redirect('');
		}
	}

	public function display_index()
	{
		list($start_date, $end_date) = util::list_assoc($_POST, 'start_date', 'end_date');
		if (empty($start_date))
		{
			$end_date = date(util::DATE);
			$start_date = substr($end_date, 0, 7).'-01';
		}
		
		$ml_manager_selects = '';
		$dept_managers = users::get_all(array(
			'select' => array(
				"users" => array("id as value", "realname as text"),
				"account" => array("dept")
			),
			'distinct' => true,
			'join' => array("account" => "account.manager = users.id && account.division = 'service' && account.status = 'Active'"),
			'flatten' => true,
			'key_col' => 'dept',
			'key_grouped' => true
		))->to_array();
		foreach ($dept_managers as $dept => $managers) {
			array_unshift($managers, array('', ' - Select - '));
			$input_name = 'manager_'.$dept;
			$ml_manager_selects .= '
				<tr>
					<td>'.ucwords($dept).' Manager</td>
					<td>'.cgi::html_select($input_name, $managers, $_POST[$input_name], array('class' => 'manager_select')).'</td>
				</tr>
			';
		}
		
		$manager_query = $this->get_manager_query();
		$department_query = $this->get_department_query();
		$pay_method_query = $this->get_pay_method_query();
		
		$date_select = ($this->do_show_both_date_types) ? "p.date_attributed attributed, p.date_received received " : "p.{$this->date_type_col} ".strtolower($this->date_type);

		// new, join in php
		$payments = db::select("
			select p.id pid, p.client_id as cl_id, p.amount total, {$date_select}, p.notes, group_concat(pp.type separator '\t') part_types, group_concat(pp.amount separator '\t') part_amounts
			from eppctwo.client_payment p, eppctwo.client_payment_part pp
			where
				".(($manager_query) ? "$manager_query && " : '')."
				".(($department_query) ? "$department_query && " : '')."
				".(($pay_method_query) ? "$pay_method_query && " : '')."
				p.{$this->date_type_col} between '$start_date' and '$end_date' &&
				p.id = pp.client_payment_id
			group by p.id
		", 'ASSOC');

		// get unique client ids
		$cl_ids = array();
		foreach ($payments as &$p) {
			$cl_ids[$p['cl_id']] = 1;
		}
		$cl_ids = array_keys($cl_ids);

		$ac_info = db::select("
			select a.client_id, a.dept, a.id, a.name, u.realname as manager
			from eac.account a
			left join 
				eppctwo.users u on a.manager = u.id
			where
				a.client_id in (:cl_ids)
		", array(
			"cl_ids" => $cl_ids
		), 'ASSOC', array('client_id'), 'dept');

		// set first payments by client id, payment type
		$first_last_payments = db::select("
			select cpp.client_id, cpp.type, min(cp.{$this->date_type_col}) min_date, max(cp.{$this->date_type_col}) max_date
			from client_payment cp, client_payment_part cpp
			where
				cp.client_id in (:cl_ids) &&
				cp.id = cpp.client_payment_id
			group by cpp.client_id, cpp.type
		", array(
			"cl_ids" => $cl_ids
		), 'NUM', 0, 1);
		
		if ($this->do_show_sales_reps) {
			$sales_reps = db::select("
				select a.client_id, group_concat(u.realname separator '\t') sales_reps
				from eac.account a
				join eppctwo.sales_client_info s on
					a.id = s.account_id
				join eppctwo.users u
					on u.id = s.sales_rep
				where
					a.client_id in (:cl_ids)
				group by a.client_id
			", array(
				"cl_ids" => $cl_ids
			), 'NUM', 0);

			// tie in sales info
			foreach ($payments as &$p) {
				$cl_id = $p['cl_id'];
				if (isset($sales_reps[$cl_id])) {
					$cl_reps = array_unique(explode("\t", $sales_reps[$cl_id]));
					$p['sales_reps'] = implode(', ', $cl_reps);
				}
			}
		}

		cgi::add_js_var('ac_info', $ac_info);
		cgi::add_js_var('manager_depts', $this->manager_depts);
		cgi::add_js_var('payments', $payments);
		cgi::add_js_var('first_last_payments', $first_last_payments);
		cgi::add_js_var('type_to_dept', client_payment_part::$part_types);
		cgi::add_js_var('depts', $this->departments);
		cgi::add_js_var('manager', $manager);
		
		?>
		<table id="payments_form" ejo>
			<tbody>
				<?php echo cgi::date_range_picker($start_date, $end_date, array('table' => false)); ?>
				<tr>
					<td>Date Type</td>
					<td>
						<?php echo cgi::html_radio('date_type', array('Attributed', 'Received'), util::unempty($this->date_type, 'Attributed'), array('separator' => ' &nbsp;')); ?>
						, <label for="do_show_both_date_types">Show Both</label>
						<input type="checkbox" id="do_show_both_date_types" name="do_show_both_date_types"<?php echo (($this->do_show_both_date_types) ? ' checked' : ''); ?> />
						<div class="clr"></div>
					</td>
				</tr>
				<?php echo $ml_manager_selects; ?>
				<tr>
					<td>Time Period</td>
					<td><?php echo cgi::html_radio('time_period', array('Off', 'All', 'Yearly', 'Quarterly', 'Monthly', 'Weekly'), util::unempty($_POST['time_period'], 'Off'), array('separator' => ' &nbsp;')); ?></td>
				</tr>
				<?php $this->print_department_options(); ?>
				<?php $this->print_pay_method_options(); ?>
				<?php $this->print_sales_rep_select(); ?>
				<tr>
					<td></td>
					<td><input type="submit" value="Submit" /></td>
				</tr>
				<tr id="highlight_dates_tr">
					<td><label for="highlight_dates">Highlight Dates</label></td>
					<td>
						<input type="checkbox" id="highlight_dates" name="highlight_dates" value="1"<?php echo (($_POST['highlight_dates']) ? ' checked' : ''); ?> />
						<span id="highlight_dates_legend">
							<span>Legend:</span>
							<span class="first_payment">First Payment</span>
							<span class="final_payment">Final Payment</span>
						</span>
					</td>
				</tr>
			</tbody>
		</table>
		<div id="payments_wrapper" ejo>
			<table id="payments"></table>
		</div>
		<?php
	}
	
	public function display_search()
	{
		?>
		<h1>Search</h1>
		<table>
			<tbody>
				<tr>
					<td>Billing Name</td>
					<td><input type="text" id="billing_name" name="billing_name" value="<?php echo $_POST['billing_name']; ?>" focus_me="1" />
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" a0="action_search_submit" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		<?php
		$this->print_search_results();
	}
	
	public function action_search_submit()
	{
		util::load_lib('billing');

		$this->search_results = db::select("
			select cc.foreign_id, cx.client_id, cc.name, cc.country, cc.zip, cc.cc_number, cc.cc_exp_month, cc.cc_exp_year
			from eppctwo.ccs cc
			left join eppctwo.cc_x_client cx on cx.cc_id = cc.id
			where cc.foreign_table in ('clients', '') && cc.name like :name
			order by name asc
		", array('name' => "%{$_POST['billing_name']}%"));
	}
	
	private function print_search_results()
	{
		if (!$this->search_results) {
			if (isset($this->search_results)) {
				feedback::add_error_msg('No clients matching billing name: '.$_POST['billing_name']);
			}
			// no search run, nothing to do
			else {
			}
			return;
		}
		$ml = '';
		for ($i = 0; list($ccs_cl_id, $cx_cl_id, $name, $country, $zip, $cc_crypt, $mo, $yr) = $this->search_results[$i]; ++$i) {
			$cl_id = ($cx_cl_id) ? $cx_cl_id : $ccs_cl_id;
			$ml .= '
				<tr>
					<td>'.($i + 1).'</td>
					<td><a href="" class="billing_name" cl_id="'.$cl_id.'">'.$name.'</a></td>
					<td>'.$cl_id.'</td>
					<td>'.$country.'</td>
					<td>'.$zip.'</td>
					<td>'.billing::cc_obscure_number(util::decrypt($cc_crypt)).'</td>
					<td>'.(($mo && $yr) ? $mo.'/'.$yr : '').'</td>
				</tr>
			';
		}
		?>
		<table>
			<thead>
				<tr>
					<td></td>
					<td>Name</td>
					<td>CID</td>
					<td>Country</td>
					<td>Zip</td>
					<td>CC Num</td>
					<td>CC Exp</td>
				</tr>
			</thead>
			<tbody id="search_results_tbody" ejo>
				<?php echo $ml; ?>
			</tbody>
		</table>
		<?php
	}
	
	private function set_date_type()
	{
		$this->date_type = util::unempty($_POST['date_type'], 'Attributed');
		$this->date_type_col = ($this->date_type == 'Attributed') ? 'date_attributed' : 'date_received';
		$this->do_show_both_date_types = $_POST['do_show_both_date_types'];
	}
	
	private function set_department()
	{
		if ($this->is_user_leader())
		{
			$this->departments = array_filter(array_keys(array_flip(client_payment_part::$part_types)));
			$this->department = util::unempty($_POST['department'], '*');
		}
		else
		{
			$dept_to_type = array_flip(client_payment_part::$part_types);
			$this->departments = array();
			$this->department = $_POST['department'];
			$is_department_set = (!empty($this->department));
			foreach (user::$guilds as $department => $roles)
			{
				foreach ($roles as $role)
				{
					if ($role == 'Leader' && array_key_exists($department, $dept_to_type))
					{
						if (($department == user::$guild && !$is_department_set) || !$this->department)
						{
							$this->department = $department;
						}
						$this->departments[] = array($department, $department);
					}
				}
			}
			// not a member Leader of any guilds, should not be in accounting
			if (empty($this->departments))
			{
				cgi::redirect('');
			}
		}
		sort($this->departments);
	}
	
	private function set_pay_method()
	{
		if ($this->is_user_leader()) {
			$this->pay_methods = client_payment::get_enum_vals('pay_method');
			$this->pay_method = util::unempty($_POST['pay_method'], '*');
		}
		else {
			$this->pay_methods = null;
			$this->pay_method = null;
		}
	}
	
	private function get_manager_query()
	{
		foreach ($this->manager_depts as $dept => $ph) {
			$input_name = 'manager_'.$dept;
			$manager = $_POST[$input_name];
			if ($manager) {
				$manager_clients = db::select("
					select distinct client_id
					from eac.account
					where
						dept = :dept &&
						manager = :manager
				", array(
					"dept" => $dept,
					"manager" => $manager
				));
				return "p.client_id in ('".implode("','", $manager_clients)."')";
			}
		}
		return '';
	}
	
	private function get_department_query(){
		if ($this->department == '*'){
			return '';
		}else{
			$types = array();
			foreach (explode("\t", $this->department) as $dept){
				$types = array_merge($types, client_payment_part::get_types_from_department($dept));
			}

			if($types){
				//e($types);
				return "pp.type in ('".implode("','", $types)."')";
			}else{
				return '';
			}
		}
	}
	
	private function get_pay_method_query()
	{
		if (!$this->pay_method || $this->pay_method == '*')
		{
			return '';
		}
		else
		{
			return "p.pay_method in ('".str_replace("\t", "','", $this->pay_method)."')";
		}
	}
	
	private function print_department_options()
	{
		// don't bother showing checkboxes for non-leaders with just one dept
		if (count($this->departments) == 1)
		{
			return;
		}
		else
		{
			echo '
				<tr>
					<td>Department</td>
					<td>'.cgi::html_checkboxes('department', $this->departments, $this->department, array('separator' => ' &nbsp; ')).'</td>
				</tr>
			';
		}
	}
	
	private function print_pay_method_options()
	{
		if (!$this->pay_methods)
		{
			return;
		}
		else
		{
			echo '
				<tr>
					<td>Pay Method</td>
					<td>'.cgi::html_checkboxes('pay_method', $this->pay_methods, $this->pay_method, array('separator' => ' &nbsp; ')).'</td>
				</tr>
			';
		}
	}
	
	private function print_sales_rep_select()
	{
		if ($this->is_user_leader())
		{
			echo '
				<tr>
					<td><label for="do_show_sales_reps">Show Sales Reps</label></td>
					<td><input type="checkbox" id="do_show_sales_reps" name="do_show_sales_reps"'.(($this->do_show_sales_reps) ? ' checked' : '').' /></td>
				</tr>
			';
		}
		else
		{
			echo '';
		}
	}
	
	public function ajax_get_account_links()
	{
		util::load_lib('as', 'sbs');

		$cl_id = $_POST['cl_id'];
		$as_links = as_lib::get_client_department_links($cl_id, array('do_include_other_links' => false));
		$sbs_links = sbs_lib::get_client_department_links($cl_id, array('do_include_empty' => false));

		if (!$as_links && !$sbs_links) {
			$ml = "Could not find any accounts";
		}
		else {
			$ml = $as_links.(($as_links && $sbs_links) ? ' &bull; ' : '').$sbs_links;
		}
		$r = array('html' => $ml);
		if (!user::has_module_observer_access('account')) {
			$r['plain_text'] = 1;
		}
		echo json_encode($r);
	}

	public function display_lp_checker()
	{
		?>
		<h2>LinkPoint Checker</h2>
		<p>
			Upload a LinkPoint transactions csv,
			get back a list of all agency payments
			marked as received over the date range
			that do not appear in linkpoint
		</p>
		<table>
			<tbody>
				<tr>
					<td><input type="file" name="lp_data" /></td>
				</tr>
				<tr>
					<td><input type="submit" a0="action_lp_checker_submit" /></td>
				</tr>
			</tbody>
		</table>
		<?= $this->ml_missing ?>
		<?php
	}

	public function action_lp_checker_submit()
	{
		$path = $_FILES['lp_data']['tmp_name'];
		$h = fopen($path, 'rb');
		if ($h === false) {
			return feedback::add_error_msg('Could not read file');
		}
		$headers = fgetcsv($h);
		$expected_headers = array(
			'Store ID',
			'Order #',
			'Date',
			'User ID',
			'Type',
			'PayerAuth',
			'Invoice #',
			'PO #',
			'Card/Route Number',
			'Exp. Date',
			'Approval',
			'Amount',
			'Approved Amount'
		);
		$missing_headers = array_diff($expected_headers, $headers);
		if ($missing_headers) {
			return feedback::add_error_msg('Missing headers: '.implode(', ', $missing_headers));
		}
		$extra_headers = array_diff($headers, $expected_headers);
		if ($extra_headers) {
			return feedback::add_error_msg('Unexpected headers: '.implode(', ', $extra_headers));
		}
		$num_headers = count($expected_headers);
		$oids = array();
		$min_date = '9999-12-31';
		$max_date = '0000-00-00';
		for ($i = 0; ($data = fgetcsv($h)) !== FALSE; ++$i)  {
			if (count($data) != $num_headers) {
				continue;
			}
			list($sid, $oid, $date, $uid, $type, $auth, $inum, $ponum, $card, $exp, $approval, $amount, $approved_amount) = $data;
			$oids[$oid] = $approved_amount;
			$lp_date = date(util::DATE, strtotime($date));
			if ($lp_date > $max_date) {
				$max_date = $lp_date;
			}
			if ($lp_date < $min_date) {
				$min_date = $lp_date;
			}
		}
		fclose($h);

		db::select($client_payments, "
			select id, client_id, fid, amount
			from eppctwo.client_payment
			where
				date_received between '$min_date' and '$max_date' &&
				pay_method = 'cc'
		");
		$problem_count = 0;
		$ml = '';
		$total_diff = 0;
		for ($i = 0, $ci = count($client_payments); $i < $ci; ++$i) {
			list($pid, $cid, $fid, $amount) = $client_payments[$i];
			if ($fid) {
				if (!array_key_exists($fid, $oids)) {
					db::select($pps, "
						select type
						from eppctwo.client_payment_part
						where client_payment_id = '$pid'
					");
					$dept_counts = array();
					foreach ($pps as $ptype) {
						$dept = client_payment_part::$part_types[$ptype];
						$dept_counts[$dept]++;
					}
					asort($dept_counts);
					end($dept_counts);
					$max_dept = key($dept_counts);
					if (empty($max_dept)) {
						$max_dept = 'seo';
					}
					$href = cgi::href($max_dept.(($max_dept == 'ppc') ? '' : '/client').'/billing/edit_payment?cl_id='.$cid.'&pid='.$pid);

					$ml .= '
						<tr>
							<td>'.(++$problem_count).'</td>
							<td>Missing</td>
							<td><a target="_blank" href="'.$href.'">'.$fid.'</a></td>
						</tr>
					';
				}
				else if ($amount != $oids[$fid]) {
					$diff = $amount - $oids[$fid];
					$total_diff += $diff;
					$ml .= '
						<tr>
							<td>'.(++$problem_count).'</td>
							<td>Amount</td>
							<td><a target="_blank" href="'.$href.'">'.$fid.'</a></td>
							<td>'.$amount.'</td>
							<td> &nbsp; </td>
							<td>'.$oids[$fid].'</td>
							<td> &nbsp; </td>
							<td>'.$diff.'</td>
						</tr>
					';
				}
			}
		}
		if ($problem_count == 0) {
			$ml = 'All payments okay';
		}
		else {
			$ml = '
				<table>
					<thead>
						<tr>
							<th></th>
							<th>Type</th>
							<th>ID</th>
							<th>Wpro</th>
							<th></th>
							<th>LP</th>
							<th></th>
							<th>Diff</th>
						</tr>
					</thead>
					<tbody>
						'.$ml.'
					</tbody>
					<tfoot>
						<tr>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th>'.$total_diff.'</th>
						</tr>
					</tfoot>
				</table>
			';
		}
		$this->ml_missing = '
			<hr />
			'.$ml.'
		';
	}
}

?>