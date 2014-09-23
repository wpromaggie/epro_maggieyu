<?php
util::load_lib('ppc');

class worker_reduce_market_data extends worker
{
	public function run()
	{
		$spec = new spec_reduce_market_data(array('id' => $this->job->fid));
		$data_opts = json_decode($spec->data_opts, true);

		// map
		all_data::schedule_map($data_opts, $this->job);

		// wait
		$this->job->wait_for_children();

		// reduce
		$this->reduce($data_opts);
	}

	private function reduce($data_opts)
	{
		// get children
		$children = spec_map_market_data::get_all(array(
			'where' => "parent_job_id = :pjid",
			'data' => array("pjid" => $this->job->id)
		));

		$data = array();
		foreach ($children as $child) {
			$data = array_merge($data, json_decode($child->data, true));
		}

		$reduced = all_data::reduce($data_opts, $data, $this->job);
		foreach ($reduced->data as $i => $d) {
			reduced_market_data::create(array(
				'job_id' => $this->job->id,
				'i' => $i,
				'data' => json_encode($d)
			));
		}
	}
}

?>