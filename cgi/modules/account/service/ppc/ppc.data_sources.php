<?php

class mod_account_service_ppc_data_sources extends mod_account_service_ppc
{
	private $cur_dss, $markets_on;
	
	public function pre_output()
	{
		parent::pre_output();
		$this->display_default = 'index';
		util::load_lib('delly');
		$this->register_widget('add_google_account');
	}
	
	public function display_index()
	{
		$this->cur_dss = db::select("
			select market, account, campaign, ad_group
			from data_sources
			where account_id = :aid
			order by market asc
		", array('aid' => $this->aid));

		$this->markets_on = array();
		for ($i = 0; list($market, $ac_id, $ca_id, $ag_id) = $this->cur_dss[$i]; ++$i) {
			if (!array_key_exists($market, $this->markets_on)) {
				$this->markets_on[$market] = self::$markets[$market];
			}
		}
		?>
		<div id="data_sources">
			<div id="current_data_sources">
				<h2>Current Data Sources</h2>
				<?php $this->print_current(); ?>
			</div>
			<?php $this->print_schedule_refresh(); ?>
			<div id="new_data_source">
				<h2>New Data Source</h2>
				<table>
					<?php $this->print_new_market(); ?>
					<?php $this->print_new_level(); ?>
					<?php $this->print_new_id('account' , 'Account' , null      , $this->ds_new_market    , 'Campaigns') ?>
					<?php $this->print_new_id('campaign', 'Campaign', 'account' , $_POST['ds_new_account'], 'Ad Groups') ?>
					<?php $this->print_new_id('ad_group', 'Ad Group', 'campaign', $_POST['ds_new_camaign'], null) ?>
				</table>
			</div>
			<?php $this->print_new_external(); ?>
			<div class="clr"></div>
		</div>
		<?php
	}

	public function display_bing_auth(){
		$bing_code =& $_REQUEST['code'];
		
		//Verify that the user indends to update the account before running request_access_token	
		if(isset($_SESSION['m_un'])):

		/* The assumption is that the code is in UUID format. The warning appears if it's not but the application continues */
		if(!preg_match('/[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}/',strtolower($bing_code))){
			$error = 'Bing code not in expected format';

		}

		//Request Access Token
		list($result,$message) = m_api::request_access_token($bing_code,$_SESSION['m_un'],$_SESSION['m_up']);
		//clear the client username and password from the session variable
		$redirect_previous = $_SESSION['add_extenal_current_page'];
		unset($_SESSION['m_un']);
		unset($_SESSION['m_up']);
		unset($_SESSION['add_extenal_current_page']);
		?>
		<div>
			<div>
				<p><?php echo $message; ?>: <a href="<?php echo $redirect_previous; ?>">Return to Account</a></p>
			</div>
			<p>Bing Code: <?php echo $bing_code; ?></p>
			<?php if(isset($error)): ?>
				<p><?php echo $error; ?></p>
			<?php endif; ?>
		</div>

		<?php
		//EndIf Update Condition
		endif;	
	}
	
	protected function print_new_external()
	{
		?>
		<fieldset id="new_external">
			<legend>Add External</legend>
			<p>
				<span style="color:red">New:</span> For new BING accounts there are additional steps.
				Once authorized, you will be redirecected back to this page.
			</p>
			<div id="new_external_instruct">
				<ol>
					<li>Enter Client's Username and Password below.</li>
					<li>Press [Submit]. You will be automatically redirected to Bing</li>
					<li>Login as the client (Enter username and password again)</li>
					<li>Grant access to wpromote web app.</li>
				</ol>
			</div>
			<table>
				<tbody>
					<tr>
						<td>User</td>
						<td><input type="text" name="ext_user" value="" /></td>
					</tr>
					<tr>
						<td>Pass</td>
						<td><input type="text" name="ext_pass" value="" /></td>
					</tr>
					<tr>
						<td></td>
						<td>
							<input type="submit" a0="action_add_external" value="Submit" />
							<input type="submit" id="cancel_add_external" value="Cancel" />
						</td>
					</tr>
				</tbody>
			</table>
		</fieldset>
		<?php
	}
	
	public function action_add_external()
	{
		$_SESSION['m_un'] = $_POST['ext_user'];
		$_SESSION['m_up'] = $_POST['ext_pass'];
		$_SESSION['add_extenal_current_page'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

		$d = db::select_row("SELECT client_id, redirect_uri FROM m_api_accounts WHERE mcc_user = :mcc_user",
							array('mcc_user'=>'technology@wpromote.com'));
		list($client_id,$redirect_uri) = $d;						
		$bing_auth_uri = 'https://login.live.com/oauth20_authorize.srf?';
		$bing_auth_uri .= http_build_query(array(
							'client_id'=>$client_id,
							'scope'=>'bingads.manage',
							'response_type'=>'code',
							'redirect_uri'=>$redirect_uri
		));

		//$bing_auth_uri = 'https://login.live.com/oauth20_authorize.srf?client_id=0000000040113A2B&scope=bingads.manage&response_type=code&redirect_uri=http://enet.wpromote.com/ppc/data_sources/bing_auth';
		header("Location: {$bing_auth_uri}");

		return true;
		list($ds_new_market, $ext_user, $ext_pass) = util::list_assoc($_POST, 'ds_new_market', 'ext_user', 'ext_pass');

		util::refresh_accounts($ds_new_market, $ext_user, $ext_pass, null, array('update_existing' => true));
		return true;
	}
	
	
	private function print_schedule_refresh()
	{
		?>
		<div id="schedule_refresh">
			<h2>Schedule Refresh</h2>
			
			<!-- currently scheduled -->
			<fieldset clear_me=1>
				<legend>Currently Scheduled</legend>
				<?php $this->print_current_refreshes(); ?>
			</fieldset>
			
			<!-- schedule new -->
			<?php $this->print_schedule_refresh_form(); ?>
		</div>
		<?php
	}
	
	private function print_current_refreshes()
	{
		$refreshes = ppc_schedule_refresh::get_all(array(
			'where' => "account_id = '{$this->aid}'",
			'order_by' => "frequency asc, day_of_week asc, day_of_month asc, time asc"
		));
		if ($refreshes->count() == 0)
		{
			echo 'None';
		}
		else
		{
			$ml = '';
			foreach ($refreshes as $refresh)
			{
				$ml .= '
					<tr rid="'.$refresh->id.'">
						<td>'.$refresh->frequency.'</td>
						<td>'.$refresh->get_frequency_details_string().'</td>
						<td time="'.$refresh->time.'">'.date('h:ia', strtotime('2000-01-01 '.$refresh->time)).'</td>
						<td>'.$refresh->num_days.' Days</td>
						<td>
							<a href="">Edit</a>
							<a href="">Delete</a>
						</td>
					</tr>
				';
			}
			echo '
				<table id="scheduled_refreshes">
					<tbody>
						'.$ml.'
					</tbody>
				</table>
			';
		}
	}
	
	private function print_current()
	{
		if (empty($this->cur_dss)) {
			echo 'None';
			return;
		}
		
		$ml_rows = '';
		$ac_counts = array();
		for ($i = 0; list($market, $ac_id, $ca_id, $ag_id) = $this->cur_dss[$i]; ++$i) {
			$ac_text = db::select_one("select text from eppctwo.{$market}_accounts where id=:acid", array('acid' => $ac_id))." ($ac_id)";
			$ca_text = (!empty($ca_id)) ? db::select_one("select text from {$market}_objects.campaign_{$this->account->id} where id=:caid", array('caid' => $ca_id))." ($ca_id)" : '';
			$ag_text = (!empty($ag_id)) ? db::select_one("select text from {$market}_objects.ad_group_{$this->account->id} where id=:agid", array('agid' => $ag_id))." ($ag_id)" : '';
			$this->set_account_user_pass($ac_user, $ac_pass, $market, $ac_id);
			
			// update m account pw
			if ($ac_user) {
				$ml_login = $ac_user.','.$ac_pass;
				if ($market == 'm' && !($ac_counts[$market] && $ac_counts[$market][$ac_id])) {
					$ml_login .= ' (<a href="" class="update_m_pass_link" ac_id="'.$ac_id.'">Update Pass</a>)';
				}
				$ac_counts[$market][$ac_id]++;
			}
			else {
				$ml_login = '';
			}
			$ml_rows .= '
				<tr ds_info="'.$market."\t".$ac_id."\t".$ca_id."\t".$ag_id.'">
					<td>'.(array_key_exists($market, self::$markets) ? self::$markets[$market] : $market).'</td>
					<td>'.$ml_login.'</td>
					<td>'.$ac_text.'</td>
					<td>'.$ca_text.'</td>
					<td>'.$ag_text.'</td>
					<td><input type="submit" name="delete_submit" a0="action_delete_data_source" value="Delete" /></td>
				</tr>
			';
		}
		
		$ml_refresh_types = '';
		$refresh_types = array(
			array('remote', '')
		);
		for ($i = 0; list($refresh_type, $refresh_text) = $refresh_types[$i]; ++$i) {
			if ($refresh_type == 'local' && !user::is_developer()) {
				continue;
			}
			foreach ($this->markets_on as $market => $market_disp) {
				$job = new job(array(), array(
					"do_get" => true,
					"select" => array("job" => array("id", "status", "finished")),
					"join" => array("ppc_data_source_refresh" => "
							ppc_data_source_refresh.account_id = :aid &&
							ppc_data_source_refresh.refresh_type = :rtype &&
							ppc_data_source_refresh.market = :market &&
							ppc_data_source_refresh.id = job.fid
						"),
					"where" => "job.type = 'PPC DATA SOURCE REFRESH'",
					"data" => array(
						'aid' => $this->aid,
						'rtype' => $refresh_type,
						'market' => $market
					),
					"order_by" => "job.created desc",
					"limit" => 1
				));
				
				$ml_header = 'Refresh'.(($refresh_text) ? ' '.$refresh_text : '');
				if (!$job->id || $job->is_done()) {
					$ml_refresh = '<a href="'.$this->href('refresh/?aid='.$this->aid.'&type='.$refresh_type.'&m='.$market).'">'.$market_disp.'</a>';
					if ($job->id) {
						$ml_refresh .= ' (Most Recent: '.$job->status.', '.$job->finished.')';
					}
				}
				else {
					$ml_refresh = '
						<span>Running: </span>
						<span job_id="'.$job->id.'" is_running="1"></span>
					';
				}
				$ml_refresh_types .= '
					<tr market="'.$market.'" refresh_type="'.$refresh_type.'">
						<td>'.$ml_header.'</td>
						<td>'.$ml_refresh.'</td>
					</tr>
				';
			}
		}
		echo '
			<table id="ds_table" border=1 cellpadding=0 cellspacing=0>
				<tr>
					<th>Market</th>
					<th>Login</th>
					<th>Account</th>
					<th>Campaign</th>
					<th>Ad Group</th>
					<th></th>
				</tr>
				'.$ml_rows.'
			</table>
			<table id="refresh_table">
				<tbody>
					'.$ml_refresh_types.'
				</tbody>
			</table>
			<input type="hidden" name="delete_info" value="" />
			<input type="hidden" id="g_update_email_ac_id" name="g_update_email_ac_id" value="" />
			<input type="hidden" id="m_update_pass_ac_id" name="m_update_pass_ac_id" value="" />
		';
	}
	
	private function print_schedule_refresh_form()
	{
		?>
		<div>
			<a href="" id="schedule_new_refresh_link">Schedule New</a>
			<a href="" id="schedule_cancel_link" class="hide">Cancel</a>
		</div>
		<fieldset id="_schedule_form" class="schedule_form hide" clear_me=1>
			<legend></legend>
			<?php echo ppc_schedule_refresh::html_form_new(); ?>
			<input type="hidden" name="schedule_refresh_edit_id" id="schedule_refresh_edit_id" value="" />
		</fieldset>
		<?php
	}
	
	public function action_ppc_schedule_refresh_submit()
	{
		$refresh = ppc_schedule_refresh::new_from_post();
		$edit_id = $_POST['schedule_refresh_edit_id'];
		if ($edit_id)
		{
			$refresh->id = $edit_id;
			$success_msg = 'Refresh Updated';
		}
		else
		{
			$success_msg = 'Refresh Scheduled';
		}
		$refresh->client_id = $this->cl_id;
		$refresh->user_id = user::$id;
		$refresh->put();
		
		feedback::add_success_msg($success_msg);
	}
	
	public function action_schedule_refresh_delete()
	{
		$refresh = new ppc_schedule_refresh(array('id' => $_POST['schedule_refresh_delete_id']), array('do_get' => false));
		if ($refresh->delete())
		{
			feedback::add_success_msg('Scheduled Refresh Deleted');
		}
		else
		{
			feedback::add_error_msg('Error Deleting Scheduled Refresh');
		}
	}
	
	public function action_delete_data_source()
	{
		list($market, $ac_id, $ca_id, $ag_id) = explode("\t", $_POST['delete_info']);
		$msg_ids = array($ac_id);
		if ($ca_id) {
			$msg_ids[] = $ca_id;
		}
		else {
			$ca_id = '';
		}
		if ($ag_id) {
			$msg_ids[] = $ag_id;
		}
		else {
			$ag_id = '';
		}
		
		$ds = new data_sources(array(
			'account_id' => $this->aid,
			'market' => $market,
			'account' => $ac_id,
			'campaign' => $ca_id,
			'ad_group' => $ag_id
		));
		$ds->delete();

		// garbage collection will take care of deleting account objects tables if there are no more data sources for a market
		
		feedback::add_success_msg("Data Source (<i>$market, ".implode(', ', $msg_ids)."</i>) Deleted");
	}
	
	public function action_m_update_ac_pass()
	{
		list($ac_id, $new_pass) = util::list_assoc($_POST, 'm_update_pass_ac_id', 'm_new_ac_pass');
		
		if (db::update("eppctwo.m_accounts", array('pass' => $new_pass), "id = :id", array('id' => $ac_id))) {
			feedback::add_success_msg('New password set for account <i>'.$ac_id.'</i>');
		}
		else {
			feedback::add_error_msg('Error updating passowrd: '.db::last_error());
		}
	}
	
	/**
	 * print_new_id
	 * used to print the ids of the list values on the view portion showing the drop down box
	 * @param
	 * @param
	 * @param
	 * @param
	 */
	protected function print_new_id($cur_short, $cur_long, $prev_short, $prev_data, $next_plural)
	{
		if (empty($this->ds_new_market) || empty($prev_data) || $this->action == 'action_new_submit') {
			return;
		}
		
		$cur_name = 'ds_new_'.$cur_short;
		$this->$cur_name = $_POST[$cur_name];
		
		$data_where = (!empty($prev_short)) ? "where {$prev_short}_id='$prev_data'" : '';
		$cur_simple_text = util::simple_text($cur_long).'s';
		
		// check for refresh
		if (array_key_exists('refresh_'.$cur_simple_text, $_POST)) {
			if ($cur_short == 'account') {
				util::refresh_accounts($this->ds_new_market);
			}
			else {
				util::load_lib('data_cache');
				$func = 'update_'.$cur_simple_text;
				data_cache::$func($this->account->id, $this->ds_new_market, $prev_data, array('do_set_client' => false, 'force_update' => true));
				feedback::add_success_msg('Refreshed');
			}
		}
		
		if ($cur_short == 'account') {
			$db = "eppctwo";
			$table = "{$this->ds_new_market}_{$cur_simple_text}";
			$id_col = "id";
			
			$options = db::select("
				select {$id_col}, concat(text, ' (', {$id_col}, ')')
				from {$db}.{$table}
				{$data_where}
				order by text asc
			");
		}
		else {
			$options = db::select("
				select id, concat(text, ' (', status, ')') as t
				from {$this->ds_new_market}_objects.{$cur_short}_{$this->aid}
				{$data_where}
				order by t asc
			");
		}
		array_unshift($options, array('', ' - Select - '));
		
		if ($this->ds_new_level == $cur_long) {
			$ml_buttons_below = '
				<tr>
					<td></td>
					<td><input type="submit" a0="action_new_submit" value="Submit" /></td>
				</tr>
			';
		}
		else {
			$ml_buttons_below = '';
		}
		
		echo '
			<tr>
				<td>'.$cur_long.'</td>
				<td>'.cgi::html_select($cur_name, $options, $this->$cur_name).'</td>
				<td><input type="submit" name="refresh_'.$cur_simple_text.'" value="Refresh" /></td>
				<td>'.(($cur_short == 'account') ? '<a href="" id="add_external_button">Add External</a>' : '').'</td>
				'.(($cur_short == 'account' && $this->ds_new_market == 'g') ? '<td> &nbsp; <a href="" class="add_google_account_link">Add Google Account</a></td>' : '').'
			</tr>
			'.$ml_buttons_below.'
		';
	}
	
	protected function print_new_market()
	{
		$this->ds_new_market = ($this->action == 'action_new_submit') ? '' : $_POST['ds_new_market'];
		$options = util::get_ppc_markets('NUM', $this->account);
		array_unshift($options, array('', ' - Select - '));
		?>
		<tr>
			<td>Market</td>
			<td><?php echo cgi::html_select('ds_new_market', $options, $this->ds_new_market); ?></td>
		</tr>
		<?php
	}
	
	protected function print_new_level()
	{
		$this->ds_new_level = ($this->action == 'action_new_submit') ? '' : $_POST['ds_new_level'];
		$options = array(
			array('', ' - Select - '),
			array('Account', 'Account'),
			array('Campaign', 'Campaign'),
			array('Ad Group', 'Ad Group')
		);
		?>
		<tr>
			<td>Level</td>
			<td><?php echo cgi::html_select('ds_new_level', $options, $this->ds_new_level); ?></td>
		</tr>
		<?php
	}
	
	protected function action_new_submit()
	{
		$market = $_POST['ds_new_market'];
		$ac_id = $_POST['ds_new_account'];
		$ca_id = $_POST['ds_new_campaign'];
		$ag_id = $_POST['ds_new_ad_group'];
		
		$ds_data = array(
			'account_id' => $this->aid,
			'market' => $market,
			'account' => $ac_id
		);
		if ($ca_id) $ds_data['campaign'] = $ca_id;
		if ($ag_id) $ds_data['ad_group'] = $ag_id;
		
		// create market account objects
		if (!ppc_lib::create_market_object_tables($market, $this->aid)) {
			feedback::add_error_msg('New datasource failed: '.db::last_error());
		}

		db::insert("eppctwo.data_sources", $ds_data);
		db::update("eppctwo.{$market}_accounts", array('status' => 'On'), "id = :id", array('id' => $ac_id));
		
		feedback::add_success_msg('Datasource successfully added.');
	}
	
	
	public function display_refresh()
	{
		list($market, $type) = util::list_assoc($_GET, 'm', 'type');
		if ($market == 'f') {
			$this->print_refresh_facebook();
		}
		else {
			$this->print_refresh_date_select($market, $type);
		}
	}
	
	private function print_refresh_facebook()
	{
		?>
		<div class="b">Refresh Facebook</div>
		<table>
			<tr>
				<td>Report CSV</td>
				<td><input type="file" name="fb_data" /></td>
			</tr>
			<tr>
				<td></td>
				<td>
					<input type="submit" value="Submit" a0="action_refresh_facebook" a0href="<?= $this->href('?aid='.$this->aid) ?>" />
					<input type="submit" value="Cancel" a0="" a0href="<?= $this->href('?aid='.$this->aid) ?>" />
				</td>
			</tr>
		</table>
		<?php
	}
	
	private function print_refresh_date_select($market, $type)
	{
		if (user::is_developer()) {
			$ml_force = '
				<tr>
					<td></td>
					<td>
						<input type="checkbox" name="do_force" id="do_force" value="1" />
						<label for="do_force">Force Refresh</label>
					</td>
				</tr>
			';
		}
		$market_disp = self::$markets[$market];
		?>
		<div class="b">Refresh <?php echo $market_disp; ?></div>
		<table>
			<tr>
				<td>Start Date</td>
				<td><input type="text" class="date_input" name="start_date" /></td>
			</tr>
			<tr>
				<td>End Date</td>
				<td><input type="text" class="date_input" name="end_date" /></td>
			</tr>
			<?= $ml_force ?>
			<tr>
				<td></td>
				<td>
					<input type="submit" value="Submit" a0="action_refresh" a0href="<?= $this->href('?aid='.$this->aid) ?>" />
					<input type="submit" value="Cancel" a0="" a0href="<?= $this->href('?aid='.$this->aid) ?>" />
				</td>
			</tr>
		</table>
		<input type="hidden" name="refresh_market" value="<?php echo $market; ?>" />
		<input type="hidden" name="refresh_type" value="<?php echo $type; ?>" />
		<?php
	}
	
	public function action_refresh_facebook()
	{
		require_once(\epro\WPROPHP_PATH.'apis/apis.php');
		
		$report_path = $_FILES['fb_data']['tmp_name'];
		
		$api = new f_api(1, $this->cl_id);
		$api->import_report($report_path);
		ppc_lib::calculate_cdl_vals($this->cl_id);
		feedback::add_success_msg('Facebook data imported');
	}
	
	public function action_refresh()
	{
		// todo: type obsolete
		list($market, $type, $do_force, $start_date, $end_date) = util::list_assoc($_POST, 'refresh_market', 'refresh_type', 'do_force', 'start_date', 'end_date');

		if (!util::is_valid_date_range($start_date, $end_date)) {
			return feedback::add_error_msg('Invalid date range');
		}
		if ($end_date >= \epro\TODAY) {
			$end_date = date(util::DATE, time() - 86400);
		}

		$job_info = ppc_data_source_refresh::create(array(
			'account_id' => $this->aid,
			'refresh_type' => $type,
			'market' => $market,
			'do_force' => $do_force,
			'start_date' => $start_date,
			'end_date' => $end_date
		));
		job::queue(array(
			'type' => 'PPC DATA SOURCE REFRESH',
			'fid' => $job_info->id,
			'account_id' => $this->aid
		));
		
		feedback::add_success_msg('Data Source Refreshed Scheduled');
	}

	public function ajax_job_status()
	{
		$job = new job(array('id' => $_POST['job_id']), array(
			"select" => array(
				"job" => array("status", "started", "finished"),
				"job_detail" => array("ts", "level", "message")
			),
			"left_join" => array("job_detail" => "job.id = job_detail.job_id"),
			"order_by" => "job_detail.ts desc",
			"limit" => 1
		));
		echo $job->json_encode();
	}
	
}

?>