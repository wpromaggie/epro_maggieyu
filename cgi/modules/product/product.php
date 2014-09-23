<?php

/*
 * pages are initialized before modules, so this is safe..
 * we need to do this now because product.calendars extends wid_calendar
 * gotta be a better way
 */
if (g::$p2 == 'calendars') {
	cgi::include_widget('calendar');
}

class mod_product extends module_base
{
	public function pre_output()
	{
		parent::pre_output();
		$this->quick_search = $this->register_widget('quick_search');
		util::load_lib('sbs');
		// todo: switch view functionality
		//  many cases i'm sure. needed here since default page is in a separate file
		//  which would need to be initialized etc
		if (!g::$p2) {
			cgi::redirect('product/queues');
		}
	}
	
	
	public function print_header_row_2()
	{
		$this->quick_search->output(false);
	}
	
	public function get_menu()
	{
		return product::cgi_get_header_row3_menu();
	}
	
	public function display_account_reps()
	{
		if (!$this->is_user_leader())
		{
			$this->display_index();
			return;
		}
		
		$users = db::select("
			select u.id, u.realname, ar.users_id, ar.name, ar.email, ar.phone
			from eppctwo.user_guilds g, eppctwo.users u
				left outer join eppctwo.sbs_account_rep ar on u.id = ar.users_id
			where
				u.password <> '' &&
				g.guild_id in ('sbs', '".implode("','", product::get_depts())."') &&
				g.user_id = u.id
			group by id, realname
			order by u.realname asc
		");
		
		// collect active reps for edit drop down
		$edit_options = array();
		$rep_data = array();
		
		$num_users = count($users);
		$num_cols = 6;
		$num_rows = ceil($num_users / $num_cols);
		$ml = '';
		for ($i = 0; $i < $num_rows; ++$i)
		{
			$ml_row = '';
			for ($j = 0; $j < $num_cols; ++$j)
			{
				$index = ($j * $num_rows) + $i;
				if ($index < $num_users)
				{
					list($uid, $name, $is_active, $rep_name, $rep_email, $rep_phone) = $users[$index];
					if ($is_active)
					{
						$edit_options[] = array($uid, $name);
						$rep_data[$uid] = array(
							'name' => $rep_name,
							'email' => $rep_email,
							'phone' => $rep_phone
						);
					}
					$input_id = 'u'.$uid;
					$ml_row .= '
						<td>
							<input type="checkbox" class="rep" rid="'.$uid.'" id="'.$input_id.'"'.(($is_active) ? ' checked' : '').' />
							<label for="'.$input_id.'">'.$name.'</label>
						</td>
					';
				}
			}
			$ml .= '
				<tr>
					'.$ml_row.'
				</tr>
			';
		}
		if ($edit_options)
		{
			util::sort2d($edit_options, 1);
			array_unshift($edit_options, array('', ' - Select - '));
			
			$ml_edit = '
				<div id="edit_rep_wrapper">
					<table>
						<tbody>
							<tr>
								<td><b>Edit Rep Info:</b></td>
								<td>'.cgi::html_select('edit_rep_select', $edit_options, $_POST['edit_rep_select']).'</td>
							</tr>
						</tbody>
					</table>
					
					<table id="edit_rep_table">
						<tbody>
							'.sbs_account_rep::html_form_new(array('table' => false)).'
							<tr>
								<td></td>
								<td><input type="submit" a0="action_edit_rep_submit" value="Submit" /></td>
							</tr>
						</tbody>
					</table>
				</div>
			';
			cgi::add_js_var('rep_data', $rep_data);
		}
		
		?>
		<h1>Account Reps</h1>
		<div id="rep_cboxes_wrapper">
			<table>
				<tbody>
					<?= $ml ?>
				</tbody>
			</table>
			<input type="submit" id="reps_submit" a0="action_account_reps_submit" value="Submit" />
			<input type="hidden" id="changes" name="changes" value="" />
		</div>
		
		<?= $ml_edit ?>
		<?php
	}
	
	public function action_account_reps_submit()
	{
		$changes = json_decode($_POST['changes'], true);
		foreach ($changes as $uid => $on_or_off)
		{
			if ($on_or_off == 'On')
			{
				db::insert("eppctwo.sbs_account_rep", array('users_id' => $uid));
			}
			else
			{
				db::delete("eppctwo.sbs_account_rep", "users_id = $uid");
			}
		}
		feedback::add_success_msg('Reps updated.');
	}
	
	public function action_edit_rep_submit()
	{
		$rep = new sbs_account_rep(array('users_id' => $_POST['edit_rep_select']));
		$updates = $rep->put_from_post();
		if ($updates)
		{
			feedback::add_success_msg($rep->name.' updated: '.implode(', ', $updates));
		}
		else
		{
			feedback::add_error_msg('No updates detected');
		}
	}
	
	public function display_search()
	{
		?>
		<h1>Search</h1>
		<div id="results">
		<?php echo $this->ml_search_results; ?>
		</div>
		<?php
	}
	
	// todo: move to quick_search widget?
	public function action_quick_search()
	{
		if (!$_POST['quick_search']) {
			return false;
		}
		$s = $_POST['quick_search'];
		$fields = array(
			'urls' => array(
				'url'
			),
			'contacts' => array(
				'email',
				'name',
				'phone'
			)
		);
		$co_select = "client_id, name, email";
		$ac_select = "id, client_id, dept, plan, prepay_paid_months, status, substring(signup_dt, 1, 10) signup_date, url";

		//search contacts and save client_ids
		$queries = array();
		foreach($fields['contacts'] as $field)
		{
			$queries[] = $field." like '%".db::escape($s)."%'";
		}
		$contacts = db::select("
			select {$co_select}
			from eppctwo.contacts
			where (".implode(" || ", $queries).")
		", 'ASSOC', 'client_id');

		// search accounts
		$queries = array();
		foreach($fields['urls'] as $field)
		{
			$queries[] = $field." like '%".db::escape($s)."%'";
		}
		$accounts = db::select("
			select {$ac_select}
			from eac.account
			where (".implode(" || ", $queries).")
		", 'ASSOC', 'id');
		
		// we found both contacts and accounts
		if ($contacts && $accounts)
		{
			$co_cl_ids = array_keys($contacts);
			$ac_cl_ids = $this->get_ac_cl_ids($accounts);
			
			$missing_co_cl_ids = array_diff($ac_cl_ids, $co_cl_ids);
			$missing_ac_cl_ids = array_diff($co_cl_ids, $ac_cl_ids);
		}
		// contacts, no accounts
		else if ($contacts)
		{
			$missing_ac_cl_ids = array_keys($contacts);
		}
		// accounts, no contacts
		else if ($accounts)
		{
			$missing_co_cl_ids = $this->get_ac_cl_ids($accounts);
		}
		
		// get contact info for accounts that did not match contacts
		if ($missing_co_cl_ids)
		{
			// get contact info for accounts that didn't match contact query
			$missing_contacts = db::select("
				select {$co_select}
				from eppctwo.contacts
				where client_id in ('".implode("','", $missing_co_cl_ids)."')
			", 'ASSOC', 'client_id');
			foreach ($missing_contacts as $cl_id => &$co)
			{
				$contacts[$cl_id] = $co;
			}
		}
		
		// get account info for contacts that did not match account query
		if ($missing_ac_cl_ids)
		{
			$missing_accounts = db::select("
				select {$ac_select}
				from eac.account
				where client_id in ('".implode("','", $missing_ac_cl_ids)."')
			", 'ASSOC', 'id');
			foreach ($missing_accounts as $ac_id => &$ac)
			{
				$accounts[$ac_id] = $ac;
			}
		}
		
		$count = count($accounts);
		// no matches
		if ($count == 0)
		{
			$this->ml_search_results = '<h2 id="no_results">No results found.</h2>';
		}
		// 1 match, go to account
		else if ($count == 1)
		{
			foreach ($accounts as $ac_id => &$account) break;
			cgi::redirect('http://'.\epro\DOMAIN.cgi::href('account/product/'.$account['dept'].'/?aid='.$ac_id));
		}
		// multiple matches, show results
		else
		{
			$ml = '';
			$i = 0;
			foreach ($accounts as $ac_id => &$account)
			{
				list($name, $email) = util::list_assoc($contacts[$account['client_id']], 'name', 'email');
				$ml .= '
					<tr>
						<td>'.(++$i).'</td>
						<td>'.strtoupper($account['dept']).'</td>
						<td><a href="'.cgi::href('account/product/'.$account['dept'].'/?aid='.$ac_id).'">'.$account['url'].'</a></td>
						<td>'.$ac_id.'</td>
						<td>'.$name.'</td>
						<td>'.$email.'</td>
						<td>'.$account['plan'].'</td>
						<td>'.$account['pay_option'].'</td>
						<td>'.$account['status'].'</td>
						<td>'.$account['signup_date'].'</td>
					</tr>
				';
			}
			$this->ml_search_results = '
				<table id="search_results">
					<thead>
						<tr>
							<th></th>
							<th>Dept</th>
							<th>URL</th>
							<th>AID</th>
							<th>Name</th>
							<th>Email</th>
							<th>Plan</th>
							<th>Prepay</th>
							<th>Status</th>
							<th>Signup Date</th>
						</tr>
					</thead>
					<tbody>
						'.$ml.'
					</tbody>
				</table>
			';
		}
	}
	
	private function get_ac_cl_ids(&$accounts)
	{
		$ac_cl_ids = array();
		foreach ($accounts as $ac_id => &$ac_info)
		{
			if (!in_array($ac_info['client_id'], $ac_cl_ids))
			{
				$ac_cl_ids[] = $ac_info['client_id'];
			}
		}
		return $ac_cl_ids;
	}
}

?>