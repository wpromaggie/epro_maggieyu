<?php 
class action_report_ppc_sheet_tables extends response_object{
	protected static function GET($id = NULL){
		return array($id,200);
	}

	protected static function POST($id = NULL){
		//error_log(json_encode(self::$body));

		$a_args = self::$body; //a_args == body args
		if(!isset($a_args['rid']))
			return array(false,400);
		

		$report_id =& $a_args['rid'];
		$r = mod_eppctwo_reports::get_report_details_by_id($report_id);
		return array($r,200);
	}

	protected static function DELETE($id){

	}

	/* other stuff that makes this work */
}

?>
