<?php

/*
 * todo: change guilds to represent a path (eg /account/product/ql instead of ql)
 * - if user has a guild that is a substring starting at index 0 of the current
 *  page, user is ok (also need to check access lvl (read only, leader, etc) of course)
 * - so if user is part of /account/, /account/product/ql/ is ok but /blah/account/ would not be
 * - make sure guilds and $page also end with a slash (/) to avoid eg /accounting vs /account problem
 * - everything based on roles? for example someone with 'ppc manager' role would by default have
 *  access to /account/service/ppc, /camp_dev, /service
 */

// class representing an e2 user
class user
{
	public static $id, $name, $realname;
	public static $primary_role, $roles;

	const COOKIE_EXPIRE = 5184000;

	// from least access to most access
	public static $standard_roles = array('Observer', 'Member', 'Director', 'Leader');

	public static function init($user_id, $clobber = false)
	{
		if (!$user_id) {
			return;
		}

		if (!array_key_exists('e2_badge', $_SESSION)) {
			$_SESSION['id'] = $user_id;
			$_SESSION['e2_badge'] = 1;
		}
		user::$id = $_SESSION['id'];
		list(user::$name, user::$realname, $tmp_dept, $tmp_role) = db::select_row("
			select u.username, u.realname, u.primary_dept, g.role
			from eppctwo.users as u, eppctwo.user_guilds as g
			where
				u.id = :id &&
				u.id = g.user_id
		", array('id' => user::$id));

		// role transition checks
		if ($tmp_dept == 'administrate') {
			$clobber = true;
			if ($tmp_role == 'Leader') {
				$tmp_dept = 'dev';
			}
			else {
				$tmp_dept = 'admin';
			}
		}
		if ($clobber || empty($_SESSION['role'])) {
			$_SESSION['role'] = $tmp_dept;
		}
		
		user::$roles = user_role::get_roles(user::$id);
		user::$primary_role = user::$roles[$tmp_dept];
	}
	
	public static function is_developer($impersonate_ok = false)
	{
		return (user::has_role('dev', '*', array('do_check_admin' => 0)) || ($impersonate_ok && user::is_imposter()));
	}
	
	public static function is_admin()
	{
		return (user::is_developer() || (user::has_role('admin', '*', array('do_check_admin' => 0))));
	}
	
	public static function impersonate($imposter_id)
	{
		$_SESSION['impersonator'] = $_SESSION['id'];
		$_SESSION['impersonator_username'] = $_SESSION['username'];
		$_SESSION['username'] = db::select_one("select username from users where id = '$imposter_id'");
		$_SESSION['id'] = $imposter_id;
		user::init($imposter_id, true);
	}
	
	public static function stop_impersonating()
	{
		$_SESSION['id'] = $_SESSION['impersonator'];
		$_SESSION['username'] = $_SESSION['impersonator_username'];
		user::init($_SESSION['id'], true);
		unset($_SESSION['impersonator']);
	}
	
	public static function is_imposter()
	{
		return (@array_key_exists('impersonator', $_SESSION));
	}

	public static function get_standard_roles()
	{
		return self::$standard_roles;
	}
	
	public static function has_module_access()
	{
		// if user is god and in admin module
		// or global module
		// or not in administration but user is admin or member of build
		return (
			(g::$p1 == 'administrate' && user::is_developer(true)) ||
			(cgi::is_global_module()) ||
			(g::$p1 != 'administrate' && (user::is_admin() || user::has_access(cgi::$url_path)))
		);
	}

	public static function has_access($test_path)
	{
		if ($test_path[0] != '/') {
			$test_path = "/{$test_path}";
		}
		if (!is_array(user::$roles)) {
			return false;
		}
		foreach (user::$roles as $role) {
			if ($role->has_access($test_path)) {
				return true;
			}
		}
		return false;
	}

	// user has access to at least one department that manages clients
	public static function has_access_to_clients()
	{
		foreach (user::$roles as $role) {
			foreach ($role->paths as $path) {
				if ($path == '*' || strpos($path, '/account') === 0) {
					return true;
				}
			}
		}
		return false;
	}

	public static function get_user_client_depts()
	{
		$depts = array();
		foreach (user::$roles as $role_name => $role) {
			foreach ($role->paths as $path) {
				if ($path == '*') {
					return account::get_all_depts();
				}
				else if (account::is_dept($role_name)) {
					$depts[] = $role_name;
				}
			}
		}
		return $depts;
	}

	// todo: move $guild param to opts
	// public static function has_role($test_roles, $guild = null, $opts = array())
	public static function has_role($test_roles, $dept = '*', $opts = array())
	{
		util::set_opt_defaults($opts, array(
			'do_check_admin' => true,
			'leader' => true,
			'director' => true
		));

		if (!is_array($test_roles)) {
			$test_roles = array($test_roles);
		}
		
		if ($opts['do_check_admin'] && user::is_admin()) {
			return true;
		}
		else if (!is_array(user::$roles)) {
			return false;
		}
		else {
			// check for any role
			if (count($test_roles) == 1 && $test_roles[0] == '*') {
				return true;
			}
			// when function was previously called with no args
			// it was either
			// 1. to check for non-departmental role
			// or
			// 2. with department pulled from first page in URL
			if ($dept == '*') {
				// case 2 above, set dept and move along to check for role below
				if (in_array('Leader', $test_roles) || in_array('Director', $test_roles)) {
					$dept = g::$p1;
				}
				// case 1 above
				else {
					foreach ($test_roles as $role) {
						if (array_key_exists($role, user::$roles)) {
							return true;
						}
					}
					return false;
				}
			}
			if (!isset(user::$roles[$dept])) {
				return false;
			}
			$user_role = user::$roles[$dept];
			return (
				in_array($user_role->role, $test_roles) ||
				($opts['leader'] && ($user_role->role == 'Leader' || $user_role->role == 'Director')) ||
				($opts['director'] && $user_role->role == 'Director')
			);
		}
	}

	public static function has_module_observer_access($mod_name = null, $admin_ok = true)
	{
		if (!$mod_name) {
			$mod_name = g::$p1;
		}
		// all our standard roles
		return (
			(cgi::is_global_module($mod_name)) ||
			($admin_ok && user::is_admin()) ||
			(user::has_access(cgi::$url_path))
		);
	}
	
	public static function get_banner_link()
	{
		return '<a href="'.user::$primary_role->home['path'].'">'.user::$primary_role->home['text'].'</a>';
	}

	// nav options:
	// 0: path
	// 1: text
	// 2: depth
	public static function get_banner_nav_options()
	{
		$options = array();
		$upaths = array();
		$do_get_all_paths = false;
		foreach (user::$roles as $role) {
			$role_paths = $role->get_paths_for_nav();
			if (in_array('*', $role_paths)) {
				$do_get_all_paths = true;
				break;
			}
			$upaths = array_unique(array_merge($upaths, $role_paths));
		}
		if ($do_get_all_paths) {
			$upaths = user_role::get_all_paths_for_nav();
		}
		$options = array();
		foreach ($upaths as $path) {
			$options[] = array($path, user_role::get_role_path_display_text($path), 0);
		}
		util::sort2d($options, 1);
		// check for sub options after sorting top level
		for ($i = 0, $ci = count($options); $i < $ci; ++$i) {
			list($path, $text) = $options[$i];
			if (user::nav_has_sub_options($path)) {
				$sub_options = user::get_nav_sub_options($path, $opts['sub_prefix']);
				if ($sub_options) {
					array_splice($options, $i + 1, 0, $sub_options);
					$i += count($sub_options);
				}
			}
		}
		return $options;
	}

	public static function get_sidebar_links()
	{
		$options = array();
		$upaths = array();
		$do_get_all_paths = false;
		foreach (user::$roles as $role) {
			$role_paths = $role->get_paths_for_nav();
			if (in_array('*', $role_paths)) {
				$do_get_all_paths = true;
				break;
			}
			$upaths = array_unique(array_merge($upaths, $role_paths));
		}
		if ($do_get_all_paths) {
			$upaths = user_role::get_all_paths_for_nav();
		}
		$options = array();
		//e($upaths);
		foreach ($upaths as $path) {
			$options[] = array($path, user_role::get_role_path_display_text($path), 0);
		}
		util::sort2d($options, 1);

		//e($options);

		// check for sub options after sorting top level
		for ($i = 0, $ci = count($options); $i < $ci; ++$i) {
			list($path, $text) = $options[$i];
			if (user::nav_has_sub_options($path)) {
				$sub_options = user::get_nav_sub_options($path, $opts['sub_prefix']);
				if ($sub_options) {
					$options[$i][] = $sub_options;
				}
			}
		}
		return $options;
	}

	private static function nav_has_sub_options($parent_path)
	{
		switch ($parent_path) {
			case ('/service'): return true;
			default: return false;
		}
	}

	private static function get_nav_sub_options($parent_path, $sub_prefix)
	{
		switch ($parent_path) {
			case ('/service'):
				$services = user::get_services();
				sort($services);
				$options = array();
				foreach ($services as $service) {
					$options[] = array('/service/client_list/'.$service, service::display_text($service), 1);
				}
				return $options;
			default: return false;
		}
	}

	public static function get_services()
	{
		$all_services = account::$org['service'];
		if (user::is_admin()) {
			return $all_services;
		}
		else {
			$user_services = array();
			foreach ($all_services as $service) {
				if (array_key_exists($service, user::$roles)) {
					$user_services[] = $service;
				}
			}
			return $user_services;
		}
	}

	public static function gravatar_path()
	{
		$hash = md5( strtolower( trim( user::$name ) ) );
		return 'http://www.gravatar.com/avatar/'.$hash;
		
	}

	public static function login($user, $pass)
	{
		$user_id = db::select_one("
			select id
			from eppctwo.users
			where
				username='".addslashes($user)."' &&
				password='".util::passhash($pass, $user)."'
		");
		if ($user_id) {
			$_SESSION['id'] = $user_id;
			$_SESSION['e2_badge'] = 1;
			user::set_epro_cookie($user_id);
			return true;
		}
		else {
			return false;
		}
	}

	public static function logout()
	{
		// cookie
		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time()-42000, '/');
		}
		setcookie('e2_persist', false, false, '/', false);
		// session
		session_destroy();
	}

	public static function is_logged_in()
	{
		return (is_array($_SESSION) && array_key_exists('e2_badge', $_SESSION));
	}

	public static function is_epro_cookie_set()
	{
		return (array_key_exists('e2_persist', $_COOKIE));
	}

	public static function get_id_from_cookie()
	{
		return (db::select_one("select id from eppctwo.users where login_cookie = '".$_COOKIE['e2_persist']."'"));
	}

	public static function set_epro_cookie($user_id)
	{
		//Check for existing login cookie (like logged in on different browser)
		$existing_cookie = db::select_one("SELECT login_cookie FROM users WHERE id='$user_id' LIMIT 1");
		if ($existing_cookie) {
			setcookie('e2_persist', $existing_cookie, time() + self::COOKIE_EXPIRE, '/', false);
			return;
		}

		//This code sets the login_cookie if one does not exist for the user.
		do {
			$login_cookie = sprintf('%08X', mt_rand(1, mt_getrandmax()));
			$already_in_use = db::select_one("select count(*) from eppctwo.users where login_cookie = '$login_cookie'");
			if (!$already_in_use)
			{
				setcookie('e2_persist', $login_cookie, time() + self::COOKIE_EXPIRE, '/', false);
				db::update(
					"eppctwo.users",
					array('login_cookie' => $login_cookie),
					"id = '$user_id'"
				);
			}
		} while ($already_in_use);
	}

}

?>