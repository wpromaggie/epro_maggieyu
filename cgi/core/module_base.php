<?php

class module_base
{
	protected $widgets;
	protected $m_name = '';
	protected $action = null;
	protected $a0_result = null;
	protected $display_default = 'index';
	protected $page_index;
	protected $base_url;
	
	// can by set by switch_display
	protected $display_page;

	/**
	 * SOME USEFUL COMMMENT.
	 * @param int $page_index the index of this module in the g::$pages array.
	 */
	function __construct($page_index)
	{
		$this->page_index = $page_index;
		$this->base_url = '/'.implode('/', array_slice(g::$pages, 0, $this->page_index + 1)).'/';
	}
	
	/**
	 * Determines what function should be called to display output. If a child
	 * has been specified on the url path (and isn't defined as its own module),
	 * then this function calls display_childname(). If no child url is specified,
	 * then display_index() is called. To change the default display function,
	 * change the value of $display_default in a module's constructor.
	 */
	public function output()
	{
		$this->call_member('display_'.$this->get_page(), 'display_'.$this->display_default);
	}

	public function pre_output()
	{
	}
	
	/**
	 * test whether the url maps to a display function
	 */
	public function is_url_display_func()
	{
		return (method_exists($this, 'display_'.$this->get_page()));
	}

	protected function href($url_path = '')
	{
		return $this->base_url.ltrim($url_path, '/');
	}
	
	public function pre_output_base()
	{
		$this->pre_output();
		// try to call more specific pre output functions
		// starting with most specific
		// if we successfully call one, stop trying
		$pages = array_slice(g::$pages, $this->page_index + 1);
		$funcs = array();
		for ($i = count($pages); $i > 0; --$i) {
			$func = 'pre_output_'.implode('_', array_slice($pages, 0, $i));
			if ($this->call_member($func)) {
				return;
				// todo? don't return, call as many pre output functions that exist
				// also call default if not already called
				$funcs[] = $func;
			}
		}
		$default_func = 'pre_output_'.$this->display_default;
		if (!in_array($default_func, $funcs)) {
			$this->call_member($default_func);
		}
	}
	
	public function get_page()
	{
		return ($this->display_page) ? $this->display_page : g::$pages[$this->page_index + 1];
	}
	
	public function display_index()
	{
	}
	
	public function switch_display($page)
	{
		$this->display_page = $page;
	}
	
	public function get_display_default()
	{
		return $this->display_default;
	}
	
	public function get_menu()
	{
		return array();
	}
	
	public function process_ajax($method)
	{
		if (method_exists($this, $method))
		{
			$this->$method();
		}
		else
		{
			if ($this->widgets)
			{
				foreach ($this->widgets as &$widget)
				{
					if (method_exists($widget, $method))
					{
						$widget->$method();
					}
				}
			}
		}
	}
	
	public function check_action()
	{
		$method = $_POST['a0'];
		if ($method)
		{
			$this->action = $method;
			if (method_exists($this, $method))
			{
				$r = $this->$method();
				if (is_bool($r))
				{
					$this->a0_result = $r;
				}
			}
			if ($this->widgets)
			{
				foreach ($this->widgets as &$widget)
				{
					if (method_exists($widget, $method))
					{
						$widget->$method();
					}
				}
			}
		}
	}
	
	public function call_member($func, $default = '', $arguments = NULL)
	{
		if (method_exists($this, $func))
		{
			$this->$func($arguments);
			return true;
		}
		else if (!empty($default) && method_exists($this, $default))
		{
			$this->$default($arguments);
			return true;
		}
		return false;
	}
	
	protected function hook($hook)
	{
		$func = 'hook_'.$hook;
		if (method_exists($this, $func)) {
			call_user_func_array(array($this, $func), array_slice(func_get_args(), 1));
		}
	}
	
	public function page_menu($menu, $base_url = null, $query = null)
	{
		if (!$base_url)
		{
			$base_url = g::$p1.'/';
			$page_offset = 1;
		}
		else
		{
			$page_offset = substr_count($base_url, '/');
		}
		
		$num_pages = count(g::$pages);
		$last_page = g::$pages[$num_pages - 1];
		if ($last_page == $this->display_default)
		{
			$pages = array_slice(g::$pages, 0, $num_pages - 1);
		}
		else
		{
			$pages = g::$pages;
		}
		$cur_page = implode('/', array_slice($pages, $page_offset));
		
		$default_page = $menu[0][0];
		$ml = '';
		for ($i = 0; list($key, $text, $attrs) = $menu[$i]; ++$i)
		{
			$ml_class = ((empty($cur_page) && $key == $default_page) || $key == $cur_page) ? ' class="on"' : '';
			$base_and_key = $base_url.$key.(($key) ? '/' : '');
			$url = ($query) ? cgi::href($base_and_key.'?'.$query) : cgi::href($base_and_key, true);
			$ml .= '<a href="'.$url.'"'.$ml_class;
			if ($attrs)
			{
				foreach($attrs as $attr => $val)
				{
					$ml .= " $attr='$val'";
				}
			}
			$ml .= '>'.$text.'</a>';
		}
		return '
			<div id="page_menu">
				'.$ml.'
			</div>
		';
	}
	
	// key: false (or any key that is not in menu) can be passed in
	//  to add new items to end of array
	public function page_menu_insert_before($menu, $key, $new_items)
	{
		for ($i = 0; list($item_key, $item_display) = $menu[$i]; ++$i)
		{
			if ($item_key === $key)
			{
				break;
			}
		}
		array_splice($menu, $i, 0, $new_items);
		return $menu;
	}
	
	public function register_widget($widget_name, $widget_args = array())
	{
		// lazy load
		if (!$this->widgets)
		{
			$this->widgets = array();
		}
		if (cgi::include_widget($widget_name))
		{
			$widget_class = 'wid_'.$widget_name;
			$widget = new $widget_class($widget_args);
			$this->widgets[$widget_name] = $widget;
			return $widget;
		}
	}
	
	// href relative to module
	protected function rhref($href)
	{
		return cgi::href(implode('/', array_slice(g::$pages, 0, $this->page_index + 1)).'/'.$href);
	}

	protected function does_user_have_role($roles, $is_developer_ok = true)
	{
		for ($class_name = get_class($this); $class_name != 'module_base'; $mod_base_extension = $class_name, $class_name = get_parent_class($class_name));
		
		$dept = str_replace('mod_', '', $mod_base_extension);
		return (user::has_role($roles, $dept) || ($is_developer_ok && user::is_developer()));
	}

	protected function is_user_director($is_developer_ok = true)
	{
		return $this->does_user_have_role(array('Director'), $is_developer_ok);
	}

	protected function is_user_leader($is_developer_ok = true)
	{
		return $this->does_user_have_role(array('Leader', 'Director'), $is_developer_ok);
	}
}

?>