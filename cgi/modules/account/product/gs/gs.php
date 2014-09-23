<?php

class mod_account_product_gs extends mod_account_product
{
	public function pre_output()
	{
		parent::pre_output();
	}

	// override - there is no activation email for gs, nothing to do
	protected function send_activation_email() {}
}

?>