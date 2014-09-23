<?php

class worker_custom_import extends worker{


	public function run(){
		$aid =& $this->job->account_id;

		$class_file = dirname(__FILE__)."/import_offline_data/goc_{$aid}.import.php";
		$class = "goc_{$aid}";

		if(file_exists($class_file)){
			if(!class_exists($class))
				include_once($class_file);
			
			$class::run($this->job);
		}else{
			throw new Exception("class goc_{$aid} not found. File goc_{$aid}.import.php is missing.");
		}

	}

	
}



?>