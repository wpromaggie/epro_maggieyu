<?php

util::load_lib('delly', 'ppc');

class email_receipts extends base_email_export{
	public function run(){
		list($job) = func_get_args();
    	self::$job = $job;

	}


	public function body(){
		return "";
	}
}


?>