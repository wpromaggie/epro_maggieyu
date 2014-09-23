<?php

class mod_account_service_email extends mod_account_service
{
	protected $m_name = 'email';

	public function pre_output()
	{
		parent::pre_output();
		$this->display_default = 'info';
	}
}

?>