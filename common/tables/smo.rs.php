<?php


class clients_smo extends rs_object
{
	public static $db, $cols, $primary_key, $has_one;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('client');
		self::$has_one = array('clients');
		self::$cols = self::init_cols(
			new rs_col('company','int'    ,11  ,0   ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('client' ,'varchar',32  ,''  ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('manager','varchar',64  ,''  ,rs::NOT_NULL),
			new rs_col('status' ,'enum'   ,null,'On',rs::NOT_NULL ,array('On', 'Cancelled', 'Incomplete', 'Off')),
			new rs_col('billing_contact_id','bigint' ,20  ,null,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('url'               ,'varchar',128 ,''  ,rs::NOT_NULL)
		);
	}
	
	public static function manager_form_input($table, $col, $val)
	{
		$options = self::manager_options($table, $col, $val);
		return cgi::html_select($table.'_'.$col->name, $options, $val);
	}
	
	private static function manager_options($table, $col, $val)
	{
		$options = db::select("
			select u.username u0, u.realname u1
			from users u, user_guilds ug
			where
				ug.guild_id = 'smo' &&
				u.id = ug.user_id
			order by u1 asc
		");
		array_unshift($options, array('', ' - None - '));
		return $options;
	}
}

class twitter_auth extends rs_object
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'social';
		self::$primary_key = array('account_id');
		self::$cols = self::init_cols(
			new rs_col('account_id'        ,'char',16 ,''),
			new rs_col('consumer_key'      ,'char',32 ,''),
			new rs_col('consumer_secret'   ,'char',64 ,''),
			new rs_col('oauth_token'       ,'char',128,''),
			new rs_col('oauth_token_secret','char',128,''),
			new rs_col('user_id'           ,'char',32 ,''),
			new rs_col('screen_name'       ,'char',32 ,'')
		);
	}
}

class facebook_auth extends rs_object
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'social';
		self::$primary_key = array('account_id');
		self::$cols = self::init_cols(
			new rs_col('account_id'  ,'char'    ,16  ,''     ),
			new rs_col('app_id'      ,'char'    ,32  ,''     ),
			new rs_col('app_secret'  ,'char'    ,64  ,''     ),
			new rs_col('access_token','char'    ,200 ,''     ),
			new rs_col('expires'     ,'int'     ,null,0      ,rs::UNSIGNED),
			new rs_col('expires_at'  ,'datetime',null,rs::DDT),
			new rs_col('page_id'     ,'char'    ,32  ,''     ),
			new rs_col('page_token'  ,'char'    ,200 ,''     ),
			new rs_col('album_id'    ,'char'    ,32  ,''     )
		);
	}
	
}

class facebook_page extends rs_object
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'social';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'          ,'char'    ,32 ,''),
			new rs_col('name'        ,'char'    ,128,''),
			new rs_col('category'    ,'char'    ,128,''),
			new rs_col('access_token','char'    ,200,'')
		);
	}
}

class facebook_album extends rs_object
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'social';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'          ,'char'    ,32 ,''),
			new rs_col('account_id'  ,'char'    ,16 ,''),
			new rs_col('page_id'     ,'char'    ,32 ,''),
			new rs_col('name'        ,'char'    ,200,'')
		);
	}
}

class post extends rs_object
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

util::load_lib('nest');
class post_media extends rs_object
{
	public static $db, $cols, $primary_key;

	public static $base_dir;

	// 2^24, 16 MBs
	const MAX_SIZE = 16777216;

	public static function set_table_definition()
	{
		self::$db = 'social';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'     ,'char'      ,16  ,''     ,rs::READ_ONLY),
			new rs_col('post_id','char'      ,16  ,''     ,rs::READ_ONLY),
			new rs_col('type'   ,'char'      ,16  ,''),
			new rs_col('name'   ,'char'      ,200 ,''),
			new rs_col('path'   ,'char'      ,255 ,''),
			new rs_col('data'   ,'mediumblob',null)
		);
	}

	public static function get_base_dir()
	{
		return \epro\CGI_PATH.'img/u/smo/swapp';
	}

	// 16 hex chars
	protected function uprimary_key($i)
	{
		$sha = sha1(mt_rand());
		return strtoupper(substr($sha, 0, 16));
	}

	public function delete()
	{
		if (!empty($this->path) && file_exists($this->path)) {
			unlink($this->path);
		}
		return parent::delete();
	}
}

class network_post extends rs_object
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
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

class facebook_post extends network_post
{
	public static $db, $cols, $primary_key;

	private static $val_keys = false;

	const MAX_MESSAGE_LENGTH = 512;
	
	public static function set_table_definition()
	{
		self::$db = 'social';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'         ,'char'   ,16  ,'',rs::READ_ONLY),
			new rs_col('album_id'   ,'char'   ,32  ,''),
			new rs_col('link'       ,'varchar',512 ,''),
			new rs_col('link_name'  ,'char'   ,200 ,''),
			new rs_col('picture_url','varchar',512 ,''),
			new rs_col('caption'    ,'varchar',512 ,''),
			new rs_col('description','varchar',1000,'')
		);
	}

	private static function get_val_keys()
	{
		if (empty(self::$val_keys)) {
			self::$val_keys = array();
			list($cols) = self::attrs('cols');
			foreach ($cols as $col_name => $col) {
				if (empty($col->ancestor_table) && !($col->attrs & rs::READ_ONLY)) {
					self::$val_keys[] = $col_name;
				}
			}
		}
		return self::$val_keys;
	}

	private static function get_saved_values($mod)
	{
		// we assume all non-read only cols should be gotten
		$keys = self::get_val_keys();
		// $keys = array_slice(func_get_args(), 1);
		$vals = array_combine($keys, array_fill(0, count($keys), ''));

		// set sources we can get values from
		// we want to check fb post before mod
		$sources = array();
		if ($mod->is_edit) {
			$facebook_post = $mod->post->network_post->find('network', 'facebook');
			if ($facebook_post) {
				$sources[] = $facebook_post;
			}
		}
		$sources[] = $mod;
		foreach ($keys as $key) {
			foreach ($sources as $src) {
				if (!empty($src->$key)) {
					$vals[$key] = $src->$key;
					break;
				}
			}
		}
		return $vals;
	}

	public function can_retry_on_error()
	{
		switch ($this->error) {
			case ('An unexpected error has occurred. Please retry your request later.'):
				return true;

			default:
				return false;
		}
	}

	// facebook image response contains id and post_id fields
	// post_id seems to be the actual post_id, id is..?
	public function process_response($api, &$r)
	{
		if (isset($r['post_id'])) {
			$r['id'] = $r['post_id'];
		}
		return parent::process_response($api, $r);
	}

	public static function print_network_inputs($mod)
	{
		// $vals = self::get_saved_values($mod, 'link', 'link_name', 'picture_url', 'caption', 'album_id');
		$vals = self::get_saved_values($mod);
		?>
		<tr id="album_tr" class="facebook" network="facebook" network_toggle_callback="album">
			<td>Album <?= $mod->ml_network_image('facebook', 'small') ?></td>
			<td id="album_select_td">
				<?= cgi::html_select('album_id', $mod->album_options, $vals['album_id']) ?>
				<a id="refresh_albums_link" href="" class="ajax">Refresh Albums</a>
				<?= cgi::loading(true) ?>
			</td>
		</tr>
		<tr class="facebook" network="facebook">
			<td>Link <?= $mod->ml_network_image('facebook', 'small') ?></td>
			<td><input class="wide" type="text" name="link" id="link" value="<?= $vals['link'] ?>" /></td>
		</tr>
		<tr class="facebook" network="facebook">
			<td>Link Text <?= $mod->ml_network_image('facebook', 'small') ?></td>
			<td><input class="wide" type="text" name="link_name" id="link_name" value="<?= $vals['link_name'] ?>" /></td>
		</tr>
		<tr class="facebook" network="facebook">
			<td>Picture URL <?= $mod->ml_network_image('facebook', 'small') ?></td>
			<td><input class="wide" type="text" name="picture_url" id="picture_url" value="<?= $vals['picture_url'] ?>" /></td>
		</tr>
		<tr class="facebook" network="facebook">
			<td>Caption <?= $mod->ml_network_image('facebook', 'small') ?></td>
			<td><input class="wide" type="text" name="caption" id="caption" value="<?= $vals['caption'] ?>" /></td>
		</tr>
		<tr class="facebook" network="facebook">
			<td>Description <?= $mod->ml_network_image('facebook', 'small') ?></td>
			<td><textarea name="description" id="description"><?= $vals['description'] ?></textarea></td>
		</tr>
		<?php
	}

	public static function get_post_data()
	{
		return array_intersect_key($_POST, array_flip(self::get_val_keys()));
	}
}

class twitter_post extends network_post
{
	public static $db, $cols, $primary_key;

	const MAX_MESSAGE_LENGTH = 140;

	public static function set_table_definition()
	{
		self::$db = 'social';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'         ,'char'    ,16 ,''      ,rs::READ_ONLY)
		);
	}

	public function process_response($api, $r)
	{
		if ($r !== false) {
			$r['id'] = $r['id_str'];
		}
		return parent::process_response($api, $r);
	}
	
	public function can_retry_on_error()
	{
		switch ($this->error) {
			case ('Over capacity.'):
				return true;

			default:
				return false;
		}
	}
}

// for some errors, we can retry a post
// if the retry is successful, error would be over written
// so we keep a log for historical purposes, to go with
// the error field in the network post table, which represents
// the error of the most recent post (or lack thereof)
class network_post_error extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	private static $val_keys = false;

	public static function set_table_definition()
	{
		self::$db = 'social';
		self::$primary_key = array('id');
		self::$indexes = array(array('network_post_id'));
		self::$cols = self::init_cols(
			new rs_col('id'             ,'int'    ,null,null     ,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('network_post_id','char'   ,16   ,''      ,rs::READ_ONLY),
			new rs_col('posted_at'      ,'datetime',null,rs::DDT),
			new rs_col('error'          ,'char'    ,200 ,''      )
		);
	}
}

?>