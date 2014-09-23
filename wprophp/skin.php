<?php
/* ----
 * Description:
 * 	Main index for WPromote PHP framework
 * Programmers:
 *  cp C-P
 * 	kk Koding Kevin
 * 	mc Merlin Corey
 *	vy Vyrus001
 * History:
 * 	0.0.2 2008February04 Updated to house-style
 * 	0.0.1 2007September05 Initial version 
 * ---- */

require_once("modules.php");

if (class_exists("skin"))
{
	trigger_error('Error: "skin" already exists!', E_USER_WARNING);
}
else
{
	class mod_doctype extends module_base
	{
		protected $m_name = "doctype";
		
		private $m_type = "XHTML";
		private $m_version = "1.0";
		private $m_sub_type = "Strict";
		
		public function output()
		{
			/* Doctype Definitions: (that we support)
			 * <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 //EN" "http://www.w3.org/TR/html4/strict.dtd">
			 * <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
			 * <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
			 * 
			 * <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
			 * <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
			 * <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
			 */		
			$is_xhtml = ($this->m_type == "XHTML");
			$html = ($is_xhtml) ? "html" : "HTML";
			$sub_type = ($this->m_type == "HTML" && $this->m_sub_type == "Strict") ? "" : $this->m_sub_type;
			$version = strtolower($this->m_type).substr($this->m_version, 0, 1);
			$dtd_path = "{$version}/".($is_xhtml ? "DTD/{$version}-" : "").strtolower($this->m_sub_type).".dtd";
			
			if ($is_xhtml)
			{
				header('Content-Type: text/html; charset=UTF-8');
				echo("<?xml version = \"1.0\" encoding = \"utf-8\" ?>\n");
			}
			echo("<!DOCTYPE {$html} PUBLIC \"-//W3C//DTD ".$this->m_type." ".$this->m_version." {$sub_type}//EN\" \"http://www.w3.org/TR/{$dtd_path}\">\n");	
			echo("<html");
			if ($is_xhtml)
			{
				echo(" xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\"");
			}
			echo(">\n");
		}		
		
		public function set($type, $sub_type = "", $sVersion = "")
		{
			$this->m_type = strtoupper($type);
			if ($sub_type == "")
			{
				$sub_type = "Strict";
			}

			if ($sVersion == "")
			{
				if ($this->m_type == "XHTML")
				{
					$sVersion = "1.0";
				}
				else
				{
					$sVersion = "4.1";
				}
			}
			$this->m_sub_type = $sub_type;
			$this->m_version = $sVersion;
		}
	}; // mod_doctype
	
	class mod_head extends module_base
	{
		protected $m_name = "head";
		protected $m_title = "Untitled";

		private $m_metas = array();
		private $m_css = array();
		private $m_js = array();
		private $m_generic = array();
		
		public $Extra;
		
		public function __construct()
		{
			$this->Extra = new modules;
		}
		
		public function output()
		{
			echo("\n\t<head>");
			echo("\n\t\t<title>".$this->m_title."</title>\n");
			$this->include_meta();
			$this->include_css();
			$this->include_js();
			$this->include_generic();
			$this->Extra->output();
			echo("\t</head>\n");			
		}
		
		public function get_title()
		{
			return ($this->m_title);
		}
		
		public function set_title($title)
		{
			$this->m_title = $title;
		}
		
		public function add_meta($name, $content, $http_equiv = "")
		{
			$this->m_metas[] = array("name" => $name, "content" => $content, "http-equiv" => $http_equiv);
		}
		
		public function add_css($css_file, $inline = false, $condition = "")
		{
			$this->m_css[] = array("href" => $css_file, "inline" => $inline, "condition" => $condition);
		}
		
		public function add_js($sJsFile, $inline = false)
		{
			$this->m_js[] = array("src" => $sJsFile, "inline" => $inline);
		}		
		
		public function add_generic($str)
		{
			$this->m_generic[] = $str;
		}
		
		private function include_meta()
		{
			$count = count($this->m_metas);
			for ($current = 0; $current < $count; ++$current)
			{
				$name = $this->m_metas[$current]["name"];
				$content = $this->m_metas[$current]["content"];
				$http_equiv = $this->m_metas[$current]["http-equiv"];
				
				echo("\t\t<meta");
				if ($name != "")
				{
					echo(" name=\"{$name}\"");
				}
				if ($http_equiv != "")
				{
					echo(" http-equiv=\"{$http_equiv}\"");	
				}			
				echo(" content=\"{$content}\"");
				echo(" />\n");
			}
		}
		
		private function include_css()
		{
			$count = count($this->m_css);
			for ($current = 0; $current < $count; $current++)
			{
				if ($this->m_css[$current]["inline"])
				{
					$sFile = file_get_contents($this->m_css[$current]["href"]);
					echo("<style type=\"text/css\"><!--\n{$sFile}\n--></style>\n");
				}
				else
				{
					$is_conditional = ($this->m_css[$current]["condition"] != "");
					if ($is_conditional)
					{
						echo("\t<!--[if ".($this->m_css[$current]["condition"])."]>\n");
					}
					echo("\t\t<link rel=\"stylesheet\" type=\"text/css\" href=\"".$this->m_css[$current]["href"]."\" />\n");
					if ($is_conditional)
					{
						echo("<![endif]-->");
					}
				}
			} // each file
		} // IncludeCss
		
		private function include_js()
		{
			$count = count($this->m_js);
			for ($current = 0; $current < $count; $current++)
			{
				if ($this->m_js[$current]["inline"])
				{
					$sFile = file_get_contents($this->m_js[$current]["src"]);
					echo("\n<script type=\"text/javascript\"><!--\n{$sFile}\n//--></script>\n");
				}
				else
				{
					echo("\t\t<script type=\"text/javascript\" src=\"".$this->m_js[$current]["src"]."\"></script>\n");
				}
			} // each file
		} // IncludeJs		
		
		private function include_generic()
		{
			for ($i = 0, $count = count($this->m_generic); $i < $count; ++$i)
				echo $this->m_generic[$i];
		}
	}; // mod_head
	
	class mod_body
	{
		public $Start;
		public $End;
		private $m_attributes = '';

		function __construct()
		{
			$this->Start = new modules;
			$this->End = new modules;
		}			
		
		public function pre_output()
		{
			$this->Start->pre_output();
			$this->End->pre_output();
		}
		
		public function post_output()
		{
			$this->End->output();
			echo("\n\t</body>\n");
			echo("\n</html>\n");

			$this->Start->post_output();
			$this->End->post_output();			
		}
		
		public function output()
		{
			echo("\n\t<body".($this->m_attributes != '' ? ' '.$this->m_attributes : '').">\n");
			$this->Start->output();
		}
		
		public function set_attributes($attributes)
		{
			if (is_array($attributes))
			{
				$s_attributes = '';
				foreach ($attributes as $key => $value)
				{
					$s_attributes = $key.' = "'.$value.'" ';
				}
				$this->m_attributes = substr($s_attributes, 0, strlen($s_attributes - 1));
			}
			else
			{
				$this->m_attributes = $attributes;
			}
		}
	}; // mod_body
	
	class skin
	{
		public $Doc;
		public $Head;
		public $Body;
		
		public $Html;
		private $m_output;
				
		public function __construct()
		{
			$this->Doc = new mod_doctype;
			$this->Head = new mod_head;
			$this->Body = new mod_body;
			
			// TODO: Remove this
			$this->Html = new html;
			$this->m_output = true;
		}	
		
		public function output_start()
		{
			if ($this->m_output)
			{
				$this->Body->pre_output();
				$this->Doc->output();
				$this->Head->output();
				$this->Body->output();
			}
		}
		
		public function output_end()
		{
			if ($this->m_output)
			{
				$this->Body->post_output();
			}
		}
		
		// Post: Returns true if skin will output, false if not
		public function get_output()
		{
			return ($this->m_output);
		}
		
		// Pre: Receives value indicating state of skin output
		// Post: If output is false, output will 
		public function set_output($output)
		{
			$this->m_output = $output;
		}
		
		// Pre: Optionally receives value indicating output state and sets it
		// Post: Returns output state before it would have been reset
		public function get_set_output($output = NULL)
		{
			$original = $this->m_output;
			if ($output !== NULL)
			{
				$this->set_output($output);
			}
			return ($original);
		}
		
		public function do_output($output = true)
		{
			trigger_error("do_output() is deprecated; please use set_output(), get_output(), or get_or_set_output()!", E_USER_WARNING);
			$this->m_output = $output;
		}
	}; // skin
}

?>