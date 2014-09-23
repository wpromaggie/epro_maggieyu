<?php

class mod_social_post extends rs_object
{
	public static $db, $cols, $primary_key;

	public static $id_alphabet = false;

	public static function set_table_definition()
	{
		self::$db = 'social';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'               ,'char'    ,16  ,''     ,rs::READ_ONLY),
			new rs_col('account_id'       ,'char'    ,16  ,''     ,rs::READ_ONLY),
			new rs_col('user_id'          ,'int'     ,null,0      ,rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('created'          ,'datetime',null,rs::DDT),
			new rs_col('separate_messages','bool'    ,null,0      ),
			new rs_col('has_posted'       ,'bool'    ,null,0      ),
			new rs_col('do_post_now'      ,'bool'    ,null,0      ),
			new rs_col('scheduled'        ,'datetime',null,rs::DDT)
		);
	}
	
	// 16 hex chars
	protected function uprimary_key($i)
	{
		if (empty(self::$id_alphabet)) {
			self::$id_alphabet = array_merge(
				array(45),
				range(48, 57),
				range(65, 90),
				array(95),
				range(97, 122)
			);
		}
		$alpha_size = count(self::$id_alphabet);
		$id = '';
		for ($i = 0, $ci = self::$cols['id']->size; $i < $ci; ++$i) {
			$id .= chr(self::$id_alphabet[mt_rand(0, $alpha_size - 1)]);
		}
		return $id;
	}

	public function submit()
	{
		util::load_lib('network');

		if (empty($this->account_id)) {
			$this->get(array(
				"select" => array(
					"post" => array("*"),
					"post_media" => array("id as mid", "path")
				),
				"left_join" => array("post_media" => "post.id = post_media.post_id"),
				"de_alias" => true
			));
		}
		if (empty($this->network_post)) {
			$this->network_post = network_post::get_all(array(
				"where" => "post_id = '".db::escape($this->id)."'"
			));
		}
		if (isset($this->post_media->id)) {
			// make sure we have everything we need to attach media
			if (empty($this->post_meda->path)) {
				$this->post_media->get();
			}
			// $tmp_path = tempnam(sys_get_temp_dir(), 'smo-media');
			// file_put_contents($tmp_path, $this->post_media->data);
			$this->media_path = $this->post_media->path;
			$this->media_data = file_get_contents($this->post_media->path);
		}
		$r = true;
		foreach ($this->network_post as $npost) {
			$net_r = $npost->submit($this);
			$r = ($r && $net_r);
		}
		$this->update_from_array(array('has_posted' => true));
		return $r;
	}

	public function send_user_error_email()
	{
		// start email body with link to the failed post
		$body = $this->get_edit_url()."\n";
		$error_nets = array();
		$dt = false;
		foreach ($this->network_post as $npost) {
			if (!empty($npost->error)) {
				$body .= "{$npost->network} error: {$npost->error}\n";
				$error_nets[] = $npost->network;
				if (!$dt) {
					$dt = $npost->posted_at;
				}
			}
		}
		$client_name = db::select_one("select name from eac.account where id = '".db::escape($this->account_id)."'");
		$user_email = db::select_one("select username from eppctwo.users where id = '".db::escape($this->user_id)."'");
		$subject = 'Post error for '.$client_name.' at '.$dt.' ('.implode(', ', $error_nets).')';
		util::mail('errors@wpromote.com', $user_email, $subject, $body);
	}

	public function get_edit_url()
	{
		//return 'http://'.\epro\DOMAIN.'/smo/client/swapp/post?cl_id='.$this->account_id.'&pid='.$this->id;
		return 'http://'.\epro\DOMAIN.'/account/service/smo/swapp/post?aid='.$this->account_id.'&pid='.$this->id;
	}
}
?>
