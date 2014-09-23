<?php
util::load_lib('delly', 'ppc');

class worker_company_market_data_pull extends worker
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
		// get all active accounts for market
		$active_clients = db::select("
			select a.id
			from eac.account a
			join eppctwo.data_sources ds on
				ds.market = :market &&
				a.id = ds.account_id
			where
				a.dept = 'ppc' &&
				a.status = 'Active'
		", array('market' => $this->market));
		sort($active_clients);

		// schedule children
		for ($i = 0, $ci = count($active_clients); $i < $ci; ++$i) {
			$aid = $active_clients[$i];

			$job_info = ppc_data_source_refresh::create(array(
				'account_id' => $aid,
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
				'account_id' => $aid
			));
		}
	}

	private function post_finished_work()
	{
		// if all markets are done, run post company data pull stuff
		$company_data_pull_cron_jobs = ppc_lib::get_company_data_pull_jobs();
		$num_done = 0;
		foreach ($company_data_pull_cron_jobs as $cj) {
			if (isset($cj->job->jid) && $cj->job->is_done()) {
				$num_done++;
			}
		}

		if ($num_done == $company_data_pull_cron_jobs->count()) {
			// make sure we're the only ones doing this
			$r = no_race::create(array(
				'baton' => "post company data pull ".\epro\TODAY,
				'dt' => date(util::DATE_TIME),
				'hostname' => \epro\HOSTNAME
			));
			if ($r) {
				// todo: schedule work that is dependent on all market data

			}
		}
	}
}

?>