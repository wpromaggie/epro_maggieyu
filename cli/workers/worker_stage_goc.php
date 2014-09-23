<?php
util::load_lib('ppc');


class worker_stage_goc extends worker{
	public function run(){
		$clients = $this->get_clients();
		foreach($clients as $client){
			if($this->run_scheduled($client)){
				job::queue(
					array(
						'type'=>'GOOGLE OFFLINE CONVERSION',
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
								next_runtime_upload,
								next_runtime_download,
								frequency_upload,
								frequency_download

							 FROM `delly`.`offline_conversion_schedule`",'ASSOC');
	}

	protected function set_next_runtime($client){
		//e(__FUNCTION__);
		$t0 =&  $client['next_runtime_upload'];
		$add =& $client['frequency_upload'];

		$nrt = date('Y-m-d H:i:s',strtotime("+ {$add} hours"));

		//e(array('nrt'=>$nrt));
		db::update("`delly`.`offline_conversion_schedule`",
					array('next_runtime_upload'=>$nrt),
					"`aid` = :aid",
					array('aid'=>$client['aid']));
	}

	protected function run_scheduled($client){
		$now = new DateTime();
		$next = new DateTime($client['next_runtime_upload']);

		$diff =$now->diff($next);
		return ($diff->invert)? true : false;
	}

}

?>