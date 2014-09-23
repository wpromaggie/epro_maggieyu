<?php

class mod_account_service_smo extends mod_account_service
{
	protected $m_name = 'email';

	public function pre_output()
	{
		parent::pre_output();
		$this->display_default = 'info';
	}

	public function get_menu()
	{
		$menu = parent::get_menu();
		$menu->append(
			new MenuItem('swapp', 'swapp', array('query_keys' => array('aid')))
		);
		return $menu;
	}
}

?>