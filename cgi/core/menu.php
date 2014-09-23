<?php

/**
 * classes for menu and menu item
 * 
 */

class Menu
{
	public $items, $base;
	
	public function __construct($items, $base = null, $base_link = null, $title = null)
	{
		$this->items = $items;
		$this->base = $base;
		$this->base_link = $base_link;
		$this->title = $title;
	}

	public function prepend()
	{
		$this->items = array_merge(func_get_args(), $this->items);
	}

	public function append()
	{
		$this->items = array_merge($this->items, func_get_args());
	}

	public function to_ml($opts = array())
	{
		util::set_opt_defaults($opts, array(
			'separator' => ' | '
		));
		if ($this->base) {
			$target_base = $this->base;
			if (!preg_match("/\/$/", $target_base)) {
				$target_base .= '/';
			}
		}
		else {
			$target_base = '';
		}
		$ml = '';
		foreach ($this->items as $item) {
			$ml_separator = (!empty($ml)) ? $opts['separator'] : '';
			
			$pages = (is_array($item->pages)) ? $item->pages : explode('/', $item->pages);
			$target = $target_base.implode('/', $pages);
			
			// if is admin
			// or no role and user has observer (or greater) access to module
			// or there is a role and user has the role
			$role = $item->role;
			if (!empty($role) && !is_array($role)) {
				$role = array($role);
			}
			if (
				(g::$p1 != 'administrate' && user::is_admin()) ||
				(g::$p1 == 'administrate' && user::is_developer(true)) ||
				(empty($role) && user::has_module_observer_access()) ||
				(!empty($role) && user::has_role($role))
			) {
				$href = cgi::href($target).$item->check_query();
				$ml .= $ml_separator.'<a class="mod_inner_link" href="'.$href.'"'.((empty($item->attrs)) ? '' : ' '.$item->attrs).'>'.$item->text."</a>\n";
			}
		}
		return $ml;
	}

	public function to_headnav($is_sub=false)
	{
		
		if ($this->base) {
			$target_base = $this->base;
			if (!preg_match("/\/$/", $target_base)) {
				$target_base .= '/';
			}
		}
		else {
			$target_base = '';
		}
		$ml = '';
		foreach ($this->items as $item) {

			if(get_class($item)=="Menu"){
				//e($item);
				$ml_sub = $item->to_headnav(true);
				$ml_sub = '<ul class="dropdown-menu">'.$ml_sub.'</ul>';
				$ml .= '<li class="dropdown show-on-hover"><a href="javascript:;" data-toggle="dropdown"><span>'.$item->title.'</span><b class="caret"></b></a>'.$ml_sub.'</li>';
				continue;
			}

			$pages = (is_array($item->pages)) ? $item->pages : explode('/', $item->pages);
			$target = $target_base.implode('/', $pages);
			
			// if is admin
			// or no role and user has observer (or greater) access to module
			// or there is a role and user has the role
			$role = $item->role;
			if (!empty($role) && !is_array($role)) {
				$role = array($role);
			}
			if (
				(g::$p1 != 'administrate' && user::is_admin()) ||
				(g::$p1 == 'administrate' && user::is_developer(true)) ||
				(empty($role) && user::has_module_observer_access()) ||
				(!empty($role) && user::has_role($role))
			) {
				$href = cgi::href($target).$item->check_query();
				$ml .= '<li><a class="mod_inner_link" href="'.$href.'"'.((empty($item->attrs)) ? '' : ' '.$item->attrs).'>'.$item->text."</a></li>";
			}
		}

		return $ml;
	}
}

class MenuItem
{
	// pages can be either an array or a string
	public $text, $pages, $attrs, $guild, $role, $query_keys;
	
	public function __construct($text, $pages, $options = array())
	{
		$this->text = $text;
		$this->pages = $pages;
		$this->attrs = $options['attrs'];
		$this->role = $options['role'];
		$this->query_keys = (isset($options['query_keys'])) ? $options['query_keys'] : '';
	}

	public function check_query()
	{
		$q = array();
		if (!empty($this->query_keys)) {
			foreach ($this->query_keys as $key) {
				if (isset($_REQUEST[$key])) {
					$q[$key] = $_REQUEST[$key];
				}
			}
		}
		return ($q) ? '?'.http_build_query($q) : '';
	}
}

?>