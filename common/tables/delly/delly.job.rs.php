<?php
class job extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	public static $status_options = array(
		'Pending',    // waiting for delly to dequeue
		'Scheduled',  // waiting for delly to dequeue at defined time

		'Starting',   // dequeued, will begin proessing shortly
		'Processing', // go go go
		'Waiting',    // waiting for children (or some other reason?)

		'Completed',  // successfully run
		'Error',      // error
		'Cancelled'   // user cancelled
	);

	public static $running_stati = array('Starting', 'Processing', 'Waiting');
	public static $done_stati = array('Completed', 'Error', 'Cancelled');

	public static $id_len = 24;
	public static function set_table_definition()
	{
		self::$db = 'delly';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('status')
		);

		// todo: no fid - all job specs can just use 
		self::$cols = self::init_cols(
			new rs_col('id'        ,'char'    ,self::$id_len,''     ),
			new rs_col('parent_id' ,'char'    ,self::$id_len,''     ), // parent id in job table, if any
			new rs_col('fid'       ,'char'    ,32           ,''     ), // foreign id
			new rs_col('type'      ,'char'    ,64           ,''     ), // type must have corresponding worker file
			new rs_col('user_id'   ,'char'    ,32           ,0      ), // user who requested the job
			new rs_col('account_id','char'    ,16           ,''     ),
			new rs_col('hostname'  ,'char'    ,32           ,''     ),
			new rs_col('process_id','int'     ,null         ,0      ),
			new rs_col('status'    ,'enum'    ,16           ,''     ),
			new rs_col('scheduled' ,'datetime',null         ,rs::DDT),
			new rs_col('created'   ,'datetime',null         ,rs::DDT),
			new rs_col('started'   ,'datetime',null         ,rs::DDT),
			new rs_col('finished'  ,'datetime',null         ,rs::DDT)
		);
	}
	
	// yet another weird pk. why do i always do this?
	private static $pk_sha, $pk_md5;
	public function uprimary_key($i)
	{
		$half_id = self::$id_len / 2;
		// md5 is 32 chars long, get a new md5 when substr reaches end
		$md5i = $i % (32 - $half_id);
		if ($md5i === 0) {
			self::$pk_md5 = md5(mt_rand());
		}
		// sha is 40 etc etc
		$shai = $i % (40 - $half_id);
		if ($shai === 0) {
			self::$pk_sha = sha1(mt_rand());
		}
		return substr(self::$pk_sha, $shai, $half_id).substr(self::$pk_md5, $md5i, $half_id);
	}

	// convenience function for creating job and scheduling it for the future
	public static function schedule($data)
	{
		if (!array_key_exists('scheduled', $data)) {
			return false;
		}
		// default status for schedule is "Scheduled"
		if (!array_key_exists('status', $data)) {
			$data['status'] = 'Scheduled';
		}
		return self::create($data);
	}

	// convenience function for creating job and putting it immediately on queue
	public static function queue($data)
	{
		// default status for queue is "Pending"
		if (!array_key_exists('status', $data)) {
			$data['status'] = 'Pending';
		}
		return self::create($data);
	}

	// override default create function
	public static function create($data, $opts = array())
	{
		if (!array_key_exists('type', $data)) {
			return false;
		}
		if (!array_key_exists('user_id', $data) && class_exists('user')) $data['user_id'] = user::$id;
		if (!array_key_exists('created', $data))                         $data['created'] = date(util::DATE_TIME);
		return parent::create($data, $opts);
	}

	/*
	 * database *should* take care of this, but seems to have rare lapses where
	 * multiple hosts think they have updated the job status and so multiple hosts
	 * end up running the same job
	 * so
	 * wrap in transaction to make extra sure only one host dequeues a job
	 */
	public function dequeue()
	{
		try {
			db::start_transaction();
			$r_update = $this->update_from_array(array(
				'status' => 'Starting'
			));
			$r_commit = db::commit();
			return ($r_update && $r_commit);
		}
		catch (Exception $e) {
			return false;
		}
	}

	public function begin_processing()
	{
		// update finished: might be re-running a previously run job
		return $this->update_from_array(array(
			'process_id' => getmypid(),
			'status' => 'Processing',
			'hostname' => \epro\HOSTNAME,
			'started' => date(util::DATE_TIME),
			'finished' => '0000-00-00 00:00:00'
		));
	}

	public function end_processing($status = 'Completed')
	{
		return $this->update_from_array(array(
			'status' => $status,
			'finished' => date(util::DATE_TIME)
		));
	}

	public function kill($opts = array())
	{
		util::set_opt_defaults($opts, array(
			'show_feedback' => (class_exists('feedback'))
		));
		// first try to kill any children
		if ($this->is_map_reduce()) {
			// update all pending children so they do not start running
			job::update_all(array(
				'set' => array("status" => "Cancelled"),
				'where' => "status = 'Pending' && parent_id = :pid",
				'data' => array("pid" => $this->id)
			));
			// check for children that are waiting
			$waiting_children = job::get_all(array(
				'where' => "status = 'Waiting' && parent_id = :pid",
				'data' => array("pid" => $this->id)
			));
			foreach ($waiting_children as $waiting_child) {
				$waiting_child->kill();
			}
			// let any children currently processing finish
		}
		// kill self
		if (function_exists('posix_kill')) {
			if (posix_kill($this->process_id, 9)) {
				$this->update_details('User Cancelled');
				$this->end_processing('Cancelled');
				if ($opts['show_feedback']) feedback::add_success_msg('Job killed');
				return true;
			}
			else {
				if ($opts['show_feedback']) {
					feedback::add_error_msg('Could not kill');
					if (class_exists('user') && user::is_developer()) {
						feedback::add_error_msg('Try from CLI: php delly.php kill_job -j '.$this->id);
					}
				}
			}
		}
		else {
			if ($opts['show_feedback']) feedback::add_error_msg('Please add posix library http://www.php.net/manual/en/book.posix.php');
		}
		return false;
	}

	public function run_in_background()
	{
		$cmd = 'php '.\epro\CLI_PATH.'delly.php run_job -j '.$this->id;
		$opts = array();
		// dbg, write worker output to file
		if (!empty(cli::$args['g'])) {
			$temp_name = tempnam(sys_get_temp_dir(), 'worker');
			$opts['stdout'] = $temp_name;
			echo "$cmd\n";
			echo "worker output file: $temp_name\n";
		}
		cli::bg_exec($cmd, $opts);
	}

	public function run_worker($method = 'run')
	{
		$worker = util::load_worker($this);
		if ($worker) {
			if (method_exists($worker, $method)) {
				$worker->$method();
				$worker->finish();
			}
			else if (class_exists('cli')) {
				die('No method '.$method);
			}
		}
		else if (class_exists('cli')) {
			die('Could not load worker');
		}
	}

	public function is_done()
	{
		return (in_array($this->status, self::$done_stati));
	}

	public function is_map_reduce()
	{
		return (strpos($this->type, 'REDUCE') === 0 || $this->status == 'Waiting');
	}

	public function error($msg)
	{
		$this->update_from_array(array('status' => 'Error'));
		$this->update_details($msg);
		return false;
	}

	const MAX_WAIT_FOR_CHILDREN = 60;
	public function wait_for_children()
	{
		$sleepy_time = 2;
		$this->update_from_array(array('status' => 'Waiting'));
		while (1) {
			sleep($sleepy_time);
			if ($this->all_children_done()) {
				$this->update_from_array(array('status' => 'Processing'));
				return;
			}
			if ($sleepy_time < self::MAX_WAIT_FOR_CHILDREN) {
				$sleepy_time += 2;
			}
		}
	}

	private function all_children_done()
	{
		$num_not_finished = self::count(array(
			'where' => "job.status not in (:done_stati) && parent_id = :pid",
			'data' => array(
				"pid" => $this->id,
				"done_stati" => self::$done_stati
			)
		));
		return ($num_not_finished == 0);
	}

	public function sub_task($callable, $args)
	{
		// raise detail level
		if (!isset($this->detail_level)) {
			$this->detail_level = 0;
		}
		$this->detail_level++;

		// call task
		$r = call_user_func_array($callable, $args);

		// lower back
		$this->detail_level--;

		return $r;
	}

	// is_relative: job can be passed around to various objects that can update job details
	//  other objects may not know what level of detail the overall work is at, but it can be set in job
	//  so they do not have to know, only their own relative detail level
	public function update_details($msg, $lvl = 0, $is_relative = true)
	{
		return job_detail::create(array(
			'job_id' => $this->id,
			'ts' => date(util::DATE_TIME, time()),
			'level' => ($is_relative && $this->detail_level) ? $lvl + $this->detail_level : $lvl,
			'message' => $msg
		));
	}
}
?>