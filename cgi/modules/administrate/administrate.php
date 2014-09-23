<?php

class mod_administrate extends module_base
{
	protected $m_name = 'administrate';
	protected $coupon;
	
	const DEFAULT_GUILD_ROLE = 'Member';
	
	public function get_menu()
	{
		return new Menu(array(
			new MenuItem('User Guilds'  ,'user_guilds'),
			new MenuItem('User Roles'   ,'user_roles'),
			new MenuItem('Impersonate'  ,'impersonate'),
			new MenuItem('Delly'        ,'delly'),
			new MenuItem('Cron'         ,'cron'),
			new MenuItem('Logs'         ,'logs'),
			new MenuItem('Coupons'      ,'coupons'),
			new MenuItem('Payments'     ,'payment_smallb_to_agency'),
			new MenuItem('phpinfo'      ,'phpinfo')
		), 'administrate');
	}
	
	public function pre_output()
	{
		// peasant!
		if (!user::is_developer() && !user::is_imposter()) {
			header('Location: '.cgi::href());
			exit;
		}
	}
        
        public function pre_output_coupons(){
                util::load_rs('sbs');
                $this->coupon = new coupons();
                $this->coupon->get();
        }
        
        public function pre_output_edit_coupon(){
                util::load_rs('sbs');
                if(!isset($_GET['id']) || empty($_GET['id'])){
                        cgi::redirect('administrate/coupons');
                }
                $this->coupon = new coupons(array('id'=> $_GET['id'])); 
                if(!$this->coupon->get()){
                        cgi::redirect('administrate/coupons');
                }
        }
	
	public function display_index()
	{
		e($_POST);
		e($_SESSION);
		e($_SERVER);
		e($_ENV);
	}
	
	public function guild_roles()
	{
		$guilds = cgi::get_modules();
		array_unshift($guilds, ' - Select - ');
		$guild = $_POST['guild'];
		
		if (array_key_exists('guild_roles_submit', $_POST)) $this->guild_roles_submit($guild);
		
		if (!empty($guild)) $this->guild_roles_show_roles($ml_roles, $guild);
		?>
		<h1>Guild Roles</h1>
		<ul><li>Enter roles 1 per line. "Member" and "Leader" are permanent roles in every guild.</li></ul>
		<table>
			<tbody>
				<tr>
					<td>Guild</td>
					<td><?php echo cgi::html_select('guild', $guilds, $guild); ?></td>
				</tr>
				<?php echo $ml_roles; ?>
			</tbody>
		</table>
		<?php
	}
	
	private function guild_roles_show_roles(&$ml, $guild)
	{
		$roles = db::select("
			select eppctwo.role
			from guild_roles
			where guild_id = :gid
			order by role asc
		", array('gid' => $guild));
		$ml = '
			<tr>
				<td>Roles</td>
				<td><textarea name="roles">'.implode("\n", $roles).'</textarea></td>
			</tr>
			<tr>
				<td></td>
				<td><input type="submit" name="guild_roles_submit" value="Submit" /></td>
			</tr>
		';
	}
	
	private function guild_roles_submit($guild)
	{
		db::delete("eppctwo.guild_roles", "guild_id = :gid", array('gid' => $guild));
		$roles = explode("\n", $_POST['roles']);
		
		foreach ($roles as $role) {
			if (in_array($role, user::$standard_roles)) continue;
			db::insert("eppctwo.guild_roles", array(
				'guild_id' => $guild,
				'role' => trim($role)
			));
		}
		feedback::add_success_msg('Roles Set');
	}

	public function pre_output_user_guilds()
	{
		$this->all_roles = user_role::get_all_roles();
	}
	
	public function display_user_guilds()
	{
		$users = db::select("
			select id, realname
			from eppctwo.users
			order by realname asc
		");
		
		$uid = $_POST['user_select'];
		array_unshift($users, array('', ''));
		$ml_user_select = cgi::html_select('user_select', $users, $uid);
		
		// check form submit
		if ($uid) {
			$ml_guilds = $this->ml_user_guilds($uid);
		}
		
		?>
		<table id="user">
			<tbody>
				<tr>
					<td>User</td>
					<td><?php echo $ml_user_select; ?></td>
				</tr>
				<?php echo $ml_guilds; ?>
			</tbody>
		</table>
		<?php
	}
	
	private function ml_user_guilds($uid)
	{
		// get user guild roles
		$user_roles = db::select("
			select guild_id, role
			from user_guilds
			where user_id = '$uid'
		", 'NUM', 0);

		$ml = '';
		$role_options = array();
		foreach ($this->all_roles as $role_name => $role) {
			if (isset($role->home)) {
				// $role_options[] = array($role_name, $role_name);
				$ml_checked = (array_key_exists($role_name, $user_roles)) ? ' checked' : '';
				$cur_role = (array_key_exists($role_name, $user_roles)) ? $user_roles[$role_name] : self::DEFAULT_GUILD_ROLE;
				
				$ml .= '
					<li>
						<div>
							<input class="guild_box" type="checkbox" id="'.$role_name.'" name="'.$role_name.'" value="1"'.$ml_checked.' />
							<label for="'.$role_name.'">'.$role_name.'</label>
							'.cgi::html_select($role_name.'_role', user::$standard_roles, $cur_role, array('class' => 'guild_role')).'
							<div class="clr"></div>
						</div>
					</li>
				';
			}
		}
		
		// get user primary guild
		$primary_dept = db::select_one("select primary_dept from users where id = '$uid'");
		
		return '
			<tr style="vertical-align:top">
				<td>Guilds</td>
				<td>
					<ul>
						'.$ml.'
					</ul>
				</td>
			</tr>
			<tr>
				<td>Primary Guild</td>
				<td><select id="primary_dept" name="primary_dept"></select></td>
			</tr>
			<tr>
				<td></td>
				<td><input type="submit" a0="action_user_guilds_submit" value="Go" /></td>
			</tr>
			<input type="hidden" id="cur_primary_dept" name="cur_primary_dept" value="'.$primary_dept.'" />
		';
	}
	
	protected function action_user_guilds_submit()
	{
		$uid = $_POST['user_select'];
		$name = db::select_one("select realname from eppctwo.users where id = '$uid'");
		
		$guilds = cgi::get_modules();
		db::delete("eppctwo.user_guilds", "user_id = '$uid'");
		
		foreach ($this->all_roles as $role_name => $role) {
			if (isset($role->home) && array_key_exists($role_name, $_POST)) {
				$role = $_POST[$role_name.'_role'];
				if (empty($role)) $role = self::DEFAULT_GUILD_ROLE;
				db::insert("eppctwo.user_guilds", array(
					'user_id' => $uid,
					'guild_id' => $role_name,
					'role' => $role
				));
			}
		}

		db::update(
			"eppctwo.users",
			array('primary_dept' => $_POST['primary_dept']),
			"id = '$uid'"
		);
		
		feedback::add_success_msg($name.' updated');
	}

	public function pre_output_user_roles()
	{
		$this->all_roles = user_role::get_all_roles();
	}
	
	public function display_user_roles()
	{
		$users = db::select("
			select id, realname
			from users
			order by realname asc
		");

		$role_options = array();
		foreach ($this->all_roles as $role_name => $role) {
			if (!isset($role->home)) {
				$role_options[] = array($role_name, $role_name);
			}
		}
		
		$cur_roles = db::select("
			select ur.id _id, u.realname name, ur.guild, ur.role
			from eppctwo.user_role ur, eppctwo.users u
			where u.id = ur.user
		", 'ASSOC');
		?>
		<h1>User Roles</h1>
		<div>
			<a href="" id="new_user_role_a">New Role</a>
			<fieldset id="new_user_role_wrapper">
				<legend>New Role</legend>
				<table>
					<tbody>
						<tr>
							<td>User</td>
							<td><?php echo cgi::html_select('new_user', $users); ?></td>
						</tr>
						<tr>
							<td>Role</td>
							<td><?php echo cgi::html_select('new_role', $role_options); ?></td>
						</tr>
						<tr>
							<td></td>
							<td>
								<input type="submit" a0="action_new_user_role" value="Submit" />
								<input type="submit" id="new_user_role_cancel" value="Cancel" />
							</td>
						</tr>
					</tbody>
				</table>
			</fieldset>
		</div>
		<div class="clr"></div>
		<div id="cur_roles_wrapper"></div>
		<input type="hidden" id="delete_ur_id" name="delete_ur_id" value="" />
		<?php
		cgi::add_js_var('roles', $cur_roles);
	}
	
	public function action_new_user_role()
	{
		list($uid, $role_name) = util::list_assoc($_POST, 'new_user', 'new_role');
		// list($guild, $role) = user_role::$roles[$role_index];
		$realname = db::select_one("select realname from eppctwo.users where id = $uid");
		
		$ur = user_role::create(array(
			'user' => $uid,
			'guild' => $role_name,
			'role' => $role_name
		));
		$ur->put();
		
		feedback::add_success_msg('Role <i>'.$role_name.'</i> assigned to <i>'.$realname.'</i>.');
	}
	
	public function action_delete_user_role()
	{
		$ur = new user_role(array('id' => $_POST['delete_ur_id']));
		$realname = db::select_one("select realname from eppctwo.users where id = {$ur->user}");
		$r = $ur->delete();
		if ($r)
		{
			feedback::add_success_msg('Role <i>'.$ur->guild.' - '.$ur->role.'</i> for <i>'.$realname.'</i> deleted.');
		}
		else
		{
			$e = db::last_error();
			feedback::add_error_msg('Error deleting user role'.(($e) ? ': '.$e : ''));
		}
	}
	
	public function display_impersonate()
	{
		echo "<h2>Impersonate</h2>\n";
		if (user::is_imposter()) $this->impersonate_show_revert();
		else $this->impersonate_show_user_select();
	}
	
	protected function impersonate_show_revert()
	{
		?>
		<p>Currently impersonating: <?php echo db::select_one("select username from users where id='".$_SESSION['id']."'"); ?></p>
		<input a0="action_impersonate_stop" type="submit" value="Stop Impersonating" />
		<?php
	}
	
	protected function impersonate_show_user_select()
	{
		$users = db::select("
			select id, realname
			from users
			where username <> '".$_SESSION['username']."'
			order by realname asc
		");
		
		array_unshift($users, array('value' => '', 'caption' => ' - Select - '));
		$ml_impersonate_select = cgi::html_select('impersonate_select', $users);
		
		?>
		<label>Impersonate: </label>
		<?php echo $ml_impersonate_select; ?>
		<?php
	}
	
	protected function action_impersonate_submit()
	{
		user::impersonate($_POST['impersonate_select']);
	}
	
	protected function action_impersonate_stop()
	{
		user::stop_impersonating();
	}
        
        public function display_coupons(){
                $coupons = db::select("SELECT * FROM coupons", "ASSOC");
                $table_headers = array('id', 'code', 'description', 'value', 'contract_length', '');
        ?>
                <h2>Add Coupon</h2>
                <?php echo $this->coupon->html_form(array('ignore' => array('id'))); ?>
                
                <table id="partner-contacts">
                        <thead>
                                <tr>
                                       <?php
                                       foreach($table_headers as $th){
                                               echo "<td>$th</td>";
                                       }
                                       ?> 
                                </tr>
                        </thead>
                        <tbody>
                                <?php
                                foreach($coupons as $c){
                                        
                                        $edit_status = "";
                                        if($c['status']=="active"){
                                                $edit_status = "<a href='".cgi::href('administrate/coupon_set_status?id='.$c['id'].'&status=expired')."'>expire</a>";
                                        } else if($c['status']=="expired"){
                                              $edit_status = "<a href='".cgi::href('administrate/coupon_set_status?id='.$c['id'].'&status=active')."'>activate</a>";  
                                        }
                                        echo "<tr>";
                                        echo "<td>{$c['id']}</td>";
                                        echo "<td>{$c['code']}</td>";
                                        echo "<td>{$c['description']}</td>";
                                        echo "<td>{$c['value']} {$c['value_type']}</td>";
                                        echo "<td>{$c['contract_length']}</td>";
                                        echo "<td><a style='padding-right: 10px;' href='".cgi::href('administrate/edit_coupon/?id='.$c['id'])."'>edit</a>$edit_status</td>";
                                        echo "</tr>";
                                }
                                ?>
                        </tbody>
                </table>
        <?php
        }
        
        protected function edit_coupon(){
        ?>
                <h2>Edit Coupon</h2>
                <p>Type: <?php echo $this->coupon->type ?></p>
                <p>Value: <?php echo $this->coupon->value." ".$this->coupon->value_type ?></p>
                <?php if($this->coupon->contract_length!=0){ ?>
                <p>Contract Length: <?php echo $this->coupon->contract_length." months" ?></p>
                <?php } ?>
                <?php echo $this->coupon->html_form(array('ignore' => array('id', 'type', 'value', 'value_type', 'contract_length'))); ?>
                <p><a href="<?php echo cgi::href('administrate/coupons') ?>">View Coupons</a></p>
        <?php
        }
        
        protected function coupon_set_status(){
                $status = $_GET['status'];
                util::load_lib('sbs');
                db::update("coupons", array('status' => $status), "id=".$_GET['id']);
                util::wpro_post('small_business_solutions', 'update_coupon', array('id' => $_GET['id'], 'status' => $status));
                cgi::redirect('administrate/coupons');
        }
        
        protected function action_coupons_submit(){
                $this->coupon->put_from_post();
                $this->update_wpro_coupon();
                cgi::redirect('administrate/coupons');
        }
        
        private function update_wpro_coupon(){
                util::load_lib('sbs');
                $wpro_keys = array('id', 'code', 'type', 'value', 'value_type', 'description', 'contract_length');
                foreach ($wpro_keys as $key)
                {
                        $wpro_data[$key] = $this->coupon->$key;
                }
                util::wpro_post('small_business_solutions', 'update_coupon', $wpro_data);
        }
	
	public function pre_output_delly()
	{
		util::load_lib('delly');
	}

	public function display_delly()
	{
		$num_jobs = (array_key_exists('num_jobs', $_POST)) ? $_POST['num_jobs'] : 10;
		$jobs = job::get_all(array(
			"select" => array(
				"job" => array("id", "parent_id", "fid", "type", "user_id", "account_id", "hostname", "process_id", "status", "scheduled", "created", "started", "finished"),
				"clients" => array("name as client"),
				"users" => array("if (realname is null, job.user_id, users.realname) as who")
			),
			"left_join" => array(
				"clients" => "clients.id = job.account_id",
				"users" => "users.id = job.user_id"
			),
			"order_by" => "created desc",
			"limit" => $num_jobs,
			"flatten" => true
		));
		cgi::add_js_var('status_options', job::$status_options);
		cgi::add_js_var('jobs', $jobs);
		?>
		<h1>Delly!</h1>
		<div>
			<label for="num_jobs">Num</label>
			<input type="text" name="num_jobs" id="num_jobs" value="<?php echo $num_jobs; ?>" />
			<input type="submit" value="Update" />
		</div>
		<div><input type="submit" a0="action_update_job_status" value="Update Status" /></div>
		<div id="w_cur_jobs"></div>
		<div><input type="submit" a0="action_update_job_status" value="Update Status" /></div>
		<input type="hidden" id="status_updates" name="status_updates" value="" />
		<?php
	}

	public function action_update_job_status()
	{
		$updates = json_decode($_POST['status_updates'], true);
		if (empty($updates)) {
			feedback::add_error_msg('Could not decode updates');
		}
		else {
			foreach ($updates as $jid => $status) {
				job::update_all(array(
					'set' => array("status" => $status),
					'where' => "id = :id",
					'data' => array("id" => $jid)
				));
			}
			feedback::add_success_msg('Updated!');
		}
	}

	public function ajax_get_job_details()
	{
		$job_details = new job(array('id' => $_REQUEST['jid']), array(
			'select' => array(
				'job' => array("status"),
				'job_detail' => array("ts", "level", "message")
			),
			'left_join_many' => array(
				"job_detail" => "
					job.id = job_detail.job_id &&
					job_detail.ts >= job.started
				"
			),
			'order_by' => "job_detail.ts asc, job_detail.id asc"
		));
		echo $job_details->json_encode();
	}

	public function pre_output_job_details()
	{
		util::load_lib('delly');

		$this->job = new job(array('id' => $_REQUEST['jid']), array(
			'select' => array(
				'job' => array("id", "parent_id", "user_id", "fid", "type", "account_id", "hostname", "process_id", "status", "created", "started", "finished")
			)
		));
	}

	public function action_job_submit()
	{
		$updates = $this->job->put_from_post();
		if ($updates) {
			feedback::add_success_msg('Job updated: '.implode(', ', $updates));
		}
		else {
			feedback::add_error_msg('No fields updated');
		}
	}

	public function action_job_kill()
	{
		$this->job->kill();
	}

	public function display_job_details()
	{
		$children = job::get_all(array(
			'select' => "id",
			'where' => "job.parent_id = :parent_id",
			'data' => array("parent_id" => $this->job->id)
		));
		if ($children->count() > 0) {
			// todo: get sample of how long these jobs usually take
		}

		cgi::add_js_var('children', $children->id);
		cgi::add_js_var('done_stati', job::$done_stati);
		?>
		<h1>Job Details</h1>
		<fieldset class="m_r16">
			<legend>Job</legend>

			<h2>Meta</h2>
			<input type="submit" a0="action_job_kill" value="Kill" />
			<?= $this->job->html_form() ?>

			<h2>Details</h2>
			<div id="w_details">
				<div>Current Status: <b id="current_status"></b></div>
				<div id="w_details_list"></div>
			</div>
		</fieldset>

		<fieldset>
			<legend>Children</legend>
			<div id="w_children_meta" class="hide">
				<input type="submit" a0="action_requeue_children" value="Re-queue Children" />
			</div>
			<div id="w_children"></div>
		</fieldset>
		<div class="clr"></div>
		<?php
	}

	public function action_requeue_children()
	{
		// todo? check if any are processing, try to kill?
		job::update_all(array(
			'set' => array("status" => "Pending"),
			'where' => "parent_id = :parent_id",
			'data' => array("parent_id" => $this->job->id)
		));
		feedback::add_success_msg("Children re-queued");
	}
	
	public function ajax_get_children_stati()
	{
		$ids = json_decode($_POST['ids']);

		$children = job::get_all(array(
			'select' => "id, hostname, process_id, status, started, finished",
			'where' => "job.id in (:ids)",
			'data' => array('ids' => $ids)
		));
		echo $children->json_encode();
	}

	public function display_phpinfo()
	{
		phpinfo();
	}

	public function pre_output_cron()
	{
		util::load_lib('delly');
	}

	public function pre_output_cron_edit()
	{
		util::load_lib('delly');
		$this->cj = new cron_job(array('id' => $_GET['id']));
	}

	public function display_cron()
	{
		if ($this->new_cj && empty($this->new_cj->id)) {
			cgi::add_js_var('error_field', $this->new_cj->error_field);
		}
		else {
			$this->new_cj = new cron_job();
		}
		$cron_jobs = cron_job::get_all(array(
			'order_by' => 'hour asc, minute asc'
		));
		cgi::add_js_var('cron_jobs', $cron_jobs);
		?>
		<h1>Cron</h1>
		<div>
			<div>
				<a href="" id="new_link">New Cron Job</a>
			</div>
			<fieldset id="w_new_cron_job">
				<legend>New Cron Job</legend>
				<?= $this->new_cj->html_form() ?>
			</fieldset>
			<div class="clr"></div>
		</div>

		<h2>Current Cron Jobs</h2>
		<div id="w_cron_jobs"></div>
		<?php
	}

	public function display_cron_edit()
	{
		if ($this->did_delete) {
			$this->print_delete_page();
		}
		else {
			$this->print_edit_form();
		}
	}

	private function print_delete_page()
	{
		echo 'Nothing more to see here.';
	}

	private function print_edit_form()
	{
		?>
		<h1>Edit Cron Job</h1>
		<a id="copy_link" href="#copy">Make a Copy</a>
		<div id="copy_msg" class="hide">
			* Now in copy mode, submitting will result in a new cron job
			rather than an edit
		</div>
		<table>
			<tbody>
				<?= $this->cj->html_form(array('table' => false)) ?>
				<tr>
					<td></td>
					<td>
						<input id="edit_button" type="submit" class="rs_submit submit" value="Submit" a0="action_cron_edit_submit" />
						<input id="delete_button" type="submit" value="Delete" a0="action_cron_delete_submit" />
					</td>
				</tr>
			</tbody>
		</table>
		<input type="hidden" name="mode" id="mode" value="edit" />
		<?php
	}

	public function action_cron_edit_submit()
	{
		$r = $this->create_time_validated_cron_job($tmp_cj);
		if ($r === false) {
			// set our cj to the failed cj so form represents what user submitted
			$this->cj = $tmp_cj;
			return false;
		}
		$updates = $this->cj->put_from_post();
		if ($updates) {
			feedback::add_success_msg('Updated: '.implode(', ', $updates));
		}
		else {
			feedback::add_error_msg('Error updating cron job');
		}
	}

	public function action_cron_copy_submit()
	{
		$r = $this->action_cron_job_submit();
		if ($r) {
			$this->cj = $this->new_cj;
			cgi::add_js_var('copy_id', $this->cj->id);
		}
	}

	public function action_cron_delete_submit()
	{
		$r = $this->cj->delete();
		if ($r) {
			feedback::add_success_msg('Cron job deleted');
			$this->did_delete = true;
		}
		else {
			feedback::add_error_msg('Error deleting cron job: '.rs::last_error());
		}
	}

	private function create_time_validated_cron_job(&$cj = null)
	{
		$cj = cron_job::new_from_post();
		// check that time fields are valid
		$time_fields = cron_job::get_time_fields();
		foreach ($time_fields as $field) {
			$field_val =  $cj->$field;
			if ($field_val != '*' && cron_job::get_time_parts($field, $field_val) === false) {
				$cj->error_field = $field;
				feedback::add_error_msg('Invalid time field: '.$field);
				return false;
			}
		}
		return true;
	}

	public function action_cron_job_submit()
	{
		$r = $this->create_time_validated_cron_job($this->new_cj);
		if ($r === false) {
			return false;
		}
		if ($this->new_cj->insert()) {
			feedback::add_success_msg('New cron job created ('.$this->new_cj->id.')');
			return true;
		}
		else {
			feedback::add_error_msg('Error creating cron job: '.db::last_error());
			return false;
		}
	}
	
	public function display_logs()
	{
		util::load_lib('smo');

		list($start_date, $end_date, $app_id) = util::list_assoc($_REQUEST, 'start_date', 'end_date', 'app_id');

		if (empty($start_date)) {
			$end_date = date(util::DATE_TIME);
			$start_date = date('Y-m-d 00:00:00');
		}

		$logs = network_log::get_all(array(
			"select" => array(
				"network_log" => array("dt", "post_id", "network", "app_id", "message"),
				"post" => array("account_id")
			),
			"left_join" => array(
				"post" => "post.id = network_log.post_id"
			),
			"where" => "
				network_log.dt between '$start_date' and '$end_date'
				".(($app_id) ? "&& network_log.app_id = '$app_id'" : "")."
			",
			"order" => "dt desc",
			"flatten" => true,
			"limit" => 64
		));

		?>
		<table>
			<tbody>
				<?= cgi::date_range_picker($start_date, $end_date) ?>
				<tr>
					<td>App ID</td>
					<td><input type="text" name="app_id" id="app_id" value="<?= $app_id ?>" /></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		<?php
		foreach ($logs as $i => $log) {
			echo '<h2><b>========== #'.$i.' ==========</b></h2>';
			if (isset($log->post->account_id)) {
				echo '<p><a target="_blank" href="'.cgi::href('smo/client/swapp/post?cl_id='.$log->post->account_id.'&pid='.$log->post_id).'">Post</a></p>';
			}
			e($log);
		}
	}

	public function pre_output_payment_smallb_to_agency()
	{
		util::load_lib('as', 'ppc', 'billing');
		$this->cid = $_REQUEST['cid'];
	}

	public function display_payment_smallb_to_agency()
	{
		?>
		<table>
			<tbody>
				<tr>
					<td>Small Business Client ID</td>
					<td><input type="text" name="cid" id="cid" value="<?= $cid ?>" /></td>
				</tr>
				<tr>
					<td></td>
					<td><input id="smallb_id_submit" type="submit" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		<?php
		$this->print_smallb_client_payments();
	}

	private function print_smallb_client_payments()
	{
		if (empty($this->cid)) {
			return;
		}
		$smallb_payments = payment::get_all(array(
			'select' => array(
				"payment" => array("id as pid","client_id","user_id","pay_id","pay_method","fid","ts","date_received","date_attributed","event","amount as pamount","notes"),
				"payment_part" => array("id as ppid","payment_id","account_id","division","dept","type","is_passthru","amount as ppamount","rep_pay_num")
			),
			'join_many' => array("payment_part" => "
				payment.client_id = '".db::str($this->cid)."' &&
				payment.id = payment_part.payment_id
			"),
			'order' => "ts desc"
		));
		foreach ($smallb_payments as $sbp) {
			e($sbp);
			echo '<a href="'.cgi::href('administrate/convert_payment?pid='.$sbp->pid).'" target="_blank">Convert</a>';
			echo "<hr />\n";
			// $client_payment = new client_payment($sbp->to_array(), array('do_get' => false));
			// e($client_payment);
		}
	}

	public function pre_output_convert_payment()
	{
		util::load_lib('as', 'ppc', 'billing');
		$this->pid = $_REQUEST['pid'];

		$tmp = payment::get_all(array(
			'select' => array(
				"payment" => array("id as pid","client_id","user_id","pay_id","pay_method","fid","ts","date_received","date_attributed","event","amount as pamount","notes"),
				"payment_part" => array("id as ppid","payment_id","account_id","division","dept","type","is_passthru","amount as ppamount","rep_pay_num")
			),
			'join_many' => array("payment_part" => "
				payment.id = '".db::str($this->pid)."' &&
				payment.id = payment_part.payment_id
			"),
			'de_alias' => true
		));
		if ($tmp->count() != 1) {
			$this->payment = false;
			feedback::add_error_msg('Could not find payment');
		}
		else {
			$this->payment = $tmp->a[0];
		}
	}

	public function display_convert_payment()
	{
		if (!$this->payment) {
			return false;
		}
		// make a client payment based on small b payment
		$client_payment = new client_payment($this->payment->to_array(), array('do_get' => false));

		$ml_types = '';
		foreach (client_payment_part::$part_types as $type => $dept) {
			$type_key = 'part_type_'.util::simple_text($type);
			$ml_types .= '
				<tr>
					<td>'.ucwords($type).'</td>
					<td><input type="text" name="'.$type_key.'" value="'.$_POST[$type_key].'" /></td>
				</tr>
			';
		}
		$ml_payment = '';
		foreach ($this->payment as $k => $v) {
			if (is_scalar($v)) {
				$ml_payment .= '
					<tr>
						<td><b>'.util::display_text($k).'</b></td>
						<td>'.$v.'</td>
					</tr>
				';
			}
		}
		$ml_pps = '';
		foreach ($this->payment->payment_part as $pp) {
			$ml_pp = '';
			foreach ($pp as $k => $v) {
				$ml_pp .= '
					<tr>
						<td><b>'.util::display_text($k).'</b></td>
						<td>'.$v.'</td>
					</tr>
				';
			}
			$input_key = 'move_'.$pp->id;
			$ml_pps .= '
				<div style="margin-bottom:8px;">
					<p>
						<input type="checkbox" name="'.$input_key.'" id="'.$input_key.'" />
						<label for="'.$input_key.'">Move This Part</label>
					<p>
					<table>
						<tbody>
							'.$ml_pp.'
						</tbody>
					</table>
				</div>
			';
		}
		?>
		<h2>Small B Payment</h2>
		<table>
			<tbody>
				<tr>
					<td>
						<table>
							<tbody>
								<?= $ml_payment ?>
							</tbody>
						</table>
					</td>
					<td>
						<?= $ml_pps ?>
					</td>
				</tr>
			</tbody>
		</table>

		<h2>Converted Client Payment</h2>
		<table>
			<tbody>
				<?=
					$client_payment->html_form(array(
						'table' => false,
						'ignore' => array('amount')
					))
				?>
				<?= $ml_types ?>
				<tr>
					<td>Target Client ID</td>
					<td><input type="text" name="target_cl_id" value="<?= $_POST['target_cl_id'] ?>" /></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" a0="action_convert_payment_submit" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	public function action_convert_payment_submit()
	{
		db::dbg();

		$target_cl_id = $_POST['target_cl_id'];
		if (empty($target_cl_id)) {
			return feedback::add_error_msg('Please enter target client id');
		}
		$new_payment = client_payment::new_from_post();
		$new_payment->client_id = $target_cl_id;
		$new_payment->user_id = $this->payment->user_id;

		// see which pps were moved
		$small_b_note = $this->payment->notes;
		$small_b_note .= ((empty($small_b_note)) ? '' : ', ').'Moved: ';

		$moved_ids = array();
		$moved_amount = 0;
		$small_b_amount_after_move = 0;
		foreach ($this->payment->payment_part as $pp) {
			$key = 'move_'.$pp->id;
			if ($_POST[$key]) {
				$small_b_note .= '('.$pp->dept.', '.$pp->type.', '.$pp->amount.')';
				$moved_ids[] = $pp->id;
				$moved_amount += $pp->amount;
			}
			else {
				$small_b_amount_after_move += $pp->amount;
			}
		}
		// check part types fields to build new payments
		$new_part_types = client_payment_part::new_array();
		foreach (client_payment_part::$part_types as $type => $dept) {
			$form_key = 'part_type_'.util::simple_text($type);
			$val = $_POST[$form_key];
			if (is_numeric($val)) {
				$part_type = new client_payment_part(array(
					'client_id' => $target_cl_id
				), array('do_get' => false));
				$part_type->amount = $val;
				$part_type->type = $type;
				$new_part_types->push($part_type);
			}
		}
		// make sure amount moved equals new amount
		$new_amount = array_sum($new_part_types->amount);
		if ($moved_amount != $new_amount) {
			return feedback::add_error_msg('Error: moved amount ('.$moved_amount.') not equal to new amount ('.$new_amount.')');
		}
		// we're good, start updating database
		$this->payment->update_from_array(array(
			'amount' => $small_b_amount_after_move,
			'notes' => $small_b_note
		));
		payment_part::update_all(array(
			'set' => array('amount' => 0),
			'where' => "id in ('".implode("','", $moved_ids)."')"
		));
		$new_payment->amount = $new_amount;
		$new_payment->insert();
		$new_part_types->client_payment_id = $new_payment->id;
		$new_part_types->insert();

		// done!
		feedback::add_success_msg('Done!');
	}

}

?>
