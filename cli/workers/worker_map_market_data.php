<?php
util::load_lib('ppc');

class worker_map_market_data extends worker
{
	public function run()
	{
		$parent_job = new job(array('id' => $this->job->parent_id));
		$parent_spec = new spec_reduce_market_data(array('id' => $parent_job->fid));
		$spec = new spec_map_market_data(array('id' => $this->job->fid));

		$data_opts = json_decode($parent_spec->data_opts, true);

		// add this instances's particulars to data opts
		$data_opts['job'] = $this->job;
		$data_opts['is_map'] = true;
		$data_opts['market'] = $spec->market;
		$data_opts['ad_groups'] = array($spec->ad_group_id);
		
		$data = all_data::get_data($data_opts);

		$spec->update_from_array(array(
			'data' => json_encode($data->data)
		));
	}
}

?>