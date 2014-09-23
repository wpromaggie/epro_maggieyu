<?php
// loaded by util::load_worker($job), which also loads the derived worker

abstract class worker
{
	protected $job, $dbg, $is_finished, $cron;
	
	public function __construct($job)
	{
		$this->is_finished = false;
		$this->job = $job;
		// not all workers need associated jobs
		if (is_object($this->job)) {
			$this->job->begin_processing();

			// cron queues a job with fid of cron job and user of cron
			// see if this is a worker for a cron job and load info
			if ($this->job->user_id == 'cron' && $this->job->fid) {
				$this->cron = new cron_job(array('id' => $this->job->fid));
				if ($this->cron->args) {
					$cron_args = cli::parse_args($this->cron->args);
					cli::$args = array_merge(cli::$args, $cron_args);
				}
			}
		}
		$this->dbg = (class_exists('cli') && !empty(cli::$args['g']));
	}
	
	abstract public function run();

	// finish is called automatically by delly
	// however a job may want to call finish itself
	// 1. if there is an error
	// 2. etc
	// use flag to only run once
	public function finish($status = 'Completed')
	{
		if (!$this->is_finished) {
			$this->is_finished = true;
			if (is_object($this->job)) {
				$this->job->end_processing($status);
			}
		}
	}
	
	public function debug()
	{
		$this->dbg = true;
	}
}

?>