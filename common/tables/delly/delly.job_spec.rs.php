<?php



// todo: create spec_ classes for ppc report and ppc data source refresh jobs

abstract class job_spec extends rs_object
{
	public static function schedule($init_data, $job_data = array(), $parent_job = false)
	{
		$job_type = strtoupper(util::display_text(preg_replace("/^spec_/", '', get_called_class())));

		$spec = self::create($init_data);
		if (!$spec) {
			return false;
		}

		// init job, job_data will overwrite defaults
		$job_info = array_merge(array(
			'type' => $job_type,
			'fid' => $spec->id
		), $job_data);

		if ($parent_job) {
			$job_info['parent_id'] = $parent_job->id;
			$job_info['user_id'] = $parent_job->user_id;
		}
		else {
			if (class_exists('user')) {
				$job_info['user_id'] = user::$id;
			}
		}
		return job::queue($job_info);
	}
}
?>