<?php


class mod_account_product_ql_markets extends mod_account_product_ql
{
	// set when ad group is selected
	protected $market, $ag_id;
	
	// campaign or ad group
	protected $ds_level;
	
	public function pre_output()
	{
		parent::pre_output();
		
		require_once(\epro\WPROPHP_PATH.'apis/apis.php');

		$this->display_default = 'index';
		$this->register_widget('add_google_account');

		$this->data_sources = $this->account->get_data_sources();
		
		list($this->market, $this->ag_id) = util::list_assoc($_REQUEST, 'm', 'ag_id');

		// set default market if no market set
		if (empty($this->market)) {
			foreach ($this->markets as $market => $market_disp) {
				if (!empty($this->data_sources->a[$market])) {
					$this->market = $market;
					break;
				}
			}
		}
		if ($this->market) {
			$this->ds = $this->data_sources->a[$this->market];
			$this->object_key = "Q{$this->ds->account}";
		}
		else {
			$this->ds = false;
			$this->object_key = "";
		}
	}
	
	public function display_index()
	{
		$this->print_data_sources();
		if ($this->market) {
			$this->print_ad_groups();
			$this->print_client_updates();
		}
		cgi::add_js_var('markets', $this->markets);
	}
	
	private function print_data_sources()
	{
		$ml = '';
		$links = array('Set Data Source', 'Un-Tie', 'Re-Tie Ads');
		foreach ($this->markets as $market => $market_display) {
			$ml_links = '';
			foreach ($links as $link_text) {
				$ml_links .= '<span><a href="" class="'.util::simple_text($link_text).'_link">'.$link_text.'</a></span>';
			}
			
			$ml .= $this->ml_data_source($market, $ml_links);
		}
		
		?>
		<fieldset class="section" id="w_data_sources">
			<legend>Data Sources</legend>
			<div class="left display_main">
				<?php echo $ml; ?>
				<div class="clr"></div>
			</div>
			<div class="left display_other"></div>
			<div class="clear"></div>
		</fieldset>
		<div class="clr"></div>
		<?php
	}
	
	private function print_ad_groups()
	{
		// get all ads for our 'copy from' feature
		$this->all_ads = array();
		$shared_links = array();
		if ($this->ds_level == 'ca') {
			$shared_links[] = 'Pull New Ad Groups';
		}
		$each_links = array('Refresh Ads', 'Refresh Keywords');
		$ml_each_links = $this->ml_links($each_links);
		
		if (!$this->is_ds_set($this->market)) {
			$ags = array();
		}
		else {
			$ags = db::select("
				select '{$this->market}' market, id, text, status
				from {$this->market}_objects.ad_group_{$this->object_key}
				where ".$this->ds->get_ad_group_query()."
				order by text asc
			", 'ASSOC');
		}
		
		// if only 1 ad group, it's okay if it's not on
		$is_paused_ok = (count($ags) == 1);
		
		$ml = '';
		for ($i = 0, $ci = count($ags); $i < $ci; ++$i)
		{
			list($ag_id, $ag_text, $status) = util::list_assoc($ags[$i], 'id', 'text', 'status');
			if ($status == 'On' || $is_paused_ok)
			{
				$ml .= '
					<fieldset class="container w_ag" ag_id="'.$ag_id.'" ejo="ad_group">
						<legend>'.$ag_text.'</legend>
						<div class="header">
							'.$ml_each_links.'
						</div>
						'.$this->ml_ads($ag_id).'
						<div class="ag_spacer">&nbsp;</div>
						'.$this->ml_keywords($ag_id).'
					</fieldset>
				';
			}
		}
		
		// now get other markets so we have a list of all ad groups for this account
		$this->add_ad_groups_js_var($ags);
		$this->add_ads_js_var($ags);
		
		?>
		<fieldset class="section" id="w_ad_groups">
			<legend>Ad Groups</legend>
			
			<!-- shared links -->
			<div class="container">
				<div class="header">
					<?php echo $this->ml_links($shared_links); ?>
				</div>
			</div>
			
			<!-- client update stuff -->
			<div id="w_update_dropdowns">
				<?php echo $this->ml_client_updates('ad'); ?>
				<?php echo $this->ml_client_updates('keywords'); ?>
			</div>
			<div id="w_update_details"></div>
			<div class="clr"></div>
			
			<!-- ad and keyword ml -->
			<div class="left display_main">
				<?php echo $ml; ?>
				<div class="clr"></div>
			</div>
			<div class="left display_other"></div>
			<div class="clear"></div>
		</fieldset>
		<div class="clr"></div>
		<input type="hidden" id="ql_ad_id" name="ql_ad_id" value="" />
		<input type="hidden" id="ag_id" name="ag_id" value="" />
		<input type="hidden" id="ad_id" name="ad_id" value="" />
		<?php
	}
	
	private function add_ad_groups_js_var($ags)
	{
		$other_markets = array_diff(array_keys($this->markets), array($this->market));
		foreach ($other_markets as $market) {
			if ($this->is_ds_set($market)) {
				$ds = $this->data_sources->a[$market];
				$market_ags = db::select("
					select '{$market}' market, id, text
					from {$market}_objects.ad_group_Q{$ds->account}
					where
						".$ds->get_ad_group_query()." &&
						status = 'On'
					order by text asc
				", 'ASSOC');
				$ags = array_values(array_merge($ags, $market_ags));
			}
		}
		cgi::add_js_var('ad_groups', $ags);
	}
	
	private function add_ads_js_var($ads)
	{
		$other_markets = array_diff(array_keys($this->markets), array($this->market));
		foreach ($other_markets as $market) {
			if ($this->is_ds_set($market)) {
				$ds = $this->data_sources->a[$market];
				$market_ads = db::select("
					select '{$market}' market, a.ad_group_id, a.id, a.text
					from {$market}_objects.ad_Q{$ds->account} a
					where
						".$ds->get_entity_query()." &&
						a.status not in ('Deleted', 'Off')
				", 'ASSOC');
				$this->all_ads = array_values(array_merge($this->all_ads, $market_ads));
			}
		}
		cgi::add_js_var('ads', $this->all_ads);
	}
	
	private function ml_links($links)
	{
		$ml_links = '';
		foreach ($links as $link_text)
		{
			$ml_links .= '<span><a href="" class="'.util::simple_text($link_text).'_link">'.$link_text.'</a></span>';
		}
		return $ml_links;
	}
	
	private function ml_client_updates($type)
	{
		return '
			<div class="container" id="client_updates_'.$type.'" ejo>
				<div class="header">
					<span class="text">Client '.util::display_text($type).' Updates</span>
					<span class="_updates"></span>
				</div>
			</div>
		';
	}

	private function is_ds_set($market)
	{
		return ($this->data_sources->key_exists($market));
	}
	
	private function ml_data_source($market, $ml_links)
	{
		if ($this->is_ds_set($market)) {
			$market_ds = $this->data_sources->a[$market];
			$ac_text = db::select_one("
				select text
				from eppctwo.{$market}_accounts
				where id = :aid
			", array('aid' => $market_ds->account));
			if ($market_ds->campaign) {
				$ca_text = db::select_one("
					select text
					from {$market}_objects.campaign_Q{$market_ds->account}
					where id = :id
				", array('id' => $market_ds->campaign));
			}
			if ($market_ds->ad_group) {
				$ag_text = db::select_one("
					select text
					from {$market}_objects.ad_group_Q{$market_ds->account}
					where id = :id
				", array('id' => $market_ds->ad_group));
			}
		}
		else {
			$market_ds = new ql_data_source();
		}
		
		// check market
		if ($this->market) {
			if ($this->market == $market) {
				$ml_selected = ' selected';
				if ($this->ds->ad_group) {
					$this->ds_level = 'ag';
				}
				else if ($this->ds->account && $this->ds->campaign) {
					$this->ds_level = 'ca';
				}
			}
		}
		// no market, datasource is set
		// "select" this market for user so they don't get an empty page when they first arrive
		else if ($ac_id)
		{
			$this->market = $market;
			cgi::add_js_var('do_set_market', $market);
			$ml_selected = ' selected';
		}
		
		return '
			<div class="w_data_source'.$ml_selected.'" market="'.$market.'" ejo="data_source">
				<div class="header">
					<span class="text"><a href="'.cgi::href('account/product/ql/markets/?aid='.$this->account->id.'&m='.$market).'">'.$this->markets[$market].'</a></span>
					'.$ml_links.'
				</div>
				<table class="data_source_info">
					<tbody>
						'.$this->ml_ad_group_entity($market_ds->account, $ac_text, 'Account' , 'ac').'
						'.$this->ml_ad_group_entity($market_ds->campaign, $ca_text, 'Campaign', 'ca').'
						'.$this->ml_ad_group_entity($market_ds->ad_group, $ag_text, 'Ad Group', 'ag').'
						<tr class="set_data_source_submit_buttons">
							<td></td>
							<td>
								<input type="submit" name="set_data_source_submit" a0="action_set_data_source_submit" value="Submit" />
								<input type="submit" name="set_data_source_cancel" value="Cancel" />
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		';
	}
	
	private function ml_ad_group_entity($id, $text, $display, $abb)
	{
		if (empty($id)) {
			$display_text = 'No '.$display.' Set';
		}
		else {
			$display_text = $text.' ('.$id.')';
		}
		
		return '
			<tr>
				<td>'.$display.'</td>
				<td entity_type="'.$abb.'" entity_id="'.$id.'">
					<span class="display">'.$display_text.'</span>
					<span class="choose">
						<span class="_select"></span>
						<a href="" class="refresh_link">Refresh</a>
						<span class="loading"><img src="'.cgi::href('img/loading.gif').'" /></span>
					</span>
				</td>
			</tr>
		';
	}
	
	protected function ml_ads($ag_id)
	{
		$ads = db::select("
			select '{$this->market}' market, a.ad_group_id, a.id, a.text, a.desc_1, a.desc_2, a.disp_url, a.dest_url, qa.id ql_ad_id, qa.is_su
			from {$this->market}_objects.ad_{$this->object_key} a
			left outer join eppctwo.ql_ad qa on
				qa.market = '{$this->market}' &&
				a.id = qa.ad_id
			where
				a.ad_group_id = '{$ag_id}' &&
				a.status not in ('Deleted', 'Off')
		", 'ASSOC');
		
		$this->all_ads = array_values(array_merge($this->all_ads, $ads));
		
		// new ad so user can add ad
		$new_ad = array(
			'ad_group_id' => $ag_id,
			'id' => 'new'
		);
		if ($this->is_new_ad_submit && feedback::is_error())
		{
			$uid = $ag_id.'_new';
			$new_ad = array_merge($new_ad, array(
				'text' => $_POST[$uid.'_title'],
				'desc_1' => $_POST[$uid.'_desc_1'],
				'desc_2' => $_POST[$uid.'_desc_2'],
				'disp_url' => $_POST[$uid.'_disp_url'],
				'dest_url' => $_POST[$uid.'_dest_url']
			));
		}
		else
		{
			$new_ad = array_merge($new_ad, array(
				'text' => '',
				'desc_1' => '',
				'desc_2' => '',
				'disp_url' => '',
				'dest_url' => ''
			));
		}
		$ads[] = $new_ad;
		
		$links = array('Copy From');
		
		$ml = '';
		for ($i = 0, $ci = count($ads); $i < $ci; ++$i)
		{
			$ad = $ads[$i];
			
			// unique id for this ad
			$uid = $ad['ad_group_id'].'_'.$ad['id'];
			
			$ad_links = $links;
			if ($ad['id'] != 'new')
			{
				$ad_links[] = 'Multi Line Popup';
			}
			
			$ml .= '
				<div class="w_ad" id="w_ad_'.$uid.'" ag_id="'.$ag_id.'" ad_id="'.$ad['id'].'" ql_ad_id="'.$ad['ql_ad_id'].'" ejo="ad">
					<div class="header">
						<span class="text">Ad '.(($ad['id'] == 'new') ? 'New' : ($i + 1)).'</span>
						<span>
							'.$this->ml_links($ad_links).'
						</span>
					</div>
					'.$this->ml_ad($this->market, $ad, $uid).'
				</div>
				<div class="clr"></div>
			';
		}
		
		return $ml;
	}
	
	protected function ml_ad($market, $ad, $uid)
	{
		if ($market == 'g')
		{
			$ml_desc_2 = '
				<tr>
					<td>Desc 2</td>
					<td>
						<input type="text" class="desc" name="'.$uid.'_desc_2" id="'.$uid.'_desc_2" value="'.htmlentities($ad['desc_2']).'" />
						<span class="chars_left" driver="'.$uid.'_desc_2" max=35></span>
					</td>
				</tr>
			';
			$desc_1_max_length = 35;
		}
		else
		{
			$ml_desc_2 = '';
			$desc_1_max_length = 70;
		}
		
		$su_key = $uid.'_su';
		return '
			<table class="ad_table">
				<tbody>
					<tr>
						<td>Title</td>
						<td>
							<input type="text" class="title" name="'.$uid.'_title" id="'.$uid.'_title" value="'.htmlentities($ad['text']).'" length_func="e2.ad_title_length" />
							<span class="chars_left" driver="'.$uid.'_title" max=25></span>
						</td>
					</tr>
					<tr>
						<td>Desc 1</td>
						<td>
							<input type="text" class="desc" name="'.$uid.'_desc_1" id="'.$uid.'_desc_1" value="'.htmlentities($ad['desc_1']).'" />
							<span class="chars_left" driver="'.$uid.'_desc_1" max='.$desc_1_max_length.'></span>
						</td>
					</tr>
					'.$ml_desc_2.'
					<tr>
						<td>Display URL</td>
						<td>
							<input type="text" class="url disp_url" name="'.$uid.'_disp_url" id="'.$uid.'_disp_url" value="'.$ad['disp_url'].'" />
							<span class="chars_left" driver="'.$uid.'_disp_url" max=35></span>
						</td>
					</tr>
					<tr>
						<td>Dest URL</td>
						<td><input type="text" class="url dest_url" name="'.$uid.'_dest_url" value="'.$ad['dest_url'].'" /></td>
					</tr>
					<tr>
						<td></td>
						<td>
							<div>
								<input type="checkbox" name="'.$su_key.'" id="'.$su_key.'" value="1"'.(($ad['is_su']) ? ' checked' : '').' />
								<label for="'.$su_key.'">Update SU</label>
							</div>
							<input type="submit" class="ad_submit" value="Submit Changes" /></td>
					</tr>
				</tbody>
			</table>
		';
	}
	
	protected function ml_keywords($ag_id)
	{
		$keywords = db::select("
			select ad_group_id, id, text, type
			from {$this->market}_objects.keyword_{$this->object_key}
			where
				ad_group_id = :agid &&
				status = 'On'
			order by text asc, type asc
		", array(
			'agid' => $ag_id
		), 'ASSOC');
		
		$num_plan_keywords = db::select_one("
			select num_keywords
			from eppctwo.ql_plans
			where name = :plan
		", array('plan' => $this->account->plan));
		$num_keywords = count($keywords);
		$loop_end = max($num_keywords, $num_plan_keywords, $this->account->alt_num_keywords);
		$ml_keywords = '';
		
		// we only show broad match for QL!
		// keep array of text we've seen, skip if we've already seen it
		$unique_kw_text = array();
		$kw_count = 0;
		for ($i = 0; $kw_count < $loop_end; ++$i)
		{
			$kw_value = '';
			if ($i < $num_keywords)
			{
				$keyword = $keywords[$i];
				$kw_value = $keyword['text'];
				if (!array_key_exists($kw_value, $unique_kw_text))
				{
					$unique_kw_text[$kw_value] = 1;
				}
				else
				{
					continue;
				}
			}
			$kw_key = $ag_id.'_keyword_'.$kw_count;
			$ml_keywords .= '
				<tr>
					<td class="r">'.($kw_count + 1).'</td>
					<td><input type="text" class="keyword" name="'.$kw_key.'" id="'.$kw_key.'" value="'.htmlentities($kw_value).'" /></td>
				</tr>
			';
			++$kw_count;
		}
		$su_key = 'keywords_'.$ag_id.'_send_to_su';
		
		$links = array('Copy From', 'Multi Line Popup', 'Toggle Modified Broad');
		
		return '
			<div class="w_keywords" ag_id="'.$ag_id.'" ejo="keywords">
				<div class="header">
					<span class="text">Keywords</span>
					<span>
						'.$this->ml_links($links).'
					</span>
				</div>
				<table class="keyword_table">
					<tbody>
						'.$ml_keywords.'
						<tr>
							<td></td>
							<td><input type="submit" value="Preview Changes" /></td>
						</tr>
					</tbody>
				</table>
				<div class="keyword_preview_changes">
					<div class="list new">
						<h4>New</h4>
						<ul></ul>
					</div>
					<div class="list keep">
						<h4>Keep</h4>
						<ul></ul>
					</div>
					<div class="list delete">
						<h4>Delete</h4>
						<ul></ul>
					</div>
					<div class="clear"></div>
					<div class="buttons">
						<p>
							<input type="checkbox" id="'.$su_key.'" name="'.$su_key.'" value="1"'.(($this->do_update_su_by_default()) ? ' checked' : '').' />
							<label for="'.$su_key.'">Update SU</label>
						</p>
						<input type="submit" a0="action_update_keywords_submit" value="Submit" />
						<input type="submit" value="Cancel" />
					</div>
				</div>
			</div>
		';
	}
	
	protected function action_set_data_source_submit()
	{
		util::load_lib('data_cache');
		$market = $_POST['market'];
		
		// set market to ds submit market
		if (!$this->market || $this->market != $market) {
			$this->market = $market;
			cgi::add_js_var('do_set_market', $market);
		}
		
		list($ac_id, $ca_id, $ag_id) = util::list_assoc($_POST, $market.'_new_account', $market.'_new_campaign', $market.'_new_ad_group');
		
		// delete old data source, add new one
		db::delete("eppctwo.ql_data_source", "account_id = '{$this->account->id}' && market = '{$market}'");
		$this->ds = new ql_data_source(array(
			'account_id' => $this->account->id,
			'market' => $market,
			'account' => $ac_id,
			'campaign' => $ca_id,
			'ad_group' => $ag_id
		));
		// add new ac/ca/ag to data sources
		$this->ds->put();
		$this->data_sources->set($this->market, $this->ds);
		$this->object_key = "Q{$ac_id}";

		// make sure we have object tables for this account
		ppc_lib::create_market_object_tables($market, $this->object_key);
		
		// we have an ad group id, use for ags array
		if ($ag_id) {
			$ags = array($ag_id);
		}
		else {
			data_cache::update_ad_groups($this->object_key, $market, $ca_id, array('force_update' => true));
			$ags = db::select("
				select id
				from {$market}_objects.ad_group_{$this->object_key}
				where campaign_id = '$ca_id'
			");
		}
		
		$this->do_refresh_ad_groups($this->ds, $ags, $this->object_key);
		feedback::add_success_msg('Data Source Set');
	}
	
	protected function action_pull_new_ags()
	{
		util::load_lib('data_cache');
		
		$ca_id = db::select_one("
			select distinct campaign_id
			from {$market}_objects.ad_group_{$this->ds->account}
			where ".$this->ds->get_ad_group_query()."
		");
		if (!$ca_id) {
			return feedback::add_error_msg('Could not get campaign for '.$this->markets[$this->market]);
		}
		
		$before_ags = db::select("
			select id
			from {$market}_objects.ad_group_{$this->ds->account}
			where ".$this->ds->get_ad_group_query()."
		");
		
		data_cache::update_ad_groups("{$this->object_key}", $this->market, $ca_id, array('force_update' => true));
		
		$after_ags = db::select("
			select id
			from {$market}_objects.ad_group_{$this->ds->account}
			where ".$this->ds->get_ad_group_query()."
		");
		
		$new_ags = array_diff($after_ags, $before_ags);
		if ($new_ags) {
			$this->do_refresh_ad_groups($this->ds, $new_ags);
			feedback::add_success_msg(count($new_ags).' new ad groups pulled');
		}
		else {
			feedback::add_error_msg('No new ad groups');
		}
	}

	// is google or doesn't have google
	private function do_update_su_by_default()
	{
		return ($this->market == 'g' || !db::select_one("select count(*) from eppctwo.ql_data_source where account_id = '{$this->account->id}' && market = 'g'"));
	}
	
	private function do_refresh_ad_groups($ds, $ags, $object_key = false)
	{
		if (!$object_key) {
			$object_key = $this->object_key;
		}
		foreach ($ags as $ag_id) {
			// pull ads and keywords from market and set client
			data_cache::update_ads($object_key, $this->market, $ag_id, array('force_update' => true));
			data_cache::update_keywords($object_key, $this->market, $ag_id, array('force_update' => true));
		}

		if ($this->do_update_su_by_default()) {
			// add to ql_ad table, default to is_su on
			$ads = db::select("
				select ad_group_id, id
				from {$this->market}_objects.ad_{$object_key}
				where
					ad_group_id in ('".implode("','", $ags)."') &&
					status = 'On'
			");
			
			for ($i = 0; list($ag_id, $ad_id) = $ads[$i]; ++$i) {
				$ql_ad_id = db::insert("eppctwo.ql_ad", array(
					'market' => $this->market,
					'ad_group_id' => $ag_id,
					'ad_id' => $ad_id,
					'account_id' => $this->account->id,
					'is_su' => 1
				));
			}
			ql_lib::update_client_ads($this->account, $this->market, $ds);
			ql_lib::update_client_keywords($this->account, $this->data_sources);
		}
	}
	
	protected function user_updates_mark_processed_submit()
	{
		$update_id = $_POST['user_updates'];
		db::update("eppctwo.ql_client_update_engine", array(
			'processed_dt' => date(util::DATE_TIME)
		), "id = '".$update_id."'");
		feedback::add_success_msg('Update Marked As Processed');
	}
	
	protected function action_refresh_keywords()
	{
		util::load_lib('data_cache');

		$ag_id = $_POST['ag_id'];
		if (!$this->do_refresh_ad_group($ag_id)) {
			return false;
		}
		if (!data_cache::update_keywords("{$this->object_key}", $this->market, $ag_id, array('force_update' => true))) {
			feedback::add_error_msg('Error refreshing keywords: '.data_cache::get_error());
			return false;
		}
		if ($this->do_update_su_by_default()) {
			ql_lib::update_client_keywords($this->account, $this->data_sources);
		}
		feedback::add_success_msg('Keywords Refreshed');
	}
	
	protected function action_refresh_ads()
	{
		util::load_lib('data_cache');
		
		$ag_id = $_POST['ag_id'];
		if (!$this->do_refresh_ad_group($ag_id)) {
			return false;
		}
		if (!data_cache::update_ads("{$this->object_key}", $this->market, $ag_id, array('force_update' => true))) {
			feedback::add_error_msg('Error refreshing ads: '.data_cache::get_error());
			return false;
		}
		if ($this->do_update_su_by_default()) {
			ql_lib::update_client_ads($this->account, $this->market, $this->ds);
		}
		feedback::add_success_msg('Ad Refreshed');
	}
	
	protected function do_refresh_ad_group($ag_id)
	{
		list($ac_id, $ca_id) = db::select_row("
			select account, campaign
			from eppctwo.ql_data_source
			where
				account_id = '{$this->account->id}' &&
				market = '{$this->market}'
		");
		if (!data_cache::update_ad_groups("Q{$ac_id}", $this->market, $ca_id, array('force_update' => true))) {
			feedback::add_error_msg('Error refreshing ad groups: '.data_cache::get_error());
			return false;
		}
		else {
			return true;
		}
	}

	protected function action_update_ad_submit()
	{
		list($ql_ad_id, $ag_id, $ad_id) = util::list_assoc($_POST, 'ql_ad_id', 'ag_id', 'ad_id');
		$this->is_new_ad_submit = ($ad_id == 'new');
		$uid = $ag_id.'_'.$ad_id;
		list($title, $desc_1, $desc_2, $disp_url, $dest_url, $is_su) = util::list_assoc($_POST, $uid.'_title', $uid.'_desc_1', $uid.'_desc_2', $uid.'_disp_url', $uid.'_dest_url', $uid.'_su');
		
		$ad = api_factory::new_ad($ag_id, $ad_id, $title, $desc_1, $desc_2, $disp_url, $dest_url);
		
		$api = base_market::get_api($this->market);
		$api->set_account($this->ds->account);
		
		// deal with the markets
		// e2 db handled below
		if ($this->market == 'g')
		{
			// set the current ad's id
			$cur_ad_id = $ad->id;
			
			// there is no "updating" ads in google
			// so we always try to add the current ad whether this is an update or new
			if (!$api->add_ad($ad, $ag_id))
			{
				feedback::add_error_msg('Error creating new Ad: '.$api->get_error());
				return;
			}
			
			// if it's an update, delete the old ad
			if (!$this->is_new_ad_submit)
			{
				// swap id and status
				$new_ad_id = $ad->id;
				$new_ad_status = $ad->status;
				
				$ad->id = $cur_ad_id;
				$ad->status = 'Deleted';
				if (!$api->update_ad($ad, $ag_id))
				{
					feedback::add_error_msg('Error deleting old Ad: '.$api->get_error());
				}
				else
				{
					// if we actually updated things and a new ad was created
					if ($new_ad_id != $cur_ad_id)
					{
						// update our ql copy of the ad
						db::update("eppctwo.ql_ad", array('ad_id' => $new_ad_id), "id = '{$ql_ad_id}'");
						
						// set the old ad to deleted
						db::update("g_objects.ad_{$this->object_key}", array('status' => 'Deleted'), "ad_group_id = '$ag_id' && id = '{$cur_ad_id}'");
					}
				}
				
				// switch id and status back
				$ad->id = $new_ad_id;
				$ad->status = $new_ad_status;
			}
		}
		else if ($this->market == 'm')
		{
			// update ad
			if (!$this->is_new_ad_submit)
			{
				if ($api->update_ad($ad, $ag_id) === false)
				{
					feedback::add_error_msg('Error Updating Ad: '.$api->get_error());
					return;
				}
			}
			// create new ad
			else
			{
				if ($api->add_ad($ad, $ag_id) === false)
				{
					feedback::add_error_msg('Error Creating New Ad: '.$api->get_error());
					return;
				}
			}
		}
		// update local cache
		$ad->db_set($this->market, $this->object_key, $this->ds->account, $this->ds->campaign, $ag_id);
		
		// su stuff
		$was_su = db::select_one("
			select is_su
			from eppctwo.ql_ad
			where id = '$ql_ad_id'
		");
		if ($was_su || $is_su) {
			if (!$this->is_new_ad_submit && $ql_ad_id) {
				$su_changed = db::update("eppctwo.ql_ad", array('is_su' => $is_su), "id = {$ql_ad_id}");
			}
			else {
				$ql_ad_id = db::insert("eppctwo.ql_ad", array(
					'market' => $this->market,
					'ad_group_id' => $ag_id,
					'ad_id' => $ad->id,
					'account_id' => $this->account->id,
					'is_su' => $is_su
				));
			}
			// update if su or su changed
			if ($is_su || $su_changed) {
				ql_lib::update_client_ads($this->account, $this->market, $this->ds);
			}
		}
		feedback::add_success_msg('Ad Updated');
	}
	
	protected function action_update_keywords_submit()
	{
		$ag_id = $_POST['ag_id'];
		
		$api = base_market::get_api($this->market);
		$api->set_account($this->ds->account);
		// e($_POST); $api->debug(); db::dbg();

		// send delete command to market
		$kw_delete = array_map('trim', explode("\t", $_POST['kw_delete']));
		if ($_POST['kw_delete'] && $kw_delete) {
			// get current keywords
			$tmp_keywords = db::select("
				select id, text, type
				from {$this->market}_objects.keyword_{$this->object_key}
				where
					".$this->ds->get_entity_query()." &&
					status <> 'Deleted'
				order by text asc, type asc
			");
			
			$kw_delete = array_flip($kw_delete);
			$kws_to_delete = array();
			for ($i = 0; list($kw_id, $kw_text, $kw_type) = $tmp_keywords[$i]; ++$i) {
				if (array_key_exists($kw_text, $kw_delete)) {
					$kws_to_delete[] = ($this->market == 'm') ? $kw_id : api_factory::new_keyword($ag_id, $kw_id);
				}
			}
			$r = $api->delete_keywords($kws_to_delete, $ag_id);
			if (!$r) {
				feedback::add_error_msg('Error Deleting Keywords: '.$api->get_error());
			}
			// if success, update local database
			else {
				feedback::add_success_msg('Keywords Deleted: '.implode(', ', array_keys($kw_delete)));
				foreach ($kws_to_delete as &$kw) {
					$kw_id = ($this->market == 'm') ? $kw : $kw->id;
					db::update(
						"{$this->market}_objects.keyword_{$this->object_key}",
						array("status" => 'Deleted'),
						"ad_group_id = '$ag_id' && id = '{$kw_id}'"
					);
				}
			}
		}
		
		// add new keywords
		$kw_new = array_map('trim', explode("\t", $_POST['kw_new']));
		if ($_POST['kw_new'] && $kw_new) {
			$kws_to_add = array();
			foreach ($kw_new as $kw_text) {
				$kw_text = trim($kw_text);
				if ($kw_text) {
					list($text, $type) = util::get_keyword_text_and_match_type($kw_text);
					$kws_to_add[] = api_factory::new_keyword($ag_id, null, $text, $type, null);
				}
			}
			if (!$api->add_keywords($kws_to_add, $ag_id)) {
				feedback::add_error_msg('Error Adding Keywords: '.$api->get_error());
			}
			// if success, update local database
			else {
				foreach ($kws_to_add as &$kw) {
					$kw->status = 'On';
					$kw->db_set($this->market, $this->object_key, $this->ds->account, $this->ds->campaign, $ag_id);
				}
				feedback::add_success_msg('Keywords Added: '.implode(', ', $kw_new));
			}
		}
		
		// conditionally update su
		if ($_POST['keywords_'.$ag_id.'_send_to_su']) {
			ql_lib::update_client_keywords($this->account);
		}
	}
	
	private function get_ag_bid_for_kw_add($ag_id, $ca_id)
	{
		$ag_bid = db::select_one("
			select max_cpc
			from {$this->market}_objects.ad_group_{$this->object_key}
			where ad_group = '{$ag_id}'
		");
		if (!$ag_bid)
		{
			// refresh ag cache and try again
			util::load_lib('data_cache');
			data_cache::update_ad_groups($this->account->id, $this->market, $ca_id, array('data_id' => 'ql', 'force_update' => true, 'do_set_client' => false));
			
			$ag_bid = db::select_one("
				select max_cpc
				from {$this->market}_objects.ad_group_{$this->object_key}
				where ad_group = '{$ag_id}'
			");
			
			if (!$ag_bid)
			{
				// guess ag bid based on most common keyword bid
				list($bid_count, $ag_bid) = db::select_row("
					select count(*) c, max_cpc
					from {$this->market}_objects.keyword_{$this->object_key}
					where ad_group = '{$ag_id}'
					group by max_cpc
					order by c desc
					limit 1
				");
				
				// alright 50 cents it is!
				if (!$ag_bid)
				{
					$ag_bid = .50;
				}
			}
		}
		return $ag_bid;
	}
	
	public function action_refresh_accounts()
	{
		$market = $_POST['market'];
		ql_lib::refresh_accounts($market);
		cgi::add_js_var('account_refresh_market', $market);
	}
	
	private function print_client_updates()
	{
		// get updates
		$updates = db::select("
			select id, dt, processed_dt, users_id, type, data
			from eppctwo.sbs_client_update
			where
				department = 'ql' &&
				account_id = '{$this->account->id}'
			order by dt desc
		", 'ASSOC');
		
		cgi::add_js_var('updates', $updates);
	}
	
	public function ajax_update_done()
	{
		sbs_lib::client_update_done($_POST['update_id']);
		echo "Update Processed";
	}
	
	public function action_un_tie_market()
	{
		$market = $_POST['un_tie_market'];
		$do_untie_su = $_POST['untie_clear_su_'.$market];
		
		db::exec("delete from eppctwo.ql_data_source where account_id = '{$this->account->id}' && market = '{$market}'");
		db::exec("delete from eppctwo.ql_ad where account_id = '{$this->account->id}' && market = '{$market}'");

		$this->data_sources->remove($market);
		$this->ds = false;
		$this->object_key = "";
		
		// untie from wpro
		feedback::add_success_msg('Market un-tied');
		if ($do_untie_su) {
			util::wpro_post('account', 'ql_clear_account_info', array(
				'aid' => $this->account->id
			));
			feedback::add_success_msg('SU Ads and Keywords Cleared');
		}
	}
	
	public function action_re_tie_ads()
	{
		// untie ads
		util::wpro_post('account', 'ql_clear_ads', array(
			'aid' => $this->account->id
		));
		
		// add to ql_ad table, default to is_su on
		$ads = db::select("
			select m.ad_group_id, m.id m_ad_id, q.id ql_ad_id
			from {$this->market}_objects.ad_{$this->object_key} m
			left outer join eppctwo.ql_ad q on
				q.market = '{$this->market}' &&
				q.ad_group_id = m.ad_group_id &&
				q.ad_id = m.id
			where
				".$this->ds->get_entity_query("m.")." &&
				status = 'On'
		");
		
		for ($i = 0; list($ag_id, $ad_id, $ql_ad_id) = $ads[$i]; ++$i) {
			// this ad isn't a ql ad yet
			if (!$ql_ad_id) {
				$ql_ad_id = db::insert("eppctwo.ql_ad", array(
					'market' => $this->market,
					'ad_group_id' => $ag_id,
					'ad_id' => $ad_id,
					'account_id' => $this->account->id,
					'is_su' => 1
				));
			}
			ql_lib::update_client_ads($this->account, $this->market, $this->ds);
		}
		
		feedback::add_success_msg('Ads Re-Tied');
	}
	
	public function ajax_copy_from_get_kws()
	{
		list($market, $ag_id) = util::list_assoc($_POST, 'market', 'ag_id');
		
		$ds = $this->data_sources->a[$market];
		$kws = db::select("
			select distinct text
			from {$market}_objects.keyword_Q{$ds->account}
			where
				ad_group_id = :agid &&
				status = 'On'
		", array('agid' => $ag_id));
		echo json_encode(ql_lib::prepare_keywords($kws));
	}
	
	public function ajax_copy_from_get_ad()
	{
		list($market, $ag_id, $ad_id) = util::list_assoc($_POST, 'market', 'ag_id', 'ad_id');
		
		$ds = $this->data_sources->a[$market];
		$ad = db::select_row("
			select text, desc_1, desc_2, disp_url, dest_url
			from {$market}_objects.ad_Q{$ds->account}
			where
				ad_group_id = :agid &&
				id = :adid
		", array(
			'agid' => $ag_id,
			'adid' => $ad_id
		), 'ASSOC');
		echo json_encode($ad);
	}
	
	public function ajax_get_accounts()
	{
		$tmp = db::select("
			select id, text
			from eppctwo.".$_POST['market']."_accounts
			order by text asc
		");
		
		$accounts = array();
		for ($i = 0; list($id, $text) = $tmp[$i]; ++$i)
		{
			if (ql_lib::is_ql_account($text)) {
				$accounts[] = array($id, $text);
			}
		}
		echo json_encode($accounts);
	}
	
	public function ajax_get_campaigns()
	{
		list($market, $ac_id) = util::list_assoc($_POST, 'market', 'ac_id');
		
		$campaigns = db::select("
			select id, text
			from {$market}_objects.campaign_Q{$ac_id}
			where account_id = '$ac_id'
			order by text asc
		");
		echo json_encode($campaigns);
	}
	
	public function ajax_get_ad_groups()
	{
		list($market, $ac_id, $ca_id) = util::list_assoc($_POST, 'market', 'ac_id', 'ca_id');
		
		$ad_groups = db::select("
			select id, text
			from {$market}_objects.ad_group_Q{$ac_id}
			where campaign_id = '$ca_id'
			order by text asc
		");
		echo json_encode($ad_groups);
	}

	public function ajax_refresh_entity()
	{
		list($market, $type, $ac_id, $parent_id) = util::list_assoc($_POST, 'market', 'type', 'ac_id', 'parent_id');

		if ($type == 'ac') {
			ql_lib::refresh_accounts($market);
			$this->ajax_get_accounts();
		}
		else {
			util::load_lib('data_cache');
			if ($type == 'ca') {
				// so ajax_get_campaigns can find account id
				$_POST['ac_id'] = $parent_id;
				data_cache::update_campaigns("Q{$ac_id}", $market, $parent_id, array('force_update' => true));
				$this->ajax_get_campaigns();
			}
			else if ($type == 'ag') {
				// so ajax_get_ad_groups can find campaign id
				$_POST['ca_id'] = $parent_id;
				data_cache::update_ad_groups("Q{$ac_id}", $market, $parent_id, array('force_update' => true));
				$this->ajax_get_ad_groups();
			}
		}
	}
}

?>