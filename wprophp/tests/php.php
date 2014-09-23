<?php

class mod_php extends module_base
{
	protected $m_name = 'php';
	
	public function output()
	{
		$moar = isset($_REQUEST['level']) ? ($_REQUEST['level'] >= 10) : false;
		echo('<div><p>');
		echo('PHP '.phpversion().' using Zend '.zend_version());
		echo(' [<a href = "?base=php&'.($moar ? 'level=0' : 'level=10').'" title = "phpinfo();">'.($moar ? 'Less' : 'More').' information</a>]');
		echo('</p></div>');
		if ($moar)
		{
			phpinfo();	
		}
	} // output
};

?>