<?php

class mod_service extends module_base
{
	protected $dept;

	public function pre_output()
	{
		$this->dept = service::get_dept_from_url();
		if (empty($this->dept) && account::is_service(user::$primary_role->dept)) {
			$this->dept = user::$primary_role->dept;
		}
		if (account::is_service($this->dept)) {
			util::load_lib($this->dept);
		}
		$this->display_default = 'index';
	}
	
	public function display_index()
	{
		$menu = $this->get_menu();
		?>
		<h2>Service</h2>
		<?= $menu->to_ml(array('separator' => "<br>\n")) ?>
		<?php
	}
	
	public function get_menu()
	{
		$menu = new Menu(
			array(
				new MenuItem('Client List', array('client_list', $this->dept))
			),
			'service'
		);
		if (user::is_admin()) {
			$menu->append(
				new MenuItem('New Client', array('new_client', $this->dept))
			);
		}
		if ($this->dept == 'partner') {
			$menu->append(
				new MenuItem('Billing Calendar', array('calendars', 'billing', $this->dept)),
				new MenuItem('Contacts', array('contacts', $this->dept)),
				new MenuItem('Task Manager', array('tasks', $this->dept))
			);
		}
		return $menu;
	}

	public function pre_output_new_client()
	{
		$this->dept_options = account::$org['service'];
	}

	public function display_new_client()
	{
		$depts_val = ($_POST['depts']) ? $_POST['depts'] : $this->dept;
		?>
		<h1>Create New Client</h1>
		<table>
			<tbody>
				<tr>
					<td><label for="name">New Client Name</label></td>
					<td><input type="text" id="name" name="name" value="<?= $_POST['name'] ?>" /></td>
				</tr>
				<tr>
					<td><label>Departments (at least one)</label></td>
					<td><?= cgi::html_checkboxes('depts', $this->dept_options, $depts_val, array('separator' => ' &nbsp; ', 'toggle_all' => false)) ?></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" a0="action_create_new_client" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	public function display_new_client_success()
	{
		?>
		<h1>Create New Client</h1>
		<a href="<?= cgi::href('account/service/'.$this->new_client_account->dept.'/info?aid='.$this->new_client_account->id) ?>">Go to new client</a>
		<?php
	}

	public function action_create_new_client()
	{
		list($name, $depts) = util::list_assoc($_POST, 'name', 'depts');

		$depts = cgi::get_posted_checkbox_value($depts, $this->dept_options);

		if (empty($name)) {
			return feedback::add_error_msg('Please enter a name');
		}
		if (empty($depts)) {
			return feedback::add_error_msg('Please select at least one department');
		}
		$client = client::create(array('name' => $name));
		$this->new_client_account = false;
		foreach ($depts as $dept) {
			util::load_lib($dept);
			$class = "as_{$dept}";
			$account = $class::create(array(
				'client_id' => $client->id,
				'name' => $name,
				'division' => 'service',
				'dept' => $dept,
				'status' => 'Active',
				'signup_dt' => date(util::DATE_TIME)
			));
			if (!$this->new_client_account) {
				$this->new_client_account = $account;
			}
		}
		feedback::add_success_msg("New client created!");

		$this->switch_display('new_client_success');
	}
}

?>