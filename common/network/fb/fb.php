<?php
require_once(__DIR__.'/src/facebook.php');
require_once(__DIR__.'/../network.php'); // for localhost testing.. when in epro, would be automatially required


// http://stackoverflow.com/questions/12706228/how-do-i-get-a-page-access-token-that-does-not-expire
// server auth: https://developers.facebook.com/docs/howtos/login/server-side-login/
// class is called fb rather than facebook because facebook implementation uses that name
// todo: stop using library supplied facebook class, create own that does not
//  use SESSION, and name it something else so we can use facebook name here

class fb extends network
{
	private $f;

	public function __construct($auth, $facebook)
	{
		$this->auth = $auth;
		$this->f = $facebook;
	}

	public function get_pages()
	{
		try {
			$r = $this->f->api('/me/accounts');
			return $r['data'];
		}
		catch (Exception $e) {
			$this->e = $e;
			return false;
		}
	}

	public function get_tabs()
	{
		try {
			$r = $this->f->api('/'.$this->auth->page_id.'/tabs');
			return $r['data'];
		}
		catch (Exception $e) {
			$this->e = $e;
			return false;
		}
	}

	public function post($data)
	{
		try {
			$mdata = $this->marketize('post', $data);
			if (isset($mdata['image'])) {
				$url_path = '/'.$mdata['album_id'].'/photos';
				$this->f->setFileUploadSupport(true);
			}
			else {
				$url_path = '/'.$this->auth->page_id.'/feed';
			}
			$this->f->logHookTmpData = array('post_id' => $data->post_id);
			$r = $this->f->api($url_path, 'post', $mdata);
			return $r;
		}
		catch (Exception $e) {
			$this->e = $e;
			return false;
		}
	}

	public function get_post($post_id)
	{
		try {
			$r = $this->f->api('/'.$post_id);
			return $r;
		}
		catch (Exception $e) {
			$this->e = $e;
			return false;
		}
	}

	public function create_album($data)
	{
		try {
			$r = $this->f->api('/'.$this->auth->page_id.'/albums', 'post', $data);
			return $r;
		}
		catch (Exception $e) {
			$this->e = $e;
			return false;
		}
	}

	public function get_albums()
	{
		try {
			$r = $this->f->api('/'.$this->auth->page_id.'/albums');
			return $r['data'];
		}
		catch (Exception $e) {
			$this->e = $e;
			return false;
		}
	}

	protected function post_get_media($data, $v)
	{
		return ('@'.$v);
	}

	protected function marketize_get_key_map($type, $data)
	{
		switch ($type) {
			case ('post'): return array(
					'message' => 'message',
					'link' => 'link',
					'link_name' => 'name',
					'picture_url' => 'picture',
					'caption' => 'caption',
					'description' => 'description',
					'album_id' => 'album_id',
					'media_path' => array('image', 'post_get_media')
				);
			default: return false;
		}
	}

	protected function marketize_get_extra($type, $data)
	{
		switch ($type) {
			case ('post'): return array(
					'access_token' => $this->auth->page_token
				);
			default: return false;
		}
	}
	
	public static function get_from_account($account_id)
	{
		$auth = new facebook_auth(array('account_id' => $account_id));
		if (isset($auth->app_id) && isset($auth->access_token)) {
			$facebook = new Facebook(array(
				'appId'  => $auth->app_id,
				'secret' => $auth->app_secret,
				'logPath' => \epro\WPROPHP_PATH.'logs/apis/f/'.php_sapi_name().'_log.txt',
				'logHook' => array('network_log', 'new_entry'),
				'logHookData' => array('facebook')
			));
			$facebook->setAccessToken($auth->access_token);
			$user_id = $facebook->getUser();
			if ($user_id) {
				return new fb($auth, $facebook);
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}

	public static function authorize_app($callee, $init_data = array())
	{
		// db::dbg();
		// echo "<pre>";
		// var_dump($_GET);
		// var_dump($_POST);
		// var_dump($_SESSION);
		// var_dump($_SERVER);
		// echo "</pre>";

		if (isset($_REQUEST["code"])) {
			return self::auth_process_callback($callee);
		}
		else {
			self::auth_app_info_form($callee);
			return false;
		}
	}

	private static function auth_get_data($account_id)
	{
		return db::select_row("
			select * from social.facebook_auth where account_id = '".db::escape($account_id)."'
		", 'ASSOC');
	}

	private static function auth_process_callback($callee)
	{
		$auth_data = self::auth_get_data($_SESSION['fbauth_account_id']);
		$code = $_REQUEST['code'];
		$token_url = "https://graph.facebook.com/oauth/access_token?"
			."client_id={$auth_data['app_id']}&"
			."redirect_uri=".urlencode(self::auth_get_redirect_uri())."&"
			."client_secret={$auth_data['app_secret']}&"
			."code={$code}"
		;
		echo "temp token url={$token_url}<br>\n";

		$response = util::get_url_contents($token_url);
		echo "token response:<br /><pre>\n";
		var_dump($response);
		echo serialize($response);
		echo "</pre>\n";
		// file_put_contents('/tmp/face-resp.txt', serialize($response));

		$params = null;
		parse_str($response, $params);

		if (!isset($params['access_token'])) {
			echo "could not get temp access token<br>\n";
			return false;
		}
		$access_token = $params['access_token'];

		// todo? instantiate facebook object and call setExtendedAccessToken
		// then call getPersistentData('access_token') and save it in our db

		$long_lived_url = "https://graph.facebook.com/oauth/access_token?"
			."client_id={$auth_data['app_id']}&"
			."client_secret={$auth_data['app_secret']}&"
			."grant_type=fb_exchange_token&"
			."fb_exchange_token={$access_token}";

		echo "long_lived_url={$long_lived_url}<br>\n";

		$response = util::get_url_contents($long_lived_url);
		echo "long lived response:<br /><pre>\n";
		var_dump($response);
		echo serialize($response);
		echo "</pre>\n";

		$params = null;
		parse_str($response, $params);

		if ($params) {
			$reply_keys = array('access_token', 'expires');
			$data = array();
			foreach ($reply_keys as $key) {
				if (isset($params[$key])) {
					$data[$key] = $params[$key];
				}
			}
			if ($data['expires']) {
				$data['expires_at'] = date('Y-m-d H:i:s', time() + $data['expires']);
			}
			db::update("social.facebook_auth", $data, "account_id = '".db::escape($auth_data['account_id'])."'");
			return true;
		}
		return false;
	}

	private static function auth_process_app_form($callee)
	{
		$post_keys = array('account_id', 'app_id', 'app_secret');
		$data = array();
		foreach ($post_keys as $key) {
			$data[$key] = $_POST[$key];
		}
		db::insert_update("social.facebook_auth", array("account_id"), $data);

		$_SESSION['fbauth_state'] = md5(uniqid(rand(), TRUE));
		$_SESSION['fbauth_account_id'] = $_POST['account_id'];
		$dialog_url = "https://www.facebook.com/dialog/oauth?"
			."client_id={$_POST['app_id']}&"
			."redirect_uri=".urlencode(self::auth_get_redirect_uri())."&"
			."response_type=code&"
			."state={$_SESSION['fbauth_state']}&"
			."scope=read_stream,manage_pages,publish_actions,publish_stream,create_event,ads_management,user_activities,user_events,user_interests,user_likes,user_photos,user_status,user_subscriptions"
		;
		return $dialog_url;
	}

	private static function auth_app_info_form($callee)
	{
		if (isset($_POST['app_id'])) {
			$auth_url = self::auth_process_app_form($callee);
			$ml_action = '
				<tr>
					<td colspan="2">
						<a href="'.$auth_url.'">Get Facebook Authorization</a>
						You will be redirected back here, where you can select the page to tie the app to.
					</td>
				</tr>
			';
		}
		else {
			$ml_action = '
				<tr>
					<td></td>
					<td><input type="submit" value="Continue"></td>
				</tr>
			';
		}
		self::auth_hook($callee, 'pre_app_info_form');
		?>
		<div class="instructions">
			<h3>Instructions</h3>
			<ol>
				<li>Log in to the wpromote facebook account</li>
				<li>From the home page, click the "Developer" section along the left nav</li>
				<li>Click "+ Create New App" in the top right</li>
				<li>Name the app (this will appear when posting) (can leave App Namespace blank)</li>
				<li>Complete CAPTCHA</li>
				<li>For "App Domains" enter <?= \epro\DOMAIN ?></li>
				<li>For "Select how your app integrates with Facebook" click "Website with Facebook login"</li>
				<li>Enter http://<?= \epro\DOMAIN ?>/smo/client/swapp/facebook_auth for the site URL</li>
				<li>Press "Save Changes"</li>
				<li>Go to advanced settings and set "Stream post URL security" to "Disabled"</li>
				<li>Copy and paste the App ID and App Secret into the form below and press "Continue"</li>
				<li>This will do a couple things in the e2 database and should produce a link for you to click</li>
				<li>Click link to go to facebook, where you can authorize the app</li>
				<li>After authorizing, you should be redirected back to e2</li>
				<li>Select the Facebook Page to tie the app to</li>
				<li>Done!</li>
				<li>* You will still need to tie the Facebook Page to e2. Please click the Facebook link in e2 to reload the page</li>
			</ol>
		</div>
		<table>
			<tbody>
				<?= self::auth_hook($callee, 'get_account_id_input') ?>
				<tr>
					<td>App ID</td>
					<td><input type="text" name="app_id" value="<?= $_POST['app_id'] ?>"></td>
				</tr>
				<tr>
					<td>App Secret</td>
					<td><input type="text" name="app_secret" value="<?= $_POST['app_secret'] ?>"></td>
				</tr>
				<?= $ml_action ?>
			</tbody>
		</table>
		<?php
		self::auth_hook($callee, 'post_app_info_form');
	}
}

// for development
// facebook requires callback URL for authentication,
// localhost is probably most generic callback domain,
// so we give our dev ap in facebook the callback url
// http://localhost/fb.php, up to local env
// to point that here (symlink, etc)
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'fb.php')) {
	session_start();
	// callbacks for authorize app
	// also implemented in e2 (current: /smo/client/swapp/facebook_auth. some day: /account/service/smo/swapp/facebook_auth)
	class local_facebook_auth
	{
		public function __construct()
		{
			// load env and db
			require(__DIR__.'/../env.php');
			require(__DIR__.'/../../lib_db.php');
			db::connect(\epro\DB_HOST, \epro\DB_USER, \epro\DB_PASS, \epro\DB_NAME);
		}

		public function auth_hook_pre_app_info_form()
		{
			?>
<!doctype html>
<html>
<body>
<form method="post">
			<?php
		}

		public function auth_hook_post_app_info_form()
		{
			?>
</form>
</html>
			<?php
		}

		public function auth_hook_get_account_id_input()
		{
			?>
				<tr>
					<td>Account ID</td>
					<td><input type="text" name="account_id" value="<?= $_POST['account_id'] ?>"></td>
				</tr>
			<?php
		}
	}
	$local_auth = new local_facebook_auth();
	fb::authorize_app($local_auth);
}

?>