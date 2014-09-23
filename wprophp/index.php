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
 *  0.0.2 2007September10 First release
 * 	0.0.1 2007September05 Initial version 
 * ---- */

// No wpro_prefix_dir because we're in it
define('WPRO_MAGIC', 'MODULES;SKIN;SIMPLE_SKIN');
require_once('wpro.php');
global $wpro;

class mod_main extends module_base
{
	protected $m_name = 'main';	
	
	public function output()
	{
		global $wpro;
		$wpro['simpleskin']->Post('Main', 'wpro::php main index.');
	}
}; // mod_main

class mod_filelist extends module_base
{
	protected $m_name = 'filelist';
	
	public $File;
	public $Title;
	public $UsePre = false;
	
	public function output()
	{
		global $wpro;
		
		$wpro['simpleskin']->PostHeaderBodyStart($this->Title);
		$vFile = file($this->File);
		if ($vFile !== false)
		{
			if ($this->UsePre)
			{
				echo('<pre>');
			}
			else
			{
				echo('<ul>');
			}
			foreach ($vFile as $sLine)
			{
				if (!$this->UsePre)
				{
					echo('<li>');
				}
				echo($sLine);
				if (!$this->UsePre)
				{
					echo('</li>');
				}
				echo('\n');
			}
			if (!$this->UsePre)
			{
				echo('</ul>');
			}
		}
		else
		{
			echo('<p><b>Error:</b> failure reading data from \''.$this->File.'\'</p>');
		}
		$wpro['simpleskin']->PostBodyEnd();		
	} // Output
	
	public function Set($sTitle, $sFile, $bPre = false)
	{
		$this->Title = $sTitle;
		$this->File = $sFile;
		$this->UsePre = $bPre;
	}
}; // mod_filelist

$wpro['modules']->set_available(array('main', 'todo', 'version'));
$act = $wpro['modules']->request_from_available('act');

if ($act == 'main')
{
	$m1 = new mod_main;
}
else
{
	$m1 = new mod_filelist;
	if ($act == 'todo')
	{
		$m1->Set('Todo List', 'todo');
	}
	else
	{
		$m1->Set('Version', 'version', true);
	}
}

$wpro['modules']->enable($m1);

$wpro['skin']->Head->Set_Title('wpro::php');
$wpro['skin']->Head->Add_Meta('', 'text/html;charset=utf-8', 'content-type');

$wpro['simpleskin']->Add_Nav('index.php?act=main', 'Main');
$wpro['simpleskin']->Add_Nav('index.php?act=todo', 'Todo List');
$wpro['simpleskin']->Add_Nav('index.php?act=version', 'Version');

$wpro['modules']->pre_output();
$wpro['skin']->Output_Start();
$wpro['modules']->output();
$wpro['skin']->Output_End();
$wpro['modules']->post_output();

?>
