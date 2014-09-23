<?php

class mod_account_product_ql_spend extends mod_account_product_ql
{
	public function pre_output()
	{
		parent::pre_output();
		$this->display_default = 'index';
	}
	
	public function display_index()
	{
		list($this->start_date, $this->end_date) = util::list_assoc($_REQUEST, 'start_date', 'end_date');
		// kw 2013-10-03: managers say they do not use this page anymore
		// must be updated to use new object databases if they want it back
		echo "coming soon";
		return;
		if (util::empty_date($this->start_date))
		{
			$this->start_date = $this->end_date = date(util::DATE, time() - 86400);
		}
		
		?>
		<div id="w_url_spend">
			<?php echo $this->ml_form(); ?>
			<?php echo $this->ml_ad_groups(); ?>
			<?php echo $this->ml_metrics(); ?>
			<div class="clr"></div>
		</div>
		<?php
	}
	
	private function ml_form()
	{
		return '<div class="w_date_range">'.cgi::date_range_picker($this->start_date, $this->end_date).'</div>';
	}
	
	private function ml_metrics()
	{
		$spend_metrics = db::select_row("
			select days_to_date, days_remaining, days_in_month, imps_to_date, spend_to_date, spend_remaining, spend_prev_month, daily_to_date, daily_remaining
			from eppctwo.ql_spend
			where account_id = :aid
		", array(
			'aid' => $this->account->id
		), 'ASSOC');
		
		$cols = array(
			array('days_to_date'    ,'DTD'  ),
			array('days_remaining'  ,'DR'   ),
			array('spend_to_date'   ,'STD' ,'format_dollars'),
			array('spend_remaining' ,'SR'  ,'format_dollars'),
			array('daily_to_date'   ,'DSTD','format_dollars'),
			array('daily_remaining' ,'DSR' ,'format_dollars'),
			array('spend_prev_month','SPM' ,'format_dollars')
		);
		
		$ml_headers = $ml_data = '';
		for ($i = 0; list($k, $header, $format_func) = $cols[$i]; ++$i)
		{
			$v = $spend_metrics[$k];
			$ml_headers .= '<th>'.$header.'</th>';
			$ml_data .= '<td>'.(($format_func) ? util::$format_func($v) : $v).'</td>';
		}
		return '
			<div id="w_metrics">
				<table>
					<thead>
						<tr>
							'.$ml_headers.'
						</tr>
					</thead>
					<tbody>
						<tr>
							'.$ml_data.'
						</tr>
					</tbody>
				</table>
			</div>
		';
	}
	
	private function ml_ad_groups()
	{
		$ml = '';
		foreach ($this->markets as $market => $market_text)
		{
			$ds_info = db::select("
				select ac.text, ca.text, ag.ad_group, ag.text, ag.kw_info_mod_time, ag.max_cpc
				from eppctwo.{$market}_accounts ac, {$market}_info.campaigns_ql ca, {$market}_info.ad_groups_ql ag
				where
					ag.client = '{$this->account->id}' &&
					ag.status = 'On' &&
					ca.campaign = ag.campaign &&
					ca.account = ac.id
			");
			
			$ml_market = '';
			
			for ($i = 0, $ci = count($ds_info); list($ac_text, $ca_text, $ag_id, $ag_text, $kw_cachetime, $ag_bid) = $ds_info[$i]; ++$i)
			{
				$ag_uid = $market.'_'.$ag_id;
				$ml_market .= '
					<div class="w_ag" id="'.$ag_uid.'" ag_id="'.$ag_id.'">
						<p>Cache: '.$kw_cachetime.' (<a href="">force update</a>)</p>
						<table>
							<tbody>
								<tr>
									<td>Account</td>
									<td>'.$ac_text.'</td>
								</tr>
								<tr>
									<td>Campaign</td>
									<td>'.$ca_text.'</td>
								</tr>
								<tr>
									<td>Ad Group</td>
									<td>
										'.$ag_text.'
										<input type="text" name="'.$ag_uid.'_ag_bid" id="'.$ag_uid.'_ag_bid" class="bid_input ag_bid_input" value="'.util::n2($ag_bid).'" />
										<input type="submit" class="ag_bid_submit small_button" value="Update" />
									</td>
								</tr>
							</tbody>
						</table>
						
						<table class="multi_bid_table">
							<tbody>
								<tr>
									<td>%</td>
									<td><input type="text" class="multi_bid_input" t="percent" /></td>
									<td>&Delta;</td>
									<td><input type="text" class="multi_bid_input" t="delta" /></td>
									<td>$</td>
									<td><input type="text" class="multi_bid_input" t="absolute" /></td>
								</tr>
							</tbody>
						</table>
						
						<div class="w_submit">
							<input class="detail_submit" type="submit" value="Submit Changes" />
							<div class="detail_updates_msg detail_updates_msg_loading"><img src="/img/loading.gif" /></div>
							<div class="detail_updates_msg detail_updates_msg_success">Success!</div>
							<div class="detail_updates_msg detail_updates_msg_error"></div>
						</div>
						
						<table class="w_keywords">
							<thead>
								<tr>
									<th></th>
									<th>Keyword</th>
									<th><!-- status --></th>
									'.$this->ml_market_kw_cols($market).'
									<th>Clks</th>
									<th>Cost</th>
									<th>CPC</th>
									<th>Pos</th>
									<th>Bid</th>
								</tr>
								<tr class="kw_totals"></tr>
							</thead>
							<tbody class="kw_form">
								<tr><td colspan="100">LOADING <img src="/img/loading.gif" /></td></tr>
							</tbody>
							<tfoot>
								<tr class="kw_totals"></tr>
								<tr class="deleted_totals"></tr>
								<tr class="all_totals"></tr>
							</tfoot>
						</table>
					</div>
				';
			}
			
			$ml .= '
				<div class="w_market" market="'.$market.'">
					<img src="'.cgi::href('img/'.$market.'.png').'" />
					'.$ml_market.'
				</div>
			';
		}
		return '
			<div id="w_ags">
				'.$ml.'
			</div>
		';
	}
	
	private function ml_market_kw_cols($market)
	{
		switch ($market)
		{
			case ('g'):
				return '
					<th>QS</th>
					<th>FPB</th>
				';
				
			case ('y'):
				return '
					<th>Min</th>
				';
		}
		return '';
	}
	
	public function ajax_get_keyword_details()
	{
		list($market, $ag_id, $start_date, $end_date, $force_update) = util::list_assoc($_POST, 'market', 'ag_id', 'start_date', 'end_date', 'force_update');
		if ($force_update) {
			$this->account->update_data();
		}
		echo json_encode($this->get_market_details($this->account->id, $market, $ag_id, $start_date, $end_date, $force_update));
	}
	
	public function ajax_submit_bid_changes($echo_success = true)
	{
		require_once(\epro\WPROPHP_PATH.'apis/apis.php');
		
		list($market, $ag_id) = util::list_assoc($_POST, 'market', 'ag_id');
		list($aid, $market_aid) = db::select_row("select client, account from {$market}_info.ad_groups_ql where ad_group = '$ag_id'");
		
		// build array of keywords to change from $_POST
		$keywords = array();
		for ($i = 0; 1; ++$i) {
			list($kw_id, $new_bid, $new_status) = util::list_assoc($_POST, 'bid_'.$i.'_kw_id', 'bid_'.$i.'_amount', 'bid_'.$i.'_status');
			if (empty($kw_id)) break;
			
			$keyword = api_factory::new_keyword($ag_id, $kw_id);
			if (is_numeric($new_bid) && $new_bid >= 0) $keyword->max_cpc = $new_bid;
			if (!empty($new_status)) $keyword->status = $new_status;
			$keywords[] = $keyword;
		}
		if (empty($keywords)) cgi::ajax_error('No bids were changed');

		$api = base_market::get_api($market, $market_aid);
		$api->debug();
		// db::dbg(); e($keywords);
		if ($api->update_keywords($keywords, $ag_id) === false) cgi::ajax_error($api->get_error());
		
		// update the local database copy
		for ($i = 0, $count = count($keywords); $i < $count; ++$i) {
			$keywords[$i]->db_set($market, 'ql', null, null, null, $ag_id);
		}
		
		// return generic success message
		if ($echo_success) echo json_encode(array('success' => 1));
	}
	
	public function ajax_submit_ad_group_changes()
	{
		require_once(\epro\WPROPHP_PATH.'apis/apis.php');
		
		list($market, $ag_id, $new_bid) = util::list_assoc($_POST, 'market', 'ag_id', 'new_bid');
		list($market_aid, $ca_id) = db::select_row("
			select account, campaign
			from {$market}_info.ad_groups_ql
			where ad_group = :agid
		", array('agid' => $ag_id));
		$ag = api_factory::new_ad_group($ca_id, $ag_id, null, $new_bid);
		$api = base_market::get_api($market, $market_aid);
		
		if ($api->update_ad_group($ag))
		{
			$ag->db_set($market, 'ql');
			echo json_encode(array('success' => 1));
		}
		else
		{
			cgi::ajax_error($api->get_error());
		}
	}
	
	
	protected function get_market_details($aid, $market, $ag_id, $start_date, $end_date, $force_update = false)
	{
		util::load_lib('data_cache');
		
		if (dbg::is_on()) data_cache::dbg();
		
		// get campaign id for ad group cache update
		$ca_id = db::select_one("select campaign from {$market}_info.ad_groups_ql where ad_group = '$ag_id'");
		
		// update ad groups
		if ($ca_id && $ca_id != '0')
		{
			data_cache::update_ad_groups($aid, $market, $ca_id, array(
				'force_update' => $force_update,
				'data_id' => 'ql',
				'do_set_client' => false
			));
			db::update(
				"{$market}_info.ad_groups_ql",
				array('client' => $aid),
				"ad_group = '$ag_id'"
			);
		}
		
		// keywords
		list($did_update_cache, $cache_modtime) = data_cache::update_keywords($aid, $market, $ag_id, array('force_update' => $force_update, 'data_id' => 'ql'));
		
		list($ac_text, $ca_text, $ag_text, $ag_max_cpc) = db::select_row("
			select ac.text, ca.text, ag.text, ag.max_cpc
			from eppctwo.{$market}_accounts ac, {$market}_info.campaigns_ql ca, {$market}_info.ad_groups_ql ag
			where
				ag.ad_group = '$ag_id' &&
				ca.campaign = ag.campaign &&
				ca.account = ac.id
		");
		
		$kw_infos = db::select("
			select keyword, text, type, status, max_cpc, market_info
			from {$market}_info.keywords_ql
			where
				client = '$aid' &&
				ad_group = '$ag_id' &&
				".util::get_content_query($market, false)."
		", 'NUM', 0);
	
		$kw_data = db::select("
			select keyword, sum(imps), sum(clicks), sum(cost), sum(pos_sum)
			from {$market}_data.keywords_ql
			where
				client = '$aid' &&
				ad_group = '$ag_id' &&
				data_date >= '$start_date' &&
				data_date <= '$end_date'
			group by keyword
		", 'NUM', 0);
		
		$market_keywords = array();
		if (!empty($kw_infos))
		{
			// create non existant match type for msn
			if ($market == 'm')
			{
				$kws_no_match_type = array();
				$kws_found = array();
			}
			foreach ($kw_infos as $kw_id => &$kw_info)
			{
				if ($market == 'm')
				{
					$kws_no_match_type[substr($kw_id, 1)] = $kw_info;
					$kws_found[$kw_id] = 1;
				}
		
				$market_keywords[] = $this->get_market_details_build_keyword($kw_id, $kw_info, $kw_data[$kw_id]);
			}
			
			// if msn, check for data associated with matchtypes that don't exist
			if ($market == 'm')
			{
				foreach ($kw_data as $kw_id => &$kw_datum)
				{
					if (!array_key_exists($kw_id, $kws_found))
					{
						$kw_id_no_match_type = substr($kw_id, 1);
						if (array_key_exists($kw_id_no_match_type, $kws_no_match_type))
						{
							// set match type!
							$kw_info = $kws_no_match_type[$kw_id_no_match_type];
							if ($kw_id[0] == 'P') $kw_info[1] = 'Phrase';
							if ($kw_id[0] == 'E') $kw_info[1] = 'Exact';
							$market_keywords[] = $this->get_market_details_build_keyword($kw_id, $kw_info, $kw_datum);
						}
					}
				}
			}
			
			util::sort2d($market_keywords, 'cost', 'desc');
		}
		return array(
			'ac_text' => $ac_text,
			'ca_text' => $ca_text,
			'ag_text' => $ag_text,
			'ag_max_cpc' => $ag_max_cpc,
			'cache_modtime' => $cache_modtime,
			'keywords' => $market_keywords
		);
	}
	
	private function get_market_details_build_keyword($kw_id, $kw_info, $kw_data)
	{
		list($kw_text, $kw_type, $kw_status, $kw_bid, $kw_market_info) = $kw_info;
		list($imps, $clicks, $cost, $pos_sum) = @$kw_data;
		
		if ($kw_market_info) {
			$kw_market_info = json_decode($kw_market_info, true);
		}
		if (is_null($imps)) {
			$imps = $clicks = $cost = $pos_sum = 0;
		}
		
		return array(
			'id' => $kw_id,
			'text' => util::get_keyword_display($kw_text, $kw_type),
			'status' => $kw_status,
			'bid' => $kw_bid,
			'market_info' => $kw_market_info,
			'imps' => $imps,
			'clicks' => $clicks,
			'cost' => $cost,
			'cpc' => util::safe_div($cost, $clicks),
			'pos' => util::safe_div($pos_sum, $imps)
		);
	}
}

?>