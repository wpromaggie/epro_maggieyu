<?php

class mod_social_network_post extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'social';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'        ,'char'    ,16  ,''      ,rs::READ_ONLY),
			new rs_col('post_id'   ,'char'    ,16  ,''      ,rs::READ_ONLY),
			new rs_col('network_id','char'    ,64  ,''      ,rs::READ_ONLY),
			new rs_col('network'   ,'char'    ,16  ,''      ),
			new rs_col('message'   ,'varchar' ,512 ,''      ),
			new rs_col('posted_at' ,'datetime',null,rs::DDT),
			new rs_col('error'     ,'char'    ,200 ,''      )
			);
	}


	// 16 hex chars
	protected function uprimary_key($i)
	{
		$sha = sha1(mt_rand());
		return strtoupper(substr($sha, 0, 16));
	}

	public function submit($post)
	{
		// add in fields from base post object that we need
		$base_keys = array('media_path', 'media_data');
		foreach ($base_keys as $key) {
			if (isset($post->$key)) {
				$this->$key = $post->$key;
			}
		}
		$api = network::get_api($this->network, $post->account_id);
		// todo: figure out why is this happening?
		if (empty($api)) {
			$error = 'e2 could not connect to API';
			$this->update_from_array(array('error' => $error));
			network_post_error::create(array(
				'network_post_id' => $this->id,
				'posted_at' => date(util::DATE_TIME),
				'error' => $error
			));
			if (class_exists('feedback')) {
				feedback::add_error_msg('Error posting to '.$this->network.': '.$error);
			}
			return false;
		}
		$r = $api->post($this);
		$this->process_response($api, $r);

		if (class_exists('feedback')) {
			if ($r !== false) {
				feedback::add_success_msg('Post to '.$this->network.' successful');
			}
			else {
				feedback::add_error_msg('Error posting to '.$this->network.': '.$api->get_error());
			}
		}

		return $r;
	}

	public function retry_submit($post, $sleep = 5)
	{
		if (is_numeric($sleep)) {
			sleep($sleep);
		}
		return $this->submit($post);
	}

	public function process_response($api, &$r)
	{
		$updates = array('posted_at' => date(util::DATE_TIME));
		if ($r === false) {
			$updates['error'] = $api->get_error();
			// write to error log
			network_post_error::create(array(
				'network_post_id' => $this->id,
				'posted_at' => $updates['posted_at'],
				'error' => $updates['error']
			));
		}
		else {
			$updates['network_id'] = $r['id'];
			// could be a repost, unset error as we have now succeeded
			$updates['error'] = '';
		}
		$rupdate = $this->update_from_array($updates);
		return ($r && $rupdate);
	}

	public function can_retry_on_error()
	{
		return false;
	}

	// override to print network specific post input fields
	public static function print_network_inputs()
	{
	}

	// default return empty array
	public static function get_post_data()
	{
		return array();
	}
}
?>
