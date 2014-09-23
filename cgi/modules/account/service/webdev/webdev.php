<?php

class mod_account_service_webdev extends mod_account_service
{
	protected $m_name = 'webdev';

	public function pre_output()
	{
		parent::pre_output();
		$this->display_default = 'info';
	}
}

?>