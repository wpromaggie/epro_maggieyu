<?php
class action_delly_jobs extends response_object{
	//protected static $ac = array('skip'=>0,'limit'=>10,'sort'=>'created','by'=>'DESC'); //allowable conditions
	
	/**
	 * GET
	 * Example Call: http://api.wpromote.local/delly/jobs/skip=0&limit=2&sort=created&by=DESC
	 */
	protected static function GET($conditions){	
		self::parse_conditions($conditions);
		$r = mod_delly_job::get_jobs_sort_with_limit(self::$ac);

		if($r)
			return array($r,200);
		else
			return array($r,500);
	}

}
?>