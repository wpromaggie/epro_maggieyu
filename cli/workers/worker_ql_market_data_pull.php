<?php
util::load_lib('delly', 'ppc', 'ql');

class worker_ql_market_data_pull extends worker
{
	private $market, $start_date, $end_date;

	public function run()
	{
		$this->init();

		$this->schedule_children();

		$this->job->wait_for_children();

		$this->finish();

		$this->post_finished_work();
	}

	private function init()
	{
		list($this->market) = util::list_assoc(cli::$args, 'm');
		if (!$this->market) {
			return $this->job->error('No market');
		}
		
		// check for date
		if (array_key_exists('s', cli::$args) && array_key_exists('e', cli::$args)) {
			list($this->start_date, $this->end_date) = util::list_assoc(cli::$args, 's', 'e');
		}
		// check command line for a single date. default is yesterday
		else {
			$tmp = (array_key_exists('d', cli::$args)) ? cli::$args['d'] : date(util::DATE, \epro\NOW - 86400);
			$this->start_date = $this->end_date = $tmp;
		}
	}

	private function schedule_children()
	{
		$active_market_accounts = db::select("
			select distinct ds.account
			from eac.ap_ql q, eac.account a, eppctwo.ql_data_source ds
			where
				a.status in ('Active', 'NonRenewing') &&
				ds.market = :market &&
				q.id = a.id &&
				q.id = ds.account_id
		", array(
			'market' => $this->market
		));

		for ($i = 0, $ci = count($active_market_accounts); $i < $ci; ++$i) {
			$ac_id = $active_market_accounts[$i];

			$ql_ac_id = "Q{$ac_id}";
			$job_info = ppc_data_source_refresh::create(array(
				'account_id' => $ql_ac_id,
				'refresh_type' => 'remote',
				'market' => $this->market,
				'start_date' => $this->start_date,
				'end_date' => $this->end_date
			));
			job::queue(array(
				'type' => 'PPC DATA SOURCE REFRESH',
				'parent_id' => $this->job->id,
				'user_id' => $this->job->user_id,
				'fid' => $job_info->id,
				'account_id' => $ql_ac_id
			));
		}
	}

	private function post_finished_work()
	{
		// todo: generalize, move to somewhere in delly.rs.php
		// if all markets are done, run post company data pull stuff
		$company_data_pull_cron_jobs = cron_job::get_all(array(
			'select' => array(
				"cron_job" => array("id as cid"),
				"job" => array("id as jid", "status")
			),
			'left_join' => array(
				"job" => "
					job.fid = cron_job.id &&
					job.started between :today_start and :today_end
				"
			),
			'where' => "
				cron_job.worker = 'QL MARKET DATA PULL' &&
				cron_job.status = 'Active'
			",
			'data' => array(
				"today_start" => \epro\TODAY." 00:00:00",
				"today_end" => \epro\TODAY." 23:59:59"
			)
		));
		$num_done = 0;
		foreach ($company_data_pull_cron_jobs as $cj) {
			if (isset($cj->job->jid) && $cj->job->is_done()) {
				$num_done++;
			}
		}

		if ($num_done == $company_data_pull_cron_jobs->count()) {
			// make sure we're the only ones doing this
			$r = no_race::create(array(
				'baton' => "post ql data pull ".\epro\TODAY,
				'dt' => date(util::DATE_TIME),
				'hostname' => \epro\HOSTNAME
			));
			if ($r) {
				// ql spend
				job::queue(array(
					'type' => 'QL SPEND',
					'parent_id' => $this->job->id,
					'user_id' => $this->job->user_id
				));
			}
		}
	}
}

?>