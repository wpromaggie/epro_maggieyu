<?php

class action_log_test extends response_object{
	protected static function POST(){
		$r = mod_log_request::create(
			array(
				'interface'=>'api',
				'user'=>1000,
				'context'=>'rest'
			));

		if($r)
			return array($r,200);
		else
			return array($r,500);
	}

}

?>