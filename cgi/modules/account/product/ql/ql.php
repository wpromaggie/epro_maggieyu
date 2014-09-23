<?php

class mod_account_product_ql extends mod_account_product
{
	protected $markets = array(
		'g' => 'Google',
		'm' => 'MSN'
	);
	
	public function pre_output()
	{
		parent::pre_output();
	}
	
	protected function get_page_menu()
	{
		$menu = parent::get_page_menu();
		return $this->page_menu_insert_before($menu, 'su', array(
			array('markets', 'Markets'),
			array('spend', '$pend')
		));
	}
	
	public function sts_client_update_ad()
	{
		sbs_lib::client_update('ql', $this->account->id, 'ql-ad', array(
			'ql_ad_id' => $_POST['id'],
			'text' => $_POST['text'],
			'desc_1' => $_POST['desc_1'],
			'desc_2' => $_POST['desc_2']
		));
	}
	
	public function sts_client_update_keywords()
	{
		sbs_lib::client_update('ql', $this->account->id, 'ql-keywords', array(
			'keywords' => $_POST['keywords']
		));
	}
	
	public function hook_cancel()
	{
		$this->do_pause_ad_groups();
	}
	
	public function hook_print_su_account_info()
	{
		$data_sources = $this->account->get_data_sources();
		$info = array();
		foreach (ql_lib::$markets as $market) {
			if (!$data_sources->key_exists($market)) {
				continue;
			}
			$ds = $data_sources->a[$market];
			$info[$market]['ads'] = ql_lib::get_ads($this->account, $market, $ds);
			$info[$market]['keywords'] = ql_lib::get_keywords($this->account, $market, $ds);
			$info[$market]['keywords'] = ql_lib::prepare_keywords($info[$market]['keywords']);
		}
		$info['su'] = util::wpro_post('account', 'ql_get_info', array('aid' => $this->account->id));

		$ml_ads = $ml_keywords = '';
		foreach ($info as $src => $src_info) {
			// ads
			if ($src_info['ads']) {
				$ml_src_ads = '';
				foreach ($src_info['ads'] as $i => $ad) {
					$ml_src_ads .= '
						<div class="ad">
							<h3>Ad '.($i + 1).'</h3>
							<p>'.$ad['text'].'</p>
							<p>'.$ad['desc_1'].'</p>
							<p>'.$ad['desc_2'].'</p>
							<p>'.$ad['disp_url'].'</p>
						</div>
					';
				}
			}
			else {
				$ml_src_ads = 'No Ads';
			}

			// keywords
			if ($src_info['keywords']) {
				$ml_src_keywords = '';
				foreach ($src_info['keywords'] as $i => $kw) {
					$ml_src_keywords .= '
						<tr>
							<td>KW '.($i + 1).'</td>
							<td>'.$kw.'</td>
						</tr>
					';
				}
				$ml_src_keywords = '
					<table>
						<tbody>
							'.$ml_src_keywords.'
						</tbody>
					</table>
				';
			}
			else {
				$ml_src_keywords = 'No Keywords';
			}

			// ml for row
			$ml_ads .= '
				<td>
					<h2 class="header">'.(array_key_exists($src, $this->markets) ? $this->markets[$src] : strtoupper($src)).' Ads</h2>
					'.$ml_src_ads.'
				</td>
			';
			$ml_keywords .= '
				<td>
					<h2 class="header">'.(array_key_exists($src, $this->markets) ? $this->markets[$src] : strtoupper($src)).' Keywords</h2>
					'.$ml_src_keywords.'
				</td>
			';
		}
		?>
		<table>
			<tbody>
				<tr>
					<?= $ml_ads ?>
				</tr>
				<tr>
					<?= $ml_keywords ?>
				</tr>
			</tbody>
		</table>
		<?php
	}

	private function do_pause_ad_groups()
	{
		require_once(\epro\WPROPHP_PATH.'apis/apis.php');
		
		$data_sources = $this->account->get_data_sources();
		foreach ($this->markets as $market => $market_text) {
			if (!$data_sources->key_exists($market)) {
				continue;
			}
			$ds = $data_sources->a[$market];

			$ags = db::select("
				select account_id, campaign_id, id
				from {$market}_objects.ad_group_Q{$ds->account}
				where ".$ds->get_ad_group_query()."
				order by text asc
			");
			
			for ($i = 0; list($ac_id, $ca_id, $ag_id) = $ags[$i]; ++$i) {
				// doesn't look like they were running ads in this market
				if (empty($ac_id) || empty($ca_id) || empty($ag_id)) {
					feedback::add_error_msg('Could not find '.$market_text.' ad group');
					continue;
				}
				
				$ag = api_factory::new_ad_group(null, $ag_id);
				$ag->status = 'Off';
				
				$api = base_market::get_api($market, $ac_id);
				
				if ($api->pause_ad_group($ag, $ca_id) === false) {
					feedback::add_error_msg('Error pausing '.$market_text.' ad group '.$ag_id.': '.$api->get_error());
				}
				else {
					$ag->db_set($market, 'ql');
					feedback::add_success_msg($market_text.' ad group '.$ag_id.' paused');
				}
			}
		}
	}
}

?>