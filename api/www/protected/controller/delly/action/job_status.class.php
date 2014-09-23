<?php
class action_delly_job_status extends response_object{
	protected static function GET($job_id){
		$job = mod_delly_job::get_job_status_by_id($job_id);
		return array($job,200);
	}
}
?>