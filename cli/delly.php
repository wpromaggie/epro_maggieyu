<?php
require_once('cli.php');
util::load_lib('delly');

// todo: for windows devs, http://stackoverflow.com/questions/5367261/php-exec-as-background-process-windows-wampserver-environment

define('DELLY_ADMIN', 'chimdi@wpromote.com');
define('DAEMON_DISPATCHER_CMD', 'delly.php dispatcher_loop');
define('DAEMON_MONITOR_CMD', 'delly.php monitor_loop');
define('DEFAULT_MONITOR_SLEEPTIME', 1);

cli::run();

class delly
{
	// todo: find separate app to monitor delly
	// keep delly daemon running forever
	public function daemon_monitor()
	{
		echo "Starting delly monitor daemon..\n";
		$path_and_action = \epro\CLI_PATH.DAEMON_MONITOR_CMD;
		$args = array_key_exists('s', cli::$args) ? ' -s '.cli::$args['s'] : '';
		cli::bg_exec('php '.$path_and_action.$args, array('verbose' => true));
	}

	// run self in background, exit
	public function daemon_start()
	{
		echo "Starting delly dispatcher deamon..\n";
		$path_and_action = \epro\CLI_PATH.DAEMON_DISPATCHER_CMD;
		$args = array_key_exists('g', cli::$args) ? ' -g' : '';
		cli::bg_exec('php '.$path_and_action.$args, array('verbose' => true));
	}

	// by default we try to stop both
	// if flag is passed in to stop one of them do not try to stop the other
	public function daemon_stop()
	{
		if (!array_key_exists('d', cli::$args)) {
			$r = $this->daemon_stop_process('monitor');
		}
		if (!array_key_exists('m', cli::$args)) {
			$r = $this->daemon_stop_process('dispatcher');
		}
	}

	private function daemon_stop_process($process)
	{
		echo "Stopping delly $process daemon..\n";
		$pid = $this->daemon_get_pid($process);
		if ($pid) {
			// create file that process loop checks for
			file_put_contents($this->daemon_get_kill_file_path($process), time());
			// give process chance to exit
			sleep(ceil($this->daemon_get_sleep_time($process) * 2));

			$pid = $this->daemon_get_pid($process);
			// success
			if ($pid === false) {
				echo "Stopped\n";
				return true;
			}
			// force kill
			else {
				$cmd = 'kill '.$pid;
				exec($cmd, $kill_output, $r);
				if ($r == 0) {
					echo "Killed!\n";
					return true;
				}
				else {
					echo "Could not kill: $cmd -> $kill_output\n";
					exit(1);
				}
			}
		}
		else {
			echo "Delly $process daemon not running\n";
			return false;
		}
	}

	public function daemon_status()
	{
		$pid = $this->daemon_get_pid('dispatcher');
		echo (($pid === false) ? "Not Running" : "Running ($pid)")."\n";
	}

	public function daemon_restart()
	{
		$this->daemon_stop();
		$this->daemon_start();
	}

	private function daemon_get_pid($process)
	{
		$process_cmd = constant('DAEMON_'.strtoupper($process).'_CMD');
		exec('ps -ef', $output);
		foreach ($output as $line) {
			// UID        PID  PPID  C STIME TTY          TIME CMD
			if (preg_match("/^[\w-]+\s+(\d+).*(php.*?".$process_cmd.".*?)\s*$/", trim($line), $matches)) {
				list($ph, $pid, $cmd) = $matches;
				return $pid;
			}
		}
		return false;
	}

	// loop forevery, monitor dispatcher loop
	public function monitor_loop()
	{
		// neither should be running
		$test_pid = $this->daemon_get_pid('monitor');
		$self_pid = getmypid();
		if ($test_pid !== false && $test_pid != $self_pid) {
			cli::error('delly monitor daemon already running');
		}
		$test_pid = $this->daemon_get_pid('dispatcher');
		if ($test_pid !== false) {
			cli::error('delly dispatcher daemon already running');
		}

		$this->last_email_time = false;
		$sleep_time = $this->daemon_get_sleep_time('monitor');
		$this->daemon_start();
		$downtimes = array();
		while (1) {
			sleep($sleep_time);

			// see if someone wants us to go away
			if ($this->do_end_loop('monitor')) {
				break;
			}

			$daemon_pid = $this->daemon_get_pid('dispatcher');
			if ($daemon_pid === false) {
				// todo: log to database?
				$downtimes[] = time();
				// restart!
				$this->daemon_start();
				// see if we have a bigger problem
				$this->monitor_loop_downtime_check($downtimes);
			}
		}
	}

	private function monitor_loop_downtime_check(&$downtimes)
	{
		// three times down in last 100 seconds = let me know
		$count = count($downtimes);
		$now = time();
		if ($count > 2) {
			$three_ago = $downtimes[$count - 3];
			if (($now - $three_ago) < 100) {
				// only send email once an hour
				if ($this->last_email_time === false || ($now - $this->last_email_time) > 3600) {
					cli::email_error(DELLY_ADMIN, 'DELLY DOWN', "Host: ".\epro\HOSTNAME."\n");
					$this->last_email_time = $now;
				}
			}
		}
		// get rid of downtimes over 1 day
		while (count($downtimes) > 0) {
			$dt = $downtimes[0];
			if (($now - $dt) > 86400) {
				array_shift($downtimes);
			}
			else {
				break;
			}
		}
	}

	private function daemon_get_sleep_time($process)
	{
		switch ($process) {
			case ('dispatcher'): return (\epro\NUM_NODES * 2);
			case ('monitor'):    return array_key_exists('s', cli::$args) ? cli::$args['s'] : DEFAULT_MONITOR_SLEEPTIME;
		}
	}

	// run jobs
	public function dispatcher_loop()
	{
		// keep hash of job parent ids
		$this->parent_ids = array();
		$sleep_time = $this->daemon_get_sleep_time('dispatcher');
		while (1) {
			$this->run_jobs();
			sleep($sleep_time);
			$this->check_for_dead_jobs();

			// see if someone wants us to go away
			if ($this->do_end_loop('dispatcher')) {
				break;
			}
		}
	}

	private function do_end_loop($process)
	{
		$kill_file = $this->daemon_get_kill_file_path($process);
		if (file_exists($kill_file)) {
			// consume
			unlink($kill_file);
			return true;
		}
		else {
			return false;
		}
	}

	private function daemon_get_kill_file_path($process)
	{
		return (sys_get_temp_dir()."/delly-{$process}-kill");
	}

	public function check_for_dead_jobs()
	{
		// get currently running process id's
		exec('ps -e', $output);
		$cur_pids = array();
		foreach ($output as $line) {
			if (preg_match("/^\s*(\d+) /", $line, $matches)) {
				$pid = $matches[1];
				$cur_pids[$pid] = 1;
			}
		}

		$jobs = job::get_all(array(
			'select' => "id, process_id",
			'where' => " job.hostname = :hostname && status in (:running_stati)",
			'data' => array(
				"hostname" => \epro\HOSTNAME,
				"running_stati" => job::$running_stati
			)
		));
		foreach ($jobs as $job) {
			if (!array_key_exists($job->process_id, $cur_pids)) {
				$job->error('Unexpected Fatal Error');
			}
		}
	}

	// run a batch of jobs
	private function run_jobs($max_jobs = \epro\MAX_JOBS, $job_id = false)
	{
		// see how many jobs are running on this machine
		// if max, nothing to do
		$num_running = job::count(array(
			'where' => "job.status = 'Processing' && job.hostname = :hostname",
			'data' => array("hostname" => \epro\HOSTNAME)
		));
		if ($num_running >= $max_jobs && $job_id === false) {
			return;
		}
		
		// get scheduled and pending jobs
		// scheduled take precedence in order
		// longest waiting jobs are first
		// limit is number of nodes * max number of jobs for each node
		$q = array(
			'select' => "id, parent_id, type",
			'order_by' => "scheduled asc, created asc",
			'limit' => $max_jobs * \epro\NUM_NODES
		);
		if ($job_id) {
			$q['where'] = array("job.id = :jid");
			$q['data'] = array("jid" => $job_id);
		}
		else {
			$q['where'] = array("
				job.status = 'Pending' ||
				(job.status = 'Scheduled' && job.scheduled <= :now)
			");
			$q['data'] = array("now" => date(util::DATE_TIME, time()));
		}
		// avoid gridlock running jobs that schedule many children
		if (!empty($this->parent_ids)) {
			// get jobs with different parent ids
			$q_different_parent = $q;
			$q_different_parent['where'][] = "parent_id not in (:parent_ids)";
			$q_different_parent['data']["parent_ids"] = array_keys($this->parent_ids);
			$jobs = job::get_all($q_different_parent);

			// if we're below limit, get more jobs without parent restriction
			$diff_parent_count = $jobs->count();
			if ($diff_parent_count < $q['limit']) {
				$q['limit'] -= $diff_parent_count;
				if ($diff_parent_count > 0) {
					// avoid getting same jobs again
					$q['where'][] = "id not in (:non_parent_ids)";
					$q['data']['non_parent_ids'] = $jobs->id;
				}
				$jobs->merge(job::get_all($q));
			}
		}
		else {
			$jobs = job::get_all($q);
		}

		// loop over jobs
		$num_launched = 0;
		$at_least_one_orphan_job = false;
		foreach ($jobs as $i => $job) {
			if ($job->dequeue()) {
				$job->run_in_background();
				// haven't hit an orphan job yet
				if ($at_least_one_orphan_job === false) {
					if (empty($job->parent_id)) {
						$at_least_one_orphan_job = true;
						// reset our parent ids
						$this->parent_ids = array();
					}
					else if (!array_key_exists($job->parent_id, $this->parent_ids)) {
						$this->parent_ids[$job->parent_id] = 1;
					}
				}
				// see if we're still ok running more jobs
				$num_launched++;
				if ($num_launched + $num_running >= $max_jobs) {
					break;
				}
			}
			// todo? else { track collisions }
		}
	}

	// run a single job
	public function run_job()
	{
		if (!empty(cli::$args['g'])) {
			db::dbg();
		}
		// foreground or background
		// default to foreground as this function is for human use
		if (!empty(cli::$args['b'])) {
			$this->run_jobs(1, empty(cli::$args['j']) ? false : cli::$args['j']);
		}
		else {
			if (array_key_exists('j', cli::$args)) {
				$job = new job(array('id' => cli::$args['j']));
				if (!$job->is_in_db()) {
					die("no jobs to run\n");
				}
			}
			else {
				// get one job
				$jobs = job::get_all(array(
					'select' => "*",
					'where' => "
						job.status = 'Pending' ||
						(job.status = 'Scheduled' && job.scheduled <= :now)
					",
					'order_by' => "scheduled asc, created asc",
					'data' => array("now" => date(util::DATE_TIME, \epro\NOW)),
					'limit' => 1
				));
				if ($jobs->count() == 0) {
					die("no jobs to run\n");
				}
				$job = $jobs->a[0];
			}
			$method = (empty(cli::$args['r'])) ? 'run' : cli::$args['r'];
			if (!empty(cli::$args['g'])) {
				db::dbg();
				echo "running job: {$job->id}, $method\n";
			}
			$job->run_worker($method);
		}
	}

	// not all workers need job information
	// can run manually using this
	public function run_worker()
	{
		if (!array_key_exists('w', cli::$args)) {
			cli::usage('-w worker_type [-m method]');
		}
		if (!empty(cli::$args['g'])) {
			db::dbg();
		}
		$type = strtoupper(util::display_text(cli::$args['w']));
		$method = array_key_exists('m', cli::$args) ? cli::$args['m'] : 'run';
		$worker = util::load_worker($type);
		if ($worker) {
			if (method_exists($worker, $method)) {
				$worker->$method();	
				$worker->finish();			
			}
			else if (class_exists('cli')) {
				die('No method '.$method);
			}
		}
		else {
			die('No worker '.$type);
		}
	}

	public function kill_job()
	{
		if (!array_key_exists('j', cli::$args)) {
			cli::usage('-j job_id');
		}
		$job = new job(array('id' => cli::$args['j']));
		$r = $job->kill();
		echo "dead? ($r)\n";
	}
}

?>