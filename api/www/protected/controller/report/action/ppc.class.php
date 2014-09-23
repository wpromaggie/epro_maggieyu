<?php 
class action_report_ppc extends response_object{
	protected static function GET($id = NULL){
		return array($id,200);
	}
	
	/**
	 * POST CREATE Empty Report
	 */
	protected static function POST($id = NULL){
		$b_args = self::$body; //b_args == body args
		if(!isset($b_args['name']))
			return array(false,400);
		if(!isset($b_args['account_id']))
			return array(false,400);

		$report_name =& $b_args['name'];
		$is_template = (isset($b_args['is_template']))? (($b_args['is_template'] === true)? 1 : 0) : 0;
		$account_id =& $b_args['account_id'];
		//$report = new mod_eppctwo_reports(array(	
		
		$r = mod_eppctwo_reports::create(array(	
			'user'=>1000,
			'name'=>$report_name,
			'is_template'=>$is_template,
			'account_id'=>$account_id,
			'create_date'=>date('Y-m-d H:i:s')
		));
		
			print_r($r);
		return array($r,200);
	}

	protected static function DELETE($id){

	}

	/* other stuff that makes this work */
}

?>
