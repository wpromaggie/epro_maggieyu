<?php
util::load_lib('ppc');

class worker_ppc_data_source_refresh extends worker
{
	public function run()
	{
		$opts = new ppc_data_source_refresh(array('id' => $this->job->fid));
		if ($opts->start_date > $opts->end_date) {
			$opts->start_date = $opts->end_date;
		}

		// this worker is used to refresh both ppc and ql accounts
		// ql accounts will have a client id prefix of "Q"
		if ($opts->account_id[0] == 'Q') {
			$ac_ids = array(substr($opts->account_id, 1));
		}
		// ppc
		else {
			// run account reports (no campaign)
			$ac_ids = db::select("
				select distinct account
				from eppctwo.data_sources
				where
					market = :market && 
					account_id = :aid
			", array(
				"market" => $opts->market,
				"aid" => $opts->account_id
			));
		}
		foreach ($ac_ids as $ac_id) {
			$api = base_market::get_api($opts->market, $ac_id);
			$api->job = $this->job;
			$api->do_force_refresh = $opts->do_force;
			$api->debug();
			
			// pass in report [f]ilename from command line
			if (array_key_exists('f', cli::$args)) {
				$report_path = cli::$args['f'];
			}
			else {
				$report_path = $api->run_account_report($opts->account_id, $opts->start_date, $opts->end_date, REPORT_DELETE_ZIP | (($this->dbg) ? REPORT_SHOW_STATUS : 0));
			}
			if ($report_path === false) {
				return $this->job->error('Error running report: '.$api->get_error());
			}
			// todo: move to m api wrapper?
			if ($opts->market == 'm') {
				$m_error_where = "
					success = 0 &&
					account_id = '$ac_id' &&
					d between '{$opts->start_date}' and '{$opts->end_date}'
				";
				$is_error = db::select_one("
					select count(*)
					from eppctwo.m_data_error_log
					where {$m_error_where}
				");
				if ($is_error) {
					db::update("eppctwo.m_data_error_log", array('success' => 1), $m_error_where);
				}
			}
		}
		ppc_lib::calculate_cdl_vals($opts->account_id);
	}
}

?>