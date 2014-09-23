<?php 
class action_report_ppc_run extends respone_object{
	protected function GET($id = NULL){

	}

	protected function POST($id = NULL){

	}


	/* other stuff that makes this work */
}
/*
list($market, $type, $do_force, $start_date, $end_date) = util::list_assoc($_POST, 'refresh_market', 'refresh_type', 'do_force', 'start_date', 'end_date');

		if (!util::is_valid_date_range($start_date, $end_date)) {
			return feedback::add_error_msg('Invalid date range');
		}
		if ($end_date >= \epro\TODAY) {
			$end_date = date(util::DATE, time() - 86400);
		}

		$job_info = ppc_data_source_refresh::create(array(
			'account_id' => $this->aid,
			'refresh_type' => $type,
			'market' => $market,
			'do_force' => $do_force,
			'start_date' => $start_date,
			'end_date' => $end_date
		));
		job::queue(array(
			'type' => 'PPC DATA SOURCE REFRESH',
			'fid' => $job_info->id,
			'account_id' => $this->aid
		));
		
		$job_info = mod_eppctwo_ppc_data_source_refresh::create(array(
			'account_id' => $this->aid,
			'refresh_type' => $type,
			'market' => $market,
			'do_force' => $do_force,
			'start_date' => $start_date,
			'end_date' => $end_date
		));
		mod_delly_job::queue(array(
			'type' => 'PPC DATA SOURCE REFRESH',
			'fid' => $job_info->id,
			'account_id' => $this->aid
		));
*/
?>