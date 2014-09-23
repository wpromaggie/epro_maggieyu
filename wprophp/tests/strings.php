<?php

class mod_strings extends module_base
{
	protected $m_name = 'strings';
	
	public function output()
	{
		global $wpro;
		echo('<div><h2>Strings</h2>');
		echo(strings::random_string(8));
		echo('</div>');
	}
}

?>