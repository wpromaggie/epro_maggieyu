<?php

class mod_marketing extends module_base
{
	
	public function get_menu()
	{
		return new Menu(
			array(
				new MenuItem('Email Unsubs', array('marketing', 'email', 'unsub')),
			)
		);
	}
	
	
}

?>