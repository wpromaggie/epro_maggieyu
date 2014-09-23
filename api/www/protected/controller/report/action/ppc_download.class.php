<?php 
class action_report_ppc_download extends response_object{
	protected static function GET($id = NULL){
		$report = json_decode('[{"campaign":"WP - In Market Setments","avg_pos":3.1,"clicks":292},{"campaign":"Remodel Themes NB US - Brand Page","avg_pos":4.8,"clicks":23}]');
		//print_r($report);
		return array(array('report_id'=>$id,'report_data'=>$report),200);
	}

	protected static function POST($id = NULL){
		$d_args = self::$body; //d_args == body args
		if(!isset($d_args['jobid']))
			return array(false,400);
		

		$job_id =& $d_args['jobid'];
		$r = mod_eppctwo_reports::get_report_summary($job_id);
		return array(array('sheets'=>$r),200);
	}


	/* other stuff that makes this work */
}

?>