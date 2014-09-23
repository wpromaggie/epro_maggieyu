<?php

class mod_account_service_ppc_bf extends mod_account_service_ppc
{
	protected $all_markets;
	
	public function pre_output()
	{
		parent::pre_output();
		$this->display_default = 'index';

		cgi::include_widget('top_box');
		cgi::add_js('lib.filter.js');

		util::init_report_cols($this->aid);

		$tmp = db::select("select distinct market from eppctwo.data_sources where account_id = :aid", array("aid" => $this->aid));
		$ppc_markets = util::get_ppc_markets('ASSOC', $this->account);
		$this->all_markets = array();
		foreach ($tmp as $market) {
			if (array_key_exists($market, $ppc_markets)) {
				$this->all_markets[$market] = $ppc_markets[$market];
			}
		}
	}
	
	public function display_index()
	{
		$this->get_account_filters();

		// load results from a filter
		if (isset($_REQUEST['faction']) && $_REQUEST['faction'] == 'results') {
			$job = new job(array("id" => $_REQUEST['fid']), array(
				'select' => array(
					"spec_reduce_market_data" => array("data_opts")
				),
				'join' => array(
					"spec_reduce_market_data" => "spec_reduce_market_data.id = job.fid && spec_reduce_market_data.source = 'filter'"
				),
				'flatten' => true
			));
			$data_opts = $this->get_data_opts(json_decode($job->data_opts, true));
			$this->data = all_data::load_filter_results($data_opts, $_REQUEST['fid']);
			$ml_filter_overview = $this->ml_filter_overview($data_opts);
		}
		// default: get data based on $_REQUEST
		else {
			$ml_filter_overview = '';
			$data_opts = $this->get_data_opts($_REQUEST);
			$this->check_cache();
			$this->check_changes_submit();
			$this->data = all_data::get_data($data_opts);
		}
		
		util::set_data_date_options($this->data_date_options);
		$this->set_columns_ml_and_add_col_formatting($ml_cols);
		$this->set_crumbs_ml($ml_crumbs);
		
		?>
		<table>
			<tbody>
				<tr valign="top">
					<td>
					
						<!-- dates -->
						<fieldset id="dates_container" class="input_container">
							<legend>Dates</legend>
							<table>
								<tbody>
									<?= cgi::date_range_picker($this->start_date, $this->end_date, array('table' => false)) ?>
									<tr>
										<td></td>
										<td><input type="submit" id="date_submit" value="Set Dates" /></td>
									</tr>
								</tbody>
							</table>
							<?php cgi::to_js('g_date_options', $this->data_date_options); ?>
						</fieldset>
					</td>
					<td id="date_filter_cell"><div id="date_filter_div">&nbsp;</div></td>
					<td>
					
						<!-- filter -->
						<fieldset id="filter_container" class="input_container">
							<legend>Filter</legend>

							<div id="account_filters">
								<div>
									<b>Currently Running:</b>
									<span id="running_filter"></span>
									<span id="running_links"><a href="" id="cancel_running_link" class="hide">Cancel</a></span>
								</div>
								<div>
									<b>Recently Completed:</b>
									<span id="completed_filter"></span>
								</div>
							</div>

							<div id="filter_links">
								<a id="filter_history_toggle" href="">Filter History</a> &bull;
								<a id="filter_new_toggle" href="">New Filter</a>
							</div>

							<div id="w_filter_history">
							</div>

							<div id="w_filter_new">
								<hr id="filter_top_spacer" />
								<!-- market and campaign -->
								<table>
									<tbody>
										<tr>
											<td>Market</td>
											<td id="w_markets"></td>
										</tr>
										<tr>
											<td>Campaign</td>
											<td id="w_campaigns"></td>
										</tr>
									</tbody>
								</table>

								<!-- filter columns -->
								<div id="filter_container_0_0" class="lft"></div>
								<div class="clr"></div>
								<div id="filter_show">
									<input type="hidden" name="filter_show" value="keyword" />
								</div>
								
								<div id="div_filter_options">
									<!--<div id="div_search_or_content">
										<div>
											<input type="radio" id="search_or_content_search" name="search_or_content" value="search" checked=1 />
											<label for="search_or_content_search">Search</label>
										</div>
										<div>
											<input type="radio" id="search_or_content_content" name="search_or_content" value="content" />
											<label for="search_or_content_content">Content</label>
										</div>
									</div>-->
									<input type="hidden" name="search_or_content" value="search" />
									
									<!-- <div id="div_ag_or_kw">
										<div>
											<input type="radio" id="ag_or_kw_ag" name="ag_or_kw" value="ad_group" />
											<label for="ag_or_kw_ag">Ad Group</label>
										</div>
										<div>
											<input type="radio" id="ag_or_kw_kw" name="ag_or_kw" value="keyword" checked=1 />
											<label for="ag_or_kw_kw">Keyword</label>
										</div>
									</div>
									<div class="clr"></div> -->
									<input type="hidden" name="ag_or_kw" value="keyword" />
								</div>
								
								<div>
									<input type="submit" id="run_submit" a0="action_schedule_filter" value="Run" />
									<input type="submit" id="clear_submit" value="Clear" />
									<input type="submit" id="cancel_submit" value="Cancel" />
								</div>
							</div>
						</fieldset>
					</td>
				</tr>
			</tbody>
		</table>
		
		<!-- crumbs -->
		<table id="data_crumbs_table">
			<tbody id="data_crumbs">
				<?php echo $ml_crumbs; ?>
			</tbody>
		</table>
		
		<input id="clear_filter" class="hide" type="submit" value="Stop Filtering" />
		
		<!-- filter results filter info -->
		<?= $ml_filter_overview ?>

		<!-- bids and status -->
		<table id="bid_table">
			<tbody>
				<tr valign="top">
					<td>
						<input id="toggle_bids_button" class="bid_button" bid_type="bid" type="submit" value="Set Bids" />
						<input id="toggle_cbids_button" class="bid_button" bid_type="cbid" type="submit" value="Set Content Bids" />
						<input type="hidden" id="bid_type" name="bid_type" value="" />
					</td>
					<td>
						<table id="change_container">
							<tbody>
								<tr valign="top">
									<td id="change_bid_cell">
										<table>
											<tbody>
												<tr>
													<td nowrap="">Percent</td>
													<td><input type="text" id="percent_bid_change" class="bid_change_input" change_type="percent" /></td>
													<td class="bid_change_help"> Change by percent, 0 is current amount</td>
												</tr>
												<tr>
													<td nowrap="">Delta</td>
													<td><input type="text" id="delta_bid_change" class="bid_change_input" change_type="delta" /></td>
													<td class="bid_change_help"> Change bid relative to current amount</td>
												</tr>
												<tr>
													<td nowrap="">Absolute</td>
													<td><input type="text" id="absolute_bid_change" class="bid_change_input" change_type="absolute" /></td>
													<td class="bid_change_help"> Set bid to amount</td>
												</tr>
											</tbody>
										</table>
									</td>
									<td id="change_status_cell">
										<label>Status</label>
										<img src="<?php echo cgi::href('img/On.jpg'); ?>" />
										<img src="<?php echo cgi::href('img/Off.jpg'); ?>" />
									</td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
			</tbody>
		</table>
		<input type="hidden" name="bid_type" id="bid_type" value="" />
		<input type="hidden" name="bid_change_type" id="bid_change_type" value="" />
		<input type="hidden" name="bid_change_amount" id="bid_change_amount" value="" />
		
		<!-- data -->
		<table id="data_table"></table>

		<?php
		$filter_definition = false;
		$filter_obj = false;
		if (!empty($_REQUEST['filter'])) {
			$filter_definition = $_REQUEST['filter'];
		}
		else if (isset($_REQUEST['faction']) && $_REQUEST['faction'] == 'edit') {
			foreach ($this->account_filters as $filter) {
				if ($filter->id == $_REQUEST['fid']) {
					$filter_obj = json_decode($filter->data_opts, true);
					$filter_definition = $filter_obj['filter'];
				}
			}
		}
		if ($filter_definition) {
			cgi::add_js_var('init_filter_data', $filter_definition);
			if ($filter_obj) {
				cgi::add_js_var('init_filter_campaigns', $filter_obj['campaigns']);
			}
		}
		$js_cols = $this->get_js_cols();
		cgi::add_js_var('markets', $this->all_markets);
		cgi::add_js_var('campaigns', $this->get_campaigns());
		cgi::add_js_var('detail_cols', $this->detail_cols);
		cgi::add_js_var('data', $this->data->data);
		cgi::add_js_var('data_cols', $js_cols);
		
		// if level is keyword, make one pass over data, get:
		// 1. ad group max cpc so we can use that for keyword max cpc if keyword doesn't have one
		// 2. ad group cache mod time so we know which ad groups we don't need to refresh 
		if ($this->detail == 'keyword') {
			$this->get_ag_info_for_keywords();
		}
	}

	private function ml_filter_overview($data_opts)
	{
		if (empty($data_opts['filter'])) {
			return '';
		}

		$ml = '';
		for ($i = 0, $ci = count($data_opts['filter']); $i < $ci; ++$i) {
			$or = $data_opts['filter'][$i];
			$ml_or = '';
			for ($j = 0, $cj = count($or); $j < $cj; ++$j) {
				$and = $or[$j];
				$ml_or .= '
					<tr class="filter_overview_row">
						<td class="filter_overview_col">'.util::display_text($and['col']).'</td>
						<td>'.$this->cmp_to_text($and['cmp']).'</td>
						<td class="filter_overview_val">'.$and['val'].'</td>
						<td class="filter_overview_and">'.(($j < ($cj - 1)) ? ' and ' : '').'</td>
					</tr>
				';
			}
			$ml .= '
				'.$ml_or.'
				<tr><td colspan="10">'.(($i < ($ci - 1)) ? ' or ' : '').'</td></tr>
			';
		}
		return '
			<fieldset id="w_filter_results_overview">
				<legend>Filter Results</legend>
				<table>
					<tbody>
						'.$ml.'
					</tbody>
				</table>
			</fieldset>
			<div class="clr"></div>
		';
	}

	private function cmp_to_text($cmp)
	{
		switch ($cmp) {
			case ('gt'): return 'Greater Than';
			case ('lt'): return 'Less Than';
			case ('eq'): return 'Equal To';
			case ('ne'): return 'Not Equal To';
			case ('contains'): return 'Contains';
			case ('not_contains'): return 'Not Contains';
			default: return '';
		}
	}

	private function get_campaigns()
	{
		$campaigns = array();
		foreach ($this->all_markets as $market => $market_disp) {
			$campaigns[$market] = db::select("
				select id, concat(:market_disp, ' - ', text, ' (', status, ')') as value
				from {$market}_objects.campaign_{$this->aid}
				where status != 'Deleted'
				order by value asc
			", array(
				'market_disp' => $market_disp,
			));
		}
		return $campaigns;
	}
	
	private function get_account_filters()
	{
		util::load_lib('delly');

		$this->account_filters = job::get_all(array(
			'select' => array(
				"job" => array("id", "status", "created", "started", "finished"),
				"spec_reduce_market_data" => array("data_opts")
			),
			'join' => array(
				"spec_reduce_market_data" => "spec_reduce_market_data.id = job.fid && spec_reduce_market_data.source = 'filter'"
			),
			'where' => "job.account_id = :aid",
			'data' => array("aid" => $this->aid),
			'flatten' => true,
			'order_by' => "finished desc, started desc"
		));
		cgi::add_js_var('done_stati', job::$done_stati);
		cgi::add_js_var('filter_history', $this->account_filters);
	}

	public function ajax_get_completed_job()
	{
		util::load_lib('delly');

		$job = new job(array('id' => $_REQUEST['jid']), array(
			'select' => array(
				"job" => array("id", "status", "created", "started", "finished"),
				"spec_reduce_market_data" => array("data_opts")
			),
			'join' => array(
				"spec_reduce_market_data" => "spec_reduce_market_data.id = job.fid && spec_reduce_market_data.source = 'filter'"
			),
			'flatten' => true
		));
		$job->data_opts = json_decode($job->data_opts, true);
		echo $job->json_encode();
	}

	private function get_data_opts($opts)
	{
		list($this->market, $this->campaign, $this->ad_group) = util::list_assoc($opts, 'market', 'campaign', 'ad_group');

		if (array_key_exists('start_date', $opts)) {
			$this->start_date = $opts['start_date'];
			$this->end_date = $opts['end_date'];
		}
		else {
			$this->end_date = date(util::DATE, time() - 86400);
			$this->start_date = date(util::DATE, strtotime("$this->end_date -30 days"));
		}
		if ($this->start_date > $this->end_date) {
			$this->start_date = $this->end_date;
		}
		
		$this->sort = util::unempty($opts['sort'], 'clicks');
		$this->sort_dir = util::unempty($opts['sort_dir'], 'desc');
		
		$this->detail = util::unempty($opts['detail'], 'market');
		$this->set_detail_cols($opts['fields']);
		
		util::init_data_cols($this->aid);

		// is ag or ca set?
		$ag_ids = array();
		$ca_ids = array();
		if ($this->ad_group) {
			$ag_ids[] = $this->ad_group;
		}
		else if ($this->campaign) {
			$ca_ids[] = $this->campaign;
		}

		// set filter
		$this->set_filter($opts);

		// info cols already set
		if ($this->detail == 'ad_group')
		{
			$this->cols = array_merge($this->cols, array(
				'status' => 1,
				'bid' => 1,
				'cbid' => 1
			));
		}
		else if ($this->detail == 'keyword')
		{
			$this->cols = array_merge($this->cols, array(
				'status' => 1,
				'bid' => 1
			));
		}
		$this->cols = array_merge($this->cols, array(
			'pos' => 1,
			'clicks' => 1,
			'imps' => 1,
			'cpc' => 1,
			'cost' => 1,
			'convs' => 1,
			'cost_conv' => 1
		));
		
		if ($this->account->revenue_tracking) {
			$this->cols = array_merge($this->cols, array(
				'revenue' => 1,
				'roas' => 1
			));
		}
		if ($this->account->conversion_types) {
			$conv_types = conv_type_count::get_account_conv_types($this->aid);
			if (count($conv_types) > 0) {
				$this->cols = array_merge($this->cols, array_flip($conv_types));
			}
		}
		
		$data_market = $this->get_data_market($opts);
		$data_opts = array(
			'aid' => $this->aid,
			'market' => $data_market,
			'detail' => $this->detail,
			'filter' => $this->filter,
			'ad_groups' => $ag_ids,
			'campaigns' => $ca_ids,
			'do_update_cache' => true,
			'do_compute_metrics' => false,
			'do_total' => false,
			'include_msn_deleted' => false,
			'keyword_style' => 'match_type',
			'time_period' => 'all',
			'sort' => $this->sort,
			'sort_dir' => $this->sort_dir,
			'fields' => $this->cols,
			'separate_totals' => 1,
			'start_date' => $this->start_date,
			'end_date' => $this->end_date
		);
		
		if ($opts['search_or_content'] == 'search') {
			$data_opts['search_only'] = true;
		}
		else if ($opts['search_or_content'] == 'content') {
			$data_opts['content_only'] = true;
		}
		return $data_opts;
	}

	private function set_filter($opts)
	{
		$filter_mixed = $opts['filter'];
		if (empty($filter_mixed)) {
			$this->filter = array();
		}
		else {
			if (is_array($filter_mixed)) {
				$this->filter = $filter_mixed;
			}
			// string, decode
			else {
				$this->filter = json_decode($filter_mixed, true);
			}
		}
	}
	
	private function get_data_market()
	{
		if (empty($this->market)) return array_keys($this->all_markets);
		
		// wild card, check campaign and ad group for a market
		if ($this->market == '*')
		{
			$info_filter_cols = array('ad_group', 'campaign');
			
			foreach ($info_filter_cols as $col)
			{
				$id = $this->$col;
				if (preg_match("/^(\w)_/", $id, $matches)) return $matches[1];
			}
			// didn't find a more specific market, return all
			return array_keys($this->all_markets);
		}
		
		return $this->market;
	}
	
	private function set_columns_ml_and_add_col_formatting(&$ml)
	{
		global $g_data_cols;
		
		$this->bid_index = array_search('bid', array_keys($this->cols));
		// loop over calls and add formatting info
		foreach ($this->cols as $col => $ph) {
			$this->cols[$col] = $g_data_cols[$col];
		}
		
		$ml_headers = '';
		foreach ($this->cols as $col => $ph) {
			$ml_headers .= '<td col="'.$col.'">'.$g_data_cols[$col]['display'].'</td>';
		}
		
		// first cell is number
		$ml = '
			<tr id="data_col_headers">
				<td></td>
				'.$ml_headers.'
			</tr>
		';
	}
	
	private function set_crumbs_ml(&$ml)
	{
		$crumbs = array(array('Client', $this->account->name));
		
		// add in market, campaign, ad group
		foreach ($this->detail_cols as $col) {
			$data_id = $this->$col;
			if (empty($data_id)) continue;
			$crumbs[] = array(util::display_text($col), $this->get_crumb_text($col, $data_id));
		}
		
		for ($i = 0, $last_index = count($crumbs) - 1; list($col, $text) = $crumbs[$i]; ++$i) {
			// show link if not the last
			$ml_text = ($i == 0 || $i < $last_index) ? '<a href="">'.$text.'</a>' : $text;
			$ml .= '
				<tr>
					<td class="crumb_label">'.$col.'</td>
					<td>'.$ml_text.'</td>
				</tr>
			';
		}
	}
	
	private function get_crumb_text($detail, $id)
	{
		if (empty($id)) return '';
		// check for wildcard
		if ($id == '*') return 'ALL';
		switch ($detail) {
			case ('market'):
				// you can't select a subset of markets greater than 1 without selecting all
				// if id is array, all
				return (is_array($id)) ? 'ALL' : $this->all_markets[$id];
				
			case ('campaign'):
			case ('ad_group'):
				preg_match("/^(\w)_(\d+)$/", $id, $matches);
				list($ph, $data_market, $data_id) = $matches;
				return db::select_one("select text from {$data_market}_objects.{$detail}_{$this->aid} where id = '$data_id'");
		}
	}
	
	// add all parent detail cols
	private function set_detail_cols($fields)
	{
		global $g_detail_cols;
		
		$this->cols = array();
		$this->detail_cols = array();
		foreach ($g_detail_cols as $col => $col_info) {
			if ($col == 'market' || $col == $this->detail || (is_array($fields) && array_key_exists($col, $fields))) {
				$this->cols[$col] = 1;
			}
			$this->detail_cols[] = $col;
		}
	}
	
	private function check_changes_submit()
	{
		$changes = $_REQUEST['changes'];
		if (empty($changes)) return;
		
		require_once(\epro\WPROPHP_PATH.'apis/apis.php');
		
		$changes = json_decode($changes, true);
		$func = 'submit_'.$this->detail.'_changes';
		$this->$func($changes);
	}
	
	private function get_ag_info_for_keywords()
	{
		// also refresh non-google ad groups to get max cpc's??
		util::load_lib('data_cache');
		
		if (dbg::is_on()) data_cache::dbg();
		
		$cas = array();
		$ag_info = array();
		for ($i = 0, $count = count($this->data->data); $i < $count; ++$i) {
			list($market, $ag_id) = explode('_', $this->data->data[$i]['uid']);
			if (empty($all_ags[$market])) {
				$ca_id = db::select_one("select campaign_id from {$market}_objects.ad_group_{$this->aid} where id = '$ag_id'");
				
				if ($market != 'g') {
					if (empty($cas[$market][$ca_id])) {
						data_cache::update_ad_groups($this->aid, $market, $ca_id);
					}
					$cas[$market][$ca_id] = 1;
				}
				
				$tmp = db::select("
					select id, max_cpc, kw_info_mod_time
					from {$market}_objects.ad_group_{$this->aid}
					where campaign_id = '$ca_id'
				", 'NUM', 0);
				if (!is_array($all_ags[$market])) {
					$all_ags[$market] = $tmp;
				}
				else {
					$all_ags[$market] = array_merge($all_ags[$market], $tmp);
				}
			}
			list($max_cpc, $kw_info_mod_time) = $all_ags[$market][$ag_id];
			$do_refresh_cache = (($kw_info_mod_time == '0000-00-00 00:00:00') || ((\epro\NOW - strtotime($kw_info_mod_time)) > util::DATA_CACHE_EXPIRE));
			$ag_info[$market][$ag_id] = array('max_cpc' => $max_cpc, 'do_refresh_cache' => $do_refresh_cache);
		}
		
		cgi::to_js('g_ag_info', $ag_info);
	}
	
	public function ajax_process_changes()
	{
		$changes = json_decode($_REQUEST['changes'], true);
		// e($_REQUEST); e($changes); db::dbg();
		$this->results = array();
		foreach ($changes as $market => $market_changes) {
			foreach ($market_changes as $ag_id => $ag_changes) {
				$this->do_process_ag_changes($market, $ag_id, $ag_changes);
			}
		}
		echo json_encode($this->results);
	}

	private function do_process_ag_changes($market, $ag_id, $ag_changes)
	{
		list($cl_id, $ag_or_kw, $bid_type) = util::list_assoc($_REQUEST, 'cl_id', 'ag_or_kw', 'bid_type');
		list($ac_id, $ca_id, $ag_name) = db::select_row("select account_id, campaign_id, text from {$market}_objects.ad_group_{$this->aid} where id = '$ag_id'");
		
		if (empty($ac_id)) {
			$this->results[$market][$ag_id] = array('type' => 'error', 'text' => '<i>'.$ag_name.'</i>: could not find account');
		}
		
		// sleep(mt_rand(1, 3)); $this->results[$market][$ag_id] = array('type' => 'success', 'text' => '<i>'.$ag_name.'</i> updated'); return;
		
		$api = base_market::get_api($market, $ac_id);
		$api->debug();
		
		if ($ag_or_kw == 'ad_group') {
			if ($bid_type == 'bid') {
				$new_bid = $_REQUEST['kw_bid_0'];
				$new_cbid = null;
			}
			else {
				$new_bid = null;
				$new_cbid = $_REQUEST['kw_bid_0'];
			}
			$new_status = (array_key_exists('kw_status_0', $_REQUEST)) ? $_REQUEST['kw_status_0'] : null;
			$ag = api_factory::new_ad_group($ca_id, $ag_id, null, $new_bid, $new_cbid, $new_status);
			
			if ($new_status == 'Off') {
				if (!$api->pause_ad_group($ag)) {
					$this->results[$market][$ag_id] = array('type' => 'error', 'text' => '<i>'.$ag_name.'</i>: '.$api->get_error());
				}
			}
			else if ($new_status == 'On') {
				if (!$api->resume_ad_group($ag)) {
					$this->results[$market][$ag_id] = array('type' => 'error', 'text' => '<i>'.$ag_name.'</i>: '.$api->get_error());
				}
			}
			if ($new_bid || $new_cbid) {
				if (!$api->update_ad_group($ag)) {
					$this->results[$market][$ag_id] = array('type' => 'error', 'text' => '<i>'.$ag_name.'</i>: '.$api->get_error());
				}
			}
			
			// we made it!
			$ag->db_set($market, $this->aid);
			$this->results[$market][$ag_id] = array('type' => 'success', 'text' => '<i>'.$ag_name.'</i> updated');
		}
		else {
			$kws = array();
			foreach ($ag_changes as $kw_id => $kw_changes) {
				$new_bid = (isset($kw_changes['bid'])) ? $kw_changes['bid'] : null;
				$new_status = (isset($kw_changes['status'])) ? $kw_changes['status'] : null;
				$kws[] = api_factory::new_keyword($ag_id, $kw_id, null, null, $new_bid, null, $new_status);
			}
			if ($api->update_keywords($kws, $ag_id)) {
				foreach ($kws as &$kw) {
					$kw->db_set($market, $this->aid);
				}
				$this->results[$market][$ag_id] = array('type' => 'success', 'text' => '<i>'.$ag_name.'</i>: '.count($kws).' keywords updated');
			}
			else {
				$this->results[$market][$ag_id] = array('type' => 'error', 'text' => '<i>'.$ag_name.'</i>: '.$api->get_error());
			}
		}
	}

	/*
	 * javascript splits work into ad group sized chunks and sends ajax requests back to server
	 * so this updates 1 ad group at a time (even when we are updating ad groups)
	 */
	
	public function ajax_process_updates()
	{
		list($cl_id, $market, $ag_id, $ag_or_kw, $bid_type) = util::list_assoc($_REQUEST, 'cl_id', 'market', 'ag_id', 'ag_or_kw', 'bid_type');
		list($ac_id, $ca_id, $ag_name) = db::select_row("select account_id, campaign_id, text from {$market}_objects.ad_group_{$this->aid} where id = '$ag_id'");
		
		//e($_REQUEST); db::dbg();
		
		if (empty($ac_id)) {
			cgi::ajax_error('<i>'.$ag_name.'</i>: could not find account');
		}
		
		//sleep(mt_rand(1, 3)); cgi::ajax_success('<i>'.$ag_name.'</i> updated'); return;
		
		$api_class = $market.'_api';
		$api = new $api_class(1, $ac_id);
		//$api->debug();
		
		if ($ag_or_kw == 'ad_group') {
			if ($bid_type == 'bid') {
				$new_bid = $_REQUEST['kw_bid_0'];
				$new_cbid = null;
			}
			else {
				$new_bid = null;
				$new_cbid = $_REQUEST['kw_bid_0'];
			}
			$new_status = (array_key_exists('kw_status_0', $_REQUEST)) ? $_REQUEST['kw_status_0'] : null;
			$ag = api_factory::new_ad_group($ca_id, $ag_id, null, $new_bid, $new_cbid, $new_status);
			
			if ($new_status == 'Off') {
				if (!$api->pause_ad_group($ag)) {
					cgi::ajax_error('<i>'.$ag_name.'</i>: '.$api->get_error());
				}
			}
			else if ($new_status == 'On') {
				if (!$api->resume_ad_group($ag)) {
					cgi::ajax_error('<i>'.$ag_name.'</i>: '.$api->get_error());
				}
			}
			if ($new_bid || $new_cbid) {
				if (!$api->update_ad_group($ag)) {
					cgi::ajax_error('<i>'.$ag_name.'</i>: '.$api->get_error());
				}
			}
			
			// we made it!
			$ag->db_set($market, $this->aid);
			cgi::ajax_success('<i>'.$ag_name.'</i> updated');
		}
		else {
			$kws = array();
			for ($i = 0; true; ++$i) {
				$kw_id = $_REQUEST['kw_id_'.$i];
				if (empty($kw_id)) break;
				
				$new_bid = (array_key_exists('kw_bid_'.$i, $_REQUEST)) ? $_REQUEST['kw_bid_'.$i] : null;
				$new_status = (array_key_exists('kw_status_'.$i, $_REQUEST)) ? $_REQUEST['kw_status_'.$i] : null;
				$kws[] = api_factory::new_keyword($ag_id, $kw_id, null, null, $new_bid, null, $new_status);
			}
			if ($api->update_keywords($kws, $ag_id)) {
				foreach ($kws as &$kw) {
					$kw->db_set($market, $this->aid);
				}
				cgi::ajax_success('<i>'.$ag_name.'</i>: '.count($kws).' keywords updated');
			}
			else {
				cgi::ajax_error('<i>'.$ag_name.'</i>: '.$api->get_error());
			}
		}		
	}
	
	public function ajax_refresh_keywords()
	{
		require_once(\epro\COMMON_PATH.'data_cache.php');
		
		list($cl_id, $market_and_ag_id) = util::list_assoc($_REQUEST, 'cl_id', 'ag_id');
		list($market, $ag_id) = explode('_', $market_and_ag_id);
/*
		// testing
		$st = mt_rand(1, 3);
		echo "?$st?<br>\n";
		sleep($st);
		e($_REQUEST, $kws, $m_keywords);
		return;
*/
		
		//data_cache::update_keywords($cl_id, $market, $ag_id);
		
		$ag_max_cpc = db::select_one("
			select max_cpc
			from {$market}_objects.ad_group_{$this->aid}
			where id = '$ag_id'
		");
		
		$tmp_keywords = db::select("
			select id, max_cpc
			from {$market}_objects.keyword_{$this->aid}
			where ad_group_id = '$ag_id'
		", 'NUM', 0);
		
		if ($market == 'm') {
			$m_keywords = db::select("
				select substring(id, 2), max_cpc
				from {$market}_objects.keyword_{$this->aid}
				where
					ad_group_id = '$ag_id' &&
					max_cpc <> 0 &&
					substring(id, 1) <> 'C'
			", 'NUM', 0);
		}
		
		$kws = array();
		for ($i = 0; $i < 1024; ++$i) {
			if (!array_key_exists('kw_id_'.$i, $_REQUEST)) {
				break;
			}
			$kw_id = $_REQUEST['kw_id_'.$i];
			$max_cpc = $tmp_keywords[$kw_id];
			if (empty($max_cpc)) {
				// because bing serves keywords users don't explicitly create?
				$m_id_no_match_type = substr($kw_id, 1);
				if ($market == 'm' && array_key_exists($m_id_no_match_type, $m_keywords)) {
					$max_cpc = $m_keywords[$m_id_no_match_type];
				}
				else {
					$max_cpc = $ag_max_cpc;
				}
			}
			$kws[$kw_id] = $max_cpc;
		}
		echo json_encode($kws);
	}
	
	public function ajax_run_filter_get_ad_groups()
	{
		$market = $cl_id = $campaign = $ad_group = $detail = null;
		extract($_REQUEST, EXTR_OVERWRITE | EXTR_IF_EXISTS);

		$markets = (empty($market) || $market == '*') ? array_keys($this->all_markets) : array($market);

		$ag_query = array();
		$q_data = array();
		if ($ad_group) {
			$ag_query[] = "id = :agid";
			$q_data['agid'] = substr($ad_group, strpos($ad_group, '_') + 1);
		}
		else if ($campaign) {
			$ag_query[] = "campaign_id = :caid";
			$q_data['caid'] = substr($campaign, strpos($campaign, '_') + 1);
		}

		// if we have an ag_query, that means we have already updated ad group cache, if it's a client query, we may not have so run update
		if (empty($ag_query)) {
			$do_update_cache = true;
			util::load_lib('data_cache');
		}
		else {
			$do_update_cache = false;
		}
		
		$ad_groups = ad_group::new_array();
		foreach ($markets as $market) {
			ppc_lib::set_market_object_tables($market, $cl_id);
			if ($do_update_cache) {
				$market_campaigns = campaign::get_all(array("select" => "id"));
				foreach ($market_campaigns as $ca) {
					data_cache::update_ad_groups($cl_id, $market, $ca->id);
				}
			}
			$market_ad_groups = ad_group::get_all(array(
				"select" => array("ad_group" => array("('$market') as market", "id as ad_group")),
				"where" => $ag_query,
				"data" => $q_data
			));
			$ad_groups->merge($market_ad_groups);
		}
		echo $ad_groups->json_encode();
	}
	
	public function ajax_run_filter_on_ad_group()
	{
		g::$client_id = $_REQUEST['client'];
		$this->pre_output();

		$this->set_data_opts();
		$this->data_opts['do_compute_metrics'] = true;
		$this->data = all_data::get_data($this->data_opts);
		
		$response = array('data' => $this->data->data);
		if ($_REQUEST['_ap_index_'] == 0) {
			$this->set_columns_ml_and_add_col_formatting($ml_cols);
			$response['cols'] = $this->get_js_cols();
		}
		echo json_encode($response);
	}
	
	private function get_js_cols()
	{
		$cols_ordered = array();
		$formats = array();
		foreach ($this->cols as $col_name => $col_info) {
			if ($col_name == 'pos') {
				$col_name = 'ave_pos';
			}
			$col = array(
				'key' => $col_name,
				'display' => $col_info['display']
			);
			
			$totals_val = null;
			switch ($col_name) {
				case ('bid'): $totals_val = '-'; break;
			}
			if (!is_null($totals_val)) {
				$col['totals_val'] = $totals_val;
			}
			
			// classes
			$classes = '';
			switch ($col_name) {
				case ('status'): $classes = 'c';
			}
			if ($classes) {
				$col['classes'] = $classes;
			}
			
			// formatting
			if (array_key_exists('format', $col_info)) {
				$formats[$col_name] = str_replace('format_', '', $col_info['format']);
			}
			
			$cols_ordered[] = $col;
		}
		cgi::add_js_var('formats', $formats);
		return $cols_ordered;
	}
	
	private function submit_ad_group_changes(&$changes)
	{
		
	}
	
	private function submit_ad_changes(&$changes)
	{
	}
	
	private function check_cache()
	{
		if ($_REQUEST['data_cache_update_submit']) {
			$this->data_cache_update_submit();
		}
		else if ($this->detail == 'ad_group') {
			require_once(\epro\COMMON_PATH.'data_cache.php');
			
			list($market, $ca_id) = explode('_', $_REQUEST['campaign']);
			data_cache::update_ad_groups($this->aid, $market, $ca_id);
		}
	}
	
	public function data_cache_update_submit()
	{
		util::load_lib('data_cache');
		
		if (dbg::is_on()) data_cache::dbg();
		$cache_id = $_REQUEST['cache_id'];
		list($market, $cache_data_id) = explode('_', $cache_id);
		$detail_index = array_search($this->detail, $this->detail_cols);
		
		// 23.09.2011: why are we looping?
		for ($i = $detail_index + 1, $ci = count($this->detail_cols); $i < $ci; ++$i) {
			$detail = $this->detail_cols[$i];
			if (array_key_exists($cache_id.'_cache_detail_'.$detail, $_REQUEST)) {
				if ($this->detail == 'market') {
					$ac_ids = db::select("
						select distinct account
						from eppctwo.data_sources
						where
							account_id = :aid &&
							market = :market
					", array(
						"aid" => $this->aid,
						"market" => $market
					));
					foreach ($ac_ids as $ac_id) {
						data_cache::update_campaigns($this->aid, $market, $ac_id, array('force_update' => true));
					}
				}
				else if ($this->detail == 'campaign') {
					if ($detail == 'ad_group') {
						data_cache::update_ad_groups($this->aid, $market, $cache_data_id, array('force_update' => true));
					}
					else if ($detail == 'keyword') {
						$ags = db::select("select ad_group from {$market}_info.ad_groups_{$this->data_id} where campaign = {$cache_data_id}");
						foreach ($ags as $ag_id) {
							data_cache::update_keywords($this->aid, $market, $ag_id, array('force_update' => true));
						}
					}
				}
				else if ($this->detail == 'ad_group') {
					if ($detail == 'keyword') {
						data_cache::update_keywords($this->aid, $market, $cache_data_id, array('force_update' => true));
					}
				}
			}
		}
		feedback::add_success_msg('Cache Updated');
	}

	public function action_schedule_filter()
	{
		$detail_before = $_REQUEST['detail'];

		// get detail from filter
		$_REQUEST['detail'] = $_REQUEST['ag_or_kw'];
		$data_opts = $this->get_data_opts($_REQUEST);
		$data_opts['do_compute_metrics'] = true;

		// get market and campaigns from filter
		$filter_market = $_REQUEST['filter_market'];
		$data_opts['market'] = $filter_market;
		$campaign_key = "{$filter_market}_filter_campaigns";
		if (!empty($_REQUEST[$campaign_key])) {
			$data_opts['campaigns'] = explode("\t", $_REQUEST[$campaign_key]);
		}
		// ignore ad group (might be set if user was looking at keywords)
		unset($data_opts['ad_groups']);
		if (is_array($data_opts['filter'])) {
			foreach ($data_opts['filter'] as $i => &$or_group) {
				for ($j = 0; $j < count($or_group); ++$j) {
					$and_condition = $or_group[$j];
					if ($and_condition['col'] == 'ad_group_id') {
						array_splice($or_group, $j, 1);
						--$j;
					}
				}
			}
		}

		// we want to show campaigns and ad groups in filter results
		$data_opts['fields']['campaign'] = 1;
		$data_opts['fields']['ad_group'] = 1;

		// todo: check if this exact filter has been run before for this account

		// schedule
		$this->data = all_data::schedule_reduce($data_opts, 'filter');
		feedback::add_success_msg('Filter scheduled');

		// reset filter stuff so when we call get_data_opts in index we ignore
		unset($_REQUEST['filter']);
		$_REQUEST['detail'] = $detail_before;
	}
}

?>