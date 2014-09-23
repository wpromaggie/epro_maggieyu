<?php

class Session
{
	private static $is_empty_session, $is_in_db;
	private static $table, $session_data;

	public static function init($table)
	{
		self::$table = $table;

		register_shutdown_function('session_write_close');
		session_set_save_handler(
			array('Session', 'open'),
			array('Session', 'close'),
			array('Session', 'read'),
			array('Session', 'write'),
			array('Session', 'destroy'),
			array('Session', 'gc')
		);
		session_start();
	}

	public static function open($savePath, $sessionName)
	{
		return (!empty(self::$table));
	}

	public static function close()
	{
		// nothing to do
		return true;
	}

	public static function read($id)
	{
		$tmp_data = db::select("select data from ".self::$table." where id = :id", array('id' => $id), 'NUM');

		self::$is_in_db = (count($tmp_data) > 0);
		if (!self::$is_in_db) {
			self::$is_empty_session = true;
		}
		else {
			self::$is_empty_session = ($tmp_data[0] === '');
		}

		if (self::$is_empty_session) {
			return '';
		}
		else {
			self::$session_data = $tmp_data[0];
			return self::$session_data;
		}
	}

	public static function write($id, $data)
	{
		if (empty($data) && self::$is_empty_session) {
			return true;
		}
		else {
			if (self::$is_in_db) {
				// only update if dirty
				if (self::$session_data != $data) {
					$r = db::update(
						self::$table,
						array('data' => $data),
						"id = :id",
						array('id' => $id)
					);
				}
				// nothing to do
				else {
					return true;
				}
			}
			// new session
			else {
				$r = db::insert(self::$table, array(
					'id' => $id,
					'data' => $data,
					'created' => date('Y-m-d H:i:s')
				), false);
			}
			return ($r !== false);
		}
	}

	public static function destroy($id)
	{
		db::delete(self::$table, "id = :id", array('id' => $id));
		setcookie(session_name(), '', time()-42000, '/');
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		return true;
	}

	public static function gc($maxlifetime)
	{
		// garbage collection taken care of by cli garbage collection services
		return true;
	}
}

?>