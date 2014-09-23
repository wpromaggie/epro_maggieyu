<?php
/* ----
 * Description:
 *	Wpromote PHP Library main public static functions
 * Programmers:
 *	k$ KMoney
 *	mc MerlinCorey
 * History:
 * 	0.0.2 2008May20 Added 
 *	0.0.1 2008March09 Split from mlib.php and created first public static functions
 * ---- */

class wpro
{
	// Pre: Receives reference to global collection
	// Post: Runs the pattern of opening a modules, skins through pre, output, and post
	public static function model_skin_view($key = 'wpro')
	{
		global $$key;
		$lib = &$$key;

		$lib['modules']->pre_output();
			$lib['skin']->output_start();
				$lib['modules']->output();
			$lib['skin']->output_end();
		$lib['modules']->post_output();
	}

	// Pre: Receives reference to global collection
	// Post: Runs the pattern of opening a modules through pre, output, and post
	public static function model_view($key = 'wpro')
	{
		global $$key;
		$lib = &$$key;
		
		$lib['modules']->pre_output();
			$lib['modules']->output();
		$lib['modules']->post_output();
	}

	// Pre: Receives reference to global collection
	// Post: Runs the pattern of opening a db, modules, skin through pre, output, and post
	public static function db_model_skin_view($key = 'wpro')
	{
		global $$key;
		$lib = &$$key;
				
		$lib['db']->connect();
		wpro::model_skin_view($key);
		$lib['db']->close();
	}

	// Pre: Receives reference to global collection
	// Post: Runs the pattern of opening a db and modules, through pre, output, and post
	public static function db_model_view($key = 'wpro')
	{
		global $$key;
		$lib = &$$key;
		
		$lib['db']->connect();
		wpro::model_view($globals);		
		$lib['db']->close();
	}	
	
	public static function create_globals($data = array())
	{	
		$lib = $data;
		return ($lib);
	} // create_globals
	
	public static function get_paths($key = 'wpro')
	{	
		global $$key;
		$lib = &$$key;
		
		wpro_require('paths.php');
		$lib['paths'] = new paths((defined('WPRO_PATH') ? WPRO_PATH : ''));
		
		$lib['paths']->add('classes', './');
		$lib['paths']->add('css', 'css/');
		$lib['paths']->add('sql', 'sql/');
		$lib['paths']->add('apis', 'apis/');
		$lib['paths']->add('adapters', 'adapters/');
	} // get_paths

	public static function do_magic()
	{
		if (defined('WPRO_MAGIC'))
		{
			$a_magic = explode(";", WPRO_MAGIC);
			foreach ($a_magic as $s_magic)
			{
				$s_magic = strtoupper($s_magic);
				define("WPRO_{$s_magic}", 1);
			} // each magic
		} // magic is defined
	} // do_magic

	public static function get_base_classes($key = 'wpro')
	{	
		global $$key;
		$lib = &$$key;
		require_once($lib['paths']->get('classes').'session.php');
		require_once($lib['paths']->get('classes').'strings.php');
		require_once($lib['paths']->get('classes').'curl.php');
		require_once($lib['paths']->get('classes').'db.php');
		$lib['strings'] = new strings;
	} // get_base_classes
	
	public static function get_requested_classes($key = 'wpro')
	{
		global $$key;
		$lib = &$$key;
		
		if (defined('WPRO_DATABASE'))
		{
			require_once($lib['paths']->get('sql').'sql.php');
			require_once($lib['paths']->get('sql').'table.php');

			$type = (defined('WPRO_DB_TYPE') ? WPRO_DB_TYPE : 'mysql');
			$host = (defined('WPRO_DB_HOST') ? WPRO_DB_HOST : 'localhost');
			$user = (defined('WPRO_DB_USER') ? WPRO_DB_USER : 'root');
			$pass = (defined('WPRO_DB_PASS') ? WPRO_DB_PASS : '');
			$name = (defined('WPRO_DB_NAME') ? WPRO_DB_NAME : '');
			
			$lib['db'] = new sql(NULL, $type, $lib['paths']->get('sql'));
			$lib['db']->set_info($host, $user, $pass, $name);
			unset($type, $host, $user, $pass, $name);
		}

		if (defined('WPRO_MODULES'))
		{
			require_once($lib['paths']->get('classes').'module_base.php');
			require_once($lib['paths']->get('classes').'modules.php');
			$lib['modules'] = new modules;	
		}

		if (defined('WPRO_SKIN'))
		{
			require_once($lib['paths']->get('classes').'module_base.php');
			require_once($lib['paths']->get('classes').'modules.php');
				
			require_once($lib['paths']->get('classes').'html.php');
			
			require_once($lib['paths']->get('classes').'skin.php');
			$lib['skin'] = new skin;
			if (defined('WPRO_SIMPLE_SKIN'))
			{
				require_once($lib['paths']->get('classes').'simpleskin.php');
				$lib['simpleskin'] = new mod_simpleskin_start;

				$lib['skin']->Body->Start->enable($lib['simpleskin']);
				$lib['skin']->Body->End->enable(new mod_simpleskin_end);		
			}
		}

		if (defined('WPRO_MAIL'))
		{
			require_once($lib['paths']->get('classes').'email.php');
			$lib['mail'] = new email;
		}

		if (defined('WPRO_APIS'))
		{
			require_once($lib['paths']->get('apis').'apis.php');
		}

		if (defined('WPRO_ADAPTERS'))
		{
			require_once($lib['paths']->get('adapters').'adapters.php');
		}
		
		if (defined('WPRO_CSV'))
		{
			require_once($lib['paths']->get('classes').'csv.php');			
		}
	} // get_requested_classes
} //wpro

?>