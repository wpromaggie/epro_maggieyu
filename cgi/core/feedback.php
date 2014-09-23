<?php

class feedback
{
	private static $errors, $successes, $other, $num_messages;
	
	private static $is_inited = false;
	
	public static function init()
	{
		self::$num_messages = 0;
		self::$errors = array();
		self::$successes = array();
		self::$other = array();
		
		self::$is_inited = true;
	}
	
	public static function is_error()
	{
		return (self::$errors && count(self::$errors) > 0);
	}
	
	public static function add_error_msg($error)
	{
		if (self::$is_inited)
		{
			self::$errors[] = $error;
			++self::$num_messages;
		}
		return false;
	}
	
	public static function add_success_msg($success)
	{
		if (self::$is_inited)
		{
			self::$successes[] = $success;
			++self::$num_messages;
		}
	}
	
	public static function add_msg($msg, $type = 'info')
	{
		if (self::$is_inited)
		{
			self::$other[$type][] = $msg;
			++self::$num_messages;
		}
	}
	
	public static function is_feedback()
	{
		return (self::$is_inited && self::$num_messages > 0);
	}
	
	public static function output()
	{
		if (self::$num_messages == 0) return;
		
		$feedback = array();
		$feedback = array_merge($feedback, self::get_messages(self::$errors, 'danger'));
		$feedback = array_merge($feedback, self::get_messages(self::$successes, 'success'));
		foreach (self::$other as $type => $messages)
		{
			$feedback = array_merge($feedback, self::get_messages($messages, $type));
		}
		cgi::add_js_var('feedback', $feedback);
	}
	
	private static function get_messages(&$messages, $type)
	{
		if (count($messages) == 0) return array();
		
		$with_type = array();
		foreach ($messages as $msg_text)
		{
			$with_type[] = array('text' => $msg_text, 'type' => $type);
		}
		return $with_type;
	}
}


?>