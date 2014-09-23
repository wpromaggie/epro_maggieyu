<?php

class jobs
{
	public static function schedule($foreign_id, $type, $user_id, $cl_id, $status = 'Pending')
	{
		$dbg = (class_exists('dbg') && dbg::is_on()) ? '-DBG' : '';
		
		$job_id = db::insert("eppctwo.jobs", array(
			'foreign_id' => $foreign_id,
			'type' => $type,
			'user' => $user_id,
			'client' => $cl_id,
			'status' => $status.$dbg,
			'create_date' => date(util::DATE_TIME)
		));
		
		return $job_id;
	}
	
	public static function update_status($job_id, $status, $details = null, $minutia = null)
	{
		$updates = array('status' => $status);
		if ($details != null) $updates['details'] = $details;
		if ($minutia != null) $updates['minutia'] = $minutia;
		db::update("eppctwo.jobs", $updates, "id = '$job_id'");
	}
	
	public static function update_details($job_id, $details)
	{
		db::exec("
			update eppctwo.jobs
			set details='".db::escape($details)."'
			where id = '$job_id'
		");
	}
	
	public static function update_minutia($job_id, $minutia)
	{
		db::exec("
			update eppctwo.jobs
			set minutia='".db::escape($minutia)."'
			where id = '$job_id'
		");
	}
	
	public static function is_completed($job_id)
	{
		$status = db::select_one("
			select status
			from eppctwo.jobs
			where id = '$job_id'
		");
		
		return ($status == 'Completed' || $status == 'Error');
	}
	
	public static function set_pending(&$jobs, $dbg = false)
	{
		$jobs = db::select("
			select id, foreign_id, type
			from eppctwo.jobs
			where status = 'Pending".(($dbg) ? '-DBG' : '')."'
			order by id asc
		");
	}
	
	public static function start($type, $job_id, $foreign_id, $dbg = false)
	{
		$class = 'worker_'.util::simple_text($type);
		require_once($class.'.php');
		$worker = new $class($job_id, $foreign_id);
		if ($dbg) $worker->debug();
		$worker->go();
	}

}

?>