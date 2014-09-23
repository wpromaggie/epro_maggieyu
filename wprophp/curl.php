<?php
/* ----
 * Description:
 * 	Commision junction api object
 * Programmers:
 *	cp C-P
 *	km K-Money
 *	mc Merlin Corey
 *	vy1 Vyrus001
 * History:
 *	0.0.1 2008February18 Initial version
 * ---- */

if (class_exists('wpro_curl'))
{
	trigger_error('Error: "wpro_curl" already exists!', E_USER_WARNING);
}
else
{
	class wpro_curl
	{
		public $m_curl;
		
		public function __construct($url = '')
		{
			if (strlen($url))
			{
				$this->m_curl = curl_init($url);
			}
			else
			{
				$this->m_curl = curl_init($url);
			}
		} // constructor
		
		public function __destruct()
		{
			$this->close();
		}
		
		public function close()
		{
			curl_close($this->m_curl);
		}
		
		public function errno()
		{
			return (curl_errno($this->m_curl));
		}		
		
		public function error()
		{
			return (curl_error($this->m_curl));
		}
		
		public function set_opt($option, $value)
		{
			return (curl_setopt($this->m_curl, $option, $value));
		}
		
		public function set_opt_array($options)
		{
			return (curl_setopt_array($this->m_curl, $options));
		}
		
		public function exec()
		{
			return (curl_exec($this->m_curl));
		}
		
	}; // base_affiliate	
}

?>