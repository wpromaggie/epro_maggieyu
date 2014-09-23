<?php
require_once(__DIR__.'/asana.php');
require_once(__DIR__.'/asana-oauth.php');

class AsanaLib
{
	public $api;

	private $client_id = epro\ASANA_CLIENT_ID;
	private $client_secret = epro\ASANA_CLIENT_SECRET;
	private $redirect_url = epro\ASANA_REDIRECT_URL;

	public function connect()
	{
		if (isset($_GET['reset_key'])){
			unset($_SESSION['asana_key']);
		}

		if(empty($_SESSION['asana_key'])){

			if (isset($_GET['access_token'])){
				$access_key = $_GET['access_token'];
			}
			else {
				$access_key = $this->get_token(user::$id);

				if (!$access_key){
					
					$this->oauth();

				}
			}

			$_SESSION['asana_key'] = $access_key;

		}

		$this->api = new Asana(array('accessToken' => $_SESSION['asana_key']));

		//now lets test the key to make sure it still works
		$this->api->getUserInfo();

		if ($this->api->responseCode != '200'){

			//we need to refresh the token
			$access_key = $this->oauth_refresh(user::$name);

			if ($access_key=='FLASE'){
				echo "Asana LIb: Unable to refresh your asana access key";
				die;
			}

			$_SESSION['asana_key'] = $access_key;

			$this->api =@ new Asana(array('accessToken' => $access_key));
		
		}

	}

	public function get_token($user_id=0)
	{
		$asana_token = db::select_row("SELECT * FROM asana_tokens WHERE user_id=$user_id LIMIT 1", "ASSOC");

		if($asana_token){
			return $asana_token['access_token']; 
		}

		return false;
	}

	public function set_token($access_token, $refresh_token)
	{
		$asana_token = db::insert_update("eppctwo.asana_tokens", 
			array(
				'user_id'
			),
			array(
				'user_id' => user::$id,
				'access_token' => $access_token,
				'refresh_token' => $refresh_token
			)
		);
	}

	public function oauth_refresh($username='')
	{
		//Request Access Token
		$asanaAuth = new AsanaAuth($this->client_id, $this->client_secret, $this->redirect_url);

		$user_id = db::select_one("SELECT id FROM users WHERE username='$username' LIMIT 1");

		$asana_token = db::select_row("SELECT * FROM asana_tokens WHERE user_id=$user_id LIMIT 1", "ASSOC");

		$refresh_token = $asana_token['refresh_token'];

		$result = $asanaAuth->refreshAccessToken($refresh_token);

		$resultJson = json_decode($result);

		// As Asana API documentation says, when response is successful, we receive a 200 in response so...
	    if ($asanaAuth->responseCode != '200' || is_null($result)) {
	        echo "Asana_Lib: Oauth Refresh Failed.";
	        return;
	    }

	    $this->set_token($resultJson->access_token, $refresh_token);

	    return $resultJson->access_token;
	}

	public function oauth($code='')
	{
		$asanaAuth = new AsanaAuth($this->client_id, $this->client_secret, $this->redirect_url);

		if (empty($code)){

			setcookie('redirect_url', "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]", 0, '/', epro\WPRO_DOMAIN);

		    // Redirect the user to asana authorization url.
		    cgi::redirect($asanaAuth->getAuthorizeUrl());
		}

		$result = $asanaAuth->getAccessToken($code);

		// As Asana API documentation says, when response is successful, we receive a 200 in response so...
	    if ($asanaAuth->responseCode != '200' || is_null($result)) {
	        echo 'Error while trying to connect to Asana to get the access token, response code: ' . $asanaAuth->responseCode;
	        return;
	    }

	    $resultJson = json_decode($result);

	    $this->set_token($resultJson->access_token, $resultJson->refresh_token);

	    return $resultJson->access_token;
	}

}