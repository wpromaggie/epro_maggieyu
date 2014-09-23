<?php

class action_client_account_info extends response_object{
	protected function GET($id){
		Logger(__FUNCTION__.$id);
		$acct_details = mod_eac_account::get_account_details_by_aid($id);
		return array($acct_details,200);
	}
}

?>