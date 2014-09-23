<?php
/* ----
 * Description:
 * 	Modules collection
 * Programmers:
 * 	cp C-P
 * 	km K-Money
 * 	mc MerlinCorey
 *  v1 Vyrus001
 * History:
 * 	0.0.3 2008May20 Added require_enable*()
 * 	0.0.2 2008February04 Updated to house-style
 * 	0.0.1 2007September05 Initial version 
 * ---- */

if (class_exists('modules'))
{
	trigger_error('Error: "modules" already exists!', E_USER_WARNING);
}
else
{
	class modules
	{
		private $m_enabled = array();
		private $m_available = array();
		private $m_default = '';
		private $m_path = '';
		
		// Pre: Receives array of available module names and optionally default module (or just uses first module)
		// Post: Sets available modules and default
		public function set_available($available, $default = '')
		{
			$this->m_available = $available;
			if ($default == '' && count($available))
			{
				$default = $available[0];
			}
			$this->m_default = $default;
		}
		
		public function available_levenshtein($key)
		{
			$closest = '';
			
			if (isset($_REQUEST[$key]))
			{
				$shortest = -1;
				$input = $_REQUEST[$key];

				foreach ($this->m_available as $word)
				{
					// calculate the distance between the input word, and the current word
					$lev = levenshtein($input, $word);

					// check for an exact match
					if ($lev == 0)
					{
						$closest = $word;
						$shortest = 0;
						break;
					}

					// if this distance is less than the next found shortest
					// distance, OR if a next shortest word has not yet been found
					if ($lev <= $shortest || $shortest < 0)
					{
						$closest  = $word;
						$shortest = $lev;
					}
				} // each word
			}
			
			return ($closest);
		} // Available_Levenshtein
		
		public function is_available($key)
		{
			return (isset($_REQUEST[$key]) ? in_array($_REQUEST[$key], $this->m_available) : false);
		}
		
		// Pre: Receives $_REQUEST key, and optionally whether value should be lowercased or not (defaults to true)
		// Post: Returns value of key if set and in available, or default
		public function request_from_available($key, $to_lower = true)
		{
			if (isset($_REQUEST[$key]))
			{
				$value =  $this->is_available($key) ? $_REQUEST[$key] : $this->m_default;
			}
			else
			{
				$value = $this->m_default;
			}	
			
                        $value = str_replace("-", "", $value);

			return ($to_lower ? strtolower($value) : $value);
		}
		
		// Pre: Receives module name
		// Post: Returns true if module is enabled
		public function is_enabled($name)
		{
			$enabled = false;
			
			$count = count($this->m_enabled);
			for ($current = 0; $current < $count && !$enabled; ++$current)
			{
				if ($this->m_enabled[$current]->get_name() == $name)
				{
					$enabled = true;
				}
			} // each module
			
			return ($enabled);
		} // Is_Enabled
		
		// Pre: Receives module name or instance of module
		// Post: Returns true if module became enabled
		public function enable($module)
		{
			$enabled = false;
			
			if (is_object($module))
			{
				if (!$this->is_enabled($module->get_name()))
				{
					$this->m_enabled[] = $module;
					$enabled = true;
				}
			}
			else
			{
			
			}
			
			return ($enabled);
		} // Enable
		
		// Pre: Receives name of module
		// Post: Looks for module and disables it
		// Returns: true if found, false if not found
		public function disable($name)
		{
			foreach ($this->m_enabled as $i => $module)
			{
				if ($module->get_name() == $name)
				{
					array_splice($this->m_enabled, $i, 1);
					return true;
				}
			}
			return false;
		}
		
		// Pre: Receives name of module
		// Post: Looks for module
		// Returns: The module, if found, null if not
		public function get_enabled($name)
		{
			foreach ($this->m_enabled as $module)
			{
				if ($module->get_name() == $name) return $module;
			}
			return NULL;
		}
		
		public function call($name, $method, $arguments = NULL)
		{
			$count = count($this->m_enabled);
			$matched = false;
			for ($current = 0; $current < $count && !$matched; ++$current)
			{
				if ($name == $this->m_enabled[$current]->Get_Name())
				{
					if ($arguments != NULL)
					{
						$this->m_enabled[$current]->$method($arguments);
					}
					else
					{
						$this->m_enabled[$current]->$method();
					}
					$matched = true;
				}
			} // each module
			
			return ($matched);	
		}
		
		// Pre: Receives name of method
		// Post: Calls method on all enabled modules
		// Returns: Number of modules iterated through
		public function call_all($method)
		{
			$count = count($this->m_enabled);
			for ($current = 0; $current < $count; ++$current)
			{
				$this->m_enabled[$current]->$method();
			} // each module
			
			return ($count);
		}

		// Post: Enabled modules' output() are called
		// Returns: Number of modules iterated through
		public function output()
		{
			return ($this->call_all('output'));
		}
		
		// Post: Enabled modules' pre_output() are called
		// Returns: Number of modules iterated through
		public function pre_output()
		{
			return ($this->call_all('pre_output'));
		}		
		
		// Post: Enabled modules' post_output() are called
		// Returns: Number of modules iterated through
		public function post_output()
		{
			return ($this->call_all('post_output'));
		}

		public function require_enable($path, $class)
		{
			require_once($path);
			return ($this->enable(new $class));
		}
		
		public function require_enable_pre($path, $class, $name)
		{
			return ($this->require_enable($path, $class) ? $this->call($name, 'pre_output') : false);
		}
	};
}

?>