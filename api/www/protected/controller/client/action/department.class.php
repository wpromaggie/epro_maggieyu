<?php
class action_client_department extends response_object{
	protected static function GET($dept){
		$clients = mod_eac_account::get_clients_by_department($dept);
		return array($clients,200);
	}
}
?>