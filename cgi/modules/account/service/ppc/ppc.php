<?php

define('PPC_REPORT_JOB_NAME', 'PPC REPORT');

class mod_account_service_ppc extends mod_account_service
{
	protected $m_name = 'ppc';
	
	protected static $markets;
	
	public function get_menu()
	{
		$menu = parent::get_menu();
		$menu->prepend(new MenuItem('CDL'         ,'cdl', array('query_keys' => array('aid'))));
		$menu->append(
			new MenuItem('Data Sources','data_sources', array('query_keys' => array('aid'))),
			new MenuItem('Reporting'   ,'reporting', array('query_keys' => array('aid'))),
			new MenuItem('B&F'         ,'bf', array('query_keys' => array('aid')))
		);
		if (user::is_admin()) {
			$menu->append(new MenuItem('Managers', array('ppc', 'managers'), array('query_keys' => array('aid'))));
		}
		if (user::is_admin() || user::has_role('Leader', 'ppc')) {
			$menu->append(new MenuItem('New Client', array('ppc', 'new_client')));
		}
		if (user::is_developer()){
			$menu->append(new MenuItem('meta'        ,array('ppc','meta')));
		}
		return $menu;
	}
	
	public function pre_output()
	{
		parent::pre_output();
		self::$markets = util::get_ppc_markets('ASSOC', $this->client);
		
		$this->display_default = 'cdl';
	}

	public function pre_output_cdl()
	{
		global $g_report_cols;

		$tmp_cols = ppc_cdl_user_cols::get_all(array(
			'select' => "*",
			'where' => "
				user_id = :uid'".db::escape(user::$id)."' &&
				account_id in (:aids)
			",
			'data' => array(
				"uid" => user::$id,
				"aids" => array("default", $this->aid)
			),
			'key_col' => "account_id"
		));
		if ($tmp_cols->key_exists($this->aid)) {
			$this->user_cols = $tmp_cols->i($this->aid);
		}
		else if ($tmp_cols->key_exists('default')) {
			$this->user_cols = $tmp_cols->i('default');
		}
		else {
			$this->user_cols = new ppc_cdl_user_cols(
				array('account_id' => $this->account_id, 'user_id' => user::$id),
				array('do_get' => false)
			);
		}

		// deny instead of allow since we do not know group names for named conversions
		$ignore_cdl_col_groups = array('ad', 'detail');
		$this->col_options = array();
		util::init_report_cols($this->aid);
		$this->report_cols = $g_report_cols;
		foreach ($g_report_cols as $col_name => $col_info) {
			if (!in_array($col_info['group'], $ignore_cdl_col_groups)) {
				$this->col_options[] = array($col_name, util::display_text($col_name), $col_info['group']);
			}
		}
	}
	
	public function pre_output_reporting()
	{
		global $g_report_cols;

		util::init_report_cols($this->aid, array('do_include_extensions' => true));
		$this->report_cols = $g_report_cols;
		
		if (g::$p5 == 'download') {
			$this->rep_download();
		}else if (g::$p5 == 'edit') {
			cgi::add_js('lib.filter.js');
			cgi::add_js('ppc.reporting.edit_sheet.js');
			cgi::add_js('ppc.reporting.edit_table.js');
		}else if (g::$p5 == 'media') {
			$this->media = new client_media(array('account_id' => $this->aid), array(
				"select" => array("client_media" => array("id")),
				"join" => array("client_media_use" => "
					client_media.id = client_media_use.client_media_id &&
					client_media_use.use = 'PPC Report Logo'
				"),
				'do_get' => true
			));
		}else if (g::$p5 == 'media_img') {
			$media = new client_media(array('id' => $_REQUEST['mid']));

			header('Content-Type: '.$media->type);
			echo $media->data;
			exit;
		}
		
		// for debugging
		if (dbg::is_on() && array_key_exists('dbg_excel_download', $_POST)) $this->action_rep_run_report();
		if (dbg::is_on() && array_key_exists('dbg_show_queries', $_POST)) db::dbg();
	}

	public function pre_output_billing()
	{
		parent::pre_output_billing();
		$this->page_menu[] = array('rollover', 'Rollover');
	}

	public function display_cdl()
	{
		// set client bill dates
		// default to pre defined of "this client month"
		$date_range = date_range::init(array(
			'default_defined' => 'This Client Month',
			'rollover_date' => $this->account->next_bill_date,
			'end_cap' => date('Y-m-d', time() - 86400)
		));
		if (!$date_range->is_valid()) {
			feedback::add_error_msg('Invalid date range: '.$date_range->start.'  to '.$date_range->end);
			$date_range->set_default_dates();
		}
		
		if (!empty($this->user_cols->cols)) {
			$this->user_cols->cols = explode("\t", $this->user_cols->cols);
		}
		// user has not set columns for this client, use default
		else {
			$this->user_cols->cols = array('clicks', 'convs', 'cost', 'revenue');
		}
		$this->conv_types = conv_type_count::get_account_conv_types($this->aid);
		$this->conv_type_map = conv_type::get_client_market_map($this->aid);
		$ml_markets = '';
		$data = all_data::get_data(array(
			'account' => $this->account,
			'market' => 'all',
			'detail' => 'account',
			'time_period' => 'daily',
			'do_total' => false,
			'do_time_period_total' => true,
			'fields' => array_combine(array_merge(array('market'), $this->user_cols->cols), array_fill(0, count($this->user_cols->cols) + 1, 1)),
			'conv_type_cols' => $this->conv_types,
			'start_date' => $date_range->start,
			'end_date' => $date_range->end
		));
		$market_data = array();
		if ($data->data) {
			foreach ($data->data as $d) {
				$market = $d['market'];
				if ($market == 'Bing') {
					$market = 'MSN';
				}
				$market_data[$market][$d['daily']] = $d;
			}
		}
		foreach (self::$markets as $market => $market_text) {

			$conv_type_counts = conv_type_count::get_all(array(
				'select' => array("conv_type_count" => array("d", "name", "sum(amount) as amount")),
				'where' => "
					aid = :aid &&
						d between :start and :end &&
					name <> '' &&
					market = :market
				",
				'data' => array(
					"aid" => $this->aid,
					"start" => $date_range->start,
					"end" => $date_range->end,
					"market" => $market
				),
				'group_by' => "d, name",
				'order_by' => "name asc",
				'key_col' => "d",
				'key_grouped' => true
			));

			$ml_markets .= $this->cdl_set_spend($market_data[$market_text], $market, $date_range->start, $date_range->end, $conv_type_counts);
		}

		?>
		<table>
			<?= $date_range->ml() ?>
			<tr>
				<td></td>
				<td><input type="submit" value="Set Dates" /></td>
			</tr>
		</table>
		<div>
			<div>
				<a href="" class="ajax" id="select_cols_link">Change Columns</a>
			</div>
			<fieldset id="w_col_options" class="hide">
				<legend>Set Columns</legend>
				<?= cgi::html_checkboxes('cols', $this->col_options, $this->user_cols->cols, array('separator' => ' &nbsp; ', 'toggle_all' => false)) ?>
				<div>
					<label for="save_for_client">Save for <i><?= $this->account->name ?></i></label>
					<input type="checkbox" id="save_for_client" name="save_for_client" value="1" checked="1" />
				</div>
				<div>
					<label for="make_default">Save as default for all my clients</label>
					<input type="checkbox" id="make_default" name="make_default" value="1" />
					<span class="small"> (will not affect other clients you have already set columns for)</span>
				</div>
				<input type="submit" a0="action_set_cols" value="Set Columns" />
			</fieldset>
			<div class="clr"></div>
		</div>
		<?= $this->client_m_error() ?>
		<table>
			<tbody>
				<tr valign="top">
					<?= $ml_markets ?>
					<?= $this->cdl_set_spend($data->time_period_totals, 'Aggregate', $date_range->start, $date_range->end) ?>
				</tr>
			</tbody>
		</table>
		<?php echo $ml_spend; ?>
		<?php
	}

	public function action_set_cols()
	{
		$tmp_cols = $_POST['cols'];
		if (empty($tmp_cols)) {
			feedback::add_error_msg("Please select at least one column");
			return false;
		}
		if ($tmp_cols == '*') {
			$cols = array();
			foreach ($this->col_options as $col_info) {
				$cols[] = $col_info[0];
			}
			$cols = implode("\t", $cols);
		}
		else {
			$cols = $tmp_cols;
		}

		if (!empty($_POST['make_default'])) {
			$default_cols = new ppc_cdl_user_cols(array(
				'account_id' => 'default',
				'user_id' => user::$id,
				'cols' => $cols
			), array('do_get' => false));
			$default_cols->put();
			feedback::add_success_msg('Default Columns Updated');
		}

		$this->user_cols->cols = $cols;
		if (!empty($_POST['save_for_client'])) {
			$this->user_cols->account_id = $this->aid;
			$this->user_cols->put();
			feedback::add_success_msg('Client Columns Updated');
		}
	}
	
	private function client_m_error()
	{
		$data_date = date(util::DATE, time() - 86400);
		$m_ds_accounts = db::select("
			select account
			from eppctwo.data_sources
			where
				market = 'm' &&
				account_id = '{$this->aid}'
		", array(
			""));
		$errors = db::select("
			select account_id, details
			from eppctwo.m_data_error_log
			where
				success = 0 &&
				d = '$data_date' &&
				account_id in ('".implode("','", $m_ds_accounts)."')
		");
		if ($errors)
		{
			$ml = '';
			for ($i = 0; list($account_id, $details) = $errors[$i]; ++$i)
			{
				$ml .= '<p>Data pull error for MSN account '.$account_id.': '.$details.'</p>';
			}
			return '
				<div class="error_msg">
					'.$ml.'
				</div>
			';
		}
		else
		{
			return '';
		}
	}
	
	protected function cdl_set_spend(&$data, $market, $start_date, $end_date, $conv_type_counts = false)
	{
		$ml = '';
		$col_count = count($this->user_cols->cols);
		$totals = array();
		$ml_headers = '';
		foreach ($this->user_cols->cols as $col_name) {
			$ml_headers .= '<th>'.util::display_text($col_name).'</th>';
			$totals[$col_name] = 0;
		}
		for ($i = $end_date; $i >= $start_date; $i = date(util::DATE, strtotime("$i -1 day"))) {
			if (is_array($data) && array_key_exists($i, $data)) {
				$d = &$data[$i];
				if ($conv_type_counts && $conv_type_counts->key_exists($i)) {
					$ctcounts_for_date = $conv_type_counts->i($i);
					foreach ($ctcounts_for_date as $ctcount) {
						$ct_name = (isset($this->conv_type_map[$market][$ctcount->name])) ? $this->conv_type_map[$market][$ctcount->name] : $ctcount->name;
						$d[$ct_name] = $ctcount->amount;
					}
				}
				foreach ($totals as $k => $v) {
					$totals[$k] = $v + $d[$k];
				}
			}
			else {
				$d = array_combine($this->user_cols->cols, array_fill(0, $col_count, 0));
			}
			
			$ml .= '
				<tr>
					<td>'.$i.'</td>
					'.$this->cdl_get_row_ml($market, $d).'
				</tr>
			';
		}
		all_data::compute_data_metrics($totals, array('do_calc_percents' => true, 'conv_type_cols' => $this->conv_types, 'cols' => $totals));
		$ml_totals = '
			<td>Total</td>
			'.$this->cdl_get_row_ml($market, $totals).'
		';
		
		$market_text = (array_key_exists($market, self::$markets)) ? self::$markets[$market] : $market;
		
		return '
			<td id="client_dashboard_data_container">
				<table class="client_dashboard_data">
					<thead>
						<tr>
							<th colspan="32">'.$market_text.'</th>
						</tr>
						<tr>
							<th>Date</th>
							'.$ml_headers.'
						</tr>
						<tr>
							'.$ml_totals.'
						</tr>
					</thead>
					<tbody>
						'.$ml.'
					</tbody>
					<tfoot>
						<tr>
							'.$ml_totals.'
						</tr>
					</tfoot>
				</table>
			</td>
		';
	}
	
	private function cdl_get_row_ml($market, &$d)
	{
		$ml = '';
		$format_keys = array('format', 'format_excel');
		foreach ($this->user_cols->cols as $col_name) {
			$do_show_func = 'cdl_do_show_'.$col_name;
			if (!method_exists($this, $do_show_func) || $this->$do_show_func($market)) {
				$v = $d[$col_name];
				foreach ($format_keys as $format_key) {
					if (isset($this->report_cols[$col_name][$format_key])) {
						$format_func = $this->report_cols[$col_name][$format_key];
						$v = util::$format_func($v);
						break;
					}
				}
				$ml .= "<td>{$v}</td>";
			}
		}
		return $ml;
	}
	
	private function cdl_do_show_revenue($market)
	{
		// 2013-03-17: what should we do about this?
		return true;
		// return ($this->client->revenue_tracking);
	}
	
	private function cdl_do_show_mpc($market)
	{
		return ($market == 'g' && $this->account->google_mpc_tracking);
	}
	
	private static $rep_optional_fields = array('meta_desc', 'time_period_weekly', 'time_period_monthly', 'ext_type', 'y_axis_label', 'total_type');
	
	// todo: should be it's own file like ppc.data_sources.php
	public function display_reporting()
	{
		$method = (!g::$p5)? 'home' : g::$p5;
		$this->call_member("rep_{$method}", 'rep_home');
	}
	
	protected function rep_home()
	{
		/*
		 * saved reports
		 */
		$saved_reports = db::select("
			select id, user, name, create_date, last_run
			from eppctwo.reports
			where account_id = :aid
			order by last_run desc
		", array(
			"aid" => $this->aid
		));
		if (empty($saved_reports)) {
			$ml_saved_reports = '<tr><td colspan=20>No Saved Reports</td></tr>';
		}
		else {
			$ml_saved_reports = '';
			for ($i = 0; list($id, $uid, $name, $create_date, $last_run) = $saved_reports[$i]; ++$i) {
				$ml_saved_reports .= '
					<tr>
						<td><a href="'.$this->href('reporting/edit?aid='.$this->aid.'&rep_id='.$id).'">'.$name.'</a></td>
						<td>'.$create_date.'</td>
						<td>'.$last_run.'</td>
					</tr>
				';
			}
		}
		/*
		 * scheduled reports
		 */
		$scheduled_reports = job::get_all(array(
			'select' => array(
				"job" => array("id", "user_id", "fid", "status", "finished"),
				"reports" => array("name as rep_name")
			),
			'where' => "reports.account_id = :aid && type='".PPC_REPORT_JOB_NAME."'",
			'join' => array("reports" => "reports.id = job.fid"),
			'data' => array("aid" => $this->aid),
			'order_by' => "created desc",
			'limit' => "20",
			'flatten' => true
		));
		$scheduled_count = $scheduled_reports->count();
		// we check for completed status in javascript and convert to link there
		// because we also check for processing reports in js to update status
		// so may as well check completed report status there as well
		$ml_scheduled_reports = '';
		foreach ($scheduled_reports as $job) {
			if (!$job->is_done()) {
				$loading_ml = '<img src="'.cgi::href('img/loading.gif').'" />';
				$status_class = ' class="rep_running"';
			}
			else {
				$loading_ml = '';
				if ($job->status == 'Completed') {
					$details = $job->finished;
					$status_class = ' class="rep_completed"';
				}
				else {
					$details = '';
					$status_class = ' class="rep_error"';
				}
			}
			$ml_scheduled_reports .= '
				<tr job_id="'.$job->id.'"'.$status_class.'>
					<td>'.$job->rep_name.'</td>
					<td class="rep_job_status" id="job_status_'.$job->id.'">'.$job->status.'</td>
					<td>'.$details.'</td>
				</tr>
			';
		}

		// check for completed reports run pre-delly
		// todo: 2013-10-06, can get rid of this eventually
		if ($scheduled_count < 20) {
			$old_reps = db::select("
				select r.name, j.id, j.processing_end
				from eppctwo.reports r, eppctwo.jobs j
				where
					r.account_id = :aid &&
					j.status = 'Completed' &&
					j.type = :rep_type &&
					r.id = j.foreign_id
				order by j.processing_end desc
				limit ".(20 - $scheduled_count)."
			", array(
				'aid' => $this->aid,
				'rep_type' => PPC_REPORT_JOB_NAME
			));
			for ($i = 0, $ci = count($old_reps); $i < $ci; ++$i) {
				list($rep_name, $job_id, $finished) = $old_reps[$i];

				$ml_scheduled_reports .= '
					<tr>
						<td>'.$rep_name.'</td>
						<td><a target="_blank" href="'.cgi::href('/ppc/reporting/download?aid='.$this->aid.'&job_id='.$job_id).'">Completed</a></td>
						<td>'.$finished.'</td>
					</tr>
				';
			}
			// no reports
			if ($scheduled_count == 0 && $i == 0) {
				$ml_scheduled_reports = '<tr><td colspan=20>No Scheduled Reports</td></tr>';
			}
		}
		
		?>
		<div class="p_v8">
			<a href="<?php echo $this->href('reporting/edit?aid='.$this->aid); ?>">Create New Report</a>
			&nbsp;
			<a href="<?php echo $this->href('reporting/media?aid='.$this->aid); ?>">Upload Client Logo</a>
		</div>
		
		<div class="lft p_r32">
			<div align="center" class="list_of_reports">Edit Report</div>
			<table>
				<thead>
					<tr>
						<th>Name</th>
						<th>Created</th>
						<th>Last Run</th>
					</tr>
				</thead>
				<tbody>
					<?php echo $ml_saved_reports; ?>
				</tbody>
			</table>
		</div>
		<div class="lft">
			<div align="center" class="list_of_reports">Download Report</div>
			<table>
				<thead>
					<tr>
						<th>Name</th>
						<th>Status</th>
						<th></th>
					</tr>
				</thead>
				<tbody id="scheduled_reports" ejo>
					<?php echo $ml_scheduled_reports; ?>
				</tbody>
			</table>
		</div>
		<div class="clr"></div>
		
		<input type="hidden" name="rep_id" value="" />
		<?php
	}
	
	public function rep_media()
	{
		$ml = '';
		if (!empty($this->media->id)) {
			$ml .= '
				<img id="current_media" src="'.$this->rhref('reporting/media_img?mid='.$this->media->id).'" />
			';
		}
		else {
			$ml .= '<span>None</span>';
		}
		?>
		<div class="p_v8">
			<a id="rep_home_link" href="<?= $this->href('reporting?aid='.$this->aid) ?>">Back to Reporting Home</a>
		</div>
		<table class="p_v8">
			<tbody>
				<tr>
					<td><lable for="media">Upload New Logo</label></td>
					<td><input type="file" name="media" id="media" /></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" a0="action_rep_media_submit" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		<div class="p_v8">
			<span>Current Media: </span>
			<?= $ml ?>
		</div>
		<?php
	}

	public function action_rep_media_submit()
	{
		$name = $type = $tmp_name = $error = $size = null;
		extract($_FILES['media'], EXTR_IF_EXISTS);
		if (!empty($error)) {
			return feedback::add_error_msg('Error: '.$error);
		}
		if (strpos($type, 'image') === false) {
			return feedback::add_error_msg('Uploaded file does not appear to be an image');
		}
		if ($size > client_media::MAX_SIZE) {
			return feedback::add_error_msg("Media max file size exceeded: {$size} > ".client_media::MAX_SIZE);
		}

		list($w, $h) = getimagesize($tmp_name);

		// delete any old ppc rep logo media for this client
		if (!empty($this->media->id)) {
			$this->media->delete();
			client_media_use::delete_all(array("where" => "
				client_media_id = '".db::escape($this->media->id)."' &&
				use = 'PPC Report Logo'
			"));
		}
		$this->media = client_media::create(array(
			'account_id' => $this->aid,
			'user_id' => user::$id,
			'ts' => date(util::DATE_TIME),
			'type' => $type,
			'name' => $name,
			'data' => file_get_contents($tmp_name),
			'w' => $w,
			'h' => $h
		));
		client_media_use::create(array(
			'client_media_id' => $this->media->id,
			'use' => 'PPC Report Logo'
		));
		feedback::add_success_msg('Media successfully uploaded');
	}

	private function print_rep_god_to_mortal($rep_id)
	{
		if (!user::is_developer()) return;
		
		list($rep_uid) = db::select_row("
			select user
			from eppctwo.reports
			where id = '$rep_id'
		");
		
		$users = db::select("
			select id, realname
			from users
			where username <> '".$_SESSION['username']."'
			order by realname asc
		");
		
		?>
		<div>
			<label>Assign To</label>
			<?php echo cgi::html_select('assign_to', $users, $rep_uid); ?>
			<input type="submit" a0="action_rep_assign_to" value="Submit" />
		</div>
		<?php
	}
	
	public function action_rep_assign_to()
	{
		list($rep_id, $assign_to_uid) = util::list_assoc($_POST, 'rep_id', 'assign_to');
		
		db::update("eppctwo.reports", array(
			'user' => $assign_to_uid
		), "id = '$rep_id'");
		feedback::add_success_msg("Report assigned");
	}
	
	protected function rep_edit()
	{
		util::load_lib('data_cache');
		
		// $this->rep_id could be set by an action
		$rep_id = $this->rep_id;
		if (!$rep_id && $_REQUEST['rep_id']) {
			$rep_id = $_REQUEST['rep_id'];
		}
		if ($rep_id) {
			list($rep_name, $is_template) = db::select_row("select name, is_template from reports where id='{$rep_id}'");
		}
		else {
			$ml_overwrite = '';
			$rep_name = $this->account->name;
		}
		
		$tpl_id = $_GET['tpl_id'];
		
		$init_data_id = util::unnull($rep_id, $tpl_id, '');
		if ($init_data_id) {
			$saved_sheets = ppc_report_sheet::get_all(array(
				'select' => array(
					"ppc_report_sheet" => array("id", "name", "position as spos"),
					"ppc_report_table" => array("position as tpos", "definition")
				),
				'join_many' => array(
					"ppc_report_table" => "ppc_report_sheet.id = ppc_report_table.sheet_id"
				),
				'where' => "ppc_report_sheet.report_id = :rep_id",
				'data' => array("rep_id" => $init_data_id),
				'order_by' => "spos asc, tpos asc"
			));
			cgi::add_js_var('rep_init_data', $saved_sheets);
		}
		
		util::set_data_date_options($data_date_options);

		$date_range = date_range::init(array(
			'default_defined' => 'This Client Month',
			'rollover_date' => $this->account->next_bill_date,
			'show_quarterly' => true,
			'end_cap' => date('Y-m-d', time() - 86400),
			'js_id' => 'rep',
			'orientation' => 'horizontal'
		));
		$date_range->output_js();

		$ml_template = '';
		if ($this->is_user_leader()) {
			$ml_template = '
				<span>
					<input type="checkbox" name="is_template" id="is_template" value="1"'.(($is_template) ? ' checked="1"' : '').' />
					<label for="is_template">Save As Template</label>
				</span>
			';
		}

		// debugging options
		if (dbg::is_on()) { ?>
		<p><input type="checkbox" name="dbg_show_queries" /> Show Queries</p>
		<p><input type="checkbox" name="dbg_browser_output" /> Output to browser</p>
		<p><input type="checkbox" name="dbg_excel_download" /> Excel Download</p>
		<?php } ?>
		<div class="p_v8">
			<a id="rep_home_link" href="<?php echo $this->href('reporting?aid='.$this->aid); ?>">Back to Reporting Home</a>
		</div>
		
		<label>Report Name</label>
		<input id="report_name" type="text" name="name" value="<?php echo $rep_name; ?>" />
		<?php echo $ml_overwrite; ?>
		<div class="clr"></div>
		
		<?php $this->print_rep_god_to_mortal($rep_id); ?>
		<?php $this->print_rep_load_template($rep_id, $tpl_id); ?>
		
		<div id="reporting_header_row">
			<div id="buttons">
				<div id="add_sheet_button" class="top_button">Add Sheet</div>
				<div id="add_table_button" class="top_button">Add Table</div>
				<div id="set_dates_button" class="top_button">Set Dates</div>
				<div id="load_sheet_button" class="top_button">Load Sheet</div>
				<div id="refresh_campaigns_button" class="top_button">Refresh Campaigns</div>
				<div id="save_changes_button" class="top_button">Save Changes</div>
				<div id="run_report_button" class="top_button">Run Report</div>
				<?= $ml_template ?>
				<div class="clr"></div>
			</div>
			<div id="saving">Saving <?= cgi::loading() ?></div>
			<table id="set_dates_container" class="hide">
				<tbody>
					<tr>
						<td>
						
							<!-- new dates -->
							<table id="set_dates_new_table">
								<tbody>
									<tr>
										<td>New Start Date</td>
										<td><input type="text" class="date_input" name="set_dates_start" /></td>
									</tr>
									<tr>
										<td>New End Date</td>
										<td><input type="text" class="date_input" name="set_dates_end" /></td>
									</tr>
								</tbody>
							</table>
							
							<!-- pick which old dates to replace -->
							<div>Replace:</div>
							<div id="set_dates_replace_container"></div>
							
							<!-- submit -->
							<table>
								<tbody>
									<tr>
										<td>
											<input type="submit" id="set_dates_submit_button" value="Set Dates" />
											<input type="submit" id="set_dates_cancel_button" value="Cancel" />
										</td>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		
		<div id="sheet_headers"></div>
		<div class="clr"></div>
		<div id="sheet_container"></div>
		
		<input type="hidden" name="rep_id" id="rep_id" value="<?php echo $rep_id; ?>" />
		<input type="hidden" name="col_count" value="<?php echo $this->rep_get_col_count($this->report_cols); ?>" />
		<?php
		
		if (array_key_exists('run_report', $_POST)) $this->action_rep_run_report();
		
		// get campaigns for filter
		// extensions for extension detail types
		$cas = array();
		$exts = array();
		foreach (self::$markets as $market => $market_display) {
			$market_cas = db::select("
				select
					concat(:market, '_', id) as value, concat(:market_display, ' - ', text, ' (', status, ')') as text,
					concat('class=\"ca_cbox\" m=\"', :market, '\"') as attrs
				from {$market}_objects.campaign_{$this->aid}
				order by text asc
			", array(
				'market' => $market,
				'market_display' => $market_display
			), 'ASSOC');
			if ($market_cas) {
				$cas = array_merge($cas, $market_cas);
			}

			// get exts for market
			// exts with all numeric types are not fully integrated yet
			$market_exts = db::select("
				select distinct type
				from {$market}_objects.extension_data_{$this->aid}
				where type not regexp '^[[:digit:]]+$'
			");
			if ($market_exts) {
				$exts = array_merge($exts, $market_exts);
			}
		}
		$exts = array_unique($exts);
		sort($exts);
		
		// get named conversions
		$conv_types = conv_type_count::get_account_conv_types($this->aid);
		
		// too many ad groups, this isn't gonna work,
		// gotta lazy load and force user to select a campaign first
		//util::set_info($ags, g::$client, $markets, 'ad_group');
		echo "\n<script type=\"text/javascript\">\n";
		cgi::to_js('g_markets', self::$markets, util::NO_FLAGS);
		cgi::to_js('g_all_cols', $this->report_cols, util::NO_FLAGS);
		cgi::to_js('g_optional_fields', self::$rep_optional_fields, util::NO_FLAGS);
		cgi::to_js('g_weekdays', util::get_weekdays(), util::NO_FLAGS);
		cgi::to_js('g_date_options', $data_date_options, util::NO_FLAGS);
		cgi::to_js('g_conv_types', $conv_types, util::NO_FLAGS);
		echo "</script>\n";
		
		if ($this->is_user_leader()) {
			cgi::add_js_var('do_show_display_type', 1);
		}
		cgi::add_js_var('is_user_loaded_template', !empty($tpl_id));
		cgi::add_js_var('bill_day', $this->account->bill_day);
		cgi::add_js_var('campaigns', $cas);
		cgi::add_js_var('extensions', $exts);
		cgi::add_js_var('display_types', array(
			array('table', 'Table'),
			array('line_chart', 'Line Chart'),
			array('bar_chart', 'Bar Chart')
		));
	}

	public function action_refresh_campaigns()
	{
		foreach (self::$markets as $market => $market_display) {
			$ac_ids = db::select("
				select distinct account
				from eppctwo.data_sources
				where client = '".$this->cl_id."' && market = '$market' && campaign = ''
			");
			foreach ($ac_ids as $ac_id) {
				data_cache::update_campaigns($this->cl_id, $market, $ac_id);
			}
		}
		feedback::add_success_msg('Campaigns refreshed');
	}
	
	public function ajax_save_sheet()
	{
		list($rep_id, $rep_name, $position, $name, $is_template) = util::list_assoc($_REQUEST, 'report_id', 'report_name', 'position', 'name', 'is_template');

		// first sheet we are saving
		if ($position == 0) {
			if ($rep_id) {
				// clear out old data
				ppc_report_sheet::delete_all(array(
					'where' => "report_id = :rep_id",
					'data' => array("rep_id" => $rep_id)
				));
				ppc_report_table::delete_all(array(
					'where' => "report_id = :rep_id",
					'data' => array("rep_id" => $rep_id)
				));
				// save name
				reports::save(array(
					'id' => $rep_id,
					'name' => $rep_name,
					'is_template' => $is_template
				));
			}
			else {
				// new report
				$report = reports::create(array(
					'user' => user::$id,
					'account_id' => $this->aid,
					'name' => $rep_name,
					'is_template' => $is_template,
					'create_date' => \epro\TODAY
				));
				$rep_id = $report->id;
			}
		}
		$sheet = ppc_report_sheet::create(array(
			'report_id' => $rep_id,
			'name' => $name,
			'position' => $position
		));
		echo json_encode($sheet);
	}
	
	public function ajax_save_table()
	{
		parse_str($_REQUEST['definition'], $table_form_data);
		// e($_REQUEST, $table_form_data); db::dbg();
		$table_key = $_REQUEST['table_key'];
				
		$this->rep_set_table_cols($cols, $table_key, $table_form_data);
		$this->rep_set_table_filter($filter, $table_key, $table_form_data);
		$this->rep_set_table_sort($sort, $table_key, $table_form_data);
		$this->rep_set_table_custom_dates($custom_dates, $table_key, $table_form_data);
		
		$time_period = $table_form_data['time_period_'.$table_key];
		$table_obj = array(
			'meta_desc_toggle' => $table_form_data['meta_desc_toggle_'.$table_key],
			'market' => $table_form_data['market_'.$table_key],
			'start_date' => $table_form_data['start_date_'.$table_key],
			'end_date' => $table_form_data['end_date_'.$table_key],
			'custom_dates' => $custom_dates,
			'time_period' => $time_period,
			'detail' => $table_form_data['detail_'.$table_key],
			'cols' => $cols,
			'aggregate_markets' => (array_key_exists('aggregate_markets_'.$table_key, $table_form_data)) ? 1 : 0,
			'keyword_style' => $table_form_data['keyword_style_'.$table_key],
			'campaigns' => $table_form_data['campaigns_'.$table_key],
			'filter' => $filter,
			'display_type' => $table_form_data['display_type_'.$table_key],
			'sort' => $sort,
			'limit' => $table_form_data['limit_'.$table_key]
		);
		
		// fields not always present
		foreach (self::$rep_optional_fields as $field) {
			$key = $field.'_'.$table_key;
			if (array_key_exists($key, $table_form_data)) {
				$table_obj[$field] = $table_form_data[$key];
			}
		}
		$_REQUEST['definition'] = json_encode($table_obj);

		$table = ppc_report_table::create($_REQUEST);
	}
	
	private function print_rep_load_template($rep_id, $tpl_id)
	{
		// if we already have a rep or template, don't show template loader
		if ($rep_id || $tpl_id)
		{
			return;
		}
		$templates = db::select("
			select id, name
			from reports
			where is_template
			order by name asc
		");
		array_unshift($templates, array('' , ' - Select - '));
		?>
		<div>
			<label>Load Template</label>
			<?php echo cgi::html_select('tpl_select', $templates); ?>
			<input type="submit" id="load_template_submit" value="Submit" />
		</div>
		<?php
	}
	
	private function rep_get_col_count(&$cols)
	{
		$i = 0;
		foreach ($cols as $group => $group_cols) {
			$i += count($group_cols);
		}
		return $i;
	}
	
	protected function rep_download()
	{
		$job_id = $_REQUEST['job_id'];

		// delly
		$file = db::select_one("
			select message
			from delly.job_detail
			where job_id = :job_id
			order by job_detail.ts desc, job_detail.id desc
			limit 1
		", array('job_id' => $job_id));

		// old jobs
		// todo: get rid of this once all old reports are garbage collected
		if (!$file) {
			$file = db::select_one("select details from jobs where id='$job_id'");
		}
		
		// we did not used to include file extension, and used xls
		// so if the file path ends with a date, append xls
		if (preg_match("/\d\d\d\d-\d\d-\d\d$/", $file)) {
			$file .= '.xls';
		}
		
		// get rid of job id at beginning of file name
		$out_name = preg_replace("/^[\da-z]+_/", '', $file);
		
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="'.$out_name.'"');
		
		$file_path = \epro\REPORTS_PATH.'ppc_report/'.$file;
		readfile($file_path);
		
		exit(0);
	}
	
	
	protected function action_rep_run_report()
	{
		// debugging
		if (dbg::is_on()) {
			if (array_key_exists('dbg_show_queries', $_POST)) {
				db::dbg();
			}
			feedback::add_error_msg('todo!');
			return;
			require(\epro\CLI_PATH.'worker_ppc_report.php');
			$worker = new worker_ppc_report(0, $this->rep_id);
			$worker->go();
			if (array_key_exists('dbg_excel_download', $_POST)) {
				exit(0);
			}
			else if (array_key_exists('dbg_browser_output', $_POST) || array_key_exists('dbg_show_queries', $_POST)) {
				return;
			}
		}

		$job = job::queue(array(
			'fid' => $_POST['rep_id'],
			'type' => PPC_REPORT_JOB_NAME,
			'account_id' => $this->aid
		));
		if ($job) {
			feedback::add_success_msg('Generating report. Please wait.');
		}
		else {
			$e = db::last_error();
			feedback::add_error_msg('Error'.(($e) ? ": {$e}" : ''));
		}
	}
	
	private function rep_set_table_cols(&$cols, $table_key, $data = false)
	{
		if ($data === false) {
			$data = $_POST;
		}
		$cols = array();
		$tmp_cols = array();
		$is_ad_detail = $at_least_one_ad_col = false;
		for ($i = 0, $ci = $this->rep_get_col_count($this->report_cols); $i < $ci; ++$i) {
			$col_key = 'col_'.$table_key.'_'.$i;
			if (array_key_exists($col_key, $data)) {
				$col_name = $data[$col_key];
				if ($col_name == 'ad') {
					$is_ad_detail = true;
				}
				else if ($this->report_cols[$col_name]['group'] == 'ad') {
					$at_least_one_ad_col = true;
				}
				$tmp_cols[] = $col_name;
			}
		}
		// if ad detail is selected, make sure at least one ad detail col is shown since "ad" does not correspond to an actual value
		if ($is_ad_detail && !$at_least_one_ad_col) {
			array_splice($tmp_cols, array_search('ad', $tmp_cols) + 1, 0, array('headline'));
		}
		$cols = array_combine($tmp_cols, array_fill(0, count($tmp_cols), 1));
	}
	
	private function rep_set_table_filter(&$filter, $table_key, $data = false)
	{
		if ($data === false) {
			$data = $_POST;
		}
		$filter = array();
		for ($i = 0; true; ++$i)
		{
			for ($j = 0; true; ++$j)
			{
				$filter_key = $table_key.'_'.$i.'_'.$j;
				
				// if filter val is empty, no filter set
				$filter_val = @$data['filter_val_'.$filter_key];
				if ($filter_val == '') break;
				
				// if there is a hidden value, use that
				$filter_val_hidden = (array_key_exists('filter_val_hidden_'.$filter_key, $data)) ? $data['filter_val_hidden_'.$filter_key] : '';
				
				$filter[$i][$j] = array(
					'col' => $data['filter_col_'.$filter_key],
					'cmp' => $data['filter_cmp_'.$filter_key],
					'val' => $filter_val,
					'val_hidden' => $filter_val_hidden
				);
			}
			if ($j == 0) break;
		}
	}
	
	private function rep_set_table_custom_dates(&$custom_dates, $table_key, $data = false)
	{
		if ($data === false) {
			$data = $_POST;
		}
		$custom_dates = array();
		for ($i = 0; true; ++$i)
		{
			$form_key = 'start_date_'.$table_key.'_'.$i;
			if (!array_key_exists($form_key, $data)) {
				break;
			}
			$custom_dates[] = array(
				'start' => $data[$form_key],
				'end' => $data['end_date_'.$table_key.'_'.$i]
			);
		}
		if (!empty($custom_dates)) {
			// we have custom dates, prepend original start and end date fields
			array_unshift($custom_dates, array(
				'start' => $data['start_date_'.$table_key],
				'end' => $data['end_date_'.$table_key]
			));
		}
	}
	
	private function rep_set_table_sort(&$sort, $table_key, $data = false)
	{
		if ($data === false) {
			$data = $_POST;
		}
		$sort = array();
		for ($i = 0; true; ++$i)
		{
			$sort_key = 'sort_'.$table_key.'_'.$i;
			if (!array_key_exists($sort_key, $data)) break;
			$sort[] = $data[$sort_key];
		}
	}
	
	public function action_rep_save_template()
	{
		if ($this->do_rep_save(true))
		{
			feedback::add_success_msg('Saved As Template');
		}
		else
		{
			feedback::add_error_msg('Error Saving Template: '.db::last_error());
		}
	}
	
	public function action_rep_save_changes()
	{
		// already saved via ajax
		feedback::add_success_msg('Report Saved');
	}
	
	public function ajax_rep_get_job_stati()
	{
		$job_ids = $_POST['job_ids'];
		if (empty($job_ids)) {
			echo json_encode(array());
			return;
		}
		
		$response = db::select("
			select id, status, concat(details, if(minutia <> '', concat('. ', minutia), '')) details, processing_end
			from jobs
			where id in ($job_ids)
		", 'ASSOC');
		echo json_encode($response);
	}
	
	protected function set_account_user_pass(&$ac_user, &$ac_pass, $market, $ac_id)
	{
		switch ($market)
		{
			case ('g'):
				return;
			
			case ('y'):
				$master_ac_id = db::select_one("select master_account from y_accounts where id='$ac_id'");
				list($ac_user, $ac_pass) = db::select_row("
					select external_user, external_pass
					from y_master_accounts
					where id='$master_ac_id'
				");
				break;
			
			case ('m'):
				list($ac_user, $ac_pass) = db::select_row("
					select user, pass
					from m_accounts
					where id='$ac_id'
				");
				break;
		}
	}
	
	public function sts_get_campaigns()
	{
		require_once(\epro\WPROPHP_PATH.'apis/apis.php');
		list($market, $ac_id) = util::list_assoc($_REQUEST, 'market', 'ac_id');
		
		$class = $market.'_api';
		$api = new $class(1, $ac_id);
		
		$cas = $api->get_campaigns();
		$out = '';
		foreach ($cas as &$ca)
		{
			$out .= $ca->id."\t".$ca->text."\n";
		}
		echo $out;
	}
	
	public function day_part()
	{
		$days_of_the_week = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
		
		$ml_day_part_rows = '';
		for ($i = 0; $i < 7; ++$i)
		{
			$ml_day_part_rows .= '
				<tr class="dp_row">
					<td style="text-align:right;">'.$days_of_the_week[$i].'</td>
				</tr>
				<tr><td colspan=500 style="height:4px;"></td></tr>
			';
		}
		
		// get day parting calendar for this program and output as js
		$dp = db::select("
			select *
			from day_parting_calendar
			where client='783'
		", 'ASSOC');
		echo cgi::to_js($dp, 'dp_calendar', array('array', 'object'), TO_JS_INCLUDE_TAGS)."\n";
		?>
		<table id="dp_container">
			<tr>
				<td></td>
				<td></td>
				<td colspan=16>Midnight</td>
				<td></td>
				<td colspan=16>4 AM</td>
				<td></td>
				<td colspan=16>8 AM</td>
				<td></td>
				<td colspan=16>Noon</td>
				<td></td>
				<td colspan=16>4 PM</td>
				<td></td>
				<td colspan=16>8 PM</td>
			</tr>
			<?php echo $ml_day_part_rows; ?>
		</table>
		<?php
	}
	
	public static function meta_info_count_cmp(&$a, &$b)
	{
		$a_count = $b_count = 0;
		$a_sum = $b_sum = 0;
		foreach (self::$markets as $market => $market_text)
		{
			if (array_key_exists($market, $a['markets']))
			{
				++$a_count;
				$a_sum += $a['markets'][$market][0];
			}
			if (array_key_exists($market, $b['markets']))
			{
				++$b_count;
				$b_sum += $b['markets'][$market][0];
			}
		}
		if ($a_count != $b_count) return ($b_count - $a_count);
		return ($b_sum - $a_sum);
	}
	
	public function meta()
	{
		if (!user::is_developer())
		{
			return;
		}
		// kw 2013-10-03: update to use new object tables
		echo "coming soon<br>\n";
		return;
		$clients = db::select("
			select id, name, data_id
			from clients
		", 'NUM', 0);
		
		list($start_date, $end_date) = util::list_assoc($_POST, 'start_date', 'end_date');
		if (!$start_date)
		{
			$end_date = date(util::DATE, time() - 86400);
			$start_date = date(util::DATE, strtotime("$end_date -14 days"));
		}
		
		$cl_tmp = array();
		for ($i = 0; $i < util::DATA_TABLE_COUNT; ++$i)
		{
			foreach (self::$markets as $market => $market_text)
			{
				$cl_counts = db::select("
					select client, sum(imps), sum(revenue)
					from {$market}_data.clients_$i
					where data_date >= '$start_date' && data_date <= '$end_date'
					group by client
					order by client asc
				");
				for ($j = 0; list($cl_id, $imps, $rev) = $cl_counts[$j]; ++$j)
				{
					$cl_tmp[$cl_id][$market] = array($imps, $rev);
				}
			}
		}
		$cl_info = array();
		foreach ($cl_tmp as $cl_id => &$cl_data)
		{
			$cl_info[] = array('cl_id' => $cl_id, 'markets' => $cl_data);
		}
		usort($cl_info, array('mod_ppc', 'meta_info_count_cmp'));
		
		for ($i = 0, $count = count($cl_info); $i < $count; ++$i)
		{
			list($cl_id, $market_info) = util::list_assoc($cl_info[$i], 'cl_id', 'markets');
			list($cl_name, $cl_data_id) = $clients[$cl_id];
			
			$ml_markets = '';
			foreach (self::$markets as $market => $market_text)
			{
				$d = (array_key_exists($market, $market_info)) ? $market_info[$market] : array(0, 0);
				$ml_markets .= '<td>'.implode('</td><td>', $d).'</td>';
			}
			
			$ml .= '
				<tr>
					<td>'.$i.'</td>
					<td>'.$cl_name.'</td>
					<td>'.$cl_id.'</td>
					<td>'.$cl_data_id.'</td>
					<td> &nbsp; - - &nbsp; </td>
					'.$ml_markets.'
				</tr>
			';
		}
		
		?>
		<h1>meta</h1>
		<?php echo cgi::date_range_picker($start_date, $end_date); ?>
		<table>
			<tbody>
				<?php echo $ml; ?>
			</tbody>
		</table>
		<?php
	}
	
	public function sts_daily_report_status()
	{
		$market = $_REQUEST['m'];
		$date = $_REQUEST['d'];
		$status = db::select_one("select status from eppctwo.market_data_status where market = '$market' && d = '$date'");
		
		echo json_encode($status);
	}
	
	public function sts_get_raw_data()
	{
		$market = $_REQUEST['m'];
		$date = $_REQUEST['d'];
		$level = $_REQUEST['l'];
		$ids = $_REQUEST['i'];

		if (strpos($ids, ',') !== false)
		{
			$id_query = "in ('".str_replace(',', "','", $ids)."')";
		}
		else
		{
			$id_query = " = '$ids'";
		}

		$data = db::select("select * from {$market}_data_tmp.".str_replace('-', '_', $date)." where $level $id_query");
		foreach ($data as &$d)
		{
			echo implode("\t", $d)."\n";
		}
	}
	
	private function info_set_input_info(&$input_info)
	{
		$input_info = array(
			'manager' => array('display' => 'Manager', 'type' => 'select'),
			'revenue_tracking' => array('display' => 'Revenue Tracking', 'type' => 'checkbox_switch'),
			'wpropath_start_date' => array('display' => 'Wpropath Start', 'type' => 'date')
		);
	}
	
	public function info_extra_data_manager()
	{
		$users = db::select("
			select username, realname
			from users
			order by realname asc
		");
		return $users;
	}
	
	public function pre_output_info()
	{
		parent::pre_output_info();
		// special input for notes
		// don't show user actual_budget field
		$this->info_ignore_cols = array_merge($this->info_ignore_cols, array('notes'));
	}
	
	public function display_info()
	{
		parent::display_info();
		?>
		<div id="_notes">
			<h2>Notes</h2>
			<p><a href="" id="edit_notes_a">Edit</a></p>
			<ul id="notes_ul"></ul>
			<div id="notes_input">
				<textarea><?php echo cgi::textarea_form($this->account->notes); ?></textarea>
				<div>
					<input type="submit" class="small_button" id="notes_update_button" value="Update" />
					<input type="submit" class="small_button" id="notes_cancel_button" value="Cancel" />
				</div>
			</div>
		</div>
		<div class="clear"></div>
		<?php
	}

	public function display_manage_conv_types()
	{
		$map = conv_type::get_all(array(
			"select" => array(
				"conv_type" => array("id", "canonical"),
				"conv_type_market" => array("conv_type_id", "market", "market_name")
			),
			"where" => "aid = '{$this->aid}'",
			"join_many" => array(
				"conv_type_market" => "conv_type.id = conv_type_market.conv_type_id"
			),
			"order_by" => "canonical asc"
		));
		$conv_types = conv_type_count::get_account_conv_types($this->aid, array('include_market' => true));
		?>
		<input type="submit" id="add_ct" value="Add Conv Type" />
		<table>
			<thead>
				<tr id="ct_headers"></tr>
			</thead>
			<tbody id="ct_body"></tbody>
		</table>
		<div id="submit_buttons">
			<input type="submit" a0="action_ct_submit" value="Submit" />
		</div>
		<?php
		cgi::add_js_var('conv_types', $conv_types);
		cgi::add_js_var('markets', self::$markets);
		cgi::add_js_var('map', $map);
	}

	public function action_ct_submit()
	{
		// todo: instead of deleting and re-creating
		// create/edit/delete as needed and also update tables
		// that rely on these columns: reports, cdl user cols.. others?
		// $user_client_cols = new ppc_cdl_user_cols(array(
		// 	'user_id' => user::$id,
		// 	'client_id' => $this->cl_id
		// ));
		// $old_map = conv_type::get_client_market_map($this->cl_id);

		if (!empty($user_client_cols->cols)) {
			$cols = explode("\t", $user_client_cols->cols);
		}
		// first verify that we have valid mappings
		$market_cts = array();
		$canon_cts = array();
		for ($i = 0, $key = 'canon_'.$i; array_key_exists($key, $_POST); $key = 'canon_'.(++$i)) {
			$canon = $_POST[$key];
			if (isset($canon_cts[$canon])) {
				feedback::add_error_msg('Duplicate Wpro name: '.$canon);
				return false;
			}
			$canon_cts[$canon] = 1;
			foreach (self::$markets as $market => $market_text) {
				$market_name = $_POST["{$market}_{$i}"];

				// multiple empty market names okay
				if (!empty($market_name) && isset($market_cts[$market][$market_name])) {
					feedback::add_error_msg('Duplicate market name: '.$market_text.', '.$market_name);
					return false;
				}
				$market_cts[$market][$market_name] = 1;
			}
		}

		// delete old mappings
		$client_cts = conv_type::get_all(array("where" => "aid = '{$this->aid}'"));
		if ($client_cts->count() > 0) {
			conv_type_market::delete_all(array("where" => "conv_type_id in ('".implode("','", $client_cts->id)."')"));
			$client_cts->delete();
		}
		// create new
		for ($i = 0, $key = 'canon_'.$i; array_key_exists($key, $_POST); $key = 'canon_'.(++$i)) {
			$canon = $_POST[$key];
			$ct = conv_type::create(array(
				'aid'       => $this->aid,
				'canonical' => $canon
			));
			foreach (self::$markets as $market => $market_text) {
				$market_name = $_POST["{$market}_{$i}"];
				conv_type_market::create(array(
					'conv_type_id' => $ct->id,
					'market'      => $market,
					'market_name' => $market_name
				));
			}
		}
		feedback::add_success_msg('Converion types updated');
	}

	private function print_secondary_managers()
	{
		$sms = db::select("
			select u.realname, u.id
			from eppctwo.users u, eppctwo.secondary_manager sm
			where sm.client_id = '{$this->cl_id}' && sm.user_id = u.id
			order by realname asc
		");
		
		$users = db::select("
			select id, realname
			from eppctwo.users
			where password <> ''
			order by realname asc
		");
		
		$ml = '';
		for ($i = 0; list($uname, $uid) = $sms[$i]; ++$i)
		{
			$ml .= '
				<tr>
					<td>'.$uname.'</td>
					<td><input type="submit" class="remove_user_button" uid="'.$uid.'" value="Remove" /></td>
				</tr>
			';
		}
		?>
		<h2>Other Managers</h2>
		<table id="other_managers_table">
			<tbody>
				<?php echo $ml; ?>
				<tr>
					<td><?php echo cgi::html_select('add_manager', $users); ?></td>
					<td><input type="submit" a0="action_add_manager" value="Add Manager" /></td>
				</tr>
			</tbody>
		</table>
		<input type="hidden" name="remove_other_manager_id" id="remove_other_manager_id" value="" />
		<?php
	}
	
	public function action_remove_other_manager()
	{
		$uid = $_POST['remove_other_manager_id'];
		$name = db::select_one("select realname from eppctwo.users where id = '".db::escape($uid)."'");
		db::delete("eppctwo.secondary_manager", "
			client_id = '{$this->cl_id}' &&
			user_id = '{$uid}'
		");
		feedback::add_success_msg('<i>'.$name.'</i> removed as manager.');
	}
	
	public function action_add_manager()
	{
		$uid = $_POST['add_manager'];
		$name = db::select_one("select realname from eppctwo.users where id = '".db::escape($uid)."'");
		db::insert("eppctwo.secondary_manager", array(
			'client_id' => $this->cl_id,
			'user_id' => $uid
		));
		feedback::add_success_msg('<i>'.$name.'</i> added as manager.');
	}
	
	public function ajax_info_update_notes()
	{
		$this->account->update_from_array(array(
			'notes' => $_POST['notes']
		));
	}
	
	public function action_account_info_submit()
	{
		$updates = parent::action_account_info_submit();

		$budget_fields = array('carryover', 'adjustment', 'budget');
		$is_budget_update = false;
		foreach ($budget_fields as $field) {
			if (in_array($field, $updates)) {
				$is_budget_update = true;
				$this->account->update_actual_budget();
			}
		}
		if ($is_budget_update || in_array('prev_bill_date', $updates) || in_array('next_bill_date', $updates)) {
			ppc_lib::calculate_cdl_vals($this->aid, $this->account->prev_bill_date, $this->account->next_bill_date);
			feedback::add_success_msg('CDL updated');
		}
	}
	
	public function managers()
	{
		$managers_widget = new wid_managers();
		$managers_widget->pre_output();
		$managers_widget->output();
	}
	
	public function new_client()
	{
		clients_ppc::load_table_definition();
		?>
		<h1>New Client</h1>
		<table>
			<tbody>
				<tr>
					<td>Account Name</td>
					<td><input type="text" name="name" value="" /></td>
				</tr>
				<tr>
					<td>PPC Manager</td>
					<td><?php echo clients_ppc::$cols['manager']->get_form_input('clients_ppc', null); ?></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" a0="new_client_submit" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}
	
	public function new_client_submit()
	{
		$client = new clients(array(
			'company' => 1,
			'name' => $_POST['name'],
			'status' => 'On'
		));
		
		if (!$client->put())
		{
			feedback::add_error_msg('Error Adding New PPC Client');
			return false;
		}
		$client->external_id = util::set_client_external_id($client->id);
		$client->data_id = util::set_client_data_id($client->id);
		
		$client_ppc = new clients_ppc(array(
			'client' => $client->id,
			'manager' => $_POST['clients_ppc_manager'],
			'ncid' => $new_account->client_id,
			'naid' => $new_account->id
		));
		if (!$client_ppc->put())
		{
			feedback::add_error_msg('Error Adding New PPC Client');
			return false;
		}
		
		feedback::add_success_msg('New PPC Client Added');
		return true;
	}
	
	public function assign_data_id()
	{
		var_dump($this->a0_result);
		?>
		<h1>Assign Data ID</h1>
		<input type="submit" a0="assign_data_id_submit" value="Submit" /></td>
		<?php
	}
	
	public function assign_data_id_submit()
	{
		$this->data_id = util::set_client_data_id($this->cl_id);
		feedback::add_success_msg('Done');
		return true;
	}
	
	public function ajax_load_external_reps()
	{
		$reps = db::select("
			select r.id, concat(a.name, ' : ', r.name) client_reps
			from eac.account a, eppctwo.reports r
			where
				".((user::is_developer()) ? '' : "r.user = ".user::$id." && ")."
				a.id = r.account_id
			order by client_reps asc
		");
		
		echo json_encode($reps);
	}
	
	public function ajax_load_external_sheets()
	{
		list($sheets) = db::select_row("select sheets from eppctwo.reports where id=".db::escape($_POST['rid']));
		
		// send sheet data back to browser
		echo $sheets;
	}

	//expects client, market, (dates in the future)
	public function sts_get_client_data()
	{
		$time_range = $_REQUEST['time_range'];

		// todo: for historical reasons wpro dashboard has client IDs instead of account IDs
		// dashboard should probably start using enet client and account ids now
		$acnt = new as_ppc(array(), array(
			'do_get' => true,
			'where' => "account.client_id = :cid",
			'data' => array("cid" => $_POST['client'])
		));

		if (!empty($time_range)){
			if ($time_range=='billing'){
				$start_date = $acnt->prev_bill_date;
				$end_date = date(util::DATE, strtotime('yesterday'));
			}
			else {
				$start_date = date(util::DATE, strtotime($_REQUEST['start_date']));
				$end_date = date(util::DATE, strtotime($_REQUEST['end_date']));
			}
		}
		else {
			//set date range for the last month
			$start_date = date(util::DATE, strtotime('first day of last month'));
			$end_date = date(util::DATE,  strtotime('last day of last month'));
		}

		$market = empty($_REQUEST['market']) ? 'all' : $_REQUEST['market'];

		$fields = array('market', 'imps', 'clicks', 'cost', 'pos', 'revenue', 'convs');

		$data = all_data::get_data(array(
			'account' => $acnt,
			'market' => $market,
			'detail' => 'account',
			'time_period' => 'all',
			'do_total' => false,
			'do_compute_metrics' => false,
			'fields' => array_combine($fields, array_fill(0, count($fields), 1)),
			'start_date' => $start_date,
			'end_date' => $end_date
		));
		$results = $data->data;
		$totals = array_combine($fields, array_fill(0, count($fields), 0));
		foreach ($results as $row) {
			foreach ($row as $k => $v) {
				if (is_numeric($v)) {
					$totals[$k] += $v;
				}
			}
		}
		// m for market
		$totals['m'] = $market;
		all_data::compute_data_metrics($totals, array('do_calc_percents' => true));
		echo serialize($totals);
	}

	public function sts_get_client_data_custom()
	{
		$time_range = $_REQUEST['time_range'];

		// todo: for historical reasons wpro dashboard has client IDs instead of account IDs
		// dashboard should probably start using enet client and account ids now
		$acnt = new as_ppc(array(), array(
			'do_get' => true,
			'where' => "account.client_id = :cid",
			'data' => array("cid" => $_POST['client'])
		));

		if (!empty($time_range)){
			if ($time_range=='billing'){
				$start_date = $acnt->prev_bill_date;
				$end_date = date(util::DATE, strtotime('yesterday'));
			}
			else {
				$start_date = date(util::DATE, strtotime($_REQUEST['start_date']));
				$end_date = date(util::DATE, strtotime($_REQUEST['end_date']));
			}
		}
		else {
			//set date range for the last month
			$start_date = date(util::DATE, strtotime('first day of last month'));
			$end_date = date(util::DATE,  strtotime('last day of last month'));
		}

		$market = empty($_REQUEST['market']) ? 'all' : $_REQUEST['market'];

		$fields = array('market', 'imps', 'clicks', 'cost', 'pos', 'revenue', 'convs');


		$data = all_data::get_data(array(
			'account' => $acnt,
			'market' => $market,
			'detail' => $_POST['datalevel'],
			'time_period' => 'all',
			'do_total' => false,
			'do_compute_metrics' => false,
			'fields' => array_combine($fields, array_fill(0, count($fields), 1)),
			'start_date' => $start_date,
			'end_date' => $end_date
		));
		$results = $data->data;

		//print_r($results); die;

		
		if ($_POST['datalevel'] == 'account'){
			$totals = array_combine($fields, array_fill(0, count($fields), 0));
			foreach ($results as $row) {
				foreach ($row as $k => $v) {
					if (is_numeric($v)) {
						$totals[$k] += $v;
					}
				}
			}
			$totals['m'] = $market;
			all_data::compute_data_metrics($totals, array('do_calc_percents' => true));
		}
		//if datalevel = campaign, break up the totals...
		else {

			$totals = $results;
			$totals_total = array_combine($fields, array_fill(0, count($fields), 0));

			for($i=0;$i<count($totals);$i++){

				foreach ($totals[$i] as $k => $v) {
					if (is_numeric($v)) {
						$totals_total[$k] += $v;
					}
				}

				$totals[$i]['m'] = $market;

				$market_id = substr($totals[$i]['uid'], 0, 1);
				$campaign_id = substr($totals[$i]['uid'], 2);

				$campaign_name = db::select_one("
					select text
					from {$market_id}_objects.campaign_{$acnt->id}
					where id = {$campaign_id}
				");

				$totals[$i]['campaign_text'] = $campaign_name;

				all_data::compute_data_metrics($totals[$i], array('do_calc_percents' => true));

			}

			all_data::compute_data_metrics($totals_total, array('do_calc_percents' => true));

			$totals_total['market'] = $market;
			$totals_total['campaign_text'] = 'Account Total';
			$totals[] = $totals_total;
		}

		//print_r($totals);
		echo serialize($totals);
	}

	public function sts_get_account()
	{
		$acnt = new as_ppc(array(), array(
			'do_get' => true,
			'where' => "account.client_id = :cid",
			'data' => array("cid" => $_POST['client'])
		));
		
		echo serialize(get_object_vars($acnt));
	}

	public function display_billing_rollover()
	{
		$next_bill_date = util::delta_month($this->account->next_bill_date, 1, $this->account->bill_day);
		list($mo_spend) = db::select_row("
			select mo_spend
			from eppctwo.ppc_cdl
			where account_id = :aid
		", array(
			"aid" => $this->aid
		));
		?>
		<table>
			<tbody>
				<tr>
					<td><b>Current Month</b></td>
					<td><?php echo date(util::US_DATE, strtotime($this->account->prev_bill_date)).' - '.date(util::US_DATE, strtotime($this->account->next_bill_date)); ?></td>
				</tr>
				<tr>
					<td><b>Who Pays Clicks</b></td>
					<td><?php echo $this->account->who_pays_clicks; ?></td>
				</tr>
				<tr>
					<td><b>Current Budget</b></td>
					<td><?php echo util::format_dollars($this->account->budget); ?></td>
				</tr>
				<tr>
					<td><b>Current Carryover</b></td>
					<td><?php echo util::format_dollars($this->account->carryover); ?></td>
				</tr>
				<tr>
					<td><b>Current Adjustment</b></td>
					<td><?php echo util::format_dollars($this->account->adjustment); ?></td>
				</tr>
				<tr>
					<td><b>Spend</b></td>
					<td><?php echo util::format_dollars($mo_spend); ?></td>
				</tr>
				<tr>
					<td><b>Budget</b></td>
					<td><input type="text" name="rollover_budget" value="<?php echo round($this->account->budget, 2); ?>" /></td>
				</tr>
				<tr>
					<td><b>Carryover</b></td>
					<td><input type="text" name="rollover_carryover" value="<?php echo round($this->account->actual_budget - $mo_spend, 2); ?>" /></td>
				</tr>
				<tr>
					<td><b>Adjustment</b></td>
					<td><input type="text" name="rollover_adjustment" value="<?php echo round($this->account->adjustment, 2); ?>" /></td>
				</tr>
				<tr>
					<td><b>Next Bill Date</b></td>
					<td><input type="text" name="rollover_next_bill_date" value="<?php echo $next_bill_date; ?>" /></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" a0="action_rollover" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}
	
	public function action_rollover()
	{
		ppc_rollover::create(array(
			'account_id' => $this->aid,
			'user_id' => user::$id,
			'd' => date(util::DATE),
			'budget' => $_POST['rollover_budget'],
			'carryover' => $_POST['rollover_carryover'],
			'adjustment' => $_POST['rollover_adjustment'],
			'next_bill_date' => $_POST['rollover_next_bill_date'],
		));

		$this->account->budget = $_POST['rollover_budget'];
		$this->account->carryover = $_POST['rollover_carryover'];
		$this->account->adjustment = $_POST['rollover_adjustment'];
		$this->account->prev_bill_date = $this->account->next_bill_date;
		$this->account->next_bill_date = $_POST['rollover_next_bill_date'];
		$this->account->calc_actual_budget();
		$this->account->update(array('cols' => array('budget', 'carryover', 'adjustment', 'prev_bill_date', 'next_bill_date', 'actual_budget')));
		
		ppc_lib::calculate_cdl_vals($this->aid, $ppc_client->prev_bill_date, $ppc_client->next_bill_date);
		
		feedback::add_success_msg('Rollover Completed');
	}

}

?>