<?php
require('cli.php');
cli::run();

class install
{
	public function create_user()
	{
		list($user) = util::list_assoc(cli::$args, 'u');
		if (empty($user)) {
			cli::usage('-u user');
		}
		$realname = cli::readline('Real name? ');

		$command = "/usr/bin/env bash -c 'read -s -p \"Password: \" mypassword && echo \$mypassword'";
		$password = rtrim(shell_exec($command));

		db::dbg();
		users::create(array(
			'company' => 1,
			'username' => $user,
			'password' => util::passhash($password, $user),
			'realname' => $realname,
			'primary_guild' => 'administrate'
		));
	}
}

