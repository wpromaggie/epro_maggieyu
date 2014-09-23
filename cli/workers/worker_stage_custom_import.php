<?php

class worker_stage_custom_import extends worker{
	
	public function run(){
		//db::dbg();

		$clients = $this->get_clients();
		foreach($clients as $client){
			//e($client);
			if($this->run_scheduled($client)){
				//e($client);
				job::queue(
					array(
						'type'=>'CUSTOM IMPORT',
						'account_id'=>$client['aid'],
					)
				);
				$this->set_next_runtime($client);
			}

		}


	}	

	private function get_clients(){
		return db::select("SELECT
								aid,
								next_runtime_download,
								frequency_download
							 FROM `delly`.`offline_conversion_schedule`",
							 'ASSOC');
	}

	/**
	 * run_scheduled
	 *
	 *
	 */
	protected function run_scheduled($client){
		$now = new DateTime();
		$next = new DateTime($client['next_runtime_download']);
		//e($client);
		$diff = $now->diff($next);
		//e(array('diff'=>$next));
		return ($diff->invert)? true : false;
	}

	protected function set_next_runtime($client){
		//e(__FUNCTION__);
		$t0 =&  $client['next_runtime_download'];
		$add =& $client['frequency_download'];

		$nrt = date('Y-m-d H:i:s',strtotime("+ {$add} hours"));

		//e(array('nrt'=>$nrt));
		db::update("`delly`.`offline_conversion_schedule`",
					array('next_runtime_download'=>$nrt),
					"`aid` = :aid",
					array('aid'=>$client['aid']));
	}


}

?>