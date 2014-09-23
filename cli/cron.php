<?php
require_once('cli.php');
util::load_lib('delly');

cli::run();

class cron
{
	public function go()
	{
		if (!empty(cli::$args['g'])) {
			db::dbg();
		}
		$now = time();
		$seconds = $now % 60;
		// get closest minute
		$time = $now + (($seconds < 30) ? -$seconds : 60 - $seconds);
		$time_str = date('Y-m-d H:i:s', $time);

		$create_data = array(
			'baton' => "cron runner {$time_str}",
			'dt' => $time_str,
			'hostname' => \epro\HOSTNAME
		);

		// t for test
		// 1. pass in -t y to test for any matching jobs
		// this is useful when testing so that user does not have to wait for
		// minute intervals but can run as many tests as they want
		// 2. pass in cron job id to test a specific cron job
		$test_cjid = (array_key_exists('t', cli::$args)) ? cli::$args['t'] : false;

		// every machine in cluster tries to become cron runner each minute
		// whichever succeeds is responsible for scheduling cron jobs
		try {
			db::start_transaction();
			$r_create = no_race::create($create_data);
			$r_commit = db::commit();
			$r = ($r_create && $r_commit);
		}
		catch (Exception $e) {
			// not a test and transaction failed, someone else is cron runner
			if (!$test_cjid) {
				return false;
			}
		}

		// if r collided, just make a fake r
		// if r did not collide, we're good to go for our testing
		if (!$r && $test_cjid) {
			$r = new no_race($create_data, array('do_get' => false));
		}
		if ($r) {
			$time_fields = cron_job::get_time_fields();
			// get cron jobs
			$cron_jobs = cron_job::get_all(array(
				'where' => "status = 'Active'"
			));
			// loop over all cron jobs, see if it should be run at this time slot
			foreach ($cron_jobs as $cron_job) {
				// check for test cron job id
				if ($test_cjid && $test_cjid != 'y') {
					// set time match to true if this is the cj we're looking for
					$does_time_match = ($test_cjid == $cron_job->id);
				}
				// normal mode, see if cj matches current time
				else {
					$does_time_match = true;
					foreach ($time_fields as $time_field) {
						if (!$this->is_time_match($time, $time_field, $cron_job)) {
							$does_time_match = false;
							break;
						}
					}
				}
				// schedule worker
				if ($does_time_match) {
					job::queue(array(
						'type' => $cron_job->worker,
						'fid' => $cron_job->id,
						'user_id' => 'cron',
					));
				}
			}
		}
	}

	private function get_time_val($time, $time_field)
	{
		switch ($time_field) {
			case ('minute'):       return date('i', $time);
			case ('hour'):         return date('H', $time);
			case ('day_of_month'): return date('j', $time);
			case ('month'):        return date('n', $time);
			case ('day_of_week'):  return date('w', $time);
		}
	}

	private function is_time_match($time, $time_field, $cron_job) {
		$cron_field_val = $cron_job->$time_field;
		if ($cron_field_val == '*') {
			return true;
		}
		else {
			$time_parts = cron_job::get_time_parts($time_field, $cron_field_val);
			$field_actual_val = $this->get_time_val($time, $time_field);
			return in_array($field_actual_val, $time_parts);
		}
	}
}

?>