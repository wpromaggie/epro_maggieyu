<?php

class mod_hr extends module_base
{
	protected $m_name = 'hr';
	
	public function pre_output()
	{
		$this->display_default = 'userlist';
	}
	
	public function get_menu()
	{
		return new Menu(array(
			new MenuItem('Userlist'  ,'userlist'),
			new MenuItem('Events'    ,'events'),
			new MenuItem('New User'  ,'new_user'),
			new MenuItem('Reset Pass','reset_pass'),
			new MenuItem('Terminate' ,'terminate')
		), 'hr');
	}
	
	public function display_userlist()
	{
		?>
		<h1>User List</h1>
		<?php
		$this->print_user_table('Current', "password <> ''");
		$this->print_user_table('Former', "password = ''");
		?>
		<div class="clr"></div>
		<?php
	}
	
	private function print_user_table($title, $q_where)
	{
		$users = db::select("
			select u.id, u.username, u.realname, u.primary_dept
			from eppctwo.users u
			where $q_where
			".users::get_test_where_clause()."
			order by realname asc
		");
		
		$ml = '';
		for ($i = 0; list($id, $username, $realname, $primary_dept) = $users[$i]; ++$i)
		{
			$ml .= '
				<tr class="r'.($i % 2).'">
					<td >'.($i + 1).'</td>
					<td><a href="'.cgi::href('/hr/edit_user?uid='.$id).'" target="_blank">'.$realname.'</a></td>
					<td>'.$username.'</td>
					<td>'.$primary_dept.'</td>
				</tr>
			';
		}
		?>
		<div class="w_userlist">
			<h2><?php echo $title; ?> Employees</h2>
			<table class="userlist_table">
				<thead>
					<tr>
						<th></th>
						<th>Name</th>
						<th>Login</th>
						<th>Dept</th>
					</tr>
				</thead>
				
				<tbody>
					<?php echo $ml; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function pre_output_edit_user()
	{
		$this->user = new users(array('id' => $_REQUEST['uid']));
	}

	public function display_edit_user()
	{
		if (user::is_developer()) {
			$ignore = array();
			$wiki_user = db::select_row("
				select *
				from wikidb.user
				where user_name = :username
			", array(
				'username' => $this->user->username
			), 'ASSOC');
			$ml_wiki_user = '<h1>Wiki User Info</h1>';
			if ($wiki_user) {
				foreach ($wiki_user as $k => $v) {
					$ml_wiki_user .= "<p>{$k}={$v}</p>\n";
				}
			}
		}
		else {
			$ignore = array('wiki_id', 'login_cookie', 'password', 'is_test_user');
			$ml_wiki_user = '';
		}
		?>
		<h1><?= $this->user->realname ?></h1>
		<?= $this->user->html_form(array(
			'ignore' => $ignore
		)) ?>
		<?= $ml_wiki_user ?>
		<?php
	}

	public function action_users_submit()
	{
		$updated = $this->user->put_from_post();
		if ($updated === false) {
			feedback::add_error_msg(db::last_error());
		}
		else if (empty($updated)) {
			feedback::add_error_msg('No changes found.');
		}
		else {
			feedback::add_success_msg('User updated: '.implode(', ', $updated));
		}
	}

	public function display_new_user()
	{
		$dept_options = user_role::get_hr_new_user_depts();
		if (user::is_developer()) {
			$ml_test_user = '
				<tr>
					<td>Is Test User</td>
					<td><input type="checkbox" name="is_test_user" value="1" /></td>
				</tr>
			';
		}
		?>
		<h1>New User</h1>
		<table>
			<tr>
				<td>Email local-part</td>
				<td><input type="text" name="email" focus_me=1 />@wpromote.com</td>
			</tr>
			<tr>
				<td>Real Name</td>
				<td><input type="text" name="name" /></td>
			</tr>
			<tr>
				<td>Department</td>
				<td><?php echo cgi::html_select('dept', $dept_options); ?></td>
			</tr>
			<?= $ml_test_user ?>
			<tr>
				<td></td>
				<td><input type="submit" a0="action_new_user" value="Submit" /></td>
			</tr>
		</table>
		<?php
	}
	
	private function random_pass()
	{
		$tmp_pass = '';
		for ($i = 0; $i < 6; ++$i) $tmp_pass .= mt_rand(0, 9);
		return $tmp_pass;
	}
	
	protected function action_new_user()
	{
		list($email, $name, $dept, $is_test_user) = util::list_assoc($_POST, 'email', 'name', 'dept', 'is_test_user');
		$tmp_pass = $this->random_pass();
		
		if (!util::is_email_address($email))
		{
			$email .= '@wpromote.com';
		}
		
		$uid = db::insert('users', array(
			'company' => g::$company,
			'username' => $email,
			'password' => util::passhash($tmp_pass, $email),
			'realname' => $name,
			'primary_dept' => $dept,
			'is_test_user' => $is_test_user
		));
		
		if (!$uid)
		{
			feedback::add_error_msg('Error creating user: '.db::last_error());
			return false;
		}
		else
		{
			// add to guild table
			db::insert("eppctwo.user_guilds", array(
				'user_id' => $uid,
				'guild_id' => $dept,
				'role' => 'Member'
			));
			
			list($first, $last) = explode(' ', $name);
			$msg 	= 'Hi '.$first.', here is your e2 login info:

URL: http://'.\epro\DOMAIN.'/
User: '.$email.'
Pass: '.$tmp_pass.'

You can change your password to something more memorable with the "My Account" link once you have logged in.
';
			// test user created by admin, just print message to screen
			if ($is_test_user) {
				echo "<pre>$msg</pre>\n";
			}
			else {
				util::mail('joanne@wpromote.com', $email, 'Welcome to E2', $msg, array('Cc' => 'joanne@wpromote.com'));
			}
			feedback::add_success_msg('New User Added');
		}
	}
	
	public function display_reset_pass()
	{
		$users = db::select("
			select id, realname
			from users
			order by realname asc
		");
		
		array_unshift($users, array('', ''));
		?>
		<h1>Reset User Password</h1>
		<table>
			<tbody>
				<tr>
					<td>User</td>
					<td><?php echo cgi::html_select('reset_pass_select', $users); ?></td>
					<td></td>
				</tr>
				<tr>
					<td>Pass</td>
					<td><input type="text" name="pass" /></td>
					<td>Leave blank for random</td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" a0="reset_pass_submit" value="Go" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}
	
	protected function reset_pass_submit()
	{
		$tmp_pass = $_POST['pass'];
		if (empty($tmp_pass)) $tmp_pass = $this->random_pass();
		$uid = $_POST['reset_pass_select'];
		$email = db::select_one("select username from users where id = '$uid'");
		
		db::update(
			"eppctwo.users",
			array("password" => util::passhash($tmp_pass, $email)),
			"id = '$uid'"
		);
		
		feedback::add_success_msg('Password Reset for '.$email.': '.$tmp_pass);
	}
	
	public function display_terminate()
	{
		$users = db::select("
			select id, realname
			from users
			order by realname asc
		");
		
		array_unshift($users, array('', ' - Select - '));
		
		?>
		<h1>Terminate User</h1>
		<table>
			<tbody>
				<tr>
					<td>User</td>
					<td><?php echo cgi::html_select('user', $users); ?></td>
					<td></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" a0="action_terminate_submit" value="Go" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}
	
	protected function action_terminate_submit()
	{
		$uid = $_POST['user'];
		$user = db::select_row("select realname, username from eppctwo.users where id = :id", array('id' => $uid), 'ASSOC');

		db::update("eppctwo.users", array(
			'login_cookie' => '',
			'password' => ''
		), "id = :id", array('id' => $uid));

		$r = util::wpro_post('dashboard', 'terminate_user', array(
			'email' => $user['username']
		));
		
		feedback::add_success_msg($user['username'].': terminated');

		if ($r){
			feedback::add_error_msg($r);
		}
	}

	public function display_events()
	{
		if (empty($this->new_event)) {
			$this->new_event = new wpro_event();
		}
		?>
		<h1>Events</h1>
		<div>
			<a id="new_link" href="">New Event</a>
		</div>
		<fieldset id="w_new">
			<legend>New Event</legend>
			<?= $this->new_event->html_form() ?>
		</fieldset>
		<div class="clr"></div>

		<div id="w_table"></div>
		<?php
		cgi::add_js_var('events', wpro_event::get_all());
	}

	public function action_wpro_event_submit()
	{
		$this->new_event = wpro_event::new_from_post();
		if (util::empty_date($this->new_event->date) || !strtotime($this->new_event->date)) {
			return feedback::add_error_msg('Please enter a valid date');
		}
		if (empty($this->new_event->name)) {
			return feedback::add_error_msg('Please enter a name');
		}
		$r = $this->new_event->insert();
		if ($r) {
			feedback::add_success_msg('New event '.$event->name.' created');
		}
		else {
			feedback::add_error_msg('Error creating new event: '.rs::get_error());
		}
	}

	public function pre_output_edit_event()
	{
		$this->event = new wpro_event(array('id' => $_REQUEST['eid']));
	}

	public function display_edit_event()
	{
		if ($this->did_delete) {
			$ml = '<div>No data</div>';
		}
		else {
			$ml = $this->event->html_form(array(
				'submit_prefix' => 'edit_',
				'delete' => true,
			));
		}
		?>
		<h1>Edit Event</h1>
		<?= $ml ?>
		<?php
	}

	public function action_edit_wpro_event_submit()
	{
		$changes = $this->event->put_from_post();
		if ($changes) {
			feedback::add_success_msg('Event updated: '.implode(', ', $changes));
		}
		else {
			if ($changes === false) {
				feedback::add_error_msg('Error editing event: '.rs::get_error());
			}
			else {
				feedback::add_error_msg('No changes detected');
			}
		}
	}

	public function action_delete_wpro_event_submit()
	{
		$r = $this->event->delete();
		if ($r) {
			$this->did_delete = true;
			feedback::add_success_msg('Event deleted');
		}
		else {
			feedback::add_error_msg('Error deleting event: '.rs::get_error());
		}
	}
}

?>