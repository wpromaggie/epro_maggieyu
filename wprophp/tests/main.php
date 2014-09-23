<?php
/* ----
 * Description:
 * 	Main module for wproPHP test rig
 * Programmers:
 *  cp C-P
 * 	kk Koding Kevin
 * 	mc Merlin Corey
 *	vy Vyrus001
 * History:
 *	0.0.1 2008February05 Initial version
 * ---- */

class mod_main extends module_base
{
	protected $m_name = 'main';
	private $m_tests = array('php', 'strings');
	
	public function pre_output()
	{
		global $wpro;
		
		$wpro['modules']->set_available(array_merge(array('main'), $this->m_tests), 'main');
		$base = $wpro['modules']->request_from_available('base');
		if ($base != 'main')
		{
			$wpro['modules']->require_enable_pre($wpro['paths']->Get('tests').$base.'.php', 'mod_'.$base, $base);
		}
		else
		{
			if (isset($_REQUEST['testall']))
			{
				foreach ($this->m_tests as $test)
				{
					$wpro['modules']->require_enable_pre($wpro['paths']->Get('tests').$test.'.php', 'mod_'.$test, $test);
				}
			} // testall
		}
		
		$wpro['skin']->Head->Set_Title('wproPHP::test::'.$base);
		$wpro['skin']->Head->Add_Css('css/styles.css');
		$wpro['skin']->Head->Add_Css('css/styles-ie.css', false, 'IE');
		$wpro['skin']->Head->Add_Js('js/functions.js', true);
		$wpro['skin']->Head->Add_Meta('description', 'A test page');
		$wpro['skin']->Head->Add_Meta('keywords', 'wpromote, wpro, wprophp');		
	} // pre_output
	
	private function get_tests()
	{
		$tests = array();
		$dir = opendir('tests/');
		if ($dir)
		{
			while ($file = readdir($dir))
			{
				$pos = strrpos($file, '.php');
				if ($pos !== false)
				{
					$test = substr($file, 0, $pos);
					if (in_array($test, $this->m_tests))
					{
						$tests[] = $test;
					}
				}
			} // each file
			closedir($dir);
		}
		return ($tests);
	} // get_tests
	
	private function tests_form($tests)
	{
		echo("\n<ul>");
		$level = isset($_REQUEST['level']) ? $_REQUEST['level'] : 0;
		echo('<li>Level: '.$level);
		if ($level > 0)
		{
			echo('[<a href = "test.php?base=main&level='.($level - 1).'" title = "Lower level">-</a>]');
		}
		if ($level < 10);
		{
			echo('[<a href = "test.php?base=main&level='.($level + 1).'" title = "Raise level">+</a>]');
		}
		echo('</li>');
		echo('</li>');
		echo('<li>[<a href = "test.php?base=main&testall&level='.$level.'" title = "All tests">All</a>]');
		foreach ($tests as $test)
		{
			echo('<li>[<a href = "test.php?base='.$test.'&level="'.$level.' title = "Test for '.$test.'">'.ucfirst($test).'</a>]</li>');	
		}
		echo("</ul><!-- /tests -->\n");
	}
	public function output()
	{
		echo("<div>\n");
		echo("<h1>wproPHP Self Testing</h1>\n");
		if (!isset($_REQUEST['base']) || $_REQUEST['base'] == 'main')
		{
			$this->tests_form($this->get_tests());
		}
		else
		{
				echo('<div>[<a href = "test.php?base=main" title = "Back to main">Back</a>]</div>');
		}
	}
	
	public function post_output()
	{
		echo("\n</div><!-- /main -->\n");
	}
};

?>