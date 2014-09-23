<?php
// lazy load?
require_once(__DIR__.'/fb/fb.php');
require_once(__DIR__.'/twitter/twitter.php');

abstract class network
{
	public $auth, $e;

	abstract public static function authorize_app($callee, $init_data = array());

	public static function get_api($network, $account_id)
	{
		if (!isset(smo_lib::$networks[$network])) {
			return false;
		}
		$class = smo_lib::$networks[$network]['class'];
		return $class::get_from_account($account_id);
	}

	public function marketize($type, $data)
	{
		if (rs::is_rs_object($data)) {
			$data = $data->to_array();
		}
		$extra = $this->marketize_get_extra($type, $data);
		if (is_array($extra)) {
			$mdata = $extra;
		}
		else {
			$mdata = array();
		}
		$key_map = $this->marketize_get_key_map($type, $data);
		foreach ($data as $k => $v) {
			if (isset($key_map[$k])) {
				$mdata_key = $key_map[$k];
				if (is_array($mdata_key)) {
					list($mdata_key, $mdata_func) = $mdata_key;
					$v = $this->$mdata_func($data, $v);
				}
				else {
					// nothing to do
				}
				if (!empty($v)) {
					$mdata[$mdata_key] = $v;
				}
			}
		}
		return $mdata;
	}

	private function set_error($e)
	{
		$this->$e = $e;
	}

	public function get_error()
	{
		if (isset($this->e)) {
			return (is_string($this->e)) ? $this->e : $this->e->getMessage();
		}
		else {
			return false;
		}
	}

	protected static function auth_hook($obj, $method)
	{
		$hook = "auth_hook_{$method}";
		if (method_exists($obj, $hook)) {
			$args = func_get_args();
			return call_user_func_array(array($obj, $hook), array_slice($args, 2));
		}
	}

	protected static function auth_get_redirect_uri()
	{
		$path = $_SERVER['REQUEST_URI'];
		$i = strpos($path, '?');
		if ($i !== false) {
			$path = substr($path, 0, $i);
		}
		return ('http://'.$_SERVER['HTTP_HOST'].$path);
	}
}

?>