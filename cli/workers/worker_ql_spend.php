<?php
util::load_lib('ql', 'sbs');

class worker_ql_spend extends worker
{
	private $plan_budgets, $data_sources;
	
	public function run()
	{
		// get accounts
		if (array_key_exists('a', cli::$args)) {
			$account_id = cli::$args['a'];
			$accounts = ap_ql::get_all(array(
				'where' => "status in ('Active', 'NonRenewing') && id = '{$account_id}'"
			));
			db::delete(
				"eppctwo.ql_spend",
				"account_id = :aid",
				array('aid' => $account_id)
			);
		}
		else {
			$accounts = ap_ql::get_all(array(
				'where' => "status in ('Active', 'NonRenewing')"
			));
			db::exec("truncate table eppctwo.ql_spend");
		}
		// get data sources
		$this->eac_to_mac = array();
		$this->data_sources = array();
		foreach (ql_lib::$markets as $market) {
			$this->set_active_ql_data_sources($market);
		}
		// update each account
		foreach ($accounts as $account) {
			$account->update_data($this->data_sources, $this->eac_to_mac);
		}
	}

	private function set_active_ql_data_sources($market)
	{
		$tmp = db::select("
			select ds.account_id, ds.account, ds.campaign, ds.ad_group
			from eac.ap_ql q, eac.account a, eppctwo.ql_data_source ds
			where
				a.status in ('Active', 'NonRenewing') &&
				ds.market = '$market' &&
				q.id = a.id &&
				q.id = ds.account_id
		");
		$this->eac_to_mac[$market] = array();
		$this->data_sources[$market] = array();
		for ($i = 0; list($cl_id, $ac_id, $ca_id, $ag_id) = $tmp[$i]; ++$i) {
			if (!array_key_exists($cl_id, $this->eac_to_mac[$market])) $this->eac_to_mac[$market][$cl_id] = $ac_id;

			if (!empty($ag_id)) $this->data_sources[$market][$cl_id]['ad_group_id'][] = $ag_id;
			else if (!empty($ca_id)) $this->data_sources[$market][$cl_id]['campaign_id'][] = $ca_id;
			else $this->data_sources[$market][$cl_id]['account_id'][] = $ac_id;
		}
	}
}

?>