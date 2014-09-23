<?php

class mod_delly extends module_base
{
	public function ajax_job_status_widget()
	{
		// get job and most recent detail (if exists)
		$job = new job(array('id' => $_REQUEST['jid']), array(
			'select' => array(
				"job" => array("*"),
				"job_detail" => array("ifnull(message, '') as most_recent_detail")
			),
			'left_join' => array(
				"job_detail" => "job.id = job_detail.job_id"
			),
			'order_by' => "job_detail.ts desc, job_detail.id desc",
			'limit' => "1",
			'flatten' => true
		));

		$css_classes = array('job_status_widget');
		// bad job id
		if (!$job->is_in_db()) {
			$css_classes += array('done', 'error');
			$msg = 'Could not find job';
		}
		// job is done
		else if ($job->is_done()) {
			$msg = $job->status.' ('.$job->finished.')';
			$css_classes[] = 'done';
			if ($job->status == 'Error') {
				$css_classes[] = 'error';
				if (!empty($job->most_recent_detail)) {
					$msg .= ': '.$job->most_recent_detail;
				}
			}
			else if ($job->status == 'Cancelled') {
				$css_classes[] = 'cancelled';
			}
		}
		// job not done
		else {
			$css_classes[] = 'running';
			if ($job->is_map_reduce()) {
				// check for children
				$children = job::get_all(array(
					'select' => array("job" => array("status", "count(*) as status_count")),
					'where' => "job.parent_id = :parent_id",
					'data' => array("parent_id" => $job->id),
					'group_by' => "status"
				));
				$num_done = $num_children = 0;
				if ($children->count() > 0) {
					foreach ($children as $child) {
						$num_children += $child->status_count;
						if (in_array($child->status, job::$done_stati)) {
							$num_done += $child->status_count;
						}
					}
					// +1: leave some for reduction at the end?
					$percent_complete = util::format_percent(($num_done / ($num_children + 1)) * 100);
				}
				else {
					$percent_complete = '0.00%';
				}
			}
			// not map reduce
			else {
				// get similar jobs to estimate time
				if (empty($_REQUEST['estimate_percent_complete'])) {
					$percent_complete = false;
				}
				else {
					$percent_complete = $this->estimate_percent_complete($job);
				}
			}
			$msg = $job->status;
			if (!empty($job->most_recent_detail)) {
				$msg .= ', '.$job->most_recent_detail;
			}
			if ($percent_complete !== false) {
				$msg .= ' ('.$percent_complete.' Complete)';
			}
		}
		echo '<span class="'.implode(' ', $css_classes).'">'.$msg.'</span>';
	}

	private function estimate_percent_complete($job)
	{
		$similar_jobs = job::get_all(array(
			'select' => "if (account_id = :aid, 1, 0) is_account, if (fid = :fid, 1, 0) is_fid, started, finished",
			'where' => "
				job.type = :type &&
				job.status = 'Completed'
			",
			'data' => array(
				"aid" => $job->account_id,
				"fid" => $job->fid,
				"type" => $job->type
			),
			'order_by' => "is_account desc, is_fid desc, finished desc",
			'limit' => "10"
		));
		$num_counted = $total_elapsed = 0;
		for ($i = 0, $ci = $similar_jobs->count(); $i < $ci; ++$i) {
			$sj = $similar_jobs->i($i);
			if (!util::empty_date_time($sj->finished) && !util::empty_date_time($sj->started)) {
				$num_counted++;
				$total_elapsed += strtotime($sj->finished) - strtotime($sj->started);
			}
		}
		$cur_elapsed = time() - strtotime($job->started);
		if ($num_counted > 0) {
			$ave_elapsed = $total_elapsed / $num_counted;
			return util::format_percent(($cur_elapsed / $ave_elapsed) * 100);
		}
		else {
			return '?';
		}
	}
}

?>