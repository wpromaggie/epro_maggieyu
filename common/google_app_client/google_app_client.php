<?php

/*
 * https://developers.google.com/drive/
 */

define('GAC_PATH', __DIR__.'/');
define('GAC_SRC_PATH', GAC_PATH.'src/');
require_once(GAC_SRC_PATH.'Google_Client.php');

class google_app_client
{
	private $client, $service, $tok;
	
	const CLIENT_ID = '854642194798.apps.googleusercontent.com';
	const SERVICE_ACCOUNT_NAME = '854642194798@developer.gserviceaccount.com';
	const KEY_FILE = '44052d4fafdd76b56e0ea79ae6199a86abeb259a-privatekey.p12';
	
	private function transaction_init($service_name)
	{
		switch ($service_name)
		{
			case ('drive'):
				require_once(GAC_SRC_PATH.'contrib/Google_DriveService.php');
				$service_class = 'Google_DriveService';
				$scopes = array('http://docs.google.com/feeds/');
				$prn = 'gdrive@wpromote.com';
				break;
			
			default:
				throw new Exception('Unknown service: '.$service_name);
		}
		
		$this->client = new Google_Client();
		$this->client->setApplicationName('wpro google app');
		
		$this->tok = new google_app_token($scopes, self::SERVICE_ACCOUNT_NAME, $prn);
		if ($this->tok->is_valid())
		{
			$this->client->setAccessToken($this->tok->serialize());
		}
		
		$key_contents = file_get_contents(GAC_PATH.self::KEY_FILE);
		$credentials = new Google_AssertionCredentials(self::SERVICE_ACCOUNT_NAME, $scopes, $key_contents);
		if ($prn)
		{
			$credentials->prn = $prn;
		}
		$this->client->setAssertionCredentials($credentials);
		$this->client->setClientId(self::CLIENT_ID);
		
		$this->service = new Google_DriveService($this->client);
	}
	
	public function dbg()
	{
		Google_CurlIO::$wpro_dbg = true;
	}
	
	private function transaction_finish()
	{
		// if we didn't have a valid token,
		// our service call should have generated one
		if (!$this->tok->is_valid())
		{
			$this->tok->import_new($this->client->getAccessToken());
		}
	}
	
	public function file_list()
	{
		$this->transaction_init('drive');
		
		$result = array();
		$page_tok = null;
		
		do
		{
			try
			{
				$opts = array();
				if ($page_tok)
				{
					$opts['pageToken'] = $page_tok;
				}
				$r = $this->service->files->listFiles($opts);
				$result = array_merge($result, $r['items']);
				$page_tok = $r['nextPageToken'];
			}
			catch (Exception $e)
			{
				$this->error = $e->getMessage();
				$result = false;
				break;
			}
		} while ($page_tok);
		
		$this->transaction_finish();
		return $result;
	}
	
	public function file_insert($file_path, $opts = array())
	{
		$pathinfo = pathinfo($file_path);
		
		// set opts
		if (!array_key_exists('mimeType', $opts))
		{
			$opts['mimeType'] = mime_content_type($file_path);
		}
		$opts['data'] = file_get_contents($file_path);
		
		// init file
		$file = new Google_DriveFile();
		$file->setTitle($pathinfo['basename']);
		$file->setMimeType($opts['mimeType']);
		
		// send file to google
		$this->transaction_init('drive');
		$r = $this->service->files->insert($file, $opts);
		$this->transaction_finish();
		return $r;
	}
	
	public function file_update($file_path, $drive_file, $opts = array())
	{
		$pathinfo = pathinfo($file_path);
		
		// set opts
		if (!array_key_exists('mimeType', $opts))
		{
			$opts['mimeType'] = mime_content_type($file_path);
		}
		if (!array_key_exists('newRevision', $opts))
		{
			$opts['newRevision'] = false;
		}
		$opts['data'] = file_get_contents($file_path);
		
		// init file
		$file = new Google_DriveFile();
		$file->setId($drive_file['id']);
		$file->setTitle($pathinfo['basename']);
		$file->setMimeType($opts['mimeType']);
		
		// send file to google
		$this->transaction_init('drive');
		$r = $this->service->files->update($file->id, $file, $opts);
		$this->transaction_finish();
		return $r;
	}
	
	public function file_delete($drive_file, $opts = array())
	{
		// send file to google
		$this->transaction_init('drive');
		$r = $this->service->files->delete($drive_file['id'], $opts);
		$this->transaction_finish();
		return $r;
	}
	
	public function download_file($drive_file)
	{
		$url = $drive_file['downloadUrl'];
		if ($url)
		{
			$req = new Google_HttpRequest($url, 'GET', null, null);
			$io_req = Google_Client::$io->authenticatedRequest($req);
			if ($io_req->getResponseHttpCode() == 200)
			{
				return $io_req->getResponseBody();
			}
		}
		return false;
	}
}

?>