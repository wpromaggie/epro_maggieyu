<?php
class action_delly_job extends response_object{
	protected static function GET($id){
		$r = mod_delly_job::get_job_by_id($id);
		return array($r,200);
	}

	protected static function POST(){
		$r = mod_delly_job::queue(self::$body);

		return array($r,200);
	}
}
?>