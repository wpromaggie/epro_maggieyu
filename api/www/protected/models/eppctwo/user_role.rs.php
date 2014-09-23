<?php

/*
 * all user roles are defined here
 * special (ie *not* Observer, Member, Director, Leader) roles are specified here rather than via cgi form
 * users are assigned the roles via the administrate module
 * use '*' for a role that spans multiple guilds
 */

class mod_eppctwo_user_role extends rs_object
{
	public static $db, $cols, $primary_key;

	// stuff we don't keep in db for now
	public static $all_roles = false;
	public $home, $paths, $hr_can_create;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'   ,'int' ,11,null,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY | rs::AUTO_INCREMENT),
			new rs_col('user' ,'int' ,11,null,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('guild','char',32,''  ,rs::NOT_NULL),
			new rs_col('role' ,'char',32,''  ,rs::NOT_NULL)
		);
	}

	private static function get_role_with_attrs($attrs)
	{
		$r = new user_role();
		foreach ($attrs as $k => $v) {
			$r->$k = $v;
		}
		return $r;
	}

	// factory for user roles
	// role attributes are defined here
	// users who have the roles are kept in database
	// probably should move attributes to database as well?
	// todo: these are just departments, I reckon they should be
	//  actual roles, eg "SEO Account Manager", "Link Builder", etc
	public static function get_role($role)
	{
		self::init_roles();
		if (isset(self::$all_roles[$role])) {
			return self::$all_roles[$role];
		}
		else {
			return self::get_dummy_role();
		}
	}

	private static function get_dummy_role()
	{
		return self::get_role_with_attrs(array(
			'home' => 'dashboard',
			'paths' => array()
		));
	}

	public static function get_all_roles()
	{
		self::init_roles();
		return self::$all_roles;
	}

	private function init_roles()
	{
		if (self::$all_roles) {
			return;
		}
		self::$all_roles = array(
			'ppc' => self::get_role_with_attrs(array(
				'home' => array('path' => '/service/client_list/ppc', 'text' => 'My Clients'),
				'hr_can_create' => true,
				'paths' => array(
					'/account/service/ppc' => 0,
					'/service' => 1,
					'/surveys' => 1,
					'/camp_dev' => 1,
					'/client_reports' =>1
				)
			)),

			'seo' => self::get_role_with_attrs(array(
				'home' => array('path' => '/service/client_list/seo', 'text' => 'My Clients'),
				'hr_can_create' => true,
				'paths' => array(
					'/account/service/seo' => 0,
					'/service' => 1,
					'/surveys' => 1
				)
			)),

			'smo' => self::get_role_with_attrs(array(
				'home' => array('path' => '/service/client_list/smo', 'text' => 'My Clients'),
				'hr_can_create' => true,
				'paths' => array(
					'/account/service/smo' => 0,
					'/service' => 1,
					'/surveys' => 1
				)
			)),

			'partner' => self::get_role_with_attrs(array(
				'home' => array('path' => '/service/client_list/partner', 'text' => 'My Clients'),
				'hr_can_create' => true,
				'paths' => array(
					'/account/service/partner' => 0,
					'/service' => 1,
					'/surveys' => 1
				)
			)),

			'email' => self::get_role_with_attrs(array(
				'home' => array('path' => '/service/client_list/email', 'text' => 'My Clients'),
				'hr_can_create' => true,
				'paths' => array(
					'/account/service/email' => 0,
					'/service' => 1,
					'/surveys' => 1
				)
			)),

			'webdev' => self::get_role_with_attrs(array(
				'home' => array('path' => '/service/client_list/webdev', 'text' => 'My Clients'),
				'paths' => array(
					'/account/service/webdev' => 0,
					'/service' => 1,
					'/surveys' => 1
				)
			)),

			// small business
			'small_business' => self::get_role_with_attrs(array(
				'home' => array('path' => '/product/queues', 'text' => 'Small Biz'),
				'hr_can_create' => true,
				'paths' => array(
					'/account/product' => 0,
					'/product' => 1,
					'/sbr' => 1
				)
			)),

			// the rest
			'sales' => self::get_role_with_attrs(array(
				'home' => array('path' => '/contracts', 'text' => 'Contracts'),
				'hr_can_create' => true,
				'paths' => array(
					'/sales' => 1,
					'/contracts' => 1,
				)
			)),

			'hr' => self::get_role_with_attrs(array(
				'home' => array('path' => '/hr', 'text' => 'HR'),
				'hr_can_create' => true,
				'paths' => array(
					'/hr' => 1
				)
			)),

			'marketing' => self::get_role_with_attrs(array(
				'home' => array('path' => '/marketing', 'text' => 'Marketing'),
				'hr_can_create' => true,
				'paths' => array(
					'/marketing' => 1,
					'/client' => 1
				)
			)),

			'accounting' => self::get_role_with_attrs(array(
				'home' => array('path' => '/accounting', 'text' => 'Accounting'),
				'paths' => array(
					'/accounting' => 1
				)
			)),

			'admin' => self::get_role_with_attrs(array(
				'home' => array('path' => '/service/client_list', 'text' => 'Client List'),
				'paths' => array(
					'*'
				)
			)),

			'dev' => self::get_role_with_attrs(array(
				'home' => array('path' => '/administrate', 'text' => 'Dev'),
				'paths' => array(
					'*'
				)
			)),

			/*
			 * NON DEPARTMENT ROLES
			 */
			'CC Admin' => self::get_role_with_attrs(array(
				'paths' => array(
					'*'
				)
			)),

			'Test Role' => self::get_role_with_attrs(array(
				'paths' => array(
					'*'
				)
			)),

			'Dashboard Admin' => self::get_role_with_attrs(array(
				'paths' => array(
					'*'
				)
			)),

			'Charge Master' => self::get_role_with_attrs(array(
				'paths' => array(
					'/contracts'
				)
			)),

			'Auto-Billing Monitor' => self::get_role_with_attrs(array(
				'paths' => array(
					'/partner'
				)
			)),

			'Lead Manager' => self::get_role_with_attrs(array(
				'paths' => array(
					'/sales'
				)
			)),

			'QL Pro' => self::get_role_with_attrs(array(
				'paths' => array(
					'/sales'
				)
			)),

			'Billing Search' => self::get_role_with_attrs(array(
				'paths' => array(
					'/accounting'
				)
			)),

			'SBP Order Doctor' => self::get_role_with_attrs(array(
				'paths' => array(
					'/sbr'
				)
			))
		);
		self::$all_roles['Partner Director'] = clone self::$all_roles['partner'];
		unset(self::$all_roles['Partner Director']->hr_can_create);
		self::$all_roles['Sales Director'] = clone self::$all_roles['sales'];
		unset(self::$all_roles['Sales Director']->hr_can_create);
		// add sap to sales director path
		self::$all_roles['Sales Director']->paths['/sap'] = 1;

		// set dept for roles
		foreach (self::$all_roles as $dept => $role) {
			$role->dept = $dept;
		}
	}

	public static function get_role_path_display_text($path)
	{
		switch ($path)
		{
			case ('/accounting'): return 'Accounting';
			case ('/camp_dev'): return 'Camp Dev';
			case ('/contracts'): return 'Contracts';
			case ('/hr'): return 'HR';
			case ('/marketing'): return 'Marketing';
			case ('/product'): return 'Small Business';
			case ('/sales'): return 'Sales';
			case ('/sap'): return 'SAP';
			case ('/service'): return 'Agency Services';
			case ('/surveys'): return 'Surveys';
			case ('/client_reports'): return 'Client Reports';
			default: return util::display_text(str_replace('/', '', $path));
		}
	}

	public static function get_roles($uid)
	{
		$roles = array();
		$dept_roles = db::select("
			select guild_id, role
			from eppctwo.user_guilds
			where user_id = :uid
		", array(
			"uid" => $uid
		));
		for ($i = 0, $ci = count($dept_roles); $i < $ci; ++$i) {
			list($dept, $role) = $dept_roles[$i];
			$user_role = user_role::get_role($dept);
			$user_role->role = $role;
			$roles[$dept] = $user_role;
		}
		$random_roles = user_role::get_all(array(
			'where' => "user = :uid",
			'data' => array("uid" => $uid)
		));

		foreach ($random_roles as $role) {
			$user_role = user_role::get_role($role->role);
			$user_role->role = $role->role;
			$roles[$role->role] = $user_role;
		}
		return $roles;
	}

	public function has_access($test_path)
	{
		foreach ($this->paths as $path => $is_nav) {
			$re = "/^".str_replace("/", "\\/", $path)."\b/";
			if (preg_match($re, $test_path)) {
				return true;
			}
		}
		return false;
	}

	public static function get_all_paths_for_nav()
	{
		$roles = user_role::get_all_roles();
		$paths = array();

		foreach ($roles as $role) {
			$role_paths = $role->get_paths_for_nav();
			$paths = array_unique(array_merge($paths, array_filter($role_paths, create_function('$x', 'return ($x != "*");'))));
		}
		return $paths;
	}

	public function get_paths_for_nav()
	{
		$nav_paths = array();
		foreach ($this->paths as $path => $is_nav) {
			if ($is_nav) {
				$nav_paths[] = $path;
			}
		}
		return ($nav_paths);
	}

	public static function get_hr_new_user_depts()
	{
		$roles = user_role::get_all_roles();
		$depts = array();
		foreach ($roles as $dept => $role) {
			if ($role->hr_can_create && !in_array($dept, $depts)) {
				$depts[] = $dept;
			}
		}
		sort($depts);
		return $depts;
	}
}

?>