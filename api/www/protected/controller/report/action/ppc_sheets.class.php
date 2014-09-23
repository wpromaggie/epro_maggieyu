<?php 
class action_report_ppc_sheets extends response_object{
	protected static function GET($id = NULL){
		return array($id,200);
	}
   /**
	 * POST CREATE Empty Sheet
	 */
	protected static function POST($id = NULL){
		error_log(json_encode(self::$body));

		$s_args = self::$body; //a_args == body args
		if(!isset($s_args['report_id']))
			return array(false,400);
		

		$report_id =& $s_args['report_id'];
		$name = & $s_args['name'];
		
		$position = & $s_args['position'];
		
		$r = mod_eppctwo_ppc_report_sheet::create(array(	
			'report_id'=>$report_id,
			'name'=>$name,
			'position'=>$position,
		));
		
		return array($r,200);
	}

	protected static function DELETE($id){

	}

	/* other stuff that makes this work */
}

?>
