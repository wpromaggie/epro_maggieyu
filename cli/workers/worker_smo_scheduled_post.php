<?php
util::load_lib('agency', 'smo');

class worker_smo_scheduled_post extends worker
{
	// to be run every 5 minutes
	const INTERVAL = 300;
	const DEV_EMAIL = 'ryan@wpromote.com';

	private $dstr;

	public function run()
	{
		$this->report_on_zero_errors = (!empty(cli::$args['z']));
		if ($this->dbg) {
			$this->dstr = '';
		}

		$post = new post(array('id' => $this->job->fid), array(
			'select' => array(
				"post" => array("id", "user_id", "account_id", "separate_messages", "scheduled", "has_posted"),
				"post_media" => array("id as mid", "path"),
				"network_post" => array("id as nid", "message", "network", "error", "posted_at"),
				"facebook_post" => array("album_id", "link", "link_name", "picture_url", "caption", "description"),
				"twitter_post" => array("(twitter_post.id) as tid")
			),
			'join_many' => array(
				"network_post" => "post.id = network_post.post_id"
			),
			'left_join' => array(
				"post_media" => "post.id = post_media.post_id",
				"facebook_post" => "network_post.id = facebook_post.id",
				"twitter_post" => "network_post.id = twitter_post.id"
			),
			'de_alias' => true
		));

		//e($post);

		$post->submit();
		$is_error = false;
		if ($this->dbg) {
			$this->dstr .= "\n".$post->get_edit_url()."\n";
		}
		foreach ($post->network_post as $npost) {
			if ($this->dbg) {
				$this->dstr .= "{$post->id} - {$npost->id}: ".((empty($npost->error)) ? 'SUCCESS' : $npost->error)."\n";
			}
			if (!empty($npost->error)) {
				if ($npost->can_retry_on_error()) {
					$r_retry = $npost->retry_submit($post);
					$this->dstr .= "retry result: $r_retry\n";
					if ($r_retry === false) {
						$is_error = true;
					}
				}
				else {
					$is_error = true;
				}
			}
		}
		// fatal error, let user who scheduled post know
		if ($is_error) {
			$error_count++;
			$post->send_user_error_email();
		}
		if ($this->dbg && ($this->report_on_zero_errors || $error_count > 0)) {
			util::mail('debug@wpromote.com', self::DEV_EMAIL, 'swapp debug '.date(util::DATE_TIME), $this->dstr);
		}
	}
}

?>