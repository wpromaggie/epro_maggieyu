<?php

abstract class base_email_export{
	public static $job;

	abstract public static function run();
	abstract private static function display();
	abstract private static function data_source();

}

?>