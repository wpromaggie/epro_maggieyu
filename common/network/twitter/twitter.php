<?php
require_once(__DIR__.'/src/codebird.php');

// see towards bottom of file for dev auth app notes

class twitter extends network
{
	// unlike facebook, twitter does not show app name when posts are made via api
	// so we only need one app. this is the consumer info for that pp
	const SWAPP_CONSUMER_KEY = 'xGUQhqwUdzoMG4OnxuI7fw';
	const SWAPP_CONSUMER_SECRET = 'UeJpuTr6QV4ezKAKhve3pfjphujAkf1ejJeaD79pfY';

	const MAX_POST_LEN = 140;
	
	// ref to codebird
	private $cb;

	public function __construct($auth, $cb)
	{
		$this->auth = $auth;
		$this->cb = $cb;
	}

	public function post($tpost)
	{
		try {
			$mdata = $this->marketize('post', $tpost);
			if (isset($mdata['media[]'])) {
				$func = 'statuses_updateWithMedia';
			}
			else {
				$func = 'statuses_update';	
			}
			$r = $this->process_response($this->cb->$func($mdata));
			return $r;
		}
		catch (Exception $e) {
			$this->e = $e;
			return false;
		}
	}

	private function process_response($r)
	{
		if (isset($r['errors'])) {
			$msg = '';
			foreach ($r['errors'] as $error) {
				$msg .= (($msg) ? ' ' : '').$error['message'].'.';
			}
			throw new Exception($msg);
		}
		else {
			return $r;
		}
	}

	protected function marketize_get_key_map($type, $data)
	{
		switch ($type) {
			case ('post'): return array(
					'message' => 'status',
					'media_path' => 'media[]'
				);
			default: return false;
		}
	}

	protected function marketize_get_extra($type, $data)
	{
		switch ($type) {
			default: return false;
		}
	}

	public static function get_from_account($account_id)
	{
		$auth = new twitter_auth(array('account_id' => $account_id));
		if (isset($auth->consumer_key) && isset($auth->oauth_token)) {
			$cb = self::init_cb($auth->consumer_key, $auth->consumer_secret);
			$cb->setToken($auth->oauth_token, $auth->oauth_token_secret);
			return new twitter($auth, $cb);
		}
		else {
			return false;
		}
	}

	public static function init_cb($key, $secret)
	{
		\Codebird\Codebird::setConsumerKey($key, $secret);
		$cb = \Codebird\Codebird::getInstance();
		$cb->setReturnFormat(CODEBIRD_RETURNFORMAT_ARRAY);
		return $cb;
	}

	private static function auth_consumer_info_form($callee, $init_data)
	{
		$data = array_merge($_POST, $init_data);
		if (isset($_POST['consumer_key'])) {
			$auth_url = self::auth_request_token($callee, $_POST);
			$ml_pin = '
				<tr>
					<td colspan="2"><a target="_blank" href="'.$auth_url.'">Get Authorization PIN</a></td>
				</tr>
				<tr>
					<td>PIN</td>
					<td><input type="text" name="pin"></td>
				</tr>
			';
			$submit_val = 'Submit';
		}
		else {
			$ml_pin = '';
			$submit_val = 'Continue';
		}
		self::auth_hook($callee, 'pre_consumer_info_form');
		?>
		<div class="instructions">
			<h3>Instructions</h3>
			<ol>
				<li>Make sure you are logged into twitter with the client account this app will be used for</li>
				<li>You should not need to change any of the "Consumer" values below, just click "Continue"</li>
				<li>There should now be a "Get Authorization PIN" link, which will open in a new tab</li>
				<li>Copy and paste the PIN from twitter back into e2</li>
				<li>Press "Submit"</li>
				<li>Done!</li>
			</ol>
		</div>
		<table>
			<tbody>
				<?= self::auth_hook($callee, 'get_account_id_input') ?>
				<tr>
					<td>Consumer Key</td>
					<td><input type="text" name="consumer_key" value="<?= $data['consumer_key'] ?>"></td>
				</tr>
				<tr>
					<td>Consumer Secret</td>
					<td><input type="text" name="consumer_secret" value="<?= $data['consumer_secret'] ?>"></td>
				</tr>
				<?= $ml_pin ?>
				<tr>
					<td></td>
					<td><input type="submit" value="<?= $submit_val ?>"></td>
				</tr>
			</tbody>
		</table>
		<?php
		self::auth_hook($callee, 'post_consumer_info_form');
	}

	private static function auth_request_token($callee, $data)
	{
		$key = $data['consumer_key'];
		$secret = $data['consumer_secret'];

		$cb = self::init_cb($key, $secret);
		$reply = $cb->oauth_requestToken(array(
			'oauth_callback' => 'oob'
		));

		// stores it
		$cb->setToken($reply['oauth_token'], $reply['oauth_token_secret']);
		self::process_request_token_response($data, $reply);

		// gets the authorize screen URL
		$auth_url = $cb->oauth_authorize();
		return $auth_url;
	}

	private static function auth_pin($callee)
	{
		$pin = $_POST['pin'];

		$data = self::auth_get_data($_POST['account_id']);
		$cb = self::init_cb($data['consumer_key'], $data['consumer_secret']);
		$cb->setToken($data['oauth_token'], $data['oauth_token_secret']);
		$reply = $cb->oauth_accessToken(array(
			'oauth_verifier' => $pin
		));
		if (is_array($reply) && isset($reply['user_id'])) {
			self::process_auth_pin_reply($_POST['account_id'], $reply);
			return true;
		}
		else {
			return false;
		}
	}

	private static function auth_get_data($account_id)
	{
		return db::select_row("
			select * from social.twitter_auth where account_id = '".db::escape($account_id)."'
		", 'ASSOC');
	}

	public function process_request_token_response($post_data, $request_token_reply)
	{
		$post_keys = array('account_id', 'consumer_key', 'consumer_secret');
		$reply_keys = array('oauth_token', 'oauth_token_secret');
		$data = array();
		foreach ($post_keys as $key) {
			$data[$key] = $post_data[$key];
		}
		foreach ($reply_keys as $key) {
			$data[$key] = $request_token_reply[$key];
		}
		db::insert_update("social.twitter_auth", array('account_id'), $data);
	}

	private static function process_auth_pin_reply($account_id, $access_token_reply)
	{
		$reply_keys = array('user_id', 'screen_name', 'oauth_token', 'oauth_token_secret');
		$data = array();
		foreach ($reply_keys as $key) {
			$data[$key] = $access_token_reply[$key];
		}
		db::update("social.twitter_auth", $data, "account_id = '".db::escape($account_id)."'");
	}

	public static function authorize_app($callee, $init_data = array())
	{
		// db::dbg();
		// echo '<pre>';
		// var_dump($_GET);
		// var_dump($_POST);
		// var_dump($init_data);
		// echo "</pre>\n";

		if (isset($_POST['pin'])) {
			return self::auth_pin($callee);
		}
		else {
			self::auth_consumer_info_form($callee, $init_data);
			return false;
		}
	}
}

/*
 * to create a dev twitter app
 *
 * 1. goto https://dev.twitter.com/apps/new
 * 2. create new app
 * 2a. name is what will appear when posting
 * 2b. callbacks for dev: http://127.0.0.1/twitter.php
 * ** do we even need a callback? when getting pin, just copy paste into form?
 * 3. submit button!
 * 4. settings tab, grant "Read and Write" access
 */


// for development
// twitter requires callback URL for authentication,
// twitter likes 127.0.0.1 rather than localhost,
// so we give our dev ap in twitter the callback url
// http://127.0.0.1/twitter.php, up to local env
// to point that here (symlink, etc)
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'twitter.php')) {
	// callbacks for authorize app
	// also implemented in e2 (current: /smo/client/swapp/auth: /account/service/smo/swapp/auth)
	class local_twitter_auth
	{
		public function __construct()
		{
			// load env and db
			require(__DIR__.'/../env.php');
			require(__DIR__.'/../lib_db.php');
			db::connect(\epro\DB_HOST, \epro\DB_USER, \epro\DB_PASS, \epro\DB_NAME);
		}

		public function auth_hook_pre_consumer_info_form()
		{
			?>
<!doctype html>
<html>
<body>
<form method="post">
			<?php
		}

		public function auth_hook_post_consumer_info_form()
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
	$lta = new local_twitter_auth();
	twitter::authorize_app($lta);
}

?>