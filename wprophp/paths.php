<?php
/* -----
// Description:
	Paths abstractor to allow easier modular loading of site pieces.
// Programmer:
	Merlin Corey
// History:
	0.7.0 2008March05 Added find_file() and require_first_found(); made member variables private (fully PHP5 now and useful)
	0.6.0 2008February05 Updated to wpro house style; is this class even necessary in PHP5?
	0.5.0 2007May16 Moved to jtk
	0.4.1 2006March24 Renamed to mlib_paths
	0.4.0 2005December13 mlib:php Lite reabsortion, plus many internal changes
	0.3.0 2005December09 mlib::php Lite release
	0.2.0 2005January06 Added PrefixDir, made return methods
---- */

if (class_exists("paths"))
{
	trigger_error('Class "paths" already exists!', E_USER_WARNING);
}
else
{
	class paths
	{	
		private $m_prefix_dir;
		private $m_use_prefix = false;
	
		private $m_paths = array();
		
		public function __construct($prefix_dir = '')
		{
			$this->set_prefix_dir($prefix_dir);
		}
				
		public function set_prefix_dir($prefix_dir)
		{
			if (!empty($prefix_dir) && $prefix_dir[strlen($prefix_dir) - 1] == '/')
			{
				$this->m_prefix_dir = substr($prefix_dir, 0, strlen($prefix_dir) - 1);
			}
			else
			{
				$this->m_prefix_dir = $prefix_dir;
			}
			
			$this->m_use_prefix = (strlen($this->m_prefix_dir)) ? true : false;
		}
		
		private function resolve_path()
		{
			$sReturn = '';
			
			if ($this->m_use_prefix)
			{
				$sReturn = $this->m_prefix_dir.'/';
			}
			
			return ($sReturn);
		}
		
		public function find_file_path($file)
		{
			$path = NULL;
			
			foreach ($this->m_paths as $name => $path_name)
			{
				$check_path = $this->resolve_path().$path_name.$file;
				if (file_exists($check_path))
				{
					$path = array('name' => $name, 'value' => $path_name, 'resolved' => $check_path);
					break;
				}
			} // each path
			
			return ($path);
		}
		
		public function require_first_found($file)
		{
			$required = false;
			
			$path = $this->find_file_path($file);
			if ($path != NULL)
			{
				require_once($path['resolved']);
				$required = true;
			}
			
			return ($required);
		}
		
		public function include_first_found($file)
		{
			$included = false;
			
			$path = $this->find_file_path($file);
			if ($path != NULL)
			{
				include_once($path['resolved']);
				$included = true;
			}
			
			return ($included);
		}
		
		public function add($name, $value)
		{
			if ($value[strlen($value) - 1] != '/')
			{
				$value .= '/';
			}
			$this->m_paths[$name] = $value;
		}
		
		public function get($name)
		{
			$path = $this->resolve_path().$this->m_paths[$name];
			return ($path);
		}		
	};
}
 
?>
