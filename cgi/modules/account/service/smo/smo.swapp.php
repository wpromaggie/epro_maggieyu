<?php

class mod_account_service_smo_swapp extends mod_account_service_smo
{
	// todo: get this value once a day from https://dev.twitter.com/docs/api/1.1/get/help/configuration
	const TWITTER_URL_LEN = 22;
	const TWITTER_MEDIA_LEN = 23;

	public function pre_output()
	{
		parent::pre_output();
		$this->display_default = 'dashboard';
		$this->page_menu = array(
			array('dashboard', 'Dashboard'),
			array('post', 'Post'),
			array('schedule', 'Schedule')
		);
		foreach (smo_lib::$networks as $network => $network_info) {
			$this->page_menu[] = array($network, util::display_text($network));
		}
	}

	public function pre_output_facebook_auth()
	{
		if (!isset($this->account) && isset($_SESSION['fbauth_account_id'])) {
			$this->init_account($_SESSION['fbauth_account_id']);
		}
	}

	protected function init_account($aid)
	{
		$this->dept = 'smo';
		$this->aid = $aid;
		$this->account = new as_smo(array('id' => $this->aid));
	}

	public function display_dashboard()
	{
		$auth_info = $this->get_authorized_networks();
		foreach (smo_lib::$networks as $network => $network_info) {
			$style = (isset($auth_info->{$network.'_auth'}->account_id)) ? '' : ' style="opacity:.21;"';
			echo '<img src="'.cgi::href('img/'.$network.'.png').'"'.$style.' />';
		}
	}

	private function get_authorized_networks()
	{
		return new account(array('id' => $this->aid), array(
			'select' => array(
				"facebook_auth" => array("account_id as faid", "page_id"),
				"twitter_auth" => array("account_id as taid", "screen_name")
			),
			'left_join' => array(
				"facebook_auth" => "account.id = facebook_auth.account_id",
				"twitter_auth" => "account.id = twitter_auth.account_id"
			),
			'de_alias' => true
		));
	}

	private function ml_network_options()
	{
		$auth_info = $this->get_authorized_networks();
		$authorized_networks = array();
		foreach (smo_lib::$networks as $network => $network_info) {
			if (isset($auth_info->{$network.'_auth'}->account_id)) {
				$authorized_networks[] = $network;
			}
		}
		if (empty($authorized_networks)) {
			return false;
		}

		$networks_on = ($this->is_edit) ? $this->post->network_post->network : (($this->posted_networks) ? $this->posted_networks : $authorized_networks);
		$ml = '';
		foreach ($authorized_networks as $network) {
			// set the opposite on/off class, so we can "click" the network in js
			$status = (in_array($network, $networks_on)) ? 'off' : 'on';
			$ml .= '<div class="w_network"><img class="network '.$status.'" network="'.$network.'" src="'.cgi::href('img/'.$network.'.png').'" /></div>';
		}
		return '
			<div class="network_options">
				'.$ml.'
				<div class="clr"></div>
				<input type="hidden" name="networks" id="networks" value="'.implode("\t", $networks_on).'" />
			</div>
		';
	}

	public function pre_output_post()
	{

		cgi::add_js('twitter-text.js');

		if (empty($_REQUEST['pid'])) {
			$this->is_edit = false;
		}
		else {
			$this->is_edit = true;
			$this->pid = $_REQUEST['pid'];
			$this->post = new post(array('id' => $this->pid), array(
				'select' => array(
					"post" => array("id", "separate_messages", "scheduled", "has_posted"),
					"post_media" => array("id as mid", "path"),
					"network_post" => array("id as nid", "message", "network", "error"),
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
		}

		$this->album_options = facebook_album::get_all(array(
			'select' => array("facebook_album" => array("id as value", "concat(name, ' (', id, ')') as text")),
			'where' => "account_id = :aid",
			'data' => array("aid" => $this->aid),
			'order_by' => "text asc"
		))->to_array();
		$fauth = new facebook_auth(array('account_id' => $this->aid), array('select' => 'album_id'));
		$this->album_id = $fauth->album_id;

		$this->ml_other_buttons = '';
		$this->network_messages = array();

		// new
		if (!$this->is_edit) {
			$this->post_submitted = false;
			if (empty($_POST)) {
				$this->separate_messages = 0;
				$this->message = '';
				$this->when = 'Now';
				$this->when_date = '';
				$this->when_time = '';
			}
			// something was posted and an error occured
			else {
				$this->separate_messages = $_POST['separate_messages'];
				$this->message = $_POST['message'];
				$this->when = $_POST['when'];
				$this->when_date = $_POST['when_date'];
				$this->when_time = $_POST['when_time'];
				$this->posted_networks = explode("\t", $_POST['networks']);
				if ($this->separate_messages) {
					$this->network_messages = array();
					foreach (smo_lib::$networks as $network => $network_info) {
						$this->network_messages[$network] = $_POST[$network.'_message'];
					}
				}
			}
		}
		// edit
		else {
			$this->post_submitted = $this->post->has_posted;
			$this->separate_messages = $this->post->separate_messages;
			foreach ($this->post->network_post as $npost) {
				$this->network_messages[$npost->network] = $npost->message;
				if (!$this->separate_messages && empty($this->message)) {
					$this->message = $npost->message;
				}
			}
			$this->when = 'At';
			$this->when_date = substr($this->post->scheduled, 0, 10);
			$this->when_time = substr($this->post->scheduled, 11, 5);
			if (!$this->post->has_posted) {
				$this->ml_other_buttons = '<input type="submit" id="delete_button" a0="action_delete_post" value="Delete" />';
			}
			$fpost = $this->post->network_post->find('network', 'facebook');
			if ($fpost !== false && !empty($fpost->album_id)) {
				$this->album_id = $fpost->album_id;
			}
		}
	}

	public function display_post()
	{
		$ml_networks = $this->ml_network_options();
		// no networks authorized, cannot post
		if ($ml_networks === false) {
			feedback::add_msg('No networks have been authorized. Please use the network links to set up an app.');
			return;
		}
		?>
		<h1 id="post_title"><?= ($this->is_edit) ? 'EDIT' : 'NEW' ?> POST</h1>
		<?= $this->print_post_results() ?>
		<table>
			<tbody id="post_table">
				<tr>
					<td>Networks</td>
					<td><?= $ml_networks ?></td>
				</tr>
				<tr>
					<td>When</td>
					<td>
						<?= cgi::html_radio('when', array('Now', 'At'), $this->when, array('separator' => ' &nbsp; ')) ?>
						<table id="when_dt">
							<tbody>
								<tr>
									<td>Date</td>
									<td><input type="text" class="when_dt date_input" id="when_date" name="when_date" value="<?= $this->when_date ?>" /></td>
								</tr>
								<tr>
									<td>Time</td>
									<td><input type="text" class="when_dt time_input" m_step="5" m_per_row="3" id="when_time" name="when_time" value="<?= $this->when_time ?>" /></td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
				<tr>
					<td>Separate Messages</td>
					<td><?= cgi::html_radio('separate_messages', array(array(0, 'No'), array(1, 'Yes')), $this->separate_messages, array('separator' => ' &nbsp; ')) ?></td>
				</tr>
				<tr id="message_tr">
					<td>Message</td>
					<td>
						<div>
							<textarea class="message" name="message" id="message"><?= $this->message ?></textarea>
							<span class="chars_left" driver="message" max="<?= twitter::MAX_POST_LEN ?>"></span>
						</div>
						<?= $this->ml_twitter_media_note() ?>
					</td>
				</tr>
				<?= $this->print_network_message_boxes() ?>
				<tr>
					<td>Media</td>
					<td>
						<div>
							<input type="file" id="media" name="media" />
							<input type="submit" class="small_button hide" id="remove_media" value="remove_media" />
						</div>
						<div><?= $this->print_media() ?></div>
					</td>
				</tr>
				<?= $this->print_network_inputs() ?>
				<tr>
					<td></td>
					<td>
						<input type="submit" id="submit_button" a0="action_post_submit" value="Submit"<?= ($this->post_submitted) ? ' class="hide"' : '' ?> />
						<?= $this->ml_other_buttons ?>
						<?= ($this->is_edit) ? '<a class="ajax" id="copy_post" href="">Copy As New</a>' : '' ?>
					</td>
				</tr>
			</tbody>
		</table>
		<input type="hidden" name="twitter_msg_len" id="twitter_msg_len" value="0" />
		<?php
		cgi::add_js_var('TWITTER_URL_LEN', self::TWITTER_URL_LEN);
		cgi::add_js_var('TWITTER_MEDIA_LEN', self::TWITTER_MEDIA_LEN);
	}

	private function print_post_results()
	{
		if ($this->post_submitted) {
			$ml = '';
			$network_post_errors = array();
			foreach ($this->post->network_post as $npost) {
				if (empty($npost->error)) {
					$ml .= '<p class="success_msg">'.$npost->network.' post was successfull</p>';
				}
				else {
					$ml .= '<p class="error_msg">'.$npost->network.' post failed: '.$npost->error.'</p>';
					$network_post_errors[$npost->network] = 1;
				}
			}
			cgi::add_js_var('network_post_errors', $network_post_errors);
			echo '
				<div id="network_post_results">
					<fieldset>
						<legend>Post already submitted</legend>
						'.$ml.'
					</fieldset>
					<div class="clr"></div>
				</div>
			';
		}
	}

	private function print_network_message_boxes()
	{
		$ml = '';
		foreach (smo_lib::$networks as $network => $network_info) {
			$key = $network.'_message';
			$post_class = $network.'_post';
			$text = (isset($this->network_messages[$network])) ? $this->network_messages[$network] : '';
			$ml .= '
				<tr class="network_message '.$network.' hide" network="'.$network.'" network_toggle_callback="message">
					<td>Message '.$this->ml_network_image($network, 'small').'</td>
					<td>
						<div>
							<textarea class="message" name="'.$key.'" id="'.$key.'">'.$text.'</textarea>
							<span class="chars_left" driver="'.$key.'" max="'.$post_class::MAX_MESSAGE_LENGTH.'"></span>
						</div>
						'.(($network == 'twitter') ? $this->ml_twitter_media_note() : '').'
					</td>
				</tr>
			';
		}
		echo $ml;
	}

	private function ml_twitter_media_note()
	{
		return '
			<div class="twitter_msg_len_note">
				<p class="twitter_media_msg">* '.self::TWITTER_MEDIA_LEN.' added to length for twitter media url</p>
				<p class="twitter_other_urls_msg">* <span class="twitter_other_urls_count"></span> urls in message accounted for in characters left calculation</p>
			</div>';
	}

	private function print_network_inputs()
	{
		foreach (smo_lib::$networks as $network => $network_info) {
			$post_class = $network.'_post';
			$post_class::print_network_inputs($this);
		}
	}

	public function ml_network_image($network, $size)
	{
		return '<img src="'.cgi::href('img/'.$network.'.png').'" class="network_'.$size.'" />';
	}

	public function display_post_success()
	{
		// nothing
	}

	public function print_media()
	{
		$ml = '';
		if ($this->is_edit) {
			$ml .= '<span>Current Media: </span>';
			if (!empty($this->post->post_media->id)) {
				// include media id so that on "Copy As New" we can copy over the media
				$ml .= '
					<img id="current_media" src="'.cgi::href(str_replace(\epro\CGI_PATH, '', $this->post->post_media->path)).'" />
					<input type="hidden" name="media_id" id="media_id" value="'.$this->post->post_media->id.'" />
					<input type="checkbox" id="delete_media" name="delete_media" value="1" />
					<label for="delete_media">Delete Media</label>
				';
			}
			else {
				$ml .= '<span>None</span>';
			}
		}
		echo $ml;
	}

	public function action_delete_post()
	{
		if (isset($this->post->post_media->id)) {
			$this->post->post_media->delete();
			// todo: rm file
		}
		// delete network post records
		foreach ($this->post->network_post as $base_network_post) {
			$base_network_post->delete();
		}
		// delete job
		$r = job::delete_all(array(
			'where' => "type = 'SMO SCHEDULED POST' && fid = :fid",
			'data' => array('fid' => $this->post->id)
		));
		// delete post itself
		$this->post->delete();

		feedback::add_success_msg('Post deleted');

		// re-init everything without post id
		unset($_REQUEST['pid']);
		$this->pre_output_post();

		// remove param from url
		cgi::remove_url_param('pid');
	}

	public function action_post_submit()
	{
		//e($_POST); e($_FILES); db::dbg();

		// stuff we expect from extract
		$message = $separate_messages = $networks = $when = $when_date = $when_time = null;
		extract($_POST);

		$networks = explode("\t", trim($networks));
		if (empty($networks)) {
			feedback::add_error_msg("Please select at least one network");
			return false;
		};
		$created = date(util::DATE_TIME);
		if ($when == 'Now') {
			$do_post_now = 1;
			$when = $created;
		}
		else {
			$do_post_now = 0;
			$when_utime = strtotime("{$when_date} {$when_time}");
			if ($when_utime === false) {
				feedback::add_error_msg("Invalid date/time");
				return false;
			}
			$when = date(util::DATE_TIME, $when_utime);
			if ($when < $created) {
				if (!user::is_developer()){
					feedback::add_error_msg("Post date/time ($when) appears to be in the past");
					return false;
				}
			}
		}
		$media = false;
		if (!empty($_FILES['media']['name'])) {
			$upload_info = $_FILES['media'];
			if ($upload_info['size'] > post_media::MAX_SIZE) {
				feedback::add_error_msg("Media max file size exceeded: {$upload_info['size']} > ".post_media::MAX_SIZE);
				return false;
			}
			$media = array_intersect_key($upload_info, array('type' => 1, 'name' => 1));
		}
		// check twitter message length
		if (in_array('twitter', $networks)) {
			if ($_POST['twitter_msg_len'] > twitter::MAX_POST_LEN) {
				feedback::add_error_msg("Twitter message is over maximum of ".twitter::MAX_POST_LEN);
				return false;
			}
		}

		if ($this->is_edit) {
			$this->post->update_from_array(array(
				'do_post_now' => $do_post_now,
				'scheduled' => $when,
				'separate_messages' => $separate_messages
			));
		}
		else {
			$this->post = post::create(array(
				'user_id' => user::$id,
				'account_id' => $this->aid,
				'created' => $created,
				'do_post_now' => $do_post_now,
				'scheduled' => $when,
				'separate_messages' => $separate_messages
			));
			$this->post->network_post = network_post::new_array();
		}
		/*
		 * handle media
		 */
		if (!empty($media)) {
			$media['path'] = nest::put(post_media::get_base_dir(), $upload_info['tmp_name'], $media['name']);
			// $media['data'] = file_get_contents($upload_info['tmp_name']);

			// media not required, so we check for media id here instead of edit/new
			if (isset($this->post->post_media->id)) {
				$this->post->post_media->update_from_array($media);
			}
			else {
				$media['post_id'] = $this->post->id;
				$this->post->post_media = post_media::create($media);
			}
		}
		// non empty on edits/copy as new.
		// we don't care about it if it is an edit
		else if (!empty($_POST['media_id']) && !$this->is_edit) {
			if (empty($_POST['delete_media'])) {
				$this->post->post_media = new post_media(array('id' => $_POST['media_id']));
				$this->post->post_media->post_id = $this->post->id;
				unset($this->post->post_media->id);
				$this->post->post_media->insert();
			}
		}
		// else if: if there was media, we can ignore this
		else if (!empty($_POST['delete_media']) && !empty($this->post->post_media->id)) {
			$this->post->post_media->delete();
		}

		foreach (smo_lib::$networks as $network => $network_info) {
			$do_make_new = false;
			$class = $network.'_post';

			if ($separate_messages) {
				$network_message = $_POST[$network.'_message'];
			}
			else {
				$network_message = $message;
			}
			$network_data = false;

			// edit
			if ($this->is_edit) {
				$edit_network = $this->post->network_post->find('network', $network);
				$in_new = (in_array($network, $networks));
				if ($in_new) {
					// update
					if ($edit_network !== false) {
						// get array of network specific data
						$network_data = $class::get_post_data();
						$edit_network->update_from_array(array_merge($network_data, array(
							'message' => $network_message
						)));
					}
					// insert
					else {
						$do_make_new = true;
					}
				}
				else {
					// deleted
					if ($edit_network !== false) {
						// delete from database
						$edit_network->delete();
						 // remove from our post object
						$this->post->network_post->remove($edit_network);
					}
					else {
						// nothing to do
					}
				}
			}
			// new
			else {
				// only care if network was on for new posts
				if (in_array($network, $networks)) {
					$do_make_new = true;
				}
			}
			// same for edits and new
			if ($do_make_new) {
				// get array of network specific data
				$network_data = $class::get_post_data();
				$this->post->network_post->push($class::create(array_merge($network_data, array(
					'post_id' => $this->post->id,
					'network' => $network,
					'message' => $network_message
				))));
			}
			// update default facebook album id if it different from what we had before
			if (!empty($network_data['album_id'])) {
				if ($network_data['album_id'] !== $this->album_id) {
					facebook_auth::update_all(array(
						'set' => array("album_id" => $network_data['album_id']),
						'where' => "account_id = :aid",
						'data' => array("aid" => $this->aid)
					));
				}
			}
		}
		if (!empty($do_post_now)) {
			// todo? this is neat, but for consistency's sake, I think we should
			// probably just queue a job here so all posts are submitted in the same
			// place, ie by worker_smo_scheduled_post (job::queue())
			$r = $this->post->submit();
			$this->switch_display('post_success');
		}
		else {
			// edit, update job
			if ($this->is_edit) {
				$r = job::update_all(array(
					'set' => array('scheduled' => $this->post->scheduled),
					'where' => "type = 'SMO SCHEDULED POST' && fid = :fid",
					'data' => array('fid' => $this->post->id)
				));
			}
			// new, create job
			else {
				$r = job::schedule(array(
					'type' => 'SMO SCHEDULED POST',
					'scheduled' => $this->post->scheduled,
					'fid' => $this->post->id,
					'account_id' => $this->aid
				));
			}
			if ($r !== false) {
				feedback::add_success_msg('Post successfully scheduled');
			}
			else {
				feedback::add_error_msg('Error scheduling post');
			}
		}
		if ($this->is_edit) {
			$this->pre_output_post();
		}
		// todo: send newly created post id to js to be added as url param,
		//  show edit form for new post
	}

	public function display_facebook()
	{
		$auth_info = new facebook_auth(array('account_id' => $this->aid), array(
			'select' => array(
				"facebook_auth" => array("*"),
				"facebook_page" => array("name")
			),
			'left_join' => array(
				"facebook_page" => "facebook_page.id = facebook_auth.page_id"
			)
		));
		if (!empty($auth_info->access_token)) {
			$ml_current = $this->ml_facebook_auth_info($auth_info);
			$ml_auth_text = 'Re-Authorize App';
		}
		else {
			$ml_current = '';
			$ml_auth_text = 'Authorize App';
		}
		?>
		<a href="<?= $this->rhref('facebook_auth?aid='.$this->aid) ?>"><?= $ml_auth_text ?></a>
		<?= $ml_current ?>
		<?php
	}

	private function ml_facebook_auth_info($auth_info)
	{
		?>
		<table>
			<tbody>
				<tr>
					<td>Page</td>
					<td>
						<p>
							<span><?= $this->print_facebook_page($auth_info) ?></span>
							<a id="f_set_page" class="ajax" href="">Set Page</a>
						</p>
						<p id="w_f_set_page_loading" class="hide">
							<img src="<?= cgi::href('img/loading.gif') ?>" />
						</p>
						<p id="w_f_set_page" class="hide">
							<select name="f_page" id="f_page"></select>
							<input type="submit" a0="action_facebook_set_page" value="Set Page" />
						</p>
					</td>
				</tr>
				<tr>
					<td>App ID</td>
					<td><?= $auth_info->app_id ?></td>
				</tr>
				<tr>
					<td>Expires</td>
					<td><?= $auth_info->expires_at ?></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	public function display_twitter()
	{
		$auth_info = new twitter_auth(array('account_id' => $this->aid));
		if (!empty($auth_info->user_id)) {
			$ml_current = $this->ml_twitter_auth_info($auth_info);
			$ml_auth_text = 'Re-Authorize App';
		}
		else {
			$ml_current = '';
			$ml_auth_text = 'Authorize App';
		}
		?>
		<a href="<?= $this->rhref('twitter_auth?aid='.$this->aid) ?>"><?= $ml_auth_text ?></a>
		<?= $ml_current ?>
		<?php
	}

	private function ml_twitter_auth_info($auth_info)
	{
		?>
		<table>
			<tbody>
				<tr>
					<td>Screen Name</td>
					<td><?= $auth_info->screen_name ?></td>
				</tr>
				<tr>
					<td>User ID</td>
					<td><?= $auth_info->user_id ?></td>
				</tr>
				<tr>
					<td>Consumer Key</td>
					<td><?= $auth_info->consumer_key ?></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	public function action_facebook_set_page()
	{
		$page_id = $_POST['f_page'];
		$page = new facebook_page(array('id' => $page_id));
		$r = facebook_auth::update_all(array(
			'set' => array(
				'page_id' => $page_id,
				'page_token' => $page->access_token
			),
			'where' => "account_id = :aid",
			'data' => array("aid" => $this->aid)
		));
		if ($r !== false) {
			feedback::add_success_msg('Page updated');
		}
		else {
			feedback::add_success_msg('Error updating page: '.rs::get_error());
		}
	}

	private function print_facebook_page($auth_info)
	{
		if (isset($auth_info->facebook_page->name)) {
			echo $auth_info->facebook_page->name;
		}
		else {
			$page = new facebook_page(array('id' => $page_id));
			if ($page->name) {
				echo $page->name;
			}
			else {
				echo $page_id;
			}
		}
	}

	public function ajax_facebook_get_pages()
	{
		$auth_info = new facebook_auth(array('account_id' => $this->aid));
		$this->fb = fb::get_from_account($this->aid);
		$pages = smo_lib::facebook_refresh_pages($this->fb);
		$options = array();
		foreach ($pages as $page) {
			$options[] = array($page['id'], $page['name']);
		}
		echo json_encode($options);
	}

	public function pre_output_schedule()
	{
		cgi::include_widget('calendar');
		require(__DIR__.'/wid.post_calendar.php');
	}

	public function display_schedule()
	{
		$cal = new post_calendar($this, $this->aid);
		$cal->output();
	}

	public function display_facebook_auth()
	{
		if (util::is_dev()) {
			echo 'You cannot authorize facebook from e2 local. Please see common/network/fb/fb.php for more.';
		}
		$r = fb::authorize_app($this);
		if ($r === true) {
			echo '
				<b>
				To complete process, please <a href="'.$this->href('facebook/?aid='.$this->aid).'">set the page</a>.
				Even if the page is already set, you must set it again. Thank you!
				</b>
			';
			feedback::add_success_msg('Success!');
		}
	}

	public function auth_hook_get_account_id_input()
	{
		echo '<input type="hidden" name="account_id" value="'.$this->aid.'">';
	}

	public function display_twitter_auth()
	{
		$r = twitter::authorize_app($this, array(
			'account_id' => $this->aid,
			'consumer_key' => twitter::SWAPP_CONSUMER_KEY,
			'consumer_secret' => twitter::SWAPP_CONSUMER_SECRET
		));
		if ($r === true) {
			feedback::add_success_msg('Success!');
		}
	}

	public function ajax_refresh_albums()
	{
		$fapi = fb::get_from_account($this->aid);
		$albums = $fapi->get_albums();
		if ($albums === false) {
			echo json_encode(array('error' => $fapi->get_error()));
		}
		else {
			$options = array();
			$prev_ids = db::select("select id from social.facebook_album where account_id = :aid", array("aid" => $this->aid));
			$new_ids = array();
			foreach ($albums as $album) {
				$new_ids[] = $album['id'];
				db::insert_update("social.facebook_album", array('id'), array(
					'id' => $album['id'],
					'account_id' => $this->aid,
					'page_id' => $fapi->auth->page_id,
					'name' => $album['name']
				));
				$options[] = array($album['id'], "{$album['name']} ({$album['id']})");
			}
			$obsolete_ids = array_diff($prev_ids, $new_ids);
			if ($obsolete_ids) {
				db::delete("social.facebook_album", "id in ('".implode("','", $obsolete_ids)."')");
			}
			echo json_encode($options);
		}
	}
}

?>